<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面 - 大会名</title>
    <link rel="stylesheet" href="../css/edit/edit-menu-style.css">
</head>
<body>
    <div class="menu-link">
        <a href="#" class="menu-text">メニュー></a>
    </div>
    
    <div class="container">
        <h2 class="subtitle">管理者画面</h2>
        <h1 class="title">大会名</h1>
        
        <div class="button-grid">
            <button class="menu-button" onclick="location.href='player-category-select.php'">
                <span class="button-text">選手の変更</span>
            </button>
            
            <button class="menu-button" onclick="location.href='match-edit.php'">
                <span class="icon">✎</span>
                <span class="button-text">試合内容の変更</span>
            </button>
        </div>
        
        <div class="button-single">
            <button class="menu-button-wide" onclick="location.href='csv-import.php'">
                <span class="icon">⤓</span>
                <span class="button-text">CSVデータの読込</span>
            </button>
        </div>
        
        <div class="back-link">
            <a href="index.html" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>