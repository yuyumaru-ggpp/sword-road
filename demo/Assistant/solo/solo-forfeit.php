<?php
require_once 'solo_db.php';

// セッションチェック
checkSoloSession();

// 変数取得
$tournament_id = (int)$_SESSION['tournament_id'];
$division_id   = (int)$_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];

// 大会・部門情報取得
$info = getTournamentInfo($pdo, $division_id);

// 選手一覧取得
$players = getPlayers($pdo, $division_id);

$error = '';

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $upper_id = trim($_POST['upper_player'] ?? '');
    $lower_id = trim($_POST['lower_player'] ?? '');
    $forfeit  = $_POST['forfeit'] ?? '';

    if ($upper_id === '' || $lower_id === '') {
        $error = '選手を選択してください';
    } else {

        // 選手IDの存在チェック
        $sql = "
            SELECT p.id, p.name, p.player_number
            FROM players p
            INNER JOIN teams t ON p.team_id = t.id
            INNER JOIN departments d ON t.department_id = d.id
            WHERE d.id = :division_id
              AND p.substitute_flg = 0
              AND p.id IN (:upper_id, :lower_id)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':division_id' => $division_id,
            ':upper_id' => $upper_id,
            ':lower_id' => $lower_id
        ]);

        $found_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($found_players) !== 2) {
            $error = '選択された選手が見つかりません';
        } else {

            // 選手情報を取得
            $player_info = [];
            foreach ($found_players as $p) {
                $player_info[$p['id']] = [
                    'name' => $p['name'],
                    'number' => $p['player_number']
                ];
            }

            /* ===============================
               不戦勝（ボタンを押した側が勝ち）
            =============================== */
            if ($forfeit === 'upper' || $forfeit === 'lower') {

                $_SESSION['forfeit_data'] = [
                    'upper_id' => $upper_id,
                    'lower_id' => $lower_id,
                    'upper_name' => $player_info[$upper_id]['name'],
                    'lower_name' => $player_info[$lower_id]['name'],
                    'upper_number' => $player_info[$upper_id]['number'],
                    'lower_number' => $player_info[$lower_id]['number'],
                    'winner' => ($forfeit === 'upper') ? 'A' : 'B',
                    'upper_score' => ($forfeit === 'upper') ? 2 : 0,
                    'lower_score' => ($forfeit === 'lower') ? 2 : 0
                ];

                header('Location: solo-forfeit-confirm.php');
                exit;
            }

            /* ===============================
               通常試合 → 詳細入力へ
            =============================== */
            $_SESSION['player_a_id']     = $upper_id;
            $_SESSION['player_b_id']     = $lower_id;
            $_SESSION['player_a_name']   = $player_info[$upper_id]['name'];
            $_SESSION['player_b_name']   = $player_info[$lower_id]['name'];
            $_SESSION['player_a_number'] = $player_info[$upper_id]['number'];
            $_SESSION['player_b_number'] = $player_info[$lower_id]['number'];

            header('Location: individual-match-detail.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>個人戦選手選択</title>

<style>
/* =============================================
   solo-match-selection.css に含まれないスタイルのみ
   ============================================= */

/* 選手番号入力・ラベル（CSS側に未定義なため追加） */
.input-label-small {
    font-size: 1rem;
    color: #666;
    margin-bottom: 2px;
    text-align: center;
    flex-shrink: 0;
}

.player-number-input {
    width: 100%;
    padding: clamp(10px, 1.8vh, 14px);
    font-size: clamp(13px, 2.2vw, 15px);
    text-align: center;
    border: 2px solid #dee2e6;
    border-radius: 12px;
    background: white;
    font-weight: 500;
    color: #212529;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.player-number-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

/* スマートフォン */
@media (max-width: 480px) {
    .input-label-small {
        font-size: 11px;
    }
    .player-number-input {
        padding: 10px;
        font-size: 13px;
    }
}

/* 小さい画面の高さ対応 */
@media (max-height: 700px) {
    .input-label-small {
        font-size: 10px;
        margin-bottom: 1px;
    }
    .player-number-input {
        padding: 8px;
        font-size: 12px;
    }
}
</style>

<!-- メインスタイルシート（レスポンシブ対応一貫） -->
<link rel="stylesheet" href="solo-match-selection.css">
</head>

<body>

<div class="container">
    <div class="header">
        <div class="header-title">個人戦</div>
        <div class="header-main">
            <?= htmlspecialchars($info['tournament_name']) ?><br>
            <?= htmlspecialchars($info['division_name']) ?>
        </div>
    </div>

    <div class="notice">
        💡 不戦勝の場合は勝者側の「不戦勝」ボタンを押してください
    </div>

    <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="forfeit" id="forfeitInput">

        <!-- クラス名を CSS と合わせた -->
        <div class="match-container">

            <!-- 赤側：player-card left -->
            <div class="player-card left">
                <div class="player-label">赤</div>
                <div class="input-label-small">選手番号</div>
                <input type="text" class="player-number-input" id="upperPlayerNumber" placeholder="番号を入力">
                <div class="input-label-small">または選手を選択</div>
                <select name="upper_player" class="player-select" id="upperPlayer" required>
                    <option value="">選手を選択してください</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>" data-number="<?= htmlspecialchars($player['player_number']) ?>" <?= (isset($_POST['upper_player']) && $_POST['upper_player'] == $player['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="forfeit-button" id="upperForfeit">不戦勝</button>
            </div>

            <!-- VS区切り -->
            <div class="vs-divider">
                <span class="vs-text">VS</span>
            </div>

            <!-- 白側：player-card right -->
            <div class="player-card right">
                <div class="player-label">白</div>
                <div class="input-label-small">選手番号</div>
                <input type="text" class="player-number-input" id="lowerPlayerNumber" placeholder="番号を入力">
                <div class="input-label-small">または選手を選択</div>
                <select name="lower_player" class="player-select" id="lowerPlayer" required>
                    <option value="">選手を選択してください</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>" data-number="<?= htmlspecialchars($player['player_number']) ?>" <?= (isset($_POST['lower_player']) && $_POST['lower_player'] == $player['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="forfeit-button" id="lowerForfeit">不戦勝</button>
            </div>
        </div>

        <div class="action-buttons">
            <button type="submit" class="action-button confirm-button">決定</button>
            <button type="button" class="action-button back-button" onclick="history.back()">戻る</button>
        </div>
    </form>
</div>

<script>
const upperBtn = document.getElementById('upperForfeit');
const lowerBtn = document.getElementById('lowerForfeit');
const forfeitInput = document.getElementById('forfeitInput');

upperBtn.onclick = () => {
    if (upperBtn.classList.contains('selected')) {
        upperBtn.classList.remove('selected');
    } else {
        upperBtn.classList.add('selected');
        lowerBtn.classList.remove('selected');
    }
};

lowerBtn.onclick = () => {
    if (lowerBtn.classList.contains('selected')) {
        lowerBtn.classList.remove('selected');
    } else {
        lowerBtn.classList.add('selected');
        upperBtn.classList.remove('selected');
    }
};

document.querySelector('form').onsubmit = (e) => {
    if (upperBtn.classList.contains('selected')) {
        forfeitInput.value = 'upper';
    } else if (lowerBtn.classList.contains('selected')) {
        forfeitInput.value = 'lower';
    } else {
        forfeitInput.value = '';
    }
};

// 選手番号入力時の自動選択機能
document.getElementById('upperPlayerNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('upperPlayer');
    if (number === '') return;
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

document.getElementById('lowerPlayerNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('lowerPlayer');
    if (number === '') return;
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// プルダウン選択時に選手番号欄に反映
document.getElementById('upperPlayer').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    document.getElementById('upperPlayerNumber').value = selectedOption.dataset.number || '';
});

document.getElementById('lowerPlayer').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    document.getElementById('lowerPlayerNumber').value = selectedOption.dataset.number || '';
});
</script>

</body>
</html>