<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会設定</title>
    <link rel="stylesheet" href="tournament-setting-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="#" class="breadcrumb-link">〇〇大会></a>
    </div>
    
    <div class="container">
        <h1 class="title">〇〇大会 設定</h1>
        
        <form class="tournament-form" method="POST" action="tournament-update.php">
            <div class="form-group">
                <label for="tournament-name">大会名称</label>
                <input type="text" id="tournament-name" name="tournament_name" class="form-input" value="〇〇大会" required>
            </div>
            
            <div class="form-group">
                <label for="venue">大会会場</label>
                <input type="text" id="venue" name="venue" class="form-input" placeholder="例：県立体育館" required>
            </div>
            
            <div class="form-group">
                <label for="event-date">大会開催日</label>
                <input type="date" id="event-date" name="event_date" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="tournament-password">大会パスワード</label>
                <input type="password" id="tournament-password" name="tournament_password" class="form-input" placeholder="パスワードを入力" required>
            </div>
            
            <div class="form-group">
                <label for="tournament-password-confirm">大会パスワード（再入力）</label>
                <input type="password" id="tournament-password-confirm" name="tournament_password_confirm" class="form-input" placeholder="パスワードを再入力" required>
            </div>
            
            <div class="form-group">
                <label for="assistant-password">補助員記録係のパスワード</label>
                <input type="password" id="assistant-password" name="assistant_password" class="form-input" placeholder="パスワードを入力" required>
            </div>
            
            <div class="form-group">
                <label for="assistant-password-confirm">補助員記録係のパスワード（再入力）</label>
                <input type="password" id="assistant-password-confirm" name="assistant_password_confirm" class="form-input" placeholder="パスワードを再入力" required>
            </div>
            
            <div class="form-group">
                <label for="court-count">試合会場数</label>
                <select id="court-count" name="court_count" class="form-input" required>
                    <option value="">選択してください</option>
                    <option value="4">4試合場</option>
                    <option value="5">5試合場</option>
                    <option value="6">6試合場</option>
                    <option value="7">7試合場</option>
                    <option value="8">8試合場</option>
                    <option value="9">9試合場</option>
                    <option value="10">10試合場</option>
                </select>
            </div>
            
            <div class="button-container">
                <button type="submit" class="save-button">保存</button>
            </div>
        </form>
        
        <div class="back-link">
            <a href="tournament-list.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>