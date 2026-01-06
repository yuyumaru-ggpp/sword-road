<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合詳細</title>
    <link rel="stylesheet" href="../css/match-detail-style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <span>個人戦</span>
        <span>〇〇大会</span>
        <span>〇〇部門</span>
    </div>

    <div class="content-wrapper">
        <!-- 上段選手 -->
        <div class="match-section upper-section">
            <div class="row">
                <div class="label">名前</div>
                <div class="value upper-name">───</div>
            </div>
            
            <div class="row">
                <div class="label">選手番号</div>
                <div class="value upper-number">───</div>
            </div>
            
            <div class="score-display">
                <div class="score-group">
                    <div class="score-numbers upper-numbers">
                        <span>1</span><span>2</span><span>3</span>
                    </div>
                    <div class="radio-circles upper-circles">
                        <div class="radio-circle" data-index="0"></div>
                        <div class="radio-circle" data-index="1"></div>
                        <div class="radio-circle" data-index="2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 中央ライン（スコア入力） -->
        <div class="divider-section">
            <hr class="divider">
            <div class="middle-controls">
                <div class="score-dropdowns upper-scores">
                    <div class="dropdown-container">
                        <button class="score-dropdown">▼</button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="×">×</div>
                            <div class="dropdown-item" data-val="コ">コ</div>
                            <div class="dropdown-item" data-val="ド">ド</div>
                            <div class="dropdown-item" data-val="反">反</div>
                            <div class="dropdown-item" data-val="ツ">ツ</div>
                            <div class="dropdown-item" data-val="〇">〇</div>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-dropdown">▼</button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="×">×</div>
                            <div class="dropdown-item" data-val="コ">コ</div>
                            <div class="dropdown-item" data-val="ド">ド</div>
                            <div class="dropdown-item" data-val="反">反</div>
                            <div class="dropdown-item" data-val="ツ">ツ</div>
                            <div class="dropdown-item" data-val="〇">〇</div>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-dropdown">▼</button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="×">×</div>
                            <div class="dropdown-item" data-val="コ">コ</div>
                            <div class="dropdown-item" data-val="ド">ド</div>
                            <div class="dropdown-item" data-val="反">反</div>
                            <div class="dropdown-item" data-val="ツ">ツ</div>
                            <div class="dropdown-item" data-val="〇">〇</div>
                        </div>
                    </div>
                </div>
                <div class="draw-container">
                    <button class="draw-button" id="drawButton">-</button>
                    <div class="draw-dropdown-menu" id="drawMenu">
                        <div class="dropdown-item">-</div>
                        <div class="dropdown-item">引分け</div>
                        <div class="dropdown-item">一本勝</div>
                        <div class="dropdown-item">延長</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 下段選手 -->
        <div class="match-section">
            <div class="row">
                <div class="label">名前</div>
                <div class="value lower-name">───</div>
            </div>
            
            <div class="row">
                <div class="label">選手番号</div>
                <div class="value lower-number">───</div>
            </div>
            
            <div class="score-display">
                <div class="score-group">
                    <div class="score-numbers lower-numbers">
                        <span>1</span><span>2</span><span>3</span>
                    </div>
                    <div class="radio-circles lower-circles">
                        <div class="radio-circle" data-index="0"></div>
                        <div class="radio-circle" data-index="1"></div>
                        <div class="radio-circle" data-index="2"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-area">
            <div class="bottom-right-button">
                <button class="cancel-button" id="cancelButton">取り消し</button>
            </div>

            <div class="bottom-buttons">
                <button class="bottom-button back-button" onclick="history.back()">キャンセル</button>
                <button class="bottom-button submit-button" id="submitButton">変更</button>
            </div>
        </div>
    </div>
</div>

<script>
// データ構造
const matchData = {
    upper: {
        name: '',
        number: '',
        scores: ['▼', '▼', '▼'],
        selected: -1
    },
    lower: {
        name: '',
        number: '',
        scores: ['▼', '▼', '▼'],
        selected: -1
    },
    special: 'none'
};

