<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会設定</title>
    <link rel="stylesheet" href="../css/Admin_registration.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="#" class="breadcrumb-link">〇〇大会></a>
    </div>
    
    <div class="container">
        <h1 class="title">〇〇大会</h1>
        
        <div class="button-grid">
            <button class="action-button" onclick="location.href='Admin_registration_namechange.php'">名称変更</button>
            <button class="action-button" onclick="location.href='Admin_registration_pwchange.php'">パスワード変更</button>
        </div>
        
        <div class="lock-button-container">
            <button class="lock-button" onclick="location.href='tournament-locked.php'">
                <span class="lock-icon">🔒</span>
                <span>ロック状態にする</span>
            </button>
        </div>
        
        <div class="back-link">
            <a href="tournament-list.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>