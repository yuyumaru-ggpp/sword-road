<?php
session_start();
require_once '../../../db_connect.php'; // 環境に合わせてパスを調整してください

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
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

    // オーダー保存（orders テーブルを上書き） - 改良版
    if (isset($_POST['save_order'])) {
        $order = $_POST['order_slot'] ?? []; // associative: order_detail => player_id (string or empty)
        try {
            $pdo->beginTransaction();

            // 既存のこのチームの orders を削除
            $del = $pdo->prepare("DELETE FROM orders WHERE team_id = :tid");
            $del->bindValue(':tid', (int)$team_id, PDO::PARAM_INT);
            $del->execute();

            // 挿入（player_id が空のものは挿入しない）
            $ins = $pdo->prepare("INSERT INTO orders (team_id, player_id, order_detail) VALUES (:tid, :pid, :od)");
            foreach ($order as $od => $pid) {
                $odInt = (int)$od;

                // 空文字や未割当はスキップ
                if ($pid === '' || $pid === null) {
                    continue;
                }

                // player_id が数値か確認（不正な値はスキップしてログに残す）
                if (!ctype_digit((string)$pid)) {
                    error_log("orders insert skipped: invalid player_id for team {$team_id}, od={$odInt}, pid=" . print_r($pid, true));
                    continue;
                }

                // player が実際に存在し、かつ team_id が一致するか確認（安全対策）
                $chk = $pdo->prepare("SELECT id FROM players WHERE id = :pid AND team_id = :tid LIMIT 1");
                $chk->execute([':pid' => (int)$pid, ':tid' => (int)$team_id]);
                $found = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$found) {
                    // 存在しない player_id はスキップしてログに残す
                    error_log("orders insert skipped: player not found or not in team. team={$team_id}, pid={$pid}, od={$odInt}");
                    continue;
                }

                $ins->bindValue(':tid', (int)$team_id, PDO::PARAM_INT);
                $ins->bindValue(':pid', (int)$pid, PDO::PARAM_INT);
                $ins->bindValue(':od', $odInt, PDO::PARAM_INT);
                $ins->execute();
            }

            $pdo->commit();
            $message = "オーダーを保存しました。";
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("order save error: " . $e->getMessage() . " | team={$team_id} | post=" . print_r($_POST['order_slot'] ?? [], true));
            $message = "オーダー保存中にエラーが発生しました。サーバログを確認してください。";
        }
    }
}

// チーム情報取得
$stmt = $pdo->prepare("SELECT id, name, team_number, withdraw_flg FROM teams WHERE id = :id LIMIT 1");
$stmt->execute([':id' => (int)$team_id]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$team) die("チームが見つかりませんでした");

// チーム所属選手（全員）取得
$stmt = $pdo->prepare("SELECT id, name, furigana, player_number FROM players WHERE team_id = :tid ORDER BY player_number ASC");
$stmt->execute([':tid' => (int)$team_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 選択肢配列（player_id => name のみ）
$options = ['' => '（未割当）'];
foreach ($players as $pl) {
    $options[$pl['id']] = $pl['name'];
}

// orders テーブルから現在の割当を取得（order_detail をキーに）
$orderMap = []; // order_detail(int) => player_id (string|null)
try {
    $stmt = $pdo->prepare("SELECT order_detail, player_id FROM orders WHERE team_id = :tid");
    $stmt->execute([':tid' => (int)$team_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $od = (int)$r['order_detail'];
        $orderMap[$od] = ($r['player_id'] === null ? null : (string)$r['player_id']);
    }
} catch (Exception $e) {
    // orders テーブルが存在しない／別スキーマの場合は空のまま
    $orderMap = [];
}

// players.id として存在しない player_id が入っている可能性がある場合は安全のため null にする
foreach ($orderMap as $k => $v) {
    if ($v === null) continue;
    $stmt = $pdo->prepare("SELECT id FROM players WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $v]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$f) {
        // 見つからなければ null にする
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
</head>
<body>
<div class="container">
  <div class="header">
    <h1 class="title">チーム編集・団体戦</h1>
    <h2 class="team-name"><?= htmlspecialchars($team['team_number']) ?>：<?= htmlspecialchars($team['name']) ?></h2>
  </div>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <p class="note">※オーダーは orders テーブルの内容が初期表示されます。変更後は「オーダーを保存」してください。</p>

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
    <?php foreach ($positions as $od => $posName): ?>
      <div class="form-row">
        <label class="position-label"><?= htmlspecialchars($posName) ?></label>

        <select name="order_slot[<?= $od ?>]" class="player-input order-select" data-od="<?= $od ?>">
          <option value="">（未割当）</option>
          <?php foreach ($options as $pid => $label): ?>
            <?php $selected = (isset($orderMap[$od]) && $orderMap[$od] !== null && (string)$orderMap[$od] === (string)$pid) ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars($pid) ?>" <?= $selected ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>

        <button type="button" class="small-btn edit-player-btn" data-od="<?= $od ?>" disabled>編集</button>
      </div>
    <?php endforeach; ?>

    <!-- ボタン群 -->
    <div class="button-container">
      <button type="submit" name="save_order" class="action-button">オーダーを保存</button>

      <input type="hidden" name="current_flag" value="<?= htmlspecialchars($team['withdraw_flg']) ?>">
      <button type="submit" name="toggle_withdraw" class="action-button <?= $team['withdraw_flg'] ? 'danger' : '' ?>"
        onclick="return confirm('このチームの棄権状態を切り替えます。よろしいですか？')">
        <?= $team['withdraw_flg'] ? '棄権解除' : '棄権' ?>
      </button>

      <button type="button" class="action-button secondary" onclick="location.href='team-list.php?id=<?= urlencode($tournament_id) ?>&dept=<?= urlencode($department_id) ?>'">一覧に戻る</button>

    </div>
  </form>
</div>

<script>
// 重複選択防止と編集ボタン制御
document.addEventListener('DOMContentLoaded', () => {
  const selects = Array.from(document.querySelectorAll('.order-select'));
  const editButtons = Array.from(document.querySelectorAll('.edit-player-btn'));

  function refreshOptions() {
    const chosen = selects.map(s => s.value).filter(v => v !== '');
    selects.forEach(s => {
      Array.from(s.options).forEach(opt => {
        if (opt.value === '') { opt.disabled = false; return; }
        const disable = chosen.includes(opt.value) && opt.value !== s.value;
        opt.disabled = disable;
      });
    });

    // 編集ボタンの有効/無効を更新
    editButtons.forEach(btn => {
      const od = btn.getAttribute('data-od');
      const sel = document.querySelector('.order-select[data-od="' + od + '"]');
      if (!sel) { btn.disabled = true; return; }
      btn.disabled = (sel.value === '');
      btn.style.opacity = btn.disabled ? '0.5' : '1';
      btn.style.cursor = btn.disabled ? 'not-allowed' : 'pointer';
      btn.setAttribute('data-player-id', sel.value || '');
    });
  }

  selects.forEach(s => s.addEventListener('change', refreshOptions));
  selects.forEach(s => s.addEventListener('keyup', refreshOptions));
  refreshOptions();

  // 編集ボタン押下時の挙動
  editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
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