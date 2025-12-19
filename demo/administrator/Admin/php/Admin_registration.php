<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会設定</title>
    <link rel="stylesheet" href="../css/Admin_registration.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="Admin_selection.php" class="breadcrumb-link">大会、部門登録・名称変更></a>
        <a href="#" class="breadcrumb-link">〇〇大会></a>
    </div>
    
    <div class="container">
        <h1 class="title">〇〇大会 設定</h1>
        
<<<<<<< HEAD:demo/administrator_version1.1/master/tournament-setting.php
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
=======
        <div class="button-grid">
            <button class="action-button" onclick="location.href='Admin_registration_namechange.php'">名称変更</button>
            <button class="action-button" onclick="location.href='Admin_department_edit.php'">部門編集</button>
            <button class="action-button" onclick="location.href='Admin_registration_pwchange.php'">パスワード変更</button>
            <button class="lock-button" onclick="location.href='Admin_unlock.php'">
                    <span class="lock-icon">🔒</span>
                    <span>ロック状態にする</span>
            </button>
        </div>
>>>>>>> ff4ed25b0838c43525ec4a674413e1eb094e8ec0:demo/administrator/Admin/php/Admin_registration.php
        
        <div class="back-link">
            <a href="Admin_selection.php" class="back-text">← 戻る</a>
        </div>
    </div>
</body>
</html>