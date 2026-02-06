<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>選手登録・団体戦 - デモ</title>
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
    max-width: 1100px;
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

.match-info {
    background: #f7fafc;
    padding: 10px 14px;
    border-radius: 10px;
    margin-bottom: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
}

.match-info-item {
    font-size: 13px;
    color: #4a5568;
}

.match-info-item strong {
    font-weight: 700;
    color: #2d3748;
}

.note {
    text-align: center;
    font-size: 11px;
    color: #744210;
    background: #fef3c7;
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 12px;
    line-height: 1.4;
    flex-shrink: 0;
}

.teams-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow-y: auto;
    padding-bottom: 5px;
}

.teams-container {
    display: flex;
    gap: 15px;
    margin-bottom: 12px;
}

.team-section {
    flex: 1;
    min-width: 0;
}

.team-header {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 10px;
    padding: 8px;
    text-align: center;
    border-radius: 10px;
}

.team-header.red {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
}

.team-header.white {
    background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
    color: white;
}

.position-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.position-label {
    font-size: 13px;
    font-weight: bold;
    min-width: 45px;
    color: #4a5568;
    flex-shrink: 0;
}

.player-display {
    flex: 1;
    padding: 8px 12px;
    font-size: 13px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    text-align: center;
    background: #f7fafc;
    color: #2d3748;
    font-weight: 600;
}

