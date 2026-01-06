<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>個人戦選手選択</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans','Meiryo',sans-serif; 
    background:#f5f5f5; 
    padding:1rem; 
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.container { 
    max-width:1200px;
    width:100%;
    background:white; 
    padding:2rem; 
    border-radius:8px; 
    box-shadow:0 10px 30px rgba(0,0,0,0.1); 
}

.header { 
    display:flex; 
    flex-wrap:wrap;
    align-items:center; 
    gap:1rem; 
    margin-bottom:3rem;
    font-size:clamp(1.2rem, 3vw, 2rem); 
    font-weight:bold; 
}

.notice {
    text-align:center;
    font-size:clamp(1rem, 2vw, 1.3rem);
    color:#666;
    margin-bottom:3rem;
}

.match-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:2rem;
    margin-bottom:3rem;
}

.player-section {
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:1.5rem;
}

.player-label {
    font-size:clamp(1.5rem, 3vw, 2.5rem);
    font-weight:bold;
}

.player-input {
    width:100%;
    max-width:300px;
    padding:1rem;
    font-size:clamp(1.2rem, 2.5vw, 1.8rem);
    text-align:center;
    border:3px solid #ddd;
    border-radius:8px;
    transition:border-color 0.2s;
}

.player-input:focus {
    outline:none;
    border-color:#3b82f6;
}

.forfeit-button {
    padding:1rem 3rem;
    font-size:clamp(1.2rem, 2.5vw, 1.8rem);
    font-weight:bold;
    background:white;
    border:3px solid #000;
    border-radius:50px;
    cursor:pointer;
    transition:all 0.2s;
    white-space:nowrap;
}

.forfeit-button:hover {
    background:#f5f5f5;
}

.forfeit-button.selected {
    background:#ef4444;
    color:white;
    border-color:#ef4444;
}

.vs-text {
    font-size:clamp(2rem, 4vw, 3rem);
    font-weight:bold;
}

.action-buttons {
    display:flex;
    justify-content:center;
    gap:2rem;
    margin-top:2rem;
}

.action-button {
    padding:1rem 3rem;
    font-size:clamp(1.2rem, 2.5vw, 1.5rem);
    font-weight:bold;
    border-radius:50px;
    cursor:pointer;
    transition:all 0.2s;
    white-space:nowrap;
}

.confirm-button {
    background:#3b82f6;
    color:white;
    border:3px solid #3b82f6;
}

.confirm-button:hover {
    background:#2563eb;
    border-color:#2563eb;
}

.back-button {
    background:white;
    border:3px solid #000;
}

.back-button:hover {
    background:#f5f5f5;
}

@media (max-width:768px) {
    .match-row {
        flex-direction:column;
        gap:2rem;
    }
    
    .vs-text {
        order:0;
    }
    
    .player-section {
        width:100%;
    }
    
    .action-buttons {
        flex-direction:column;
        width:100%;
    }
    
    .action-button {
        width:100%;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <span>個人戦</span>
        <span>〇〇大会</span>
        <span>〇〇部門</span>
    </div>

    <div class="notice">
        ※不戦勝ボタンは勝った方の選手を押してください。
    </div>

    <div class="match-row">
        <div class="player-section">
            <div class="player-label">選手番号</div>
            <input type="text" class="player-input" id="upperPlayer" placeholder="番号を入力">
            <button class="forfeit-button" id="upperForfeit">不戦勝</button>
        </div>

        <div class="vs-text">対</div>

        <div class="player-section">
            <div class="player-label">選手番号</div>
            <input type="text" class="player-input" id="lowerPlayer" placeholder="番号を入力">
            <button class="forfeit-button" id="lowerForfeit">不戦勝</button>
        </div>
    </div>

    <div class="action-buttons">
        <button class="action-button confirm-button" id="confirmButton">決定</button>
        <button class="action-button back-button" onclick="history.back()">戻る</button>
    </div>
</div>

<script>
const upperBtn = document.getElementById('upperForfeit');
const lowerBtn = document.getElementById('lowerForfeit');

upperBtn.addEventListener('click', () => {
    if (upperBtn.classList.contains('selected')) {
        upperBtn.classList.remove('selected');
    } else {
        upperBtn.classList.add('selected');
        lowerBtn.classList.remove('selected');
    }
});

lowerBtn.addEventListener('click', () => {
    if (lowerBtn.classList.contains('selected')) {
        lowerBtn.classList.remove('selected');
    } else {
        lowerBtn.classList.add('selected');
        upperBtn.classList.remove('selected');
    }
});

document.getElementById('confirmButton').addEventListener('click', () => {
    const upperSelected = upperBtn.classList.contains('selected');
    const lowerSelected = lowerBtn.classList.contains('selected');
    const upperPlayer = document.getElementById('upperPlayer').value.trim();
    const lowerPlayer = document.getElementById('lowerPlayer').value.trim();
    
    // バリデーション
    if (!upperPlayer || !lowerPlayer) {
        alert('両方の選手番号を入力してください');
        return;
    }
    
    // 不戦勝情報と選手番号を保存
    const matchData = {
        upperPlayer: upperPlayer,
        lowerPlayer: lowerPlayer,
        forfeit: {
            upper: upperSelected,
            lower: lowerSelected
        }
    };
    
    // セッションストレージにデータを保存
    sessionStorage.setItem('matchData', JSON.stringify(matchData));
    
    // 試合詳細画面に遷移
    window.location.href = 'individual-match-detail.php';
});
</script>
</body>
</html>