<?php
session_start();

// ログインしていなければログイン画面へ
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会運営責任者メニュー</title>
    <link rel="stylesheet" href="../css/Admin_top.css">
</head>
<body>

    <div class="menu-link">
        <a href="#" class="menu-text">メニュー ></a>
    </div>

    <div class="container">
        <h1 class="title">大会運営責任者メニュー</h1>

        <div class="button-grid">
            <button class="menu-button" onclick="location.href='detail_change/Admin_selection.php'">
                <span class="icon">□</span>
                <span class="button-text">大会詳細変更</span>
            </button>

            <button class="menu-button" onclick="location.href='lock/Admin_unlock.php'">
                <span class="icon">🔓</span>
                <span class="button-text">大会ロック解除</span>
            </button>

            <button class="menu-button" onclick="location.href='csv_connection/Admin_addition_selection_tournament.php'">
                <span class="icon">⤓</span>
                <span class="button-text">登録・閲覧・削除</span>
            </button>

            <button class="menu-button" onclick="location.href='change_pass/admin_change_password.php'">
                <span class="icon">🛡</span>
                <span class="button-text">パスワード変更</span>
            </button>
        </div>

        <div class="back-link">
            <a href="../../master.php" class="back-text">← 戻る</a>
        </div>
    </div>

</body>
</html>
