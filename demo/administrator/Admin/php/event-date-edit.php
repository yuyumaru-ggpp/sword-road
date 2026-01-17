<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
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
    $new_date = $_POST['event_date'] ?? '';

    if ($new_date === '') {
        $error = "開催日を入力してください";
    } else {
        $sql = "UPDATE tournaments 
                SET event_date = :event_date,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':event_date', $new_date, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: tournament-detail.php?id=" . $id);
        exit;
    }
}

// 現在の大会情報取得
$sql = "SELECT title, event_date FROM tournaments WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tournament) {
    echo "大会が見つかりません";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>開催日修正</title>
    <link rel="stylesheet" href="../css/Admin_registration_namechange.css">
</head>
<body>

<div class="breadcrumb">
    <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
    <a href="Admin_selection.php" class="breadcrumb-link">大会一覧 ></a>
    <a href="tournament-detail.php?id=<?= $id ?>" class="breadcrumb-link"><?= htmlspecialchars($tournament['title']) ?> ></a>
    <a href="#" class="breadcrumb-link">開催日修正 ></a>
</div>

<div class="container">
    <h1 class="title">開催日修正</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="input-container">
            <input type="date" name="event_date" class="name-input"
                   value="<?= htmlspecialchars($tournament['event_date']) ?>" required>
        </div>

        <div class="button-container">
            <button type="submit" class="action-button">決定</button>
            <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
        </div>
    </form>
</div>

</body>
</html>