// データ読み込み
function loadData() {
    document.querySelector('.upper-name').textContent = matchData.upper.name || '───';
    document.querySelector('.upper-number').textContent = matchData.upper.number || '───';
    document.querySelector('.lower-name').textContent = matchData.lower.name || '───';
    document.querySelector('.lower-number').textContent = matchData.lower.number || '───';

    // スコアドロップダウンの初期値
    document.querySelectorAll('.upper-scores .score-dropdown').forEach((btn, i) => {
        btn.textContent = matchData.upper.scores[i];
    });

    // 選択された本数
    document.querySelectorAll('.upper-circles .radio-circle').forEach((circle, i) => {
        circle.classList.toggle('selected', matchData.upper.selected === i);
    });
    document.querySelectorAll('.lower-circles .radio-circle').forEach((circle, i) => {
        circle.classList.toggle('selected', matchData.lower.selected === i);
    });

    // 引分けボタン
    const specialText = matchData.special === 'ippon' ? '一本勝' :
                        matchData.special === 'extend' ? '延長' :
                        matchData.special === 'draw' ? '引分け' : '-';
    document.getElementById('drawButton').textContent = specialText;
}

// 本数選択（上下で排他的、同じ列のみ選択可能）
for (let i = 0; i < 3; i++) {
    const upperCircle = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);
    const lowerCircle = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);

    upperCircle.addEventListener('click', () => {
        // 同じ列の下段を解除
        lowerCircle.classList.remove('selected');
        // 上段のこのボタンをトグル
        upperCircle.classList.toggle('selected');
        matchData.upper.selected = upperCircle.classList.contains('selected') ? i : -1;
        matchData.lower.selected = -1;
    });

    lowerCircle.addEventListener('click', () => {
        // 同じ列の上段を解除
        upperCircle.classList.remove('selected');
        // 下段のこのボタンをトグル
        lowerCircle.classList.toggle('selected');
        matchData.lower.selected = lowerCircle.classList.contains('selected') ? i : -1;
        matchData.upper.selected = -1;
    });
}

// スコアドロップダウン
document.querySelectorAll('.dropdown-container').forEach(container => {
    const btn = container.querySelector('.score-dropdown');
    const menu = container.querySelector('.dropdown-menu');
    
    btn.addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => m.classList.remove('show'));
        menu.classList.toggle('show');
    });
    
    menu.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', () => {
            btn.textContent = item.dataset.val || item.textContent;
            menu.classList.remove('show');
        });
    });
});

// 引分けドロップダウン
document.getElementById('drawButton').addEventListener('click', e => {
    e.stopPropagation();
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    document.getElementById('drawMenu').classList.toggle('show');
});

document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', () => {
        document.getElementById('drawButton').textContent = item.textContent;
        document.getElementById('drawMenu').classList.remove('show');
        
        const text = item.textContent;
        matchData.special = text === '一本勝' ? 'ippon' :
                           text === '延長' ? 'extend' :
                           text === '引分け' ? 'draw' : 'none';
    });
});

// 外側クリックでドロップダウンを閉じる
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => m.classList.remove('show'));
});

// 取り消しボタン
document.getElementById('cancelButton').addEventListener('click', () => {
    if (confirm('入力内容をリセットしますか？')) {
        matchData.upper.scores = ['▼', '▼', '▼'];
        matchData.upper.selected = -1;
        matchData.lower.selected = -1;
        matchData.special = 'none';
        
        document.querySelectorAll('.upper-scores .score-dropdown').forEach((btn, i) => {
            btn.textContent = '▼';
        });
        document.querySelectorAll('.radio-circle').forEach(c => c.classList.remove('selected'));
        document.getElementById('drawButton').textContent = '-';
    }
});

// 送信ボタン
document.getElementById('submitButton').addEventListener('click', () => {
    // スコアを保存
    matchData.upper.scores = Array.from(document.querySelectorAll('.upper-scores .score-dropdown'))
        .map(btn => btn.textContent);
    
    console.log('保存データ:', matchData);
    alert('変更を保存しました！\n\n' + JSON.stringify(matchData, null, 2));
});

// 初期化
loadData();
</script>
</body>
</html>