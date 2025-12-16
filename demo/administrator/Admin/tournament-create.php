<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トーナメント作成</title>
    <link rel="stylesheet" href="tournament-create-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-create.html" class="breadcrumb-link">トーナメント作成</a>
    </div>
    
    <div class="container">
        <div class="input-container">
            <input type="text" class="tournament-input" placeholder="">
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="alert('トーナメントを生成します')">生成</button>
            <button class="action-button" onclick="alert('ダウンロードします')">ダウンロード</button>
        </div>
        
        <div class="menu-link-container">
            <button class="menu-link-button" onclick="location.href='master2.php'">メニューへ戻る</button>
        </div>
    </div>
</body>
</html>