<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手変更 - 部門選択</title>
    <link rel="stylesheet" href="../css/edit/player-category-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="menu.php" class="breadcrumb-link">メニュー></a>
        <a href="#" class="breadcrumb-link">選手変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">変更したい選手の部門を選択してください</h1>
        
        <h2 class="tournament-name">令和7年　盛岡大会</h2>
        
        <div class="category-grid">
            <button class="category-button" onclick="location.href='player-list.html?category=elementary-boys-individual'">
                小学生男子　個人戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=junior-boys-individual'">
                中学生男子　個人戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=elementary-girls-individual'">
                小学生女子　個人戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=junior-girls-individual'">
                中学生女子　個人戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=elementary-boys-team'">
                小学生男子　団体戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=junior-boys-team'">
                中学生男子　団体戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=elementary-girls-team'">
                小学生女子　団体戦
            </button>
            
            <button class="category-button" onclick="location.href='player-list.html?category=junior-girls-team'">
                中学生女子　団体戦
            </button>
        </div>
        
        <div class="back-link">
            <a href="tournament-detail.html" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>