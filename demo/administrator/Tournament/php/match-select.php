<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合選択</title>
    <link rel="stylesheet" href="match-select-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="tournament-detail.php" class="breadcrumb-link">メニュー></a>
        <a href="match-category-select.php" class="breadcrumb-link">試合内容変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">変更したい試合を選択してください</h1>
        
        <div class="match-grid">
            <button class="match-button" onclick="location.href='match-detail.php?match=5'">第五試合</button>
            <button class="match-button" onclick="location.href='match-edit.html?match=3'">第三試合</button>
        </div>
        
        <div class="match-single">
            <button class="match-button" onclick="location.href='match-edit.html?match=1'">第一試合</button>
        </div>
        
        <div class="back-link">
            <a href="match-list.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>