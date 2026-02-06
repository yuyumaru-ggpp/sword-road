<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// 大会ID・部門ID取得
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
    die("大会ID または 部門ID が指定されていません");
}

// 部門名取得
$sql = "SELECT name FROM departments WHERE id = :dept";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id, PDO::PARAM_INT);
$stmt->execute();
$department = $stmt->fetch();

// チーム一覧取得
$sql = "SELECT id, name, team_number 
        FROM teams 
        WHERE department_id = :dept 
        ORDER BY team_number ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id, PDO::PARAM_INT);
$stmt->execute();
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>チーム一覧</title>
    <link rel="stylesheet" href="../../css/player_change/team-list-style.css">
</head>
<body>

    <div class="breadcrumb">
        <a href="../tournament-detail.php?id=<?= $tournament_id ?>" class="breadcrumb-link">メニュー></a>
        <a href="player-category-select.php?id=<?= $tournament_id ?>" class="breadcrumb-link">選手変更></a>
        <a href="#" class="breadcrumb-link"><?= htmlspecialchars($department['name']) ?> ></a>
    </div>
    
    <div class="container">
        <h1 class="title"><?= htmlspecialchars($department['name']) ?> - チーム一覧</h1>
        
        <div class="search-container">
            <input type="text" class="search-input" placeholder="🔍 検索" onkeyup="filterTeams()">
        </div>
        
        <div class="team-grid" id="teamGrid">

            <?php foreach ($teams as $t): ?>
                <button class="team-button"
                    onclick="location.href='team-edit.php?team=<?= $t['id'] ?>&id=<?= $tournament_id ?>&dept=<?= $department_id ?>'">
                    <?= htmlspecialchars($t['team_number']) ?>：<?= htmlspecialchars($t['name']) ?>
                </button>
            <?php endforeach; ?>

        </div>
        
        <div class="back-link">
            <a href="category_select.php?id=<?= $tournament_id ?>" class="back-text">← 戻る</a>
        </div>
    </div>

<script>
// 🔍 検索フィルター
function filterTeams() {
    const input = document.querySelector('.search-input').value.toLowerCase();
    const buttons = document.querySelectorAll('.team-button');

    buttons.forEach(btn => {
        const text = btn.textContent.toLowerCase();
        btn.style.display = text.includes(input) ? "block" : "none";
    });
}
</script>

</body>
</html>