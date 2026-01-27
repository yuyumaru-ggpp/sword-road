<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小学生男子部門</title>
    <link rel="stylesheet" href="../css/match-list-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="tournament-detail.php" class="breadcrumb-link">メニュー></a>
        <a href="match-category-select.php" class="breadcrumb-link">試合内容変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">小学生男子部門</h1>
        
        <div class="search-container">
            <input type="text" class="search-input">
            <button class="search-button">検索</button>
        </div>
        
        <table class="match-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名前</th>
                    <th>出場区分</th>
                </tr>
            </thead>
            <tbody>
                <tr class="match-row" onclick="location.href='match-select.html?id=1'">
                    <td>1</td>
                    <td>〇〇 〇〇</td>
                    <td>個人</td>
                </tr>
                <tr class="match-row" onclick="location.href='match-select.php?id=2'">
                    <td>2</td>
                    <td>〇〇 〇〇</td>
                    <td>個人</td>
                </tr>
                <tr class="match-row" onclick="location.href='match-select2.php?id=3'">
                    <td>3</td>
                    <td>〇〇 〇〇</td>
                    <td>団体</td>
                </tr>
            </tbody>
        </table>
        
        <div class="back-link">
            <a href="match-category-select.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>