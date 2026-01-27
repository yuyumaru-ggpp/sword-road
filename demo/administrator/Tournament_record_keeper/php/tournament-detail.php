<?php
// tournament-detail.php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../../db_connect.php'; // 環境に合わせてパスを調整してください

// GET パラメータ（大会ID・部門IDは任意）
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$department_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

// 大会名取得（id が指定されていれば取得、なければデフォルト表示）
$tournament_name = '大会名';
if ($tournament_id) {
    $sql = "SELECT title FROM tournaments WHERE id = :tid AND del_flg = 0 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['title'])) {
        $tournament_name = $row['title'];
    }
}

// 管理者表示名（セッションに保存している想定）
$admin_display = $_SESSION['admin_user']['display_name'] ?? $_SESSION['admin_user']['user_id'] ?? '管理者';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面 - <?= htmlspecialchars($tournament_name, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="../css/tournament-detail-style.css">
    <style>
      /* 最低限の右上ユーザー表示スタイル（既存CSSがあれば不要） */
      .user-info { position: absolute; top: 12px; right: 16px; font-size: 0.9rem; color:#333; }
    </style>
</head>
<body>
    <div class="user-info">
        <?= htmlspecialchars($admin_display, ENT_QUOTES, 'UTF-8') ?> | <a href="logout.php">ログアウト</a>
    </div>

    <div class="menu-link">
        <a href="tournament-detail.php" class="menu-text">メニュー&gt;</a>
    </div>
    
    <div class="container">
        <h2 class="subtitle">大会記録画面</h2>
        <h1 class="title"><?= htmlspecialchars($tournament_name, ENT_QUOTES, 'UTF-8') ?></h1>
        
        <div class="button-grid">
            <button class="menu-button"
                onclick="location.href='./player_change/tournament_select.php<?= $tournament_id ? '?id=' . $tournament_id . ($department_id ? '&dept=' . $department_id : '') : '' ?>'">
                <span class="button-text">選手の変更</span>
            </button>
            
            <button class="menu-button"
                onclick="location.href='./result_change/match-category-select.php<?= $tournament_id ? '?id=' . $tournament_id . ($department_id ? '&dept=' . $department_id : '') : '' ?>'">
                <span class="icon">✎</span>
                <span class="button-text">試合内容の変更</span>
            </button>
        </div>
        
        <div class="back-link">
            <a href="../../master.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>