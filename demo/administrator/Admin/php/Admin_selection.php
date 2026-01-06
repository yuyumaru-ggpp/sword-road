<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// 大会一覧取得
$sql = "SELECT id, title FROM tournaments ORDER BY id ASC";
$stmt = $pdo->query($sql);
$tournaments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会名、部門の登録・名称変更画面</title>
    <link rel="stylesheet" href="../css/Admin_selection.css">
</head>

<body>

    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
        <a href="#" class="breadcrumb-link">大会、部門登録・名称変更 ></a>
    </div>

    <div class="container">
        <h1 class="title">大会名、部門の登録・名称変更画面</h1>

        <div class="tournament-list-container">
            <div class="tournament-list">

                <?php foreach ($tournaments as $t): ?>
                    <div class="tournament-row" onclick="location.href='Admin_registration.php?id=<?= $t['id'] ?>'">
                        <span class="tournament-id"><?= htmlspecialchars($t['id']) ?></span>

                        <span class="tournament-name"
                            onclick="event.stopPropagation(); location.href='tournament-setting.php?id=<?= $t['id'] ?>'">
                            <?= htmlspecialchars($t['title']) ?>
                        </span>

                        <button class="delete-button"
                            onclick="event.stopPropagation(); confirmDelete(<?= $t['id'] ?>, '<?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?>')">
                            削除
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="action-buttons">
            <button class="register-button" onclick="location.href='Admin_registration_create.php'">大会登録</button>
            <button class="back-button" onclick="location.href='Admin_top.php'">戻る</button>
        </div>
    </div>

    <script>
        function confirmDelete(id, name) {
            if (confirm(`「${name}」を削除してもよろしいですか?`)) {
                window.location.href = `tournament-delete.php?id=${id}`;
            }
        }
    </script>

</body>

</html>