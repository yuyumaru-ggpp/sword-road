<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>団体戦チーム選択 - デモ</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

body {
    font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.container {
    width: 100%;
    max-width: 1000px;
    height: 100%;
    max-height: calc(100vh - 16px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.header-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.3);
    white-space: nowrap;
}

.main-content {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 0;
    overflow-y: auto;
}

.demo-notice {
    background-color: #fef5e7;
    color: #7d6608;
    padding: 6px 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 11px;
    text-align: center;
    border-left: 4px solid #f39c12;
    flex-shrink: 0;
}

.notice {
    text-align: center;
    font-size: 12px;
    color: #718096;
    margin-bottom: 12px;
    padding: 6px 10px;
    background: #f7fafc;
    border-radius: 8px;
    flex-shrink: 0;
}

.error {
    background-color: #fed7d7;
    color: #c53030;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 12px;
    text-align: center;
    border-left: 4px solid #c53030;
    animation: shake 0.4s;
}

.success {
    background-color: #c6f6d5;
    color: #2f855a;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 12px;
    text-align: center;
    border-left: 4px solid #2f855a;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.match-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 0;
}

.match-row {
    display: flex;
    gap: 15px;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.team-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.team-label {
    font-size: 20px;
    font-weight: bold;
    color: #2d3748;
    padding: 5px 16px;
    border-radius: 8px;
}

.team-label.red {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
}

.team-label.white {
    background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
    color: white;
}

.input-label-small {
    font-size: 10px;
    color: #718096;
    font-weight: 600;
}

.team-number-input {
    width: 100%;
    max-width: 160px;
    padding: 8px 12px;
    font-size: 14px;
    text-align: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    outline: none;
    background-color: #f7fafc;
    transition: all 0.3s ease;
    font-family: inherit;
}

.team-number-input:focus {
    border-color: #667eea;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.team-select {
    width: 100%;
    max-width: 240px;
    padding: 8px 12px;
    font-size: 12px;
    text-align: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    appearance: none;
    background-color: #f7fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='%234a5568'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.team-select:focus {
    outline: none;
    border-color: #667eea;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.forfeit-button {
    padding: 7px 18px;
    font-size: 12px;
    font-weight: 600;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
}

.forfeit-button:hover {
    border-color: #cbd5e0;
    background: #f7fafc;
}

.forfeit-button.selected {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.vs-text {
    font-size: 24px;
    font-weight: bold;
    color: #4a5568;
    flex-shrink: 0;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 12px;
    flex-shrink: 0;
}

.action-button {
    flex: 1;
    padding: 11px 16px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
}

.confirm-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.confirm-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.back-button {
    background-color: #e2e8f0;
    color: #4a5568;
}

.back-button:hover {
    background-color: #cbd5e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-button:active {
    transform: translateY(0);
}

/* 小さい画面での調整 */
@media (max-height: 700px) {
    .main-content {
        padding: 12px;
    }

    .demo-notice,
    .notice {
        font-size: 11px;
        padding: 5px 8px;
        margin-bottom: 8px;
    }

    .team-label {
        font-size: 18px;
        padding: 4px 14px;
    }

    .vs-text {
        font-size: 20px;
    }

    .team-number-input {
        padding: 7px 10px;
        font-size: 13px;
        max-width: 140px;
    }

    .team-select {
        padding: 7px 10px;
        font-size: 11px;
        max-width: 200px;
    }

    .forfeit-button {
        padding: 6px 16px;
        font-size: 11px;
    }

    .action-button {
        padding: 10px 14px;
        font-size: 13px;
    }

    .match-row {
        gap: 12px;
        margin-bottom: 10px;
    }

    .team-section {
        gap: 5px;
    }

    .action-buttons {
        margin-top: 10px;
    }
}

/* 非常に小さい画面 */
@media (max-height: 600px) {
    .header {
        padding: 6px 8px;
        gap: 5px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 10px;
    }

    .demo-notice,
    .notice {
        font-size: 10px;
        padding: 4px 6px;
        margin-bottom: 6px;
    }

    .team-label {
        font-size: 14px;
        padding: 3px 10px;
    }

    .vs-text {
        font-size: 16px;
    }

    .input-label-small {
        font-size: 9px;
    }

    .team-number-input {
        padding: 5px 8px;
        font-size: 12px;
        max-width: 120px;
    }

    .team-select {
        padding: 5px 8px;
        font-size: 10px;
        max-width: 170px;
    }

    .forfeit-button {
        padding: 5px 12px;
        font-size: 10px;
    }

    .action-button {
        padding: 8px 12px;
        font-size: 12px;
    }

    .match-row {
        gap: 8px;
        margin-bottom: 8px;
    }

    .team-section {
        gap: 4px;
    }

    .action-buttons {
        margin-top: 8px;
        gap: 8px;
    }
}

/* タブレット縦向き・横向き */
@media (max-width: 900px) {
    .match-row {
        flex-direction: column;
        gap: 15px;
    }

    .vs-text {
        transform: rotate(90deg);
    }

    .team-section {
        width: 100%;
        max-width: 400px;
    }

    .team-select,
    .team-number-input {
        max-width: 100%;
    }
}

/* スマートフォン横向き */
@media (max-width: 900px) and (max-height: 500px) {
    body {
        padding: 4px;
    }

    .container {
        max-width: 98%;
        max-height: calc(100vh - 8px);
        border-radius: 12px;
    }

    .header {
        padding: 5px 8px;
        gap: 4px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 8px 10px;
    }

    .demo-notice,
    .notice {
        font-size: 9px;
        padding: 3px 6px;
        margin-bottom: 5px;
    }

    .match-row {
        flex-direction: row;
        gap: 6px;
        margin-bottom: 6px;
    }

    .team-section {
        gap: 3px;
    }

    .team-label {
        font-size: 12px;
        padding: 3px 8px;
    }

    .vs-text {
        font-size: 14px;
        transform: none;
    }

    .input-label-small {
        font-size: 8px;
    }

    .team-number-input {
        padding: 4px 6px;
        font-size: 11px;
        max-width: 100px;
    }

    .team-select {
        padding: 4px 6px;
        font-size: 9px;
        max-width: 150px;
        padding-right: 24px;
    }

    .forfeit-button {
        padding: 4px 10px;
        font-size: 10px;
    }

    .action-button {
        padding: 7px 10px;
        font-size: 11px;
    }

    .action-buttons {
        margin-top: 6px;
        gap: 6px;
    }
}

/* 小さいスマートフォン */
@media (max-width: 400px) {
    .team-label {
        font-size: 18px;
    }

    .team-select,
    .team-number-input {
        font-size: 13px;
    }
}
</style>
</head>

<body>

<div class="container">
    <div class="header">
        <div class="header-badge">団体戦</div>
        <div class="header-badge">2024年度 全国選手権大会</div>
        <div class="header-badge">男子団体</div>
    </div>

    <div class="main-content">
        <div class="demo-notice">
            ⚠️ これはデモ画面です
        </div>

        <div class="notice">
            ※ 不戦勝の場合は勝者側の「不戦勝」ボタンを押してください
        </div>

        <div id="messageArea"></div>

        <form id="teamForm">
            <input type="hidden" name="forfeit" id="forfeitInput">

            <div class="match-container">
                <div class="match-row">
                    <div class="team-section">
                        <div class="team-label red">赤</div>
                        <div class="input-label-small">チーム番号</div>
                        <input type="text" class="team-number-input" id="redTeamNumber" placeholder="番号入力">
                        <div class="input-label-small">またはチームを選択</div>
                        <select name="red_team" class="team-select" id="redTeam" required>
                            <option value="">選択してください</option>
                            <option value="1" data-number="101">東京高校 (101)</option>
                            <option value="2" data-number="102">神奈川学園 (102)</option>
                            <option value="3" data-number="103">埼玉中央高校 (103)</option>
                            <option value="4" data-number="104">千葉第一高校 (104)</option>
                            <option value="5" data-number="105">大阪南高校 (105)</option>
                            <option value="6" data-number="106">京都学院 (106)</option>
                            <option value="7" data-number="107">愛知高校 (107)</option>
                            <option value="8" data-number="108">福岡中央高校 (108)</option>
                        </select>
                        <button type="button" class="forfeit-button" id="redForfeit">不戦勝</button>
                    </div>

                    <div class="vs-text">対</div>

                    <div class="team-section">
                        <div class="team-label white">白</div>
                        <div class="input-label-small">チーム番号</div>
                        <input type="text" class="team-number-input" id="whiteTeamNumber" placeholder="番号入力">
                        <div class="input-label-small">またはチームを選択</div>
                        <select name="white_team" class="team-select" id="whiteTeam" required>
                            <option value="">選択してください</option>
                            <option value="1" data-number="101">東京高校 (101)</option>
                            <option value="2" data-number="102">神奈川学園 (102)</option>
                            <option value="3" data-number="103">埼玉中央高校 (103)</option>
                            <option value="4" data-number="104">千葉第一高校 (104)</option>
                            <option value="5" data-number="105">大阪南高校 (105)</option>
                            <option value="6" data-number="106">京都学院 (106)</option>
                            <option value="7" data-number="107">愛知高校 (107)</option>
                            <option value="8" data-number="108">福岡中央高校 (108)</option>
                        </select>
                        <button type="button" class="forfeit-button" id="whiteForfeit">不戦勝</button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="action-button back-button" onclick="handleBack()">戻る</button>
                    <button type="submit" class="action-button confirm-button" id="confirmButton">決定</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const redBtn = document.getElementById('redForfeit');
const whiteBtn = document.getElementById('whiteForfeit');
const forfeitInput = document.getElementById('forfeitInput');

// 不戦勝ボタンの制御
redBtn.onclick = () => {
    if (redBtn.classList.contains('selected')) {
        redBtn.classList.remove('selected');
    } else {
        redBtn.classList.add('selected');
        whiteBtn.classList.remove('selected');
    }
};

whiteBtn.onclick = () => {
    if (whiteBtn.classList.contains('selected')) {
        whiteBtn.classList.remove('selected');
    } else {
        whiteBtn.classList.add('selected');
        redBtn.classList.remove('selected');
    }
};

// フォーム送信処理
document.getElementById('teamForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const redTeam = document.getElementById('redTeam').value;
    const whiteTeam = document.getElementById('whiteTeam').value;
    const redTeamText = document.getElementById('redTeam').options[document.getElementById('redTeam').selectedIndex].text;
    const whiteTeamText = document.getElementById('whiteTeam').options[document.getElementById('whiteTeam').selectedIndex].text;
    const messageArea = document.getElementById('messageArea');
    
    // バリデーション
    if (redTeam === '' || whiteTeam === '') {
        showMessage('チームを選択してください', 'error');
        return;
    }
    
    if (redTeam === whiteTeam) {
        showMessage('同じチームは選択できません', 'error');
        return;
    }
    
    // 不戦勝の処理
    if (redBtn.classList.contains('selected')) {
        forfeitInput.value = 'red';
        showForfeitConfirmation('red', redTeamText, whiteTeamText);
        return;
    } else if (whiteBtn.classList.contains('selected')) {
        forfeitInput.value = 'white';
        showForfeitConfirmation('white', whiteTeamText, redTeamText);
        return;
    } else {
        forfeitInput.value = '';
        // 通常試合：オーダー登録画面へ遷移
        window.location.href = 'demo-team-order-registration.php';
    }
});

function showForfeitConfirmation(winner, winnerTeam, loserTeam) {
    const color = winner === 'red' ? '赤' : '白';
    const message = `
        <div style="background: #fff; border-radius: 16px; padding: 30px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; margin: 20px auto;">
            <div style="font-size: 48px; margin-bottom: 20px;">✓</div>
            <h3 style="font-size: 20px; margin-bottom: 15px; color: #2d3748;">不戦勝を登録しました</h3>
            <div style="background: #f7fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <div style="font-size: 14px; color: #718096; margin-bottom: 8px;">勝者：${color}</div>
                <div style="font-size: 16px; font-weight: 600; color: #2d3748; margin-bottom: 12px;">${winnerTeam}</div>
                <div style="font-size: 14px; color: #718096; margin-bottom: 8px;">敗者</div>
                <div style="font-size: 16px; color: #718096;">${loserTeam}</div>
            </div>
            <p style="font-size: 16px; color: #4a5568; margin-bottom: 25px; line-height: 1.6;">
                不戦勝の操作はこれで終わります。<br>続けますか？最初に戻りますか？
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="backToStart()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: #e2e8f0; color: #4a5568; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    最初に戻る
                </button>
                <button onclick="continueToNext()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    続ける
                </button>
            </div>
        </div>
    `;
    
    // 画面全体をオーバーレイで覆う
    const overlay = document.createElement('div');
    overlay.id = 'confirmOverlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; backdrop-filter: blur(4px);';
    overlay.innerHTML = message;
    document.body.appendChild(overlay);
}

function backToStart() {
    window.location.href = 'match_input_demo.php';
}

function continueToNext() {
    // オーバーレイを削除
    const overlay = document.getElementById('confirmOverlay');
    if (overlay) {
        overlay.remove();
    }
    
    // フォームをリセット
    document.getElementById('teamForm').reset();
    document.getElementById('redTeamNumber').value = '';
    document.getElementById('whiteTeamNumber').value = '';
    redBtn.classList.remove('selected');
    whiteBtn.classList.remove('selected');
    
    // 成功メッセージを表示
    showMessage('✓ 不戦勝を登録しました。次の試合を入力できます', 'success');
    
    // フォーカスを戻す
    document.getElementById('redTeamNumber').focus();
}

function showMessage(message, type) {
    const messageArea = document.getElementById('messageArea');
    const className = type === 'error' ? 'error' : 'success';
    messageArea.innerHTML = `<div class="${className}">${message}</div>`;
    
    if (type === 'error') {
        if ('vibrate' in navigator) {
            navigator.vibrate(200);
        }
    }
    
    // 成功メッセージは3秒後に自動で消す
    if (type === 'success') {
        setTimeout(() => {
            messageArea.innerHTML = '';
        }, 3000);
    }
}

function handleBack() {
    window.location.href = 'match_input.php';
}

// チーム番号入力時の自動選択機能（赤チーム）
document.getElementById('redTeamNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('redTeam');
    
    if (number === '') {
        return;
    }
    
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// チーム番号入力時の自動選択機能（白チーム）
document.getElementById('whiteTeamNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('whiteTeam');
    
    if (number === '') {
        return;
    }
    
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// プルダウン選択時にチーム番号欄に反映（赤チーム）
document.getElementById('redTeam').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const numberInput = document.getElementById('redTeamNumber');
    
    if (selectedOption.dataset.number) {
        numberInput.value = selectedOption.dataset.number;
    } else {
        numberInput.value = '';
    }
});

// プルダウン選択時にチーム番号欄に反映（白チーム）
document.getElementById('whiteTeam').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const numberInput = document.getElementById('whiteTeamNumber');
    
    if (selectedOption.dataset.number) {
        numberInput.value = selectedOption.dataset.number;
    } else {
        numberInput.value = '';
    }
});

// 初期フォーカス
window.addEventListener('load', function() {
    document.getElementById('redTeamNumber').focus();
});
</script>

</body>
</html>