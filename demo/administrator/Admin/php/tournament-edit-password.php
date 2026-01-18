<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: Admin_selection.php");
    exit;
}

// POST処理（更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_password'] ?? '';
    $new_pass2 = $_POST['new_password_confirm'] ?? '';

    if ($new_pass === '' || $new_pass2 === '') {
        $error = "パスワードを入力してください";
    } elseif ($new_pass !== $new_pass2) {
        $error = "パスワードが一致しません";
    } else {
        // 更新処理
        $sql = "UPDATE tournaments 
                SET password = :password,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':password', $new_pass, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: tournament-setting.php?id=" . $id);
        exit;
    }
}

// 大会名取得（パンくず用）
$sql = "SELECT title FROM tournaments WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード変更</title>
    <link rel="stylesheet" href="../css/Admin_registration_namechange.css">
</head>
<body>

<div class="breadcrumb">
    <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
    <a href="Admin_selection.php" class="breadcrumb-link">大会一覧 ></a>
    <a href="tournament-setting.php?id=<?= $id ?>" class="breadcrumb-link"><?= htmlspecialchars($tournament['title']) ?> ></a>
    <a href="#" class="breadcrumb-link">パスワード変更 ></a>
</div>

<div class="container">
    <h1 class="title">パスワード変更</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="input-container">
            <input type="password" name="new_password" class="name-input" placeholder="新しいパスワード" required>
        </div>

        <div class="input-container">
            <input type="password" name="new_password_confirm" class="name-input" placeholder="新しいパスワード（再入力）" required>
        </div>

        <div class="button-container">
            <button type="submit" class="action-button">決定</button>
            <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
        </div>
    </form>
</div>

</body>
</html>