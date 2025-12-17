<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会名の登録・名称変更画面</title>
    <link rel="stylesheet" href="tournament-list-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-registration.php" class="breadcrumb-link">大会登録・名称変更></a>
    </div>
    
    <div class="container">
        <h1 class="title">大会名の登録・名称変更画面</h1>
        
        <div class="tournament-list-container">
            <div class="tournament-list">
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=1'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(1, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=2'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(2, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=3'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(3, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=4'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(4, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=5'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(5, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=6'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(6, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=7'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(7, '〇〇大会')">削除</button>
                </div>
                
                <div class="tournament-row">
                    <span class="tournament-id">ID</span>
                    <span class="tournament-name" onclick="location.href='tournament-setting.php?id=8'">〇〇大会</span>
                    <button class="delete-button" onclick="confirmDelete(8, '〇〇大会')">削除</button>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="register-button" onclick="location.href='tournament-register.php'">大会登録</button>
            <button class="back-button" onclick="location.href='master2.php'">戻る</button>
        </div>
    </div>

    <script>
        function confirmDelete(id, name) {
            if (confirm(`「${name}」を削除してもよろしいですか?`)) {
                window.location.href = `tournament-delete.php?id=${id}`;
            }
        }
    </script>
</body>
</html>