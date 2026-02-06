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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $venue = $_POST['venue'] ?? '';
    $match_field = $_POST['match_field'] ?? '';

    if ($venue === '' || $match_field === '') {
        $error = "すべての項目を入力してください";
    } elseif (!ctype_digit($match_field)) {
        $error = "試合場数は数字で入力してください";
    } else {
        $sql = "UPDATE tournaments 
                SET venue = :venue,
                    match_field = :match_field,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':venue', $venue, PDO::PARAM_STR);
        $stmt->bindValue(':match_field', $match_field, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: tournament-detail.php?id=" . $id);
        exit;
    }
}

// 現在の大会情報取得
$sql = "SELECT title, venue, match_field FROM tournaments WHERE id = :id";
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
    <title>会場・試合場編集</title>
    <link rel="stylesheet" href="../../css/Admin_registration_namechange.css">
</head>

<body>

    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
        <a href="Admin_selection.php" class="breadcrumb-link">大会一覧 ></a>
        <a href="tournament-detail.php?id=<?= $id ?>" class="breadcrumb-link"><?= htmlspecialchars($tournament['title']) ?> ></a>
        <a href="#" class="breadcrumb-link">会場・試合場編集 ></a>
    </div>

    <div class="container">
        <h1 class="title">会場・試合場編集</h1>

        <?php if (!empty($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="input-container">
                <label for="venue" class="input-label">会場名</label>
                <input type="text" name="venue" id="venue" class="name-input"
                    value="<?= htmlspecialchars($tournament['venue']) ?>" required>
            </div>
            <div class="input-container">
                <label for="match_field" class="input-label">試合場数</label>
                <input type="number" name="match_field" id="match_field" class="name-input"
                    value="<?= htmlspecialchars($tournament['match_field']) ?>" required>
            </div>
            <div class="button-container">
                <button type="submit" class="action-button">決定</button>
                <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
            </div>

        </form>
    </div>

</body>

</html>