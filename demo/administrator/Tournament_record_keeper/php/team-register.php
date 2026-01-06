<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手変更・団体戦</title>
    <link rel="stylesheet" href="../css/team-register-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">選手変更・団体戦</h1>
            <h2 class="team-name">チーム名</h2>
        </div>
        
        <p class="note">※棄権する場合は空欄にしてください</p>
        
        <div class="form-container">
            <div class="form-row">
                <label class="position-label">先鋒</label>
                <input type="text" class="player-input">
                
                <label class="position-label second">次鋒</label>
                <input type="text" class="player-input">
            </div>
            
            <div class="form-row">
                <label class="position-label">中堅</label>
                <input type="text" class="player-input">
                
                <label class="position-label second">副将</label>
                <input type="text" class="player-input">
            </div>
            
            <div class="form-row single">
                <label class="position-label">大将</label>
                <input type="text" class="player-input">
            </div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="alert('決定しました')">決定</button>
            <button class="action-button" onclick="location.href='team-list.php'">戻る</button>
        </div>
    </div>
</body>
</html>