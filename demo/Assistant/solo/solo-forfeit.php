<?php
session_start();

/* ===============================
   必須セッションチェック
=============================== */
if (
    !isset($_SESSION['tournament_id'], $_SESSION['division_id'], $_SESSION['match_number'])
) {
    header('Location: match_input.php');
    exit;
}

$tournament_id = (int)$_SESSION['tournament_id'];
$division_id   = (int)$_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];

/* ===============================
   DB接続
=============================== */

$dsn = "mysql:host=localhost;port=3308;dbname=kendo_support_system;charset=utf8mb4";

$pdo = new PDO($dsn, "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

/* ===============================
   大会・部門名取得
=============================== */
$sql = "
    SELECT
        t.title AS tournament_name,
        d.name  AS division_name
    FROM departments d
    INNER JOIN tournaments t ON t.id = d.tournament_id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':division_id' => $division_id
]);
$info = $stmt->fetch();

if (!$info) {
    exit('部門情報が取得できません');
}

/* ===============================
   部門に属する選手一覧を取得（画面表示用）
=============================== */
$sql = "
    SELECT
        p.id,
        p.player_number,
        p.name,
        t.name as team_name
    FROM players p
    INNER JOIN teams t ON p.team_id = t.id
    INNER JOIN departments d ON t.department_id = d.id
    WHERE d.id = :division_id
      AND p.substitute_flg = 0
    ORDER BY p.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':division_id' => $division_id
]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
               不戦勝
            =============================== */
            if ($forfeit === 'upper' || $forfeit === 'lower') {

                // セッションに不戦勝情報を保存
                $_SESSION['forfeit_data'] = [
                    'upper_id' => $upper_id,
                    'lower_id' => $lower_id,
                    'upper_name' => $player_info[$upper_id]['name'],
                    'lower_name' => $player_info[$lower_id]['name'],
                    'upper_number' => $player_info[$upper_id]['number'],
                    'lower_number' => $player_info[$lower_id]['number'],
                    'winner' => ($forfeit === 'upper') ? 'A' : 'B'
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

        <div class="match-container">
            <div class="player-card left">
                <div class="player-label">上段選手</div>
                <select name="upper_player" class="player-select" id="upperPlayer" required>
                    <option value="">選手を選択してください</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>" <?= (isset($_POST['upper_player']) && $_POST['upper_player'] == $player['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="forfeit-button" id="upperForfeit">不戦勝</button>
            </div>

            <div class="vs-divider">
                <span class="vs-text">VS</span>
            </div>

            <div class="player-card right">
                <div class="player-label">下段選手</div>
                <select name="lower_player" class="player-select" id="lowerPlayer" required>
                    <option value="">選手を選択してください</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>" <?= (isset($_POST['lower_player']) && $_POST['lower_player'] == $player['id']) ? 'selected' : '' ?>>
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
</script>

</body>
</html>