<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会登録</title>
    <link rel="stylesheet" href="tournament-register-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="#" class="breadcrumb-link">大会登録></a>
    </div>
    
    <div class="container">
        <div class="form-section">
            <h2 class="section-title">登録したい大会名を入力してください</h2>
            <input type="text" class="form-input" placeholder="大会名">
        </div>
        
        <div class="form-section">
            <h2 class="section-title">パスワードを入力してください</h2>
            <input type="password" class="form-input" placeholder="パスワード">
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">キャンセル</button>
            <button class="action-button" onclick="location.href='tournament-category-select.php'">部門選択へ</button>
        </div>
    </div>
</body>
</html>