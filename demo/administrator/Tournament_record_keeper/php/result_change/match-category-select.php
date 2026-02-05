<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// 大会ID取得
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$tournament_id) {
    header("Location: ../select_tournament.php");
    exit;
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tournament) {
    die("大会が存在しません");
}

// 部門一覧取得（departments テーブルを参照）
$sql = "SELECT id, name, distinction FROM departments WHERE tournament_id = :tid AND (del_flg IS NULL OR del_flg = 0) ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 戻る先（ロールに応じてトップへ戻す場合はここで切替可）
$user_role = $_SESSION['admin_user']['role'] ?? 'admin';
$back_link = ($user_role === 'recorder') ? '../../recorder_top.php' : '../../Admin_top.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合内容変更 - 部門選択</title>
    <link rel="stylesheet" href="../../css/category_select.css">
</head>
<body>

    <div class="breadcrumb">
        <a href="../tournament-detail.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="breadcrumb-link">メニュー ></a>
        <a href="#" class="breadcrumb-link">試合内容変更 ></a>
    </div>

    <div class="container">
        <h1 class="title">部門を選択してください</h1>

        <h2 class="tournament-name"><?= htmlspecialchars($tournament['title'], ENT_QUOTES, 'UTF-8') ?></h2>

        <div class="category-grid">
            <?php if (empty($departments)): ?>
                <p style="padding:1rem;color:#666;">この大会には登録された部門がありません。</p>
            <?php else: ?>
                <?php foreach ($departments as $dept):
                    $dept_id = (int)$dept['id'];
                    $name = htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8');
                    $dist = (int)$dept['distinction']; // 例: 1=団体, 2=個人（既存の定義に合わせてください）
                    // 遷移先：試合一覧（個人戦/団体戦で同じ match-list.php を使うなら dept と一緒に種別は不要）
                    $link = "match-list.php?id={$tournament_id}&dept={$dept_id}";
                ?>
                    <button class="category-button" onclick="location.href='<?= $link ?>'">
                        <?= $name ?>（<?= $dist === 1 ? "団体戦" : "個人戦" ?>）
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="back-link">
            <a href="../tournament_editor_menu.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="back-text">← 戻る</a>
            &nbsp;&nbsp;
        </div>
    </div>

</body>
</html>