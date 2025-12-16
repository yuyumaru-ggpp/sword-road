<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>部門作成</title>
    <link rel="stylesheet" href="../css/Admin_registration_selection_create.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="tournament-register.php" class="breadcrumb-link">大会登録></a>
        <a href="tournament-category-select.php" class="breadcrumb-link">部門選択></a>
        <a href="#" class="breadcrumb-link">部門作成></a>
    </div>
    
    <div class="container">
        <h1 class="title">部門作成</h1>
        
        <div class="input-container">
            <input type="text" class="category-input" placeholder="部門名を入力">
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">戻る</button>
            <button class="action-button" onclick="location.href='tournament-register-confirm.php'">作成</button>
        </div>
    </div>
</body>
</html>