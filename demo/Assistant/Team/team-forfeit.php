<?php
require_once 'team_db.php';

// 基本セッションチェック（team_red_id, team_white_idはまだ不要）
if (
    !isset(
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number']
    )
) {
    header('Location: match_input.php');
    exit;
}

// 変数取得
$tournament_id = (int)$_SESSION['tournament_id'];
$division_id   = (int)$_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];

// 大会・部門情報取得
$sql = "
    SELECT
        t.title AS tournament_name,
        d.name  AS division_name
    FROM tournaments t
    JOIN departments d ON d.tournament_id = t.id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $division_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// 部門に属するチーム一覧を取得
$sql = "
    SELECT
        t.id,
        t.team_number,
        t.name
    FROM teams t
    WHERE t.department_id = :division_id
    ORDER BY t.team_number
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $division_id]);
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $red_team_id = trim($_POST['red_team'] ?? '');
    $white_team_id = trim($_POST['white_team'] ?? '');

    if ($red_team_id === '' || $white_team_id === '') {
        $error = 'チームを選択してください';
    } else if ($red_team_id === $white_team_id) {
        $error = '同じチームは選択できません';
    } else {

        // チームIDの存在チェック
        $sql = "
            SELECT t.id, t.name, t.team_number
            FROM teams t
            WHERE t.department_id = :division_id
              AND t.id IN (:red_id, :white_id)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':division_id' => $division_id,
            ':red_id' => $red_team_id,
            ':white_id' => $white_team_id
        ]);

        $found_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($found_teams) !== 2) {
            $error = '選択されたチームが見つかりません';
        } else {

            // セッションに保存
            $_SESSION['team_red_id'] = $red_team_id;
            $_SESSION['team_white_id'] = $white_team_id;

            header('Location: team-order-registration.php');
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
<title>団体戦チーム選択</title>

<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans','Meiryo',sans-serif;
    background:#f5f5f5;
    padding:1rem;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}
.container {
    max-width:1200px;
    width:100%;
    background:white;
    padding:2rem;
    border-radius:8px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}
.header {
    display:flex;
    flex-wrap:wrap;
    gap:1rem;
    font-size:clamp(1.2rem, 3vw, 2rem);
    font-weight:bold;
    margin-bottom:3rem;
}
.match-row {
    display:flex;
    gap:2rem;
    justify-content:space-between;
    margin-bottom:3rem;
    align-items:center;
}
.team-section {
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:1.5rem;
}
.team-label {
    font-size:2rem;
    font-weight:bold;
}
.team-select {
    width:100%;
    max-width:350px;
    padding:1rem;
    font-size:1.2rem;
    text-align:center;
    border:3px solid #ddd;
    border-radius:8px;
    cursor:pointer;
}
.team-select:focus {
    outline:none;
    border-color:#3b82f6;
}
.vs-text {
    font-size:3rem;
    font-weight:bold;
    flex-shrink:0;
}
.action-buttons {
    display:flex;
    justify-content:center;
    gap:2rem;
}
.action-button {
    padding:1rem 3rem;
    font-size:1.4rem;
    font-weight:bold;
    border-radius:50px;
    cursor:pointer;
    transition:all 0.2s;
}
.confirm-button {
    background:#3b82f6;
    color:white;
    border:3px solid #3b82f6;
}
.confirm-button:hover {
    background:#2563eb;
}
.back-button {
    background:white;
    border:3px solid #000;
}
.back-button:hover {
    background:#f9fafb;
}
.team-number-input {
    width:100%;
    max-width:350px;
    padding:1rem;
    font-size:1.2rem;
    text-align:center;
    border:3px solid #ddd;
    border-radius:8px;
    margin-bottom:0.5rem;
}
.team-number-input:focus {
    outline:none;
    border-color:#3b82f6;
}
.input-label-small {
    font-size:1rem;
    color:#666;
    margin-bottom:0.5rem;
}

.error {
    color:#ef4444;
    text-align:center;
    font-size:1.2rem;
    margin-bottom:1rem;
}
@media (max-width: 768px) {
    .match-row {
        flex-direction:column;
    }
    .vs-text {
        font-size:2rem;
    }
}
</style>
</head>

<body>

<div class="container">
    <div class="header">
        <span>団体戦</span>
        <span><?= htmlspecialchars($info['tournament_name']) ?></span>
        <span><?= htmlspecialchars($info['division_name']) ?></span>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="match-row">
            <div class="team-section">
                <div class="team-label">赤</div>
                <div class="input-label-small">チーム番号</div>
                <input type="text" class="team-number-input" id="redTeamNumber" placeholder="チーム番号を入力">
                <div class="input-label-small">またはチームを選択</div>
                <select name="red_team" class="team-select" id="redTeam" required>
                    <option value="">チームを選択してください</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>" data-number="<?= htmlspecialchars($team['team_number']) ?>">
                            <?= htmlspecialchars($team['name']) ?> (<?= htmlspecialchars($team['team_number']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vs-text">対</div>

            <div class="team-section">
                <div class="team-label">白</div>
                <div class="input-label-small">チーム番号</div>
                <input type="text" class="team-number-input" id="whiteTeamNumber" placeholder="チーム番号を入力">
                <div class="input-label-small">またはチームを選択</div>
                <select name="white_team" class="team-select" id="whiteTeam" required>
                    <option value="">チームを選択してください</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>" data-number="<?= htmlspecialchars($team['team_number']) ?>">
                            <?= htmlspecialchars($team['name']) ?> (<?= htmlspecialchars($team['team_number']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="action-buttons">
            <button type="submit" class="action-button confirm-button">決定</button>
            <button type="button" class="action-button back-button" onclick="history.back()">戻る</button>
        </div>
    </form>
</div>

<script>
// チーム番号入力時の自動選択機能（赤チーム）
document.getElementById('redTeamNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('redTeam');
    
    if (number === '') {
        return;
    }
    
    // チーム番号に一致するオプションを探す
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// チーム番号入力時の自動選択機能（白チーム）
document.getElementById('whiteTeamNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('whiteTeam');
    
    if (number === '') {
        return;
    }
    
    // チーム番号に一致するオプションを探す
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// プルダウン選択時にチーム番号欄に反映（赤チーム）
document.getElementById('redTeam').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const numberInput = document.getElementById('redTeamNumber');
    
    if (selectedOption.dataset.number) {
        numberInput.value = selectedOption.dataset.number;
    } else {
        numberInput.value = '';
    }
});

// プルダウン選択時にチーム番号欄に反映（白チーム）
document.getElementById('whiteTeam').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const numberInput = document.getElementById('whiteTeamNumber');
    
    if (selectedOption.dataset.number) {
        numberInput.value = selectedOption.dataset.number;
    } else {
        numberInput.value = '';
    }
});
</script>

</body>
</html>