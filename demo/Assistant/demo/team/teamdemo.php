<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>団体戦システム - チュートリアル</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans','Meiryo',sans-serif; 
    background:#f5f5f5; 
    padding:0;
    min-height:100vh;
}

.tutorial-container {
    max-width:1400px;
    margin:0 auto;
    background:white;
    border-radius:0;
    box-shadow:none;
    overflow:hidden;
    min-height:100vh;
}

.tutorial-header {
    background:linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color:white;
    padding:1.5rem 1rem;
    text-align:center;
}

.tutorial-title {
    font-size:1.3rem;
    font-weight:bold;
    margin-bottom:0.3rem;
    line-height:1.3;
}

.tutorial-subtitle {
    font-size:0.85rem;
    opacity:0.9;
    line-height:1.4;
}

.tutorial-content {
    padding:1rem;
}

.section {
    margin-bottom:2rem;
}

.section-header {
    display:flex;
    align-items:center;
    gap:0.8rem;
    margin-bottom:1rem;
    padding-bottom:0.8rem;
    border-bottom:3px solid #fee2e2;
}

.section-icon {
    font-size:1.8rem;
    flex-shrink:0;
}

.section-title {
    font-size:1.3rem;
    font-weight:bold;
    color:#dc2626;
    line-height:1.2;
}

.steps-grid {
    display:flex;
    flex-direction:column;
    gap:1rem;
    margin-top:1rem;
}

.step-card {
    background:#f9fafb;
    border:2px solid #e5e7eb;
    border-radius:12px;
    padding:1rem;
    transition:all 0.3s;
}

.step-card:active {
    border-color:#dc2626;
    background:#fef2f2;
}

.step-number {
    display:inline-block;
    background:#dc2626;
    color:white;
    width:32px;
    height:32px;
    border-radius:50%;
    text-align:center;
    line-height:32px;
    font-weight:bold;
    font-size:1rem;
    margin-bottom:0.8rem;
}

.step-title {
    font-size:1.05rem;
    font-weight:bold;
    margin-bottom:0.6rem;
    color:#333;
    line-height:1.3;
}

.step-description {
    font-size:0.9rem;
    line-height:1.6;
    color:#666;
    margin-bottom:0.8rem;
}

