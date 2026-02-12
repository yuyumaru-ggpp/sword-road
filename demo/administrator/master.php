<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者用URL</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .button-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 30px 0;
        }
        
        .url-link {
            display: inline-block;
            padding: 15px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .url-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="url-panel">
            <h1 class="title">管理者画面</h1>
            
            <div class="button-container">
                <a href="Admin/login.php" class="url-link">大会運営者</a>
                <a href="Tournament_record_keeper/login.php" class="url-link">大会の記録編集</a>
            </div>
            
            <div class="back-button-container">
                <button class="back-button" onclick="location.href='../index.php'">大会一覧に戻る</button>
            </div>
        </div>
    </div>
</body>
</html>