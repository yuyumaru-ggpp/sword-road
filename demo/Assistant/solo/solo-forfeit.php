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

/* 不戦勝結果画面のスタイル */
.result-container {
    display:none;
}

.result-container.active {
    display:block;
}

.result-header {
    text-align:center;
    font-size:clamp(1.8rem, 4vw, 3rem);
    font-weight:bold;
    color:#ef4444;
    margin-bottom:3rem;
}

.winner-display {
    text-align:center;
    padding:3rem;
    background:#f0fdf4;
    border:3px solid #22c55e;
    border-radius:12px;
    margin-bottom:3rem;
}

.winner-label {
    font-size:clamp(1.2rem, 2.5vw, 1.8rem);
    color:#666;
    margin-bottom:1rem;
}

.winner-number {
    font-size:clamp(3rem, 6vw, 5rem);
    font-weight:bold;
    color:#22c55e;
}

.loser-display {
    text-align:center;
    padding:2rem;
    background:#fef2f2;
    border:3px solid #ef4444;
    border-radius:12px;
    margin-bottom:3rem;
}

.loser-label {
    font-size:clamp(1rem, 2vw, 1.5rem);
    color:#666;
    margin-bottom:0.5rem;
}

.loser-number {
    font-size:clamp(2rem, 4vw, 3rem);
    font-weight:bold;
    color:#ef4444;
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
<!-- 選手選択画面 -->
<div class="container" id="selectionScreen">
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

<!-- 不戦勝結果画面 -->
<div class="container result-container" id="forfeitScreen">
    <div class="header">
        <span>個人戦</span>
        <span>〇〇大会</span>
        <span>〇〇部門</span>
    </div>

    <div class="result-header">不戦勝</div>

    <div class="winner-display">
        <div class="winner-label">勝者（不戦勝）</div>
        <div class="winner-number" id="winnerNumber">-</div>
    </div>

    <div class="loser-display">
        <div class="loser-label">敗者（不戦敗）</div>
        <div class="loser-number" id="loserNumber">-</div>
    </div>

    <div class="action-buttons">
        <button class="action-button confirm-button" id="saveButton">結果を保存</button>
        <button class="action-button back-button" id="backToSelection">選手選択に戻る</button>
    </div>
</div>

<script>
const upperBtn = document.getElementById('upperForfeit');
const lowerBtn = document.getElementById('lowerForfeit');
const selectionScreen = document.getElementById('selectionScreen');
const forfeitScreen = document.getElementById('forfeitScreen');

// 不戦勝ボタンの切り替え
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

// 決定ボタン
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
    
    // 不戦勝が選択されている場合
    if (upperSelected || lowerSelected) {
        const winner = upperSelected ? upperPlayer : lowerPlayer;
        const loser = upperSelected ? lowerPlayer : upperPlayer;
        
        document.getElementById('winnerNumber').textContent = winner;
        document.getElementById('loserNumber').textContent = loser;
        
        // 画面切り替え
        selectionScreen.style.display = 'none';
        forfeitScreen.classList.add('active');
    } else {
        // 通常の試合（この部分は実際のページ遷移に置き換え可能）
        alert('通常の試合画面に遷移します\n\n上段: ' + upperPlayer + '\n下段: ' + lowerPlayer);
        // window.location.href = 'individual-match-detail.php';
    }
});

// 選手選択に戻るボタン
document.getElementById('backToSelection').addEventListener('click', () => {
    forfeitScreen.classList.remove('active');
    selectionScreen.style.display = 'block';
    
    // 不戦勝ボタンの選択状態をリセット
    upperBtn.classList.remove('selected');
    lowerBtn.classList.remove('selected');
});

// 結果を保存ボタン
document.getElementById('saveButton').addEventListener('click', () => {
    const winner = document.getElementById('winnerNumber').textContent;
    const loser = document.getElementById('loserNumber').textContent;
    
    // ここで実際のデータ保存処理を実行
    alert('不戦勝の結果を保存しました\n\n勝者: ' + winner + '\n敗者: ' + loser);
    
    // 実際の実装では以下のような処理になります
    // const resultData = {
    //     winner: winner,
    //     loser: loser,
    //     matchType: 'forfeit'
    // };
    // window.location.href = 'save-result.php?data=' + JSON.stringify(resultData);
});
</script>
</body>
</html>