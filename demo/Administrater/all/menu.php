<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面メニュー</title>
    <link rel="stylesheet" href="../css/all/admin-menu-style.css">
</head>
<body>
    <div class="menu-link">
        <a href="#" class="menu-text">メニュー></a>
    </div>
    
    <div class="container">
        <h1 class="title">管理者画面メニュー</h1>
        
        <div class="button-grid">
            <button class="menu-button" onclick="location.href='tournament-registration.html'">
                <span class="icon">□</span>
                <span class="button-text">大会登録・名称変更</span>
            </button>
            
            <button class="menu-button" onclick="location.href='unlock-tournament.html'">
                <span class="icon">🔓</span>
                <span class="button-text">大会ロック解除</span>
            </button>
        </div>
        
        <div class="button-single">
            <button class="menu-button-wide" onclick="location.href='tournament-create.html'">
                <span class="icon">⊞</span>
                <span class="button-text">トーナメント作成</span>
            </button>
        </div>
        
        <div class="back-link">
            <a href="index.html" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>