<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会のロック解除</title>
    <link rel="stylesheet" href="../css/Admin_unlock.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-unlock.php" class="breadcrumb-link">大会ロック解除></a>
    </div>
    
    <div class="container">
        <h1 class="title">大会のロック解除</h1>
        
        <div class="search-container">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" placeholder="IDまたは大会名">
            </div>
            <button class="search-button">検索</button>
        </div>
        
        <div class="tournament-list-container">
            <div class="tournament-list">
                <div class="tournament-row">
                    <span class="tournament-id">ID 19</span>
                    <span class="tournament-name">〇〇大会</span>
                    <span class="lock-status">ロック中</span>
                    <button class="lock-icon">🔒</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID 18</span>
                    <span class="tournament-name">〇〇大会</span>
                    <span class="lock-status">ロック中</span>
                    <button class="lock-icon">🔒</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID 17</span>
                    <span class="tournament-name">〇〇大会</span>
                    <span class="lock-status">ロック中</span>
                    <button class="lock-icon">🔒</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID 16</span>
                    <span class="tournament-name">〇〇大会</span>
                    <span class="lock-status">ロック中</span>
                    <button class="lock-icon">🔒</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID 15</span>
                    <span class="tournament-name">〇〇大会</span>
                    <span class="lock-status">ロック中</span>
                    <button class="lock-icon">🔒</button>
                </div>
            </div>
        </div>
        
        <div class="back-button-container">
            <button class="back-button" onclick="location.href='master2.php'">戻る</button>
        </div>
    </div>
</body>
</html>