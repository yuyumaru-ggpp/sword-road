<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦不戦勝</title>
    <link rel="stylesheet" href="team-forfeit-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">団体戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
            <span class="header-text">5人制用</span>
        </div>
        
        <p class="note">※不戦勝ボタンは勝った方のチームを押してください。</p>
        
        <div class="team-section">
            <div class="team-row">
                <input type="text" class="team-input" placeholder="チームID" id="team1">
                <span class="vs-text">対</span>
                <input type="text" class="team-input" placeholder="チームID" id="team2">
            </div>
            
            <div class="forfeit-buttons">
                <button class="forfeit-button" id="leftButton">不戦勝</button>
                <button class="forfeit-button" id="rightButton">不戦勝</button>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="action-button" id="confirmButton">決定</button>
            <button class="action-button" onclick="history.back()">戻る</button>
        </div>
    </div>
    
    <script>
        let selectedWinner = null;
        
        const leftButton = document.getElementById('leftButton');
        const rightButton = document.getElementById('rightButton');
        const confirmButton = document.getElementById('confirmButton');
        
        leftButton.addEventListener('click', function() {
            if (selectedWinner === 'left') {
                // 既に選択されている場合は取り消し
                selectedWinner = null;
                leftButton.style.backgroundColor = 'white';
                leftButton.style.color = 'black';
                leftButton.style.borderColor = '#000';
            } else {
                // 新規選択
                selectedWinner = 'left';
                leftButton.style.backgroundColor = '#3b82f6';
                leftButton.style.color = 'white';
                leftButton.style.borderColor = '#3b82f6';
                
                rightButton.style.backgroundColor = 'white';
                rightButton.style.color = 'black';
                rightButton.style.borderColor = '#000';
            }
        });
        
        rightButton.addEventListener('click', function() {
            if (selectedWinner === 'right') {
                // 既に選択されている場合は取り消し
                selectedWinner = null;
                rightButton.style.backgroundColor = 'white';
                rightButton.style.color = 'black';
                rightButton.style.borderColor = '#000';
            } else {
                // 新規選択
                selectedWinner = 'right';
                rightButton.style.backgroundColor = '#3b82f6';
                rightButton.style.color = 'white';
                rightButton.style.borderColor = '#3b82f6';
                
                leftButton.style.backgroundColor = 'white';
                leftButton.style.color = 'black';
                leftButton.style.borderColor = '#000';
            }
        });
        
        confirmButton.addEventListener('click', function() {
            const team1 = document.getElementById('team1').value;
            const team2 = document.getElementById('team2').value;
            
            if (!team1 || !team2) {
                alert('両方のチームIDを入力してください');
                return;
            }
            
            // 不戦勝の選択は任意（選択なしでも次に進める）
            // 次の画面に遷移
            location.href = 'team-match-detail.php';
        });
    </script>
</body>
</html>