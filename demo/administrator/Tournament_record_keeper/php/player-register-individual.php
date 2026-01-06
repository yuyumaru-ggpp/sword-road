<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手登録・個人戦</title>
    <link rel="stylesheet" href="../css/player-register-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="tournament-detail.html" class="breadcrumb-link">メニュー></a>
        <a href="player-category-select.php" class="breadcrumb-link">選手変更></a>
        <a href="#" class="breadcrumb-link">個人戦></a>
    </div>
    
    <div class="container">
        <h1 class="title">選手登録・個人戦</h1>
        
        <div class="search-container">
            <input type="text" class="search-input" placeholder="選手名またはidを入力してください">
            <button class="search-button">検索</button>
        </div>
        
        <div class="player-info">
            <label class="player-label">選手名</label>
            <input type="text" class="player-input">
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="alert('廃棄します')">放棄</button>
            <button class="action-button" onclick="alert('修正します')">修正</button>
            <button class="action-button" onclick="alert('決定します')">決定</button>
            <button class="action-button" onclick="location.href='player-category-select.php'">戻る</button>
        </div>
    </div>
</body>
</html>