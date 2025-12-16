<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会名の登録・名称変更画面</title>
    <link rel="stylesheet" href="../css/Admin_selection.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-registration.php" class="breadcrumb-link">大会登録・名称変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">大会名の登録・名称変更画面</h1>
        
        <div class="tournament-list-container">
            <div class="tournament-list">
                <div class="tournament-row" onclick="location.href='tournament-setting.php?id=1'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=2'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=3'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=4'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=5'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=6'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=7'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
                
                <div class="tournament-row" onclick="location.href='tournament-detail.html?id=8'">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name">〇〇大会</span>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="register-button" onclick="location.href='tournament-register.php'">大会登録</button>
            <button class="back-button" onclick="location.href='master2.php'">戻る</button>
        </div>
    </div>
</body>
</html>