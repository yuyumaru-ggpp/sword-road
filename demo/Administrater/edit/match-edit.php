<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合内容変更 - 部門選択</title>
    <link rel="stylesheet" href="../css/edit/match-category-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="menu.php" class="breadcrumb-link">メニュー></a>
        <a href="#" class="breadcrumb-link">試合内容変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">部門を選択してください</h1>
        
        <div class="category-grid">
            <button class="category-button" onclick="location.href='match-list.html?category=elementary-boys'">
                小学生男子部門
            </button>
            
            <button class="category-button" onclick="location.href='match-list.html?category=junior-boys'">
                中学生男子部門
            </button>
            
            <button class="category-button" onclick="location.href='match-list.html?category=elementary-girls'">
                小学生女子部門
            </button>
            
            <button class="category-button" onclick="location.href='match-list.html?category=junior-girls'">
                中学生女子部門
            </button>
        </div>
        
        <div class="back-link">
            <a href="tournament-detail.html" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>