<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>代表決定戦 - デモ</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    position: relative;
}

.demo-notice {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #fef5e7;
    color: #7d6608;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 10px;
    text-align: center;
    border-left: 3px solid #f39c12;
    z-index: 101;
}

.position-header {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    font-size: 22px;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    padding: 10px 36px;
    text-align: center;
    z-index: 100;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 54px 15px 12px;
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
    min-height: 0;
    overflow-y: auto;
}

.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
}

.match-section {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.player-info {
    background: #f7fafc;
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 8px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
    font-size: 13px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    min-width: 75px;
    font-weight: 600;
    color: #4a5568;
}

.info-value {
    font-weight: 700;
    color: #2d3748;
    font-size: 15px;
}

.player-select {
    flex: 1;
    padding: 7px 10px;
    font-size: 13px;
    font-weight: 600;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    cursor: pointer;
    background: white;
    color: #2d3748;
    transition: all 0.2s;
}

.player-select:focus {
    outline: none;
    border-color: #667eea;
    background: #f7fafc;
}

.player-select:hover {
    border-color: #667eea;
}

.score-display {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 12px 0;
}

.score-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.score-numbers {
    display: flex;
    gap: 16px;
    font-size: 13px;
    font-weight: bold;
    color: #4a5568;
}

.score-numbers span {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.radio-circles {
    display: flex;
    gap: 16px;
}

.radio-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.radio-circle.selected {
    background: #ef4444;
    transform: scale(1.15);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
}

.radio-circle:hover {
    opacity: 0.8;
}

.divider-section {
    position: relative;
    margin: 15px 0;
    text-align: center;
}

.divider {
    border: none;
    border-top: 2px dashed #cbd5e0;
}

.middle-controls {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    background: white;
    padding: 8px 12px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dropdown-container {
    position: relative;
    width: 36px;
    height: 36px;
}

.score-dropdown {
    width: 100%;
    height: 100%;
    font-size: 15px;
    font-weight: bold;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.score-dropdown:hover {
    border-color: #667eea;
    background: #f7fafc;
}

.dropdown-menu {
    display: none;
    position: absolute;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    min-width: 70px;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    padding: 6px 0;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 10px 16px;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s;
}

.dropdown-item:hover {
    background: #eef2ff;
    color: #667eea;
}

.dropdown-item:active {
    background: #667eea;
    color: white;
}

.bottom-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.bottom-buttons {
    display: flex;
    justify-content: center;
    gap: 12px;
}

.bottom-button {
    flex: 1;
    max-width: 200px;
    padding: 12px 18px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
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

.submit-button {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
}

.submit-button:active,
.back-button:active {
    transform: translateY(0);
}

/* スクロールバー */
.main-content::-webkit-scrollbar {
    width: 6px;
}

.main-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* 小さい画面での調整 */
@media (max-height: 750px) {
    .position-header {
        font-size: 18px;
        padding: 8px 28px;
    }

    .header {
        padding: 48px 12px 10px;
    }

    .header-badge {
        font-size: 12px;
        padding: 5px 12px;
    }

    .main-content {
        padding: 12px;
    }

    .player-info {
        padding: 8px;
        margin-bottom: 6px;
    }

    .info-row {
        font-size: 12px;
        gap: 6px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 13px;
    }

    .player-select {
        padding: 6px 8px;
        font-size: 12px;
    }

    .score-display {
        padding: 10px 0;
    }

    .score-numbers span,
    .radio-circle {
        width: 32px;
        height: 32px;
    }

    .dropdown-container {
        width: 32px;
        height: 32px;
    }

    .score-dropdown {
        font-size: 13px;
    }

    .divider-section {
        margin: 12px 0;
    }

    .bottom-area {
        gap: 8px;
        padding-top: 10px;
    }

    .bottom-button {
        padding: 10px 16px;
        font-size: 14px;
    }
}

/* 非常に小さい画面 */
@media (max-height: 650px) {
    .position-header {
        font-size: 16px;
        padding: 6px 24px;
    }

    .header {
        padding: 42px 10px 8px;
    }

    .header-badge {
        font-size: 11px;
        padding: 4px 10px;
    }

    .main-content {
        padding: 10px;
    }

    .player-info {
        padding: 6px;
        margin-bottom: 5px;
    }

    .info-row {
        font-size: 11px;
        gap: 5px;
        margin-bottom: 3px;
    }

    .info-label {
        min-width: 65px;
    }

    .info-value {
        font-size: 12px;
    }

    .player-select {
        padding: 5px 8px;
        font-size: 11px;
    }

    .score-display {
        padding: 8px 0;
    }

    .score-numbers span,
    .radio-circle {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .divider-section {
        margin: 10px 0;
    }

    .middle-controls {
        padding: 6px 10px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .dropdown-item {
        padding: 8px 12px;
        font-size: 12px;
    }

    .bottom-area {
        gap: 6px;
        padding-top: 8px;
    }

    .bottom-button {
        padding: 8px 14px;
        font-size: 13px;
        max-width: 180px;
    }
}

/* スマートフォン縦向き */
@media (max-width: 600px) {
    body {
        padding: 4px;
    }

    .container {
        max-height: calc(100vh - 8px);
        border-radius: 12px;
    }

    .position-header {
        font-size: 16px;
        padding: 6px 20px;
        border-radius: 0 0 10px 10px;
    }

    .header {
        padding: 42px 10px 8px;
    }

    .header-badge {
        font-size: 11px;
        padding: 4px 10px;
    }

    .main-content {
        padding: 10px;
    }

    .player-info {
        padding: 6px;
        margin-bottom: 5px;
    }

    .info-row {
        font-size: 11px;
        gap: 5px;
        margin-bottom: 3px;
    }

    .info-label {
        min-width: 60px;
    }

    .info-value {
        font-size: 12px;
    }

    .player-select {
        padding: 5px 8px;
        font-size: 11px;
    }

    .score-display {
        padding: 8px 0;
    }

    .score-numbers {
        font-size: 11px;
    }

    .score-numbers span,
    .radio-circle {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .divider-section {
        margin: 10px 0;
    }

    .middle-controls {
        padding: 6px 10px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .dropdown-item {
        padding: 7px 12px;
        font-size: 12px;
    }

    .bottom-area {
        gap: 6px;
        padding-top: 8px;
    }

    .bottom-button {
        padding: 8px 14px;
        font-size: 13px;
        max-width: 160px;
    }

    .bottom-buttons {
        gap: 10px;
    }
}

/* スマートフォン横向き */
@media (max-width: 900px) and (max-height: 500px) {
    body {
        padding: 3px;
    }

    .container {
        max-height: calc(100vh - 6px);
        border-radius: 10px;
    }

    .position-header {
        font-size: 14px;
        padding: 5px 18px;
    }

    .header {
        padding: 38px 8px 6px;
        gap: 5px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 8px;
    }

    .player-info {
        padding: 5px;
        margin-bottom: 4px;
    }

    .info-row {
        font-size: 10px;
        gap: 4px;
        margin-bottom: 2px;
    }

    .info-label {
        min-width: 55px;
    }

    .info-value {
        font-size: 11px;
    }

    .player-select {
        padding: 4px 6px;
        font-size: 10px;
    }

    .score-display {
        padding: 6px 0;
    }

    .score-numbers {
        font-size: 10px;
    }

    .score-numbers span,
    .radio-circle {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }

    .divider-section {
        margin: 8px 0;
    }

    .middle-controls {
        padding: 5px 8px;
    }

    .dropdown-container {
        width: 24px;
        height: 24px;
    }

    .score-dropdown {
        font-size: 11px;
        border-width: 1px;
    }

    .dropdown-item {
        padding: 6px 10px;
        font-size: 11px;
    }

    .bottom-area {
        gap: 5px;
        padding-top: 6px;
    }

    .bottom-button {
        padding: 7px 12px;
        font-size: 12px;
        max-width: 140px;
    }

    .bottom-buttons {
        gap: 8px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="demo-notice">⚠️ デモ</div>
    <div class="position-header">代表決定戦</div>
    
    <div class="header">
        <div class="header-badge">団体戦</div>
        <div class="header-badge">2024年度 全国選手権大会</div>
        <div class="header-badge">男子団体</div>
    </div>

    <div class="main-content">
        <div class="content-wrapper">
            <div class="match-section upper-section">
                <div class="player-info" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value">東京高校</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">選手選択</div>
                        <select class="player-select" id="redPlayerSelect">
                            <option value="">選手を選択</option>
                            <option value="1">山田 太郎 (先鋒)</option>
                            <option value="2">佐藤 次郎 (次鋒)</option>
                            <option value="3">鈴木 三郎 (中堅)</option>
                            <option value="4">田中 四郎 (副将)</option>
                            <option value="5">高橋 五郎 (大将)</option>
                        </select>
                    </div>
                </div>
                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers">
                            <span>1</span>
                        </div>
                        <div class="radio-circles red-circles">
                            <div class="radio-circle red-circle"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider-section">
                <hr class="divider">
                <div class="middle-controls">
                    <div class="dropdown-container">
                        <div class="score-dropdown">▼</div>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="▼">▼</div>
                            <div class="dropdown-item" data-val="メ">メ</div>
                            <div class="dropdown-item" data-val="コ">コ</div>
                            <div class="dropdown-item" data-val="ド">ド</div>
                            <div class="dropdown-item" data-val="ツ">ツ</div>
                            <div class="dropdown-item" data-val="×">×</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="match-section lower-section">
                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles white-circles">
                            <div class="radio-circle white-circle"></div>
                        </div>
                        <div class="score-numbers">
                            <span>1</span>
                        </div>
                    </div>
                </div>
                <div class="player-info" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
                    <div class="info-row">
                        <div class="info-label">選手選択</div>
                        <select class="player-select" id="whitePlayerSelect">
                            <option value="">選手を選択</option>
                            <option value="1">伊藤 一朗 (先鋒)</option>
                            <option value="2">渡辺 二朗 (次鋒)</option>
                            <option value="3">中村 三朗 (中堅)</option>
                            <option value="4">小林 四朗 (副将)</option>
                            <option value="5">加藤 五朗 (大将)</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value">神奈川学園</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-area">
            <div class="bottom-buttons">
                <button type="button" class="bottom-button back-button" onclick="handleBack()">戻る</button>
                <button type="button" class="bottom-button submit-button" id="submitButton">送信（確認へ）</button>
            </div>
        </div>
    </div>
</div>

<script>
const data = {
    red: { player_id: null, selected: false },
    white: { player_id: null, selected: false }
};

const redSelect = document.getElementById('redPlayerSelect');
const whiteSelect = document.getElementById('whitePlayerSelect');
const redCircle = document.querySelector('.red-circle');
const whiteCircle = document.querySelector('.white-circle');

redSelect.addEventListener('change', (e) => {
    data.red.player_id = e.target.value;
});

whiteSelect.addEventListener('change', (e) => {
    data.white.player_id = e.target.value;
});

redCircle.addEventListener('click', () => {
    if (redCircle.classList.contains('selected')) {
        redCircle.classList.remove('selected');
        data.red.selected = false;
    } else {
        redCircle.classList.add('selected');
        whiteCircle.classList.remove('selected');
        data.red.selected = true;
        data.white.selected = false;
    }
});

whiteCircle.addEventListener('click', () => {
    if (whiteCircle.classList.contains('selected')) {
        whiteCircle.classList.remove('selected');
        data.white.selected = false;
    } else {
        whiteCircle.classList.add('selected');
        redCircle.classList.remove('selected');
        data.white.selected = true;
        data.red.selected = false;
    }
});

const dropdown = document.querySelector('.dropdown-container');
const btn = dropdown.querySelector('.score-dropdown');
const menu = dropdown.querySelector('.dropdown-menu');

btn.addEventListener('click', e => {
    e.stopPropagation();
    menu.classList.toggle('show');
});

menu.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', () => {
        btn.textContent = item.dataset.val || item.textContent;
        menu.classList.remove('show');
    });
});

document.addEventListener('click', () => menu.classList.remove('show'));

document.getElementById('submitButton').onclick = () => {
    if (!data.red.player_id || !data.white.player_id) {
        alert('両チームの選手を選択してください');
        return;
    }
    if (!data.red.selected && !data.white.selected) {
        alert('勝者を選択してください');
        return;
    }
    
    showConfirmation();
};

function showConfirmation() {
    const message = `
        <div style="background: #fff; border-radius: 16px; padding: 30px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; margin: 20px auto;">
            <div style="font-size: 48px; margin-bottom: 20px;">✓</div>
            <h3 style="font-size: 20px; margin-bottom: 15px; color: #2d3748;">送信が完了しました</h3>
            <p style="font-size: 16px; color: #4a5568; margin-bottom: 25px; line-height: 1.6;">
                練習を終えますか？
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="closeConfirmation()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: #e2e8f0; color: #4a5568; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    戻る
                </button>
                <button onclick="finishPractice()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    終了
                </button>
            </div>
        </div>
    `;
    
    const overlay = document.createElement('div');
    overlay.id = 'confirmOverlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; backdrop-filter: blur(4px);';
    overlay.innerHTML = message;
    document.body.appendChild(overlay);
}

function closeConfirmation() {
    const overlay = document.getElementById('confirmOverlay');
    if (overlay) {
        overlay.remove();
    }
}

function finishPractice() {
    // index.phpに戻る
    window.location.href = '../../index.php';
}

function handleBack() {
    window.location.href = 'demo-team-match-senpo.php';
}
</script>
</body>
</html>