<?php
session_start();
require_once '../../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// ロール取得（admin / recorder）
$user_role = $_SESSION['admin_user']['role'] ?? 'admin';

// 大会一覧取得
$sql = "SELECT id, title, event_date FROM tournaments ORDER BY event_date DESC";
$stmt = $pdo->query($sql);
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 戻るボタンの遷移先をロールで切り替え
$back_link = ($user_role === 'recorder')
    ? '../recorder_top.php'
    : 'Admin_top.php';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会選択画面</title>
    <link rel="stylesheet" href="../../css/player_change/select_tournament.css">
</head>

<body>

    <div class="breadcrumb">
        <a href="<?= $back_link ?>" class="breadcrumb-link">メニュー ></a>
        <a href="#" class="breadcrumb-link">大会選択画面 ></a>
    </div>

    <div class="container">
        <h1 class="title">大会選択</h1>

        <div class="tournament-list-container">
            <div class="tournament-list">

                <?php if (empty($tournaments)): ?>
                    <p style="padding: 1rem; color: #666;">登録されている大会はありません。</p>
                <?php else: ?>

                    <?php foreach ($tournaments as $t): ?>
                        <div class="tournament-row"
                            onclick="location.href='category_select.php?id=<?= $t['id'] ?>'">

                            <span class="tournament-id"><?= htmlspecialchars($t['id']) ?></span>

                            <span class="tournament-name">
                                <?= htmlspecialchars($t['title']) ?>
                            </span>

                            <span class="tournament-date">
                                <?= htmlspecialchars($t['event_date']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>
        </div>

        <button type="button"
            class="action-button secondary"
            onclick="location.href='../tournament-detail.php'">
            戻る
        </button>
    </div>

</body>

</html>