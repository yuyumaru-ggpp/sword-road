<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>個人戦システム - チュートリアル</title>
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
    background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
    border-bottom:3px solid #dbeafe;
}

.section-icon {
    font-size:1.8rem;
    flex-shrink:0;
}

.section-title {
    font-size:1.3rem;
    font-weight:bold;
    color:#3b82f6;
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
    border-color:#3b82f6;
    background:#eff6ff;
}

.step-number {
    display:inline-block;
    background:#3b82f6;
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

.demo-input {
    width:120px;
    padding:0.5rem;
    border:2px solid #d1d5db;
    border-radius:6px;
    text-align:center;
    font-size:0.9rem;
}

.demo-select {
    width:150px;
    padding:0.5rem;
    border:2px solid #d1d5db;
    border-radius:6px;
    text-align:center;
    font-size:0.9rem;
    background:white;
}

.demo-button {
    padding:0.4rem 1.2rem;
    border:2px solid #000;
    border-radius:25px;
    font-weight:bold;
    background:white;
    font-size:0.85rem;
    white-space:nowrap;
}

.demo-button.primary {
    background:#3b82f6;
    color:white;
    border-color:#3b82f6;
}

.demo-button.selected {
    background:#ef4444;
    color:white;
    border-color:#ef4444;
}

.demo-player-section {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:0.6rem;
}

.demo-label {
    font-weight:bold;
    font-size:0.9rem;
}

.demo-score-row {
    display:flex;
    gap:0.6rem;
    align-items:center;
    justify-content:center;
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
    background:#3b82f6;
    color:white;
}

.btn-start:active {
    background:#2563eb;
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
        border-color:#3b82f6;
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(59,130,246,0.15);
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
        width:150px;
        padding:0.6rem;
        font-size:1rem;
    }

    .demo-select {
        width:180px;
        padding:0.6rem;
        font-size:1rem;
    }
    
    .demo-button {
        padding:0.5rem 1.5rem;
        font-size:1rem;
    }
    
    .demo-label {
        font-size:1rem;
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
        background:#2563eb;
        transform:translateY(-2px);
        box-shadow:0 4px 12px rgba(59,130,246,0.3);
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
        <div class="tutorial-title">🥋 個人戦システム 使い方ガイド</div>
        <div class="tutorial-subtitle">試合番号入力から試合結果記録までの完全ガイド</div>
    </div>

    <div class="tutorial-content">
        <!-- 試合番号入力セクション -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">📝</div>
                <div class="section-title">STEP 1：試合番号入力</div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">試合場を選択</div>
                    <div class="step-description">
                        ドロップダウンメニューから試合が行われる試合場を選択します。前回選択した試合場が自動的に選択されています。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-section">
                                <div class="demo-label">試合場</div>
                                <select class="demo-select">
                                    <option>第1試合場</option>
                                    <option selected>第2試合場</option>
                                    <option>第3試合場</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">試合番号を入力</div>
                    <div class="step-description">
                        これから記録する試合の番号を入力します。同じ試合場と試合番号の組み合わせは重複登録できません。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-section">
                                <div class="demo-label">試合番号</div>
                                <input class="demo-input" placeholder="試合番号" value="10">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">決定して次へ</div>
                    <div class="step-description">
                        試合場と試合番号を入力したら「決定」ボタンを押して選手選択画面に進みます。
                    </div>
                    <div class="step-visual">
                        <div style="text-align:center; padding:1rem;">
                            <div style="display:flex; gap:0.8rem; justify-content:center;">
                                <button style="border:2px solid #e5e7eb; border-radius:12px; padding:0.7rem 3rem; background:white; color:#667eea; font-size:1rem; font-weight:700; cursor:pointer;">戻る</button>
                                <button style="border:none; border-radius:12px; padding:0.7rem 3rem; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; font-size:1rem; font-weight:700; cursor:pointer;">決定</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 選手選択セクション -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">👥</div>
                <div class="section-title">STEP 2：選手選択</div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">選手番号を入力</div>
                    <div class="step-description">
                        赤側（上段）と白側（下段）の選手番号を入力します。番号を入力すると自動的に選手が選択されます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-section">
                                <div class="demo-label" style="color:#dc2626;">赤 - 選手番号</div>
                                <input class="demo-input" placeholder="番号を入力" value="1">
                            </div>
                            <div style="font-size:1.2rem; font-weight:bold; margin:0.5rem 0;">VS</div>
                            <div class="demo-player-section">
                                <div class="demo-label" style="color:#6b7280;">白 - 選手番号</div>
                                <input class="demo-input" placeholder="番号を入力" value="2">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">または選手を選択</div>
                    <div class="step-description">
                        選手番号の代わりに、ドロップダウンメニューから直接選手を選択することもできます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-section">
                                <div class="demo-label">選手を選択</div>
                                <select class="demo-select">
                                    <option>選手を選択してください</option>
                                    <option selected>田中太郎 (Aチーム)</option>
                                    <option>鈴木花子 (Aチーム)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">不戦勝の記録（必要時）</div>
                    <div class="step-description">
                        <strong style="color:#3b82f6;">重要：</strong> 不戦勝の場合、勝利した選手の「不戦勝」ボタンを押します。ボタンは赤色に変わり、そのまま「決定」で完了画面に進みます。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-player-section">
                                <div style="font-size:0.85rem; margin-bottom:0.3rem;">赤側選手</div>
                                <button style="border:2px solid rgba(102,126,234,0.4); border-radius:10px; padding:0.5rem 2rem; background:white; color:#667eea; font-size:0.9rem; font-weight:700; cursor:pointer;">不戦勝</button>
                            </div>
                            <div style="margin:0.3rem 0; font-size:0.75rem; color:#666;">← 勝った方を選択</div>
                            <div class="demo-player-section">
                                <div style="font-size:0.85rem; margin-bottom:0.3rem;">白側選手</div>
                                <button style="border:none; border-radius:10px; padding:0.5rem 2rem; background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:white; font-size:0.9rem; font-weight:700; cursor:pointer;">不戦勝</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 試合詳細入力セクション -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">📋</div>
                <div class="section-title">STEP 3：試合詳細入力</div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">ポイントを選択</div>
                    <div class="step-description">
                        中央のドロップダウンから各本のポイントを選択します。<br>
                        <strong>選択肢：</strong>▼（未選択）、メ、コ、ド、ツ、反、判、×
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div class="demo-score-row">
                                <div class="demo-dropdown">▼</div>
                                <div class="demo-dropdown">メ</div>
                                <div class="demo-dropdown">コ</div>
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
                        各選手が取った本数を、丸いボタンで選択します。赤側（上段）と白側（下段）それぞれ記録できます。複数選択可能です。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div style="font-weight:bold; color:#ef4444; margin-bottom:0.3rem; font-size:0.8rem;">赤側選手</div>
                            <div class="demo-score-row">
                                <div class="demo-circle"></div>
                                <div class="demo-circle selected"></div>
                                <div class="demo-circle"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">特殊な試合結果</div>
                    <div class="step-description">
                        二本勝、一本勝、延長戦、判定、引き分けなどは中央右側のボタンから選択できます。通常の試合は「-」のままでOK。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <button style="border:2px solid #667eea; border-radius:10px; padding:0.5rem 1.5rem; background:white; color:#667eea; font-size:0.9rem; font-weight:700; cursor:pointer;">-</button>
                            <div style="font-size:0.7rem; color:#999; margin-top:0.5rem; text-align:center;">
                                二本勝 / 一本勝 / 延長戦<br>判定 / 引き分け
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">リセットと決定</div>
                    <div class="step-description">
                        入力を間違えた場合は「入力内容をリセット」ボタンでリセット。全て入力したら「決定」ボタンを押して確認画面へ進みます。
                    </div>
                    <div class="step-visual">
                        <div style="text-align:center; padding:1rem;">
                            <button style="margin-bottom:0.8rem; color:#ef4444; border:2px solid rgba(239,68,68,0.3); background:white; border-radius:50px; padding:0.4rem 1.2rem; font-weight:700; cursor:pointer;">入力内容をリセット</button>
                            <div style="display:flex; gap:0.8rem; justify-content:center;">
                                <button style="border:2px solid #e5e7eb; border-radius:12px; padding:0.7rem 3rem; background:white; color:#667eea; font-size:1rem; font-weight:700; cursor:pointer;">戻る</button>
                                <button style="border:none; border-radius:12px; padding:0.7rem 3rem; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; font-size:1rem; font-weight:700; cursor:pointer;">決定</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="step-card">
                    <div class="step-number">5</div>
                    <div class="step-title">確認モーダルでOK</div>
                    <div class="step-description">
                        「決定」を押すと確認モーダルが表示されます。「OK」を押すと試合結果が保存され、最初の画面に戻ります。
                    </div>
                    <div class="step-visual">
                        <div class="demo-ui">
                            <div style="font-size:2rem; margin-bottom:0.5rem;">🏁</div>
                            <div style="font-weight:bold; font-size:0.9rem; margin-bottom:0.3rem;">練習を終えますか？</div>
                            <div style="display:flex; gap:0.6rem; margin-top:0.8rem;">
                                <button style="border:2px solid #e5e7eb; border-radius:12px; padding:0.5rem 2rem; background:white; color:#667eea; font-size:0.9rem; font-weight:700; cursor:pointer;">キャンセル</button>
                                <button style="border:none; border-radius:12px; padding:0.5rem 2rem; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; font-size:0.9rem; font-weight:700; cursor:pointer;">OK</button>
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
                <strong>試合場・試合番号：</strong> 最初に正しい試合場と試合番号を入力してください。<br>
                <strong>選手番号：</strong> 正しい選手番号を入力するか、プルダウンから選択してください。<br>
                <strong>不戦勝：</strong> 不戦勝の場合は勝利した選手のボタンを押して「決定」してください。通常の試合詳細入力は不要です。<br>
                <strong>赤・白の位置：</strong> 選手の位置（赤側/白側、上段/下段）を間違えないように記録してください。<br>
                <strong>確認：</strong> 送信前に入力内容を必ず確認してください。
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
    // 個人戦選手選択画面に遷移
    window.location.href = 'match_input.php';
}

function goBack() {
    // デモトップページに戻る
    window.location.href = '../demo.php';
}
</script>
</body>
</html>