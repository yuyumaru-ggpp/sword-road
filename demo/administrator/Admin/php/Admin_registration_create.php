<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会登録</title>
    <link rel="stylesheet" href="../css/Admin_registration_create.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="Admin_selection.php" class="breadcrumb-link">大会、部門登録・名称変更></a>
        <a href="#" class="breadcrumb-link">大会登録></a>
    </div>
    
    <div class="container">
<<<<<<< HEAD:demo/administrator_version1.1/master/tournament-register.php
        <form method="POST" action="tournament-category-select.php" id="tournamentForm">
            <div class="form-section">
                <h2 class="section-title">大会名称を入力してください</h2>
                <input type="text" name="tournament_name" class="form-input" placeholder="例: 第50回全国高等学校剣道大会" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">大会会場を入力してください</h2>
                <input type="text" name="venue_name" class="form-input" placeholder="例: 県立総合体育館" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">大会開催日を入力してください</h2>
                <input type="date" name="tournament_date" class="form-input" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">大会パスワードを設定してください</h2>
                <input type="password" name="tournament_password" class="form-input" placeholder="大会パスワード" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">大会パスワード（確認）</h2>
                <input type="password" name="tournament_password_confirm" class="form-input" placeholder="大会パスワード（確認）" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">補助員記録係のパスワードを設定してください</h2>
                <input type="password" name="assistant_password" class="form-input" placeholder="補助員記録係パスワード" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">補助員記録係のパスワード（確認）</h2>
                <input type="password" name="assistant_password_confirm" class="form-input" placeholder="補助員記録係パスワード（確認）" required>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">試合会場数を選択してください</h2>
                <select name="court_count" class="form-input" required>
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
                <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
                <button type="submit" class="action-button">部門選択へ</button>
            </div>
        </form>
=======
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
            <button class="action-button" onclick="location.href='Admin_registration_selection_create.php'">部門作成へ</button>
        </div>
>>>>>>> ff4ed25b0838c43525ec4a674413e1eb094e8ec0:demo/administrator/Admin/php/Admin_registration_create.php
    </div>

    <script>
        document.getElementById('tournamentForm').addEventListener('submit', function(e) {
            const tournamentPassword = document.querySelector('input[name="tournament_password"]').value;
            const tournamentPasswordConfirm = document.querySelector('input[name="tournament_password_confirm"]').value;
            const assistantPassword = document.querySelector('input[name="assistant_password"]').value;
            const assistantPasswordConfirm = document.querySelector('input[name="assistant_password_confirm"]').value;
            
            if (tournamentPassword !== tournamentPasswordConfirm) {
                e.preventDefault();
                alert('大会パスワードが一致しません。再度入力してください。');
                return false;
            }
            
            if (assistantPassword !== assistantPasswordConfirm) {
                e.preventDefault();
                alert('補助員記録係のパスワードが一致しません。再度入力してください。');
                return false;
            }
        });
    </script>
</body>
</html>