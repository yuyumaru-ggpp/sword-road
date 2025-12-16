<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者用URL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="url-panel">
            <h1 class="title">管理者画面</h1>
            
            <div class="url-section">
                <h2 class="section-title">全体</h2>
                <a href="Admin/login.php" class="url-link">https://www.example.com/all.html</a>
            </div>
            
            <div class="url-section">
                <h2 class="section-title">大会の記録編集</h2>
                <a href="Tournament/login.php" class="url-link">https://www.example.com/edit.html</a>
            </div>
            
            <div class="back-button-container">
                <button class="back-button" onclick="location.href='../index.php'">大会一覧に戻る</button>
            </div>
        </div>
    </div>
</body>
</html>