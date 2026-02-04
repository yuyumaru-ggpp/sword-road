<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../../db_connect.php';

// GET パラメータ
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$department_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

// 大会名取得
$tournament_name = '大会名';
if ($tournament_id) {
    $sql = "SELECT title FROM tournaments WHERE id = :tid LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['title'])) {
        $tournament_name = $row['title'];
    }
}
// --- ここから挿入: 大会ロック判定と戻り先設定 ---
$back_link = $_SERVER['HTTP_REFERER'] ?? 'tournament_select.php';

// is_locked を取得してロックならメッセージを表示して終了
if ($tournament_id) {
    $stmt = $pdo->prepare("SELECT is_locked FROM tournaments WHERE id = :tid LIMIT 1");
    $stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
    $stmt->execute();
    $lockRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_locked = isset($lockRow['is_locked']) ? (int)$lockRow['is_locked'] : 0;

    if ($is_locked === 1) {
        // ロック時の簡易画面（必要ならデザインを合わせてください）
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>編集ロック - <?= htmlspecialchars($tournament_name) ?></title>
            <link rel="stylesheet" href="../css/tournament-detail-style.css">
            <style>
                /* 最低限のスタイル（既存CSSがあれば不要） */
                .locked-container { max-width:720px; margin:4rem auto; padding:2rem; border:1px solid #ddd; border-radius:8px; text-align:center; }
                .locked-title { font-size:1.25rem; margin-bottom:0.5rem; }
                .locked-msg { margin:1rem 0 1.5rem; color:#b00; }
                .btn { display:inline-block; padding:0.6rem 1rem; border-radius:6px; background:#0078d4; color:#fff; text-decoration:none; }
                .btn.secondary { background:#666; margin-left:0.5rem; }
            </style>
        </head>
        <body>
            <div class="locked-container">
                <div class="locked-title"><?= htmlspecialchars($tournament_name) ?></div>
                <div class="locked-msg">この大会はロックされています。編集はできません。</div>
                <div>
                    <a class="btn secondary" href="tournament_select.php">大会一覧へ</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
// --- 挿入ここまで ---

// ロール取得
$user_role = $_SESSION['admin_user']['role'] ?? 'admin';


// 管理者表示名
$admin_display = $_SESSION['admin_user']['display_name']
    ?? $_SESSION['admin_user']['user_id']
    ?? '管理者';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面 - <?= htmlspecialchars($tournament_name) ?></title>
    <link rel="stylesheet" href="../css/tournament-detail-style.css">
</head>
<body>

    <div class="user-info">
        <?= htmlspecialchars($admin_display) ?> | <a href="logout.php">ログアウト</a>
    </div>

    <div class="menu-link">
        <a href="<?= $back_link ?>" class="menu-text">メニュー ></a>
    </div>

    <div class="container">
        <h2 class="subtitle">大会記録画面</h2>
        <h1 class="title"><?= htmlspecialchars($tournament_name) ?></h1>

        <div class="button-grid">

            <!-- 選手変更 -->
            <button class="menu-button"
                onclick="location.href='./player_change/category_select.php?id=<?= $tournament_id ?>'">
                <span class="button-text">選手の変更</span>
            </button>

            <!-- 試合内容の変更 -->
            <button class="menu-button"
                onclick="location.href='./result_change/match-category-select.php?id=<?= $tournament_id ?>'">
                <span class="icon">✎</span>
                <span class="button-text">試合内容の変更</span>
            </button>

        </div>

        <button type="button"
            class="action-button secondary"
            onclick="location.href='tournament_select.php'">
            戻る
        </button>
    </div>

</body>
</html>