.buttons {
    display: flex;
    gap: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.btn {
    flex: 1;
    padding: 12px 18px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: inherit;
}

.btn-back {
    background-color: #e2e8f0;
    color: #4a5568;
}

.btn-back:hover {
    background-color: #cbd5e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn:active {
    transform: translateY(0);
}

/* 小さい画面での調整 */
@media (max-height: 750px) {
    body {
        padding: 4px;
    }

    .container {
        max-height: calc(100vh - 8px);
        border-radius: 16px;
    }

    .header {
        padding: 8px 10px;
    }

    .header-badge {
        font-size: 11px;
        padding: 4px 10px;
    }

    .main-content {
        padding: 10px;
    }

    .match-info {
        padding: 6px 10px;
        gap: 8px;
        margin-bottom: 8px;
    }

    .match-info-item {
        font-size: 11px;
    }

    .demo-notice,
    .note {
        font-size: 10px;
        padding: 5px;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .team-header {
        font-size: 13px;
        padding: 6px;
        margin-bottom: 8px;
    }

    .position-row {
        margin-bottom: 6px;
        gap: 6px;
    }

    .position-label {
        font-size: 11px;
        min-width: 38px;
    }

    .player-display {
        padding: 6px 10px;
        font-size: 11px;
    }

    .btn {
        padding: 10px 14px;
        font-size: 13px;
    }

    .buttons {
        padding-top: 8px;
        gap: 10px;
    }

    .teams-container {
        gap: 10px;
        margin-bottom: 8px;
    }
}

/* 非常に小さい画面 */
@media (max-height: 650px) {
    .header {
        padding: 6px 8px;
        gap: 5px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 8px;
    }

    .match-info {
        padding: 5px 8px;
        gap: 6px;
        margin-bottom: 6px;
    }

    .match-info-item {
        font-size: 10px;
    }

    .demo-notice,
    .note {
        font-size: 9px;
        padding: 4px;
        margin-bottom: 6px;
    }

    .team-header {
        font-size: 12px;
        padding: 5px;
        margin-bottom: 6px;
    }

    .position-row {
        margin-bottom: 5px;
        gap: 5px;
    }

    .position-label {
        font-size: 10px;
        min-width: 32px;
    }

    .player-display {
        padding: 5px 8px;
        font-size: 10px;
        border-width: 1px;
    }

    .btn {
        padding: 8px 12px;
        font-size: 12px;
    }

    .buttons {
        padding-top: 6px;
        gap: 8px;
    }

    .teams-container {
        gap: 8px;
        margin-bottom: 6px;
    }
}

/* タブレット縦向き・横向き */
@media (max-width: 900px) {
    .teams-container {
        flex-direction: column;
    }

    .team-section {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
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

    .header {
        padding: 6px 8px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 8px;
    }

    .match-info {
        padding: 5px 8px;
        gap: 6px;
        margin-bottom: 6px;
        font-size: 11px;
    }

    .match-info-item {
        font-size: 10px;
    }

    .demo-notice,
    .note {
        font-size: 9px;
        padding: 4px 6px;
        margin-bottom: 6px;
        line-height: 1.3;
    }

    .teams-container {
        gap: 8px;
        margin-bottom: 6px;
    }

    .team-header {
        font-size: 12px;
        padding: 5px;
        margin-bottom: 6px;
    }

    .position-row {
        margin-bottom: 5px;
        gap: 5px;
    }

    .position-label {
        font-size: 10px;
        min-width: 32px;
    }

    .player-display {
        padding: 5px 8px;
        font-size: 10px;
        border-width: 1px;
    }

    .btn {
        padding: 8px 12px;
        font-size: 13px;
    }

    .buttons {
        padding-top: 6px;
        gap: 8px;
    }

    .team-section {
        max-width: 100%;
    }

    .teams-wrapper {
        padding-bottom: 3px;
    }
}

/* スマートフォン横向き */
@media (max-width: 900px) and (max-height: 500px) {
    body {
        padding: 3px;
    }

    .container {
        max-width: 98%;
        max-height: calc(100vh - 6px);
        border-radius: 10px;
    }

    .header {
        padding: 4px 6px;
        gap: 4px;
    }

    .header-badge {
        font-size: 9px;
        padding: 2px 6px;
    }

    .main-content {
        padding: 6px;
    }

    .match-info {
        padding: 4px 6px;
        gap: 5px;
        margin-bottom: 5px;
    }

    .match-info-item {
        font-size: 9px;
    }

    .demo-notice,
    .note {
        font-size: 8px;
        padding: 3px;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .teams-container {
        flex-direction: row;
        gap: 6px;
        margin-bottom: 5px;
    }

    .team-header {
        font-size: 10px;
        padding: 3px;
        margin-bottom: 4px;
    }

    .position-row {
        margin-bottom: 3px;
        gap: 4px;
    }

    .position-label {
        font-size: 9px;
        min-width: 28px;
    }

    .player-display {
        padding: 3px 6px;
        font-size: 9px;
        border-width: 1px;
    }

    .btn {
        padding: 6px 10px;
        font-size: 11px;
    }

    .buttons {
        padding-top: 5px;
        gap: 6px;
    }

    .team-section {
        max-width: none;
    }
}

/* カスタムスクロールバー */
.main-content::-webkit-scrollbar,
.teams-wrapper::-webkit-scrollbar {
    width: 6px;
}

.main-content::-webkit-scrollbar-track,
.teams-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb,
.teams-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb:hover,
.teams-wrapper::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-badge">選手登録・団体戦</div>
        <div class="header-badge">2024年度 全国選手権大会</div>
        <div class="header-badge">男子団体</div>
    </div>
    
    <div class="main-content">
        <div class="demo-notice">
            ⚠️ これはデモ画面です
        </div>

        <div class="match-info">
            <div class="match-info-item">試合番号: <strong>A-01</strong></div>
            <div class="match-info-item" style="color:#dc2626;">赤: <strong>東京高校</strong></div>
            <div class="match-info-item">白: <strong>神奈川学園</strong></div>
        </div>
        
        <div class="note">
            ※選手名はordersテーブルの登録内容が表示されます<br>
            ※選手変更は必ず本部に届けてから変更してください
        </div>
        
        <form id="orderForm" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
            <div class="teams-wrapper">
                <div class="teams-container">
                    <!-- 赤チーム -->
                    <div class="team-section">
                        <div class="team-header red">赤チーム</div>
                        
                        <div class="position-row">
                            <div class="position-label">先鋒</div>
                            <div class="player-display">山田 太郎</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">次鋒</div>
                            <div class="player-display">佐藤 次郎</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">中堅</div>
                            <div class="player-display">鈴木 三郎</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">副将</div>
                            <div class="player-display">田中 四郎</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">大将</div>
                            <div class="player-display">高橋 五郎</div>
                        </div>
                    </div>
                    
                    <!-- 白チーム -->
                    <div class="team-section">
                        <div class="team-header white">白チーム</div>
                        
                        <div class="position-row">
                            <div class="position-label">先鋒</div>
                            <div class="player-display">伊藤 一朗</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">次鋒</div>
                            <div class="player-display">渡辺 二朗</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">中堅</div>
                            <div class="player-display">中村 三朗</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">副将</div>
                            <div class="player-display">小林 四朗</div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">大将</div>
                            <div class="player-display">加藤 五朗</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="buttons">
                <button type="button" class="btn btn-back" onclick="handleBack()">戻る</button>
                <button type="submit" class="btn btn-submit">決定</button>
            </div>
        </form>
    </div>
</div>

<script>
// フォーム送信処理
document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 先鋒戦の画面へ遷移（デモでは次の画面を想定）
    window.location.href = 'demo-team-match-senpo.php';
});

function handleBack() {
    window.location.href = 'demo-team-forfeit.php';
}
</script>

</body>
</html>