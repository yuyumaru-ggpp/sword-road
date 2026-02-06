<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>団体戦 先鋒 - デモ</title>
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
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
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

.score-dropdowns {
    display: flex;
    gap: 16px;
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

.dropdown-menu,
.draw-dropdown-menu {
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

.dropdown-menu.show,
.draw-dropdown-menu.show {
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

.draw-container-wrapper {
    position: absolute;
    top: 50%;
    right: -90px;
    transform: translateY(-50%);
    background: white;
    padding: 10px 0;
}

.draw-container {
    position: relative;
}

.draw-button {
    padding: 7px 14px;
    font-size: 13px;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}

.draw-button:hover {
    border-color: #667eea;
    background: #f7fafc;
}

.draw-dropdown-menu {
    right: auto;
    left: 50%;
    transform: translateX(-50%);
    top: calc(100% + 4px);
}

.bottom-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.bottom-right-button {
    display: flex;
    justify-content: flex-end;
}

.cancel-button {
    padding: 7px 18px;
    font-size: 12px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    color: #4a5568;
    transition: all 0.2s;
}

.cancel-button:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
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

.next-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.next-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.next-button:active,
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

    .score-display {
        padding: 10px 0;
    }

    .score-numbers,
    .radio-circles {
        gap: 14px;
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

    .draw-container-wrapper {
        right: -80px;
    }

    .draw-button {
        padding: 6px 12px;
        font-size: 12px;
    }

    .bottom-area {
        gap: 8px;
        padding-top: 10px;
    }

    .cancel-button {
        padding: 6px 16px;
        font-size: 11px;
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

    .score-display {
        padding: 8px 0;
    }

    .score-numbers,
    .radio-circles {
        gap: 12px;
    }

    .score-numbers span,
    .radio-circle {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .divider-section {
        margin: 10px 0;
    }

    .middle-controls {
        padding: 6px 10px;
    }

    .score-dropdowns {
        gap: 12px;
    }

    .draw-container-wrapper {
        right: -70px;
    }

    .draw-button {
        padding: 5px 10px;
        font-size: 11px;
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

    .cancel-button {
        padding: 5px 14px;
        font-size: 10px;
        border-width: 1px;
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

    .score-display {
        padding: 8px 0;
    }

    .score-numbers {
        font-size: 11px;
        gap: 12px;
    }

    .radio-circles {
        gap: 12px;
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

    .score-dropdowns {
        gap: 12px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .draw-container-wrapper {
        right: -70px;
    }

    .draw-button {
        padding: 5px 10px;
        font-size: 11px;
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

    .cancel-button {
        padding: 5px 12px;
        font-size: 10px;
        border-width: 1px;
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

    .score-display {
        padding: 6px 0;
    }

    .score-numbers {
        font-size: 10px;
        gap: 10px;
    }

    .radio-circles {
        gap: 10px;
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

    .score-dropdowns {
        gap: 10px;
    }

    .dropdown-container {
        width: 24px;
        height: 24px;
    }

    .score-dropdown {
        font-size: 11px;
        border-width: 1px;
    }

    .draw-container-wrapper {
        right: -60px;
    }

    .draw-button {
        padding: 4px 8px;
        font-size: 10px;
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

    .cancel-button {
        padding: 4px 10px;
        font-size: 9px;
        border-width: 1px;
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
    <div class="position-header">先鋒</div>
    
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
                        <div class="info-label">名前</div>
                        <div class="info-value">山田 太郎</div>
                    </div>
                </div>
                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                        <div class="radio-circles red-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider-section">
                <hr class="divider">
                <div class="middle-controls">
                    <div class="score-dropdowns">
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="×">×</div>
                            </div>
                        </div>
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="×">×</div>
                            </div>
                        </div>
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="×">×</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="draw-container-wrapper">
                    <div class="draw-container">
                        <button type="button" class="draw-button" id="drawButton">-</button>
                        <div class="draw-dropdown-menu" id="drawMenu">
                            <div class="dropdown-item">二本勝</div>
                            <div class="dropdown-item">一本勝</div>
                            <div class="dropdown-item">延長戦</div>
                            <div class="dropdown-item">判定</div>
                            <div class="dropdown-item">引き分け</div>
                            <div class="dropdown-item">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="match-section lower-section">
                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles white-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                        <div class="score-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                    </div>
                </div>
                <div class="player-info" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
                    <div class="info-row">
                        <div class="info-label">名前</div>
                        <div class="info-value">伊藤 一朗</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value">神奈川学園</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-area">
            <div class="bottom-right-button">
                <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
            </div>

            <div class="bottom-buttons">
                <button type="button" class="bottom-button back-button" onclick="handleBack()">戻る</button>
                <button type="button" class="bottom-button next-button" id="nextButton">次鋒へ</button>
            </div>
        </div>
    </div>
</div>

<script>
const data = {
    red: { selected: [] },
    white: { selected: [] },
    scores: ['▼','▼','▼'],
    special: 'none'
};

function saveLocal() {
    data.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown')).map(b=>b.textContent);
    data.red.selected = Array.from(document.querySelectorAll('.red-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));
    data.white.selected = Array.from(document.querySelectorAll('.white-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));
    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='二本勝'?'nihon':dt==='一本勝'?'ippon':dt==='延長戦'?'extend':dt==='判定'?'hantei':dt==='引き分け'?'draw':'none';
}

for (let i = 0; i < 3; i++) {
    const red = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
    const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);
    red.addEventListener('click', () => {
        if (red.classList.contains('selected')) red.classList.remove('selected');
        else { red.classList.add('selected'); white.classList.remove('selected'); }
    });
    white.addEventListener('click', () => {
        if (white.classList.contains('selected')) white.classList.remove('selected');
        else { white.classList.add('selected'); red.classList.remove('selected'); }
    });
}

document.querySelectorAll('.dropdown-container').forEach(container=>{
    const btn=container.querySelector('.score-dropdown');
    const menu=container.querySelector('.dropdown-menu');
    btn.addEventListener('click',e=>{
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m=>m.classList.remove('show'));
        menu.classList.toggle('show');
    });
    menu.querySelectorAll('.dropdown-item').forEach(item=>{
        item.addEventListener('click',()=>{
            btn.textContent=item.dataset.val||item.textContent;
            menu.classList.remove('show');
        });
    });
});

document.getElementById('drawButton').addEventListener('click',e=>{
    e.stopPropagation();
    document.querySelectorAll('.dropdown-menu').forEach(m=>m.classList.remove('show'));
    document.getElementById('drawMenu').classList.toggle('show');
});
document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item=>{
    item.addEventListener('click',()=>{
        document.getElementById('drawButton').textContent=item.textContent;
        document.getElementById('drawMenu').classList.remove('show');
    });
});

document.addEventListener('click',()=>document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m=>m.classList.remove('show')));

document.getElementById('cancelButton').addEventListener('click',()=>{
    if(confirm('入力内容をリセットしますか?')){
        data.red = { selected: [] };
        data.white = { selected: [] };
        data.scores = ['▼','▼','▼'];
        data.special = 'none';
        document.querySelectorAll('.radio-circle').forEach(c=>c.classList.remove('selected'));
        document.querySelectorAll('.score-dropdown').forEach(b=>b.textContent='▼');
        document.getElementById('drawButton').textContent = '-';
    }
});

document.getElementById('nextButton').onclick = () => {
    saveLocal();
    showConfirmation();
};

function showConfirmation() {
    const message = `
        <div style="background: #fff; border-radius: 16px; padding: 30px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 500px; margin: 20px auto;">
            <div style="font-size: 48px; margin-bottom: 20px;">✓</div>
            <h3 style="font-size: 20px; margin-bottom: 15px; color: #2d3748;">先鋒戦を保存しました</h3>
            <p style="font-size: 16px; color: #4a5568; margin-bottom: 25px; line-height: 1.6;">
                次鋒から大将まで同じように入力します。<br>
                次は代表決定戦の画面に移動します。
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="closeConfirmation()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: #e2e8f0; color: #4a5568; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    キャンセル
                </button>
                <button onclick="continueToNext()" style="flex: 1; max-width: 180px; padding: 14px 20px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;">
                    次へ進む
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

function continueToNext() {
    // 代表決定戦の画面へ遷移
    window.location.href = 'demo-team-representative.php';
}

function handleBack() {
    window.location.href = 'demo-team-order-registration.php';
}
</script>
</body>
</html>