.step-visual {
    background:white;
    border:2px solid #e5e7eb;
    border-radius:8px;
    padding:0.8rem;
    min-height:100px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.demo-ui {
    width:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:0.8rem;
}

.demo-team-section {
    display:flex;
    gap:1rem;
    align-items:center;
    flex-wrap:wrap;
    justify-content:center;
}

.demo-team-box {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:0.5rem;
}

.demo-input {
    width:80px;
    padding:0.3rem;
    border:2px solid #d1d5db;
    border-radius:4px;
    text-align:center;
    font-size:0.8rem;
}

.demo-button {
    padding:0.3rem 0.8rem;
    border:2px solid #000;
    border-radius:18px;
    font-weight:bold;
    background:white;
    font-size:0.75rem;
    white-space:nowrap;
}

.demo-button.primary {
    background:#3b82f6;
    color:white;
    border-color:#3b82f6;
}

.demo-vs {
    font-size:1rem;
    font-weight:bold;
}

.demo-score-row {
    display:flex;
    gap:0.6rem;
    align-items:center;
}

.demo-dropdown {
    width:30px;
    height:30px;
    border:2px solid #000;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    font-size:0.9rem;
    background:white;
}

.demo-circle {
    width:30px;
    height:30px;
    border-radius:50%;
    background:#d1d5db;
}

.demo-circle.selected {
    background:#ef4444;
    box-shadow:0 0 0 2px rgba(239,68,68,0.3);
}

.demo-player-list {
    background:white;
    border:2px solid #d1d5db;
    border-radius:8px;
    padding:0.6rem;
    width:100%;
    max-width:220px;
}

.demo-player-item {
    display:flex;
    gap:0.3rem;
    align-items:center;
    padding:0.3rem;
    background:#f9fafb;
    border-radius:4px;
    margin-bottom:0.3rem;
    font-size:0.7rem;
}

.demo-position {
    font-weight:bold;
    min-width:35px;
    font-size:0.65rem;
}

.demo-select {
    flex:1;
    padding:0.2rem;
    border:1px solid #d1d5db;
    border-radius:3px;
    font-size:0.65rem;
}

.important-note {
    background:#fef3c7;
    border-left:4px solid #f59e0b;
    padding:0.8rem;
    margin-top:1.5rem;
    border-radius:4px;
}

.important-note-title {
    font-weight:bold;
    color:#92400e;
    margin-bottom:0.4rem;
    display:flex;
    align-items:center;
    gap:0.4rem;
    font-size:0.9rem;
}

.important-note-text {
    color:#78350f;
    font-size:0.8rem;
    line-height:1.6;
}

.action-buttons {
    display:flex;
    flex-direction:column;
    gap:0.8rem;
    padding:1.5rem 1rem;
    background:#f9fafb;
}

.action-btn {
    padding:0.9rem 2rem;
    font-size:1rem;
    font-weight:bold;
    border-radius:30px;
    cursor:pointer;
    transition:all 0.3s;
    border:none;
    width:100%;
}

.btn-start {
    background:#dc2626;
    color:white;
}

.btn-start:active {
    background:#b91c1c;
    transform:scale(0.98);
}

.btn-skip {
    background:white;
    color:#333;
    border:2px solid #d1d5db;
}

.btn-skip:active {
    border-color:#9ca3af;
    transform:scale(0.98);
}

.btn-back {
    background:white;
    color:#333;
    border:2px solid #000;
}

.btn-back:active {
    border-color:#666;
    transform:scale(0.98);
}

@media (min-width:768px) {
    body {
        padding:1rem;
    }
    
    .tutorial-container {
        border-radius:16px;
        box-shadow:0 10px 40px rgba(0,0,0,0.1);
    }
    
    .tutorial-header {
        padding:2rem;
    }
    
    .tutorial-title {
        font-size:2rem;
        margin-bottom:0.5rem;
    }
    
    .tutorial-subtitle {
        font-size:1.1rem;
    }
    
    .tutorial-content {
        padding:2rem;
    }
    
    .section {
        margin-bottom:3rem;
    }
    
    .section-header {
        gap:1rem;
        margin-bottom:1.5rem;
        padding-bottom:1rem;
    }
    
    .section-icon {
        font-size:2.5rem;
    }
    
    .section-title {
        font-size:1.8rem;
    }
    
    .steps-grid {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));
        gap:1.5rem;
        margin-top:1.5rem;
    }
    
    .step-card {
        padding:1.5rem;
    }
    
    .step-card:hover {
        border-color:#dc2626;
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(220,38,38,0.15);
    }
    
    .step-number {
        width:40px;
        height:40px;
        line-height:40px;
        font-size:1.2rem;
        margin-bottom:1rem;
    }
    
    .step-title {
        font-size:1.2rem;
        margin-bottom:0.8rem;
    }
    
    .step-description {
        font-size:0.95rem;
        margin-bottom:1rem;
    }
    
    .step-visual {
        padding:1rem;
        min-height:120px;
    }
    
    .demo-input {
        width:100px;
        padding:0.4rem;
        font-size:0.9rem;
    }
    
    .demo-button {
        padding:0.4rem 1rem;
        font-size:0.85rem;
    }
    
    .demo-vs {
        font-size:1.2rem;
    }
    
    .demo-score-row {
        gap:0.8rem;
    }
    
    .demo-dropdown {
        width:35px;
        height:35px;
        font-size:1rem;
    }
    
    .demo-circle {
        width:35px;
        height:35px;
    }
    
    .demo-player-list {
        max-width:250px;
        padding:0.8rem;
    }
    
    .demo-player-item {
        gap:0.4rem;
        padding:0.4rem;
        margin-bottom:0.4rem;
        font-size:0.75rem;
    }
    
    .demo-position {
        min-width:40px;
        font-size:0.7rem;
    }
    
    .demo-select {
        font-size:0.7rem;
    }
    
    .important-note {
        padding:1rem;
    }
    
    .important-note-title {
        font-size:1rem;
        gap:0.5rem;
        margin-bottom:0.5rem;
    }
    
    .important-note-text {
        font-size:0.95rem;
    }
    
    .action-buttons {
        flex-direction:row;
        gap:1rem;
        padding:2rem;
    }
    
    .action-btn {
        padding:1rem 3rem;
        font-size:1.1rem;
        width:auto;
    }
    
    .btn-start:hover {
        background:#b91c1c;
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(220,38,38,0.3);
    }
    
    .btn-skip:hover {
        border-color:#9ca3af;
        transform:translateY(-2px);
    }
    
    .btn-back:hover {
        background:#f3f4f6;
        transform:translateY(-2px);
    }
}
</style>
</head>
<body>
<div class="tutorial-container">
    <div class="tutorial-header">
        <div class="tutorial-title">🥋 団体戦システム 使い方ガイド</div>
        <div class="tutorial-subtitle">不戦勝入力と試合詳細入力の完全ガイド</div>
    </div>

    <div class="tutorial-content">
        <!-- 不戦勝入力セクション -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">🚫</div>
                <div class="section-title">不戦勝入力</div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">チームIDを入力</div>
                    <div class="step-description">
                        対戦する両チームのチームIDを入力します。入力後、「選手変更」ボタンで各ポジションの選手を確認・変更できます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-team-section">
                                <div class="demo-team-box">
                                    <input class="demo-input" placeholder="チームID" value="A001">
                                    <button class="demo-button">選手変更</button>
                                </div>
                                <div class="demo-vs">対</div>
                                <div class="demo-team-box">
                                    <input class="demo-input" placeholder="チームID" value="B002">
                                    <button class="demo-button">選手変更</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">選手の配置を変更</div>
                    <div class="step-description">
                        「選手変更」ボタンをクリックすると、先鋒・次鋒・中堅・副将・大将の各ポジションに選手を割り当てられます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-list">
                                <div class="demo-player-item">
                                    <span class="demo-position">先鋒</span>
                                    <select class="demo-select">
                                        <option>選手1</option>
                                    </select>
                                </div>
                                <div class="demo-player-item">
                                    <span class="demo-position">次鋒</span>
                                    <select class="demo-select">
                                        <option>選手2</option>
                                    </select>
                                </div>
                                <div class="demo-player-item">
                                    <span class="demo-position">中堅</span>
                                    <select class="demo-select">
                                        <option>選手3</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">不戦勝を記録</div>
                    <div class="step-description">
                        <strong style="color:#dc2626;">重要：</strong> 勝利したチームの「不戦勝」ボタンを押します。ボタンは青色に変わり、もう一度押すと選択解除できます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-team-section">
                                <div class="demo-team-box">
                                    <div style="font-weight:bold; margin-bottom:0.3rem; font-size:0.85rem;">チームA</div>
                                    <button class="demo-button primary">不戦勝</button>
                                </div>
                                <div class="demo-vs">対</div>
                                <div class="demo-team-box">
                                    <div style="font-weight:bold; margin-bottom:0.3rem; font-size:0.85rem;">チームB</div>
                                    <button class="demo-button">不戦勝</button>
                                </div>
                            </div>
                            <div style="margin-top:0.5rem; font-size:0.75rem; color:#666;">
                                ← 勝った方を選択
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">決定して次へ</div>
                    <div class="step-description">
                        全ての情報を入力したら「決定」ボタンを押します。間違えた場合は「戻る」ボタンで前の画面に戻れます。
                    </div>
                    <div class="step-visual">
                        <div style="text-align:center; padding:1rem;">
                            <div style="display:flex; gap:0.8rem; justify-content:center; margin-bottom:1rem;">
                                <button class="demo-button primary">決定</button>
                                <button class="demo-button">戻る</button>
                            </div>
                            <div style="font-size:1.5rem; color:#10b981;">✓</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 試合詳細入力セクション -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div class="section-title">試合詳細入力</div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">ポイントを選択</div>
                    <div class="step-description">
                        中央のドロップダウンから各本のポイントを選択します。<br>
                        <strong>選択肢：</strong>×、メ、コ、ド、反、ツ、〇
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-score-row">
                                <div class="demo-dropdown">▼</div>
                                <div class="demo-dropdown">メ</div>
                                <div class="demo-dropdown">〇</div>
                            </div>
                            <div style="font-size:0.75rem; color:#666; margin-top:0.3rem;">
                                ↑ クリックして選択
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">取った本数を記録</div>
                    <div class="step-description">
                        各選手が取った本数を、丸いボタンで選択します。赤（上段）と白（下段）それぞれ記録できます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div style="font-weight:bold; color:#ef4444; margin-bottom:0.3rem; font-size:0.8rem;">■ 赤</div>
                            <div class="demo-score-row">
                                <div class="demo-circle"></div>
                                <div class="demo-circle selected"></div>
                                <div class="demo-circle"></div>
                            </div>
                            <div style="height:0.5rem;"></div>
                            <div style="font-weight:bold; color:#666; margin-bottom:0.3rem; font-size:0.8rem;">■ 白</div>
                            <div class="demo-score-row">
                                <div class="demo-circle"></div>
                                <div class="demo-circle"></div>
                                <div class="demo-circle selected"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">特殊な試合結果</div>
                    <div class="step-description">
                        引分け、一本勝、延長、不戦勝などは中央右側のボタンから選択できます。通常の試合は「-」のままでOK。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-button">-</div>
                            <div style="font-size:0.7rem; color:#999; margin-top:0.5rem; text-align:center;">
                                引分け / 一本勝 / 延長<br>赤不戦勝 / 白不戦勝
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">ポジション移動</div>
                    <div class="step-description">
                        右上の「次へ」「戻る」ボタンで各ポジション（先鋒→次鋒→中堅→副将→大将→代表決定戦）を移動できます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div style="display:flex; gap:0.5rem;">
                                <div class="demo-button" style="background:#ef4444; color:white; border-color:#ef4444;">次へ</div>
                                <div class="demo-button" style="background:#ef4444; color:white; border-color:#ef4444;">戻る</div>
                            </div>
                            <div style="font-size:0.75rem; color:#666; margin-top:0.8rem; text-align:center;">
                                先鋒 → 次鋒 → 中堅<br>→ 副将 → 大将
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">5</div>
                    <div class="step-title">取り消しと送信</div>
                    <div class="step-description">
                        入力を間違えた場合は「取り消し」ボタンでリセット。全て入力したら「送信」ボタンで保存します。
                    </div>
                    <div class="step-visual">
                        <div style="text-align:center; padding:1rem;">
                            <button class="demo-button" style="margin-bottom:0.8rem;">取り消し</button>
                            <div style="display:flex; gap:0.8rem; justify-content:center;">
                                <button class="demo-button">キャンセル</button>
                                <button class="demo-button primary">送信</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">6</div>
                    <div class="step-title">代表決定戦</div>
                    <div class="step-description">
                        大将まで入力完了後、右上に「代表決定戦」ボタンが表示されます。必要に応じて記録できます。
                    </div>
                    <div class="step-visual">
                        <div style="text-align:center; padding:1.5rem;">
                            <div style="background:#fee2e2; color:#dc2626; padding:0.5rem 1.5rem; border-radius:8px; font-weight:bold; display:inline-block; margin-bottom:0.8rem;">
                                代表決定戦
                            </div>
                            <div style="font-size:0.75rem; color:#666;">
                                ↑ 1本勝負で記録
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="important-note">
            <div class="important-note-title">
                <span>⚠️</span>
                <span>重要な注意事項</span>
            </div>
            <div class="important-note-text">
                <strong>不戦勝の記録：</strong> 必ず勝利したチームのボタンを押してください。<br>
                <strong>データの保存：</strong> 各ポジションの入力後は「次へ」で自動保存されます。<br>
                <strong>代表決定戦：</strong> 通常の3本勝負と異なり、1本勝負として記録されます。
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <button class="action-btn btn-start" onclick="startSystem()">システムを始める</button>
        <button class="action-btn btn-back" onclick="goBack()">戻る</button>
    </div>
</div>

<script>
function startSystem() {
    // 団体戦不戦勝入力画面に遷移
    window.location.href = 'match_input.php';
}

function goBack() {
    // デモトップページに戻る
    window.location.href = '../demo.php';
}

// スムーズスクロール
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>