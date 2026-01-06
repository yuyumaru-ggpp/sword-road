<?php
session_start();

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// POST 以外は戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Admin_registration_create.php");
    exit;
}

$tournament_name = $_POST['tournament_name'];
$venue = $_POST['venue'];
$event_date = $_POST['event_date'];
$court_count = $_POST['court_count'];
$tournament_password = $_POST['tournament_password'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>大会登録確認</title>
    <link rel="stylesheet" href="../css/Admin_registration_confirm.css">
</head>
<body>

<div class="container">
    <h1>大会登録確認</h1>

    <p><strong>大会名称：</strong> <?= htmlspecialchars($tournament_name) ?></p>
    <p><strong>会場：</strong> <?= htmlspecialchars($venue) ?></p>
    <p><strong>開催日：</strong> <?= htmlspecialchars($event_date) ?></p>
    <p><strong>試合会場数：</strong> <?= htmlspecialchars($court_count) ?></p>
    <p><strong>大会パスワード：</strong> <?= htmlspecialchars($tournament_password) ?></p>

    <form action="tournament-register-complete.php" method="POST">
        <input type="hidden" name="tournament_name" value="<?= htmlspecialchars($tournament_name) ?>">
        <input type="hidden" name="venue" value="<?= htmlspecialchars($venue) ?>">
        <input type="hidden" name="event_date" value="<?= htmlspecialchars($event_date) ?>">
        <input type="hidden" name="court_count" value="<?= htmlspecialchars($court_count) ?>">
        <input type="hidden" name="tournament_password" value="<?= htmlspecialchars($tournament_password) ?>">

        <button type="submit">登録する</button>
        <button type="button" onclick="history.back()">戻る</button>
    </form>
</div>

</body>
</html>