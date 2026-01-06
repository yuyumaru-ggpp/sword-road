<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Admin_registration_create.php");
    exit;
}

$tournament_name = $_POST['tournament_name'];
$venue = $_POST['venue'];
$event_date = $_POST['event_date'];
$court_count = $_POST['court_count'];
$tournament_password = $_POST['tournament_password'];

try {
    $sql = "INSERT INTO tournaments (title, venue, event_date, match_field, password, created_at)
        VALUES (:title, :venue, :event_date, :match_field, :password, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title', $tournament_name, PDO::PARAM_STR);
    $stmt->bindValue(':venue', $venue, PDO::PARAM_STR);
    $stmt->bindValue(':event_date', $event_date, PDO::PARAM_STR);
    $stmt->bindValue(':match_field', $court_count, PDO::PARAM_INT);
    $stmt->bindValue(':password', $tournament_password, PDO::PARAM_STR);

    $stmt->execute();

} catch (Exception $e) {
    echo "登録に失敗しました：" . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>大会登録完了</title>
</head>
<body>

<h1>大会登録が完了しました</h1>

<button onclick="location.href='Admin_selection.php'">大会一覧へ戻る</button>

</body>
</html>