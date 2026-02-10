<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: Admin_selection.php");
    exit;
}

// POST処理（更新）
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $new_title = $_POST['new_title'] ?? '';

//     if ($new_title !== '') {
//         $sql = "UPDATE tournaments 
//         SET title = :title,
//             updated_at = NOW()
//         WHERE id = :id";
//         $stmt = $pdo->prepare($sql);
//         $stmt->bindValue(':title', $new_title, PDO::PARAM_STR);
//         $stmt->bindValue(':id', $id, PDO::PARAM_INT);
//         $stmt->execute();

//         header("Location: Admin_selection.php");
//         exit;
//     }
// }

// 現在の大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :id";
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
    <title>名称変更</title>
    <link rel="stylesheet" href="../../css/Admin_registration_namechange.css">
</head>
<body>

<div class="breadcrumb">
    <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
    <a href="Admin_selection.php" class="breadcrumb-link">大会、部門登録・名称変更 ></a>
    <a href="tournament-setting.php?id=<?= $id ?>" class="breadcrumb-link"><?= htmlspecialchars($tournament['title']) ?> ></a>
    <a href="#" class="breadcrumb-link">名称変更 ></a>
</div>

<div class="container">
    <h1 class="title">名称変更</h1>

    <form method="POST">
        <div class="input-container">
            <input type="text" name="new_title" class="name-input" value="<?= htmlspecialchars($tournament['title']) ?>" required>
        </div>

        <div class="button-container">
            <button type="submit" class="action-button">決定</button>
            <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
        </div>
    </form>
</div>

</body>
</html>