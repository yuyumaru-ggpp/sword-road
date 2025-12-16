<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>名称変更</title>
    <link rel="stylesheet" href="../css/Admin_registration_namechange.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="Admin_selection.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="Admin_registration.php" class="breadcrumb-link">〇〇大会></a>
        <a href="#" class="breadcrumb-link">名称変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">名称変更</h1>
        
        <div class="input-container">
            <input type="text" class="name-input" placeholder="">
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="alert('名称を変更しました'); location.href='Admin_selection.php'">決定</button>
            <button class="action-button" onclick="history.back()">キャンセル</button>
        </div>
    </div>
</body>
</html>