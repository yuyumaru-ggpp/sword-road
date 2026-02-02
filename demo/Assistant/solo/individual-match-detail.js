/* ===============================
   初期データ
=============================== */
const data = {
    upper: {
        name: document.querySelector('.upper-name').textContent,
        number: document.querySelector('.upper-number').textContent,
        scores: ['▼', '▼', '▼'],
        selected: -1,
        decision: false
    },
    lower: {
        name: document.querySelector('.lower-name').textContent,
        number: document.querySelector('.lower-number').textContent,
        scores: ['▲', '▲', '▲'],
        selected: -1,
        decision: false
    },
    special: 'none'
};

/* ===============================
   判定ボタン表示/非表示の制御
=============================== */
function updateDecisionButtonsVisibility() {
    const drawText = document.getElementById('drawButton').textContent;
    const showButtons = drawText === '延長';
    
    document.getElementById('upperDecisionRow').classList.toggle('show', showButtons);
    document.getElementById('lowerDecisionRow').classList.toggle('show', showButtons);
    
    if (!showButtons) {
        document.getElementById('upperDecisionBtn').classList.remove('active');
        document.getElementById('lowerDecisionBtn').classList.remove('active');
        data.upper.decision = false;
        data.lower.decision = false;
    }
}

/* ===============================
   画面の読み込み
=============================== */
function load() {
    document.querySelector('.upper-name').textContent = data.upper.name || '───';
    document.querySelector('.upper-number').textContent = data.upper.number || '───';
    document.querySelector('.lower-name').textContent = data.lower.name || '───';
    document.querySelector('.lower-number').textContent = data.lower.number || '───';

    document.querySelectorAll('.middle-controls .score-dropdown').forEach((btn, i) => {
        btn.textContent = (data.upper.scores && data.upper.scores[i]) || '▼';
    });

    document.querySelectorAll('.upper-circles .radio-circle').forEach((circle, i) => {
        circle.classList.toggle('selected', data.upper.selected === i);
    });
    
    document.querySelectorAll('.lower-circles .radio-circle').forEach((circle, i) => {
        circle.classList.toggle('selected', data.lower.selected === i);
    });

    document.getElementById('upperDecisionBtn').classList.toggle('active', data.upper.decision || false);
    document.getElementById('lowerDecisionBtn').classList.toggle('active', data.lower.decision || false);

    const special = data.special || 'none';
    const text = special === 'ippon' ? '一本勝' : 
                 special === 'extend' ? '延長' : 
                 special === 'draw' ? '引分け' : '-';
    document.getElementById('drawButton').textContent = text;
    
    updateDecisionButtonsVisibility();
}

/* ===============================
   ローカルデータの保存
=============================== */
function saveLocal() {
    data.upper.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown'))
        .map(btn => btn.textContent);
    
    const upperSelected = document.querySelector('.upper-circles .radio-circle.selected');
    data.upper.selected = upperSelected ? +upperSelected.dataset.index : -1;
    
    const lowerSelected = document.querySelector('.lower-circles .radio-circle.selected');
    data.lower.selected = lowerSelected ? +lowerSelected.dataset.index : -1;

    data.upper.decision = document.getElementById('upperDecisionBtn').classList.contains('active');
    data.lower.decision = document.getElementById('lowerDecisionBtn').classList.contains('active');

    const drawText = document.getElementById('drawButton').textContent;
    data.special = drawText === '一本勝' ? 'ippon' :
                   drawText === '延長' ? 'extend' :
                   drawText === '引分け' ? 'draw' : 'none';
}

/* ===============================
   判定勝ちボタンの処理
=============================== */
document.getElementById('upperDecisionBtn').addEventListener('click', function() {
    const lowerBtn = document.getElementById('lowerDecisionBtn');
    
    if (this.classList.contains('active')) {
        this.classList.remove('active');
    } else {
        this.classList.add('active');
        lowerBtn.classList.remove('active');
    }
});

document.getElementById('lowerDecisionBtn').addEventListener('click', function() {
    const upperBtn = document.getElementById('upperDecisionBtn');
    
    if (this.classList.contains('active')) {
        this.classList.remove('active');
    } else {
        this.classList.add('active');
        upperBtn.classList.remove('active');
    }
});

/* ===============================
   ラジオサークルの処理
=============================== */
for (let i = 0; i < 3; i++) {
    const upper = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);
    const lower = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);

    upper.addEventListener('click', () => {
        if (upper.classList.contains('selected')) {
            upper.classList.remove('selected');
        } else {
            upper.classList.add('selected');
            lower.classList.remove('selected');
        }
    });

    lower.addEventListener('click', () => {
        if (lower.classList.contains('selected')) {
            lower.classList.remove('selected');
        } else {
            lower.classList.add('selected');
            upper.classList.remove('selected');
        }
    });
}

/* ===============================
   スコアドロップダウンの処理
=============================== */
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

/* ===============================
   引分けボタンの処理
=============================== */
document.getElementById('drawButton').addEventListener('click', e => {
    e.stopPropagation();
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    document.getElementById('drawMenu').classList.toggle('show');
});

document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', () => {
        document.getElementById('drawButton').textContent = item.textContent;
        document.getElementById('drawMenu').classList.remove('show');
        updateDecisionButtonsVisibility();
    });
});

/* ===============================
   ドロップダウンを閉じる
=============================== */
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => m.classList.remove('show'));
});

/* ===============================
   リセットボタン
=============================== */
document.getElementById('cancelButton').addEventListener('click', () => {
    if (confirm('試合内容をリセットしますか?')) {
        data.upper = {
            name: data.upper.name,
            number: data.upper.number,
            scores: ['▼', '▼', '▼'],
            selected: -1,
            decision: false
        };
        data.lower = {
            name: data.lower.name,
            number: data.lower.number,
            scores: ['▲', '▲', '▲'],
            selected: -1,
            decision: false
        };
        data.special = 'none';
        load();
    }
});

/* ===============================
   決定ボタン
=============================== */
document.getElementById('submitButton').onclick = async () => {
    saveLocal();
    
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const json = await response.json();
        
        if (json.status === 'ok') {
            window.location.href = 'match-confirm.php';
        } else {
            alert('保存に失敗しました');
        }
    } catch (error) {
        alert('エラーが発生しました');
        console.error(error);
    }
};

/* ===============================
   初期化
=============================== */
load();