<?php
session_start();

// ログインしていなければログイン画面へ
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../../db_connect.php';

// 大会一覧取得
$sql = "SELECT id, title FROM tournaments ORDER BY id DESC";
$stmt = $pdo->query($sql);
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSVデータの登録・閲覧・削除</title>
    <link rel="stylesheet" href="../../css/Admin_selection.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="#" class="breadcrumb-link">大会選択></a>
    </div>
    
    <div class="container">
        <h1 class="title">CSVデータの登録・閲覧・削除する大会の選択画面</h1>
        
        <div class="tournament-list-container">
            <div class="tournament-list">

                <?php if (empty($tournaments)): ?>
                    <p>大会が登録されていません。</p>
                <?php else: ?>
                    <?php foreach ($tournaments as $t): ?>
                        <div class="tournament-row"
                             onclick="location.href='Admin_addition_selection_department.php?id=<?= $t['id'] ?>'">
                            <span class="tournament-id">ID <?= htmlspecialchars($t['id']) ?></span>
                            <span class="tournament-name"><?= htmlspecialchars($t['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
        
        <div class="action-buttons">
            <button class="back-button" onclick="location.href='../Admin_top.php'">戻る</button>
        </div>
    </div>
</body>
</html>