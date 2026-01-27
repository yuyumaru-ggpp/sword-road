<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// 大会一覧取得
$sql = "SELECT id, title, event_date FROM tournaments ORDER BY event_date DESC";
$stmt = $pdo->query($sql);
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会詳細変更画面</title>
    <link rel="stylesheet" href="../css/Admin_selection.css">
</head>

<body>

    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
        <a href="#" class="breadcrumb-link">大会詳細変更 ></a>
    </div>

    <div class="container">
        <h1 class="title">大会詳細変更画面</h1>

        <div class="tournament-list-container">
            <div class="tournament-list">

                <?php foreach ($tournaments as $t): ?>
                    <div class="tournament-row" onclick="location.href='tournament-detail.php?id=<?= $t['id'] ?>'">
                        <span class="tournament-id"><?= htmlspecialchars($t['id']) ?></span>

                        <span class="tournament-name">
                            <?= htmlspecialchars($t['title']) ?>
                        </span>

                        <span class="tournament-date">
                            <?= htmlspecialchars($t['event_date']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="action-buttons">
            <button class="register-button" onclick="location.href='Admin_registration_create.php'">大会登録</button>
            <button class="back-button" onclick="location.href='Admin_top.php'">戻る</button>
        </div>
    </div>
</body>

</html>