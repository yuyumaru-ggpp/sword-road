<?php
session_start();
require_once '../../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// 大会ID取得
$tournament_id = $_GET['id'] ?? null;

if (!$tournament_id) {
    die("大会IDが指定されていません");
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch();

if (!$tournament) {
    die("大会が存在しません");
}

// 部門一覧取得
$sql = "SELECT id, name, distinction 
        FROM departments 
        WHERE tournament_id = :tid 
        ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手変更 - 部門選択</title>
    <link rel="stylesheet" href="../../css/player_change/category_select.css">
</head>

<body>

    <div class="breadcrumb">
        <a href="../tournament-detail.php?id=<?= $tournament_id ?>" class="breadcrumb-link">メニュー></a>
        <a href="#" class="breadcrumb-link">選手変更></a>
    </div>

    <div class="container">
        <h1 class="title">変更したい選手の部門を選択してください</h1>

        <h2 class="tournament-name"><?= htmlspecialchars($tournament['title']) ?></h2>

        <div class="category-grid">

            <?php foreach ($departments as $dept): ?>
                <?php
                $dept_id = $dept['id'];
                $name = htmlspecialchars($dept['name']);
                $dist = (int)$dept['distinction'];

                // 遷移先
                $link = ($dist === 2)
                    ? "individual.php?id={$tournament_id}&dept={$dept_id}"
                    : "team-list.php?id={$tournament_id}&dept={$dept_id}";
                ?>

                <button class="category-button" onclick="location.href='<?= $link ?>'">
                    <?= $name ?>（<?= $dist === 1 ? "団体戦" : "個人戦" ?>）
                </button>

            <?php endforeach; ?>

        </div>

        <button type="button" class="action-button" onclick="location.href='./tournament_select.php?id=<?= htmlspecialchars($tournament_id) ?>>'">戻る</button>
    </div>

</body>

</html>