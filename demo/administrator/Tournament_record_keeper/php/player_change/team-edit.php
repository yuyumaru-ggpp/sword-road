<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$tournament_id = $_REQUEST['id'] ?? null;
$department_id = $_REQUEST['dept'] ?? null;
$team_id = $_REQUEST['team'] ?? null;

if (!$tournament_id || !$department_id || !$team_id) {
    die("必要なパラメータが指定されていません");
}

$message = "";

// ポジション対応（order_detail: 1=先鋒,2=次鋒,3=中堅,4=副将,5=大将）
$positions = [
    1 => '先鋒',
    2 => '次鋒',
    3 => '中堅',
    4 => '副将',
    5 => '大将'
];

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 補欠選手を追加
    if (isset($_POST['add_substitute'])) {
        $playerName = trim($_POST['new_player_name'] ?? '');
        $playerFurigana = trim($_POST['new_player_furigana'] ?? '');
        
        if ($playerName !== '') {
            try {
                // 部門内の現在の最大 player_number を取得
                $stmt = $pdo->prepare("
                    SELECT COALESCE(MAX(p.player_number), 0) AS max_no
                    FROM players p
                    JOIN teams t ON p.team_id = t.id
                    WHERE t.department_id = :dept
                ");
                $stmt->execute([':dept' => (int)$department_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $nextPlayerNumber = (int)$row['max_no'] + 1;
                
                // 補欠選手として登録（substitute_flg = 1）
                $stmt = $pdo->prepare("
                    INSERT INTO players (name, furigana, player_number, team_id, substitute_flg) 
                    VALUES (:name, :furigana, :pnum, :team_id, 1)
                ");
                $stmt->execute([
                    ':name' => $playerName,
                    ':furigana' => $playerFurigana === '' ? null : $playerFurigana,
                    ':pnum' => $nextPlayerNumber,
                    ':team_id' => (int)$team_id
                ]);
                
                $message = "補欠選手「{$playerName}」を追加しました（選手番号: {$nextPlayerNumber}）";
            } catch (Exception $e) {
                $message = "補欠選手の追加中にエラーが発生しました: " . $e->getMessage();
            }
        } else {
            $message = "選手名を入力してください。";
        }
    }

    // チーム名保存
    if (isset($_POST['save_team'])) {
        $team_name = trim($_POST['team_name'] ?? '');
        $stmt = $pdo->prepare("UPDATE teams SET name = :name WHERE id = :id");
        $stmt->execute([':name' => $team_name, ':id' => (int)$team_id]);
        $message = "チーム名を更新しました。";
    }

    // 棄権トグル（チーム単位）
    if (isset($_POST['toggle_withdraw'])) {
        $current = (int)($_POST['current_flag'] ?? 0);
        $new = $current ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE teams SET withdraw_flg = :f WHERE id = :id");
        $stmt->execute([':f' => $new, ':id' => (int)$team_id]);
        $message = $new ? "チームを棄権にしました。" : "チームの棄権を解除しました。";
    }

    // 選手交代処理
    if (isset($_POST['swap_players'])) {
        $mainPlayerId = (int)($_POST['main_player_id'] ?? 0);
        $subPlayerId = (int)($_POST['sub_player_id'] ?? 0);
        
        if ($mainPlayerId && $subPlayerId) {
            try {
                $pdo->beginTransaction();
                
                // 正選手を補欠に
                $stmt = $pdo->prepare("UPDATE players SET substitute_flg = 1 WHERE id = :id AND team_id = :tid");
                $stmt->execute([':id' => $mainPlayerId, ':tid' => (int)$team_id]);
                
                // 補欠を正選手に
                $stmt = $pdo->prepare("UPDATE players SET substitute_flg = 0 WHERE id = :id AND team_id = :tid");
                $stmt->execute([':id' => $subPlayerId, ':tid' => (int)$team_id]);
                
                // オーダーの更新（補欠選手が入っているorder_detailを取得）
                $stmt = $pdo->prepare("SELECT order_detail FROM orders WHERE team_id = :tid AND player_id = :pid");
                $stmt->execute([':tid' => (int)$team_id, ':pid' => $mainPlayerId]);
                $orderRow = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($orderRow) {
                    // そのポジションに補欠選手を割り当て
                    $stmt = $pdo->prepare("UPDATE orders SET player_id = :new_pid WHERE team_id = :tid AND order_detail = :od");
                    $stmt->execute([
                        ':new_pid' => $subPlayerId,
                        ':tid' => (int)$team_id,
                        ':od' => $orderRow['order_detail']
                    ]);
                }
                
                $pdo->commit();
                $message = "選手を交代しました。";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "選手交代中にエラーが発生しました: " . $e->getMessage();
            }
        } else {
            $message = "交代する選手を選択してください。";
        }
    }

    // オーダー保存（orders テーブルを上書き）
    if (isset($_POST['save_order'])) {
        $order = $_POST['order_slot'] ?? [];
        
        // 重複チェック
        $selectedPlayers = array_filter($order, function($pid) {
            return $pid !== '' && $pid !== null;
        });
        
        $uniquePlayers = array_unique($selectedPlayers);
        if (count($selectedPlayers) !== count($uniquePlayers)) {
            $message = "❌ 同じ選手が複数のポジションに割り当てられています。オーダーを確認してください。";
        } else {
            try {
                $pdo->beginTransaction();

                // 既存のこのチームの orders を削除
                $del = $pdo->prepare("DELETE FROM orders WHERE team_id = :tid");
                $del->bindValue(':tid', (int)$team_id, PDO::PARAM_INT);
                $del->execute();

                // 挿入
                $ins = $pdo->prepare("INSERT INTO orders (team_id, player_id, order_detail) VALUES (:tid, :pid, :od)");
                foreach ($order as $od => $pid) {
                    $odInt = (int)$od;

                    if ($pid === '' || $pid === null) {
                        continue;
                    }

                    if (!ctype_digit((string)$pid)) {
                        error_log("orders insert skipped: invalid player_id for team {$team_id}, od={$odInt}, pid=" . print_r($pid, true));
                        continue;
                    }

                    $chk = $pdo->prepare("SELECT id FROM players WHERE id = :pid AND team_id = :tid LIMIT 1");
                    $chk->execute([':pid' => (int)$pid, ':tid' => (int)$team_id]);
                    $found = $chk->fetch(PDO::FETCH_ASSOC);
                    if (!$found) {
                        error_log("orders insert skipped: player not found or not in team. team={$team_id}, pid={$pid}, od={$odInt}");
                        continue;
                    }

                    $ins->bindValue(':tid', (int)$team_id, PDO::PARAM_INT);
                    $ins->bindValue(':pid', (int)$pid, PDO::PARAM_INT);
                    $ins->bindValue(':od', $odInt, PDO::PARAM_INT);
                    $ins->execute();
                }

                $pdo->commit();
                $message = "✅ オーダーを保存しました。";
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("order save error: " . $e->getMessage());
                $message = "❌ オーダー保存中にエラーが発生しました。";
            }
        }
    }
}

// チーム情報取得
$stmt = $pdo->prepare("SELECT id, name, team_number, withdraw_flg FROM teams WHERE id = :id LIMIT 1");
$stmt->execute([':id' => (int)$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$team) die("チームが見つかりませんでした");

// チーム所属選手（全員）取得 - substitute_flgで分類
$stmt = $pdo->prepare("SELECT id, name, furigana, player_number, substitute_flg FROM players WHERE team_id = :tid ORDER BY player_number ASC");
$stmt->execute([':tid' => (int)$team_id]);
$allPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 正選手と補欠に分ける
$players = [];      // substitute_flg = 0
$substitutes = [];  // substitute_flg = 1
foreach ($allPlayers as $pl) {
    if (!empty($pl['substitute_flg']) && $pl['substitute_flg'] == 1) {
        $substitutes[] = $pl;
    } else {
        $players[] = $pl;
    }
}

// 選択肢配列（正選手のみ）
$options = [];
foreach ($players as $pl) {
    $options[$pl['id']] = $pl['name'];
}

// orders テーブルから現在の割当を取得
$orderMap = [];
try {
    $stmt = $pdo->prepare("SELECT order_detail, player_id FROM orders WHERE team_id = :tid");
    $stmt->execute([':tid' => (int)$team_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $od = (int)$r['order_detail'];
        $orderMap[$od] = ($r['player_id'] === null ? null : (string)$r['player_id']);
    }
} catch (Exception $e) {
    $orderMap = [];
}

// 安全性チェック
foreach ($orderMap as $k => $v) {
    if ($v === null) continue;
    $stmt = $pdo->prepare("SELECT id FROM players WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $v]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$f) {
        $orderMap[$k] = null;
    } else {
        $orderMap[$k] = (string)$f['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>チーム編集（オーダー）</title>
<link rel="stylesheet" href="../../css/player_change/team-list-style.css">
<style>
.swap-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #28a745;
}

.swap-section h3 {
    margin-top: 0;
    color: #28a745;
}

.swap-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.swap-field {
    flex: 1;
    min-width: 200px;
}

.swap-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.swap-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.swap-btn {
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.swap-btn:hover {
    background: #218838;
}

.substitutes-list {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #ffc107;
}

.substitutes-list h3 {
    margin-top: 0;
    color: #856404;
}

.substitute-item {
    padding: 8px;
    margin: 5px 0;
    background: white;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.note {
    background: #d1ecf1;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
    border-left: 4px solid #0c5460;
    color: #0c5460;
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1 class="title">チーム編集・団体戦</h1>
    <h2 class="team-name"><?= htmlspecialchars($team['team_number']) ?>：<?= htmlspecialchars($team['name']) ?></h2>
  </div>

  <?php if ($message): ?>
    <p class="message" style="<?= strpos($message, '❌') !== false ? 'color:#dc3545;background:#f8d7da;border:1px solid #f5c6cb;' : 'color:#28a745;' ?>font-weight:bold;padding:15px;border-radius:8px;">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

  <div class="note">
    <strong>💡 補欠選手の追加と交代の手順:</strong><br>
    <strong>【補欠選手を追加する場合】</strong><br>
    1. 「補欠選手一覧」セクションの「補欠選手を追加」フォームに名前を入力<br>
    2. 「追加」ボタンをクリック（選手番号は自動採番されます）<br>
    <br>
    <strong>【選手を交代する場合】</strong><br>
    1. 補欠選手一覧から交代させたい補欠を確認<br>
    2. 「選手交代」セクションで、現在のポジション選手と補欠選手を選択<br>
    3. 「交代実行」ボタンをクリック<br>
    ※ 交代すると、ポジションの選手が補欠になり、補欠選手がそのポジションに入ります
  </div>

  <form method="POST" class="form-container" id="orderForm">
    <input type="hidden" name="id" value="<?= htmlspecialchars($tournament_id) ?>">
    <input type="hidden" name="dept" value="<?= htmlspecialchars($department_id) ?>">
    <input type="hidden" name="team" value="<?= htmlspecialchars($team_id) ?>">

    <!-- チーム名編集 -->
    <div class="form-row">
      <label class="position-label">チーム名</label>
      <input type="text" name="team_name" class="player-input" value="<?= htmlspecialchars($team['name']) ?>" required>
      <button type="submit" name="save_team" class="small-btn">保存</button>
    </div>

    <!-- オーダー編集 -->
    <h3 style="margin-top:30px;">正選手オーダー</h3>
    <?php foreach ($positions as $od => $posName): ?>
      <div class="form-row">
        <label class="position-label"><?= htmlspecialchars($posName) ?></label>

        <select name="order_slot[<?= $od ?>]" class="player-input order-select" data-od="<?= $od ?>" required>
          <?php 
          $currentSelection = isset($orderMap[$od]) && $orderMap[$od] !== null ? (string)$orderMap[$od] : '';
          if (empty($currentSelection) && !empty($players)) {
            // 未割当の場合は最初の選手を選択状態に
            $currentSelection = (string)$players[0]['id'];
          }
          ?>
          <?php foreach ($options as $pid => $label): ?>
            <?php $selected = ((string)$pid === $currentSelection) ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars($pid) ?>" <?= $selected ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>

        <button type="button" class="small-btn edit-player-btn" data-od="<?= $od ?>">編集</button>
      </div>
    <?php endforeach; ?>

    <!-- オーダー保存ボタン -->
    <div style="margin:20px 0;">
      <button type="submit" name="save_order" class="action-button">オーダーを保存</button>
    </div>
  </form>

  <!-- 補欠選手一覧 -->
  <div class="substitutes-list">
    <h3>📋 補欠選手一覧</h3>
    
    <!-- 補欠選手追加フォーム -->
    <form method="POST" style="background:white;padding:15px;border-radius:8px;margin-bottom:15px;border:2px dashed #ffc107;">
      <input type="hidden" name="id" value="<?= htmlspecialchars($tournament_id) ?>">
      <input type="hidden" name="dept" value="<?= htmlspecialchars($department_id) ?>">
      <input type="hidden" name="team" value="<?= htmlspecialchars($team_id) ?>">
      
      <h4 style="margin-top:0;color:#856404;">➕ 補欠選手を追加</h4>
      <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <label style="display:block;margin-bottom:5px;font-weight:bold;">選手名 <span style="color:red;">*</span></label>
          <input type="text" name="new_player_name" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <div style="flex:1;min-width:200px;">
          <label style="display:block;margin-bottom:5px;font-weight:bold;">フリガナ</label>
          <input type="text" name="new_player_furigana" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <button type="submit" name="add_substitute" style="padding:10px 20px;background:#ffc107;color:#000;border:none;border-radius:4px;cursor:pointer;font-weight:bold;white-space:nowrap;">
          追加
        </button>
      </div>
      <p style="margin:10px 0 0 0;font-size:0.9em;color:#856404;">
        ℹ️ 選手番号は自動で採番されます
      </p>
    </form>
    
    <!-- 既存の補欠選手一覧 -->
    <?php if (empty($substitutes)): ?>
      <p>補欠選手はいません</p>
    <?php else: ?>
      <?php foreach ($substitutes as $sub): ?>
        <div class="substitute-item">
          <span>
            <strong><?= htmlspecialchars($sub['player_number']) ?></strong>
            <?= htmlspecialchars($sub['name']) ?>
          </span>
          <button type="button" class="small-btn" onclick="location.href='player-edit.php?player=<?= $sub['id'] ?>&team=<?= $team_id ?>&id=<?= $tournament_id ?>&dept=<?= $department_id ?>'">編集</button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- 選手交代セクション -->
  <div class="swap-section">
    <h3>🔄 選手交代</h3>
    <form method="POST" class="swap-form">
      <input type="hidden" name="id" value="<?= htmlspecialchars($tournament_id) ?>">
      <input type="hidden" name="dept" value="<?= htmlspecialchars($department_id) ?>">
      <input type="hidden" name="team" value="<?= htmlspecialchars($team_id) ?>">

      <div class="swap-field">
        <label>現在のポジション選手</label>
        <select name="main_player_id" required>
          <option value="">選択してください</option>
          <?php foreach ($positions as $od => $posName): ?>
            <?php if (isset($orderMap[$od]) && $orderMap[$od]): ?>
              <?php
              $playerId = $orderMap[$od];
              $playerName = '';
              foreach ($players as $p) {
                if ((string)$p['id'] === (string)$playerId) {
                  $playerName = $p['name'];
                  break;
                }
              }
              ?>
              <option value="<?= htmlspecialchars($playerId) ?>"><?= htmlspecialchars($posName) ?>: <?= htmlspecialchars($playerName) ?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="swap-field">
        <label>交代する補欠選手</label>
        <select name="sub_player_id" required>
          <option value="">選択してください</option>
          <?php foreach ($substitutes as $sub): ?>
            <option value="<?= htmlspecialchars($sub['id']) ?>"><?= htmlspecialchars($sub['player_number']) ?>: <?= htmlspecialchars($sub['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" name="swap_players" class="swap-btn" onclick="return confirm('選手を交代しますか？')">交代実行</button>
    </form>
  </div>

  <!-- その他のボタン -->
  <form method="POST" style="margin-top:30px;">
    <input type="hidden" name="id" value="<?= htmlspecialchars($tournament_id) ?>">
    <input type="hidden" name="dept" value="<?= htmlspecialchars($department_id) ?>">
    <input type="hidden" name="team" value="<?= htmlspecialchars($team_id) ?>">
    <input type="hidden" name="current_flag" value="<?= htmlspecialchars($team['withdraw_flg']) ?>">

    <div class="button-container">
      <button type="submit" name="toggle_withdraw" class="action-button <?= $team['withdraw_flg'] ? 'danger' : '' ?>"
        onclick="return confirm('このチームの棄権状態を切り替えます。よろしいですか？')">
        <?= $team['withdraw_flg'] ? '棄権解除' : '棄権' ?>
      </button>

      <button type="button" class="action-button secondary" onclick="location.href='team-list.php?id=<?= urlencode($tournament_id) ?>&dept=<?= urlencode($department_id) ?>'">一覧に戻る</button>
    </div>
  </form>
</div>

<script>
// 編集ボタン制御
document.addEventListener('DOMContentLoaded', () => {
  const selects = Array.from(document.querySelectorAll('.order-select'));
  const editButtons = Array.from(document.querySelectorAll('.edit-player-btn'));

  function updateEditButtons() {
    editButtons.forEach(btn => {
      const od = btn.getAttribute('data-od');
      const sel = document.querySelector('.order-select[data-od="' + od + '"]');
      if (sel) {
        btn.setAttribute('data-player-id', sel.value || '');
      }
    });
  }

  selects.forEach(s => s.addEventListener('change', updateEditButtons));
  updateEditButtons();

  // 編集ボタン押下時の挙動
  editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const pid = btn.getAttribute('data-player-id');
      if (!pid) return;
      const params = new URLSearchParams({
        player: pid,
        team: '<?= addslashes($team_id) ?>',
        id: '<?= addslashes($tournament_id) ?>',
        dept: '<?= addslashes($department_id) ?>'
      });
      location.href = 'player-edit.php?' + params.toString();
    });
  });
});
</script>
</body>
</html>