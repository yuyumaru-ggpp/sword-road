// データオブジェクト
const data = {
    upper: {
        name: '',
        number: '',
        selected: [],
        decision: false
    },
    lower: {
        name: '',
        number: '',
        selected: [],
        decision: false
    },
    scores: ['▼', '▼', '▼'],
    special: 'none'
};

/**
 * dataオブジェクトの内容をUIに反映
 */
function load() {
    console.log('=== load() 開始 ===');
    console.log('data:', data);

    // スコアドロップダウンの復元
    const scoreDropdowns = document.querySelectorAll('.middle-controls .score-dropdown');
    data.scores.forEach((score, i) => {
        if (scoreDropdowns[i]) {
            scoreDropdowns[i].textContent = score;
        }
    });

    // 上段の赤丸の復元
    document.querySelectorAll('.upper-circles .radio-circle').forEach(circle => {
        circle.classList.remove('selected');
    });
    if (Array.isArray(data.upper.selected)) {
        data.upper.selected.forEach(index => {
            const circle = document.querySelector(`.upper-circles .radio-circle[data-index="${index}"]`);
            if (circle) {
                circle.classList.add('selected');
            }
        });
    }

    // 下段の赤丸の復元
    document.querySelectorAll('.lower-circles .radio-circle').forEach(circle => {
        circle.classList.remove('selected');
    });
    if (Array.isArray(data.lower.selected)) {
        data.lower.selected.forEach(index => {
            const circle = document.querySelector(`.lower-circles .radio-circle[data-index="${index}"]`);
            if (circle) {
                circle.classList.add('selected');
            }
        });
    }

    // 判定勝ちボタンの復元
    const upperDecisionBtn = document.getElementById('upperDecisionBtn');
    const lowerDecisionBtn = document.getElementById('lowerDecisionBtn');
    
    if (data.upper.decision) {
        upperDecisionBtn.classList.add('active');
    } else {
        upperDecisionBtn.classList.remove('active');
    }

    if (data.lower.decision) {
        lowerDecisionBtn.classList.add('active');
    } else {
        lowerDecisionBtn.classList.remove('active');
    }

    // 特殊な試合結果ボタンの復元
    const drawButton = document.getElementById('drawButton');
    const specialText = 
        data.special === 'nihon' ? '二本勝' :
        data.special === 'ippon' ? '一本勝' :
        data.special === 'extend' ? '延長戦' :
        data.special === 'hantei' ? '判定' :
        data.special === 'draw' ? '引き分け' : '-';
    
    drawButton.textContent = specialText;

    console.log('=== load() 完了 ===');
}

/**
 * UIの状態をdataオブジェクトに保存
 */
function saveLocal() {
    data.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown'))
        .map(b => b.textContent);

    // 選択されたすべてのインデックスを配列で保存
    data.upper.selected = Array.from(document.querySelectorAll('.upper-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));

    data.lower.selected = Array.from(document.querySelectorAll('.lower-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));

    data.upper.decision = document.getElementById('upperDecisionBtn').classList.contains('active');
    data.lower.decision = document.getElementById('lowerDecisionBtn').classList.contains('active');

    const dt = document.getElementById('drawButton').textContent;
    data.special = dt === '二本勝' ? 'nihon' :
        dt === '一本勝' ? 'ippon' :
            dt === '延長戦' ? 'extend' :
                dt === '判定' ? 'hantei' :
                    dt === '引き分け' ? 'draw' : 'none';
}

/**
 * イベントリスナーの設定
 */
function initEventListeners() {
    // 判定勝ちボタン（上段）
    document.getElementById('upperDecisionBtn').addEventListener('click', function () {
        const lowerBtn = document.getElementById('lowerDecisionBtn');

        if (this.classList.contains('active')) {
            this.classList.remove('active');
        } else {
            this.classList.add('active');
            lowerBtn.classList.remove('active');
        }
    });

    // 判定勝ちボタン（下段）
    document.getElementById('lowerDecisionBtn').addEventListener('click', function () {
        const upperBtn = document.getElementById('upperDecisionBtn');

        if (this.classList.contains('active')) {
            this.classList.remove('active');
        } else {
            this.classList.add('active');
            upperBtn.classList.remove('active');
        }
    });

    // 赤丸クリックの処理（複数選択対応）
    for (let i = 0; i < 3; i++) {
        const upper = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);
        const lower = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);

        upper.addEventListener('click', () => {
            const lowerSame = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);

            if (upper.classList.contains('selected')) {
                // 選択解除
                upper.classList.remove('selected');
            } else {
                // 選択
                upper.classList.add('selected');
                // 同じ位置の下段を解除
                lowerSame.classList.remove('selected');
            }
        });

        lower.addEventListener('click', () => {
            const upperSame = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);

            if (lower.classList.contains('selected')) {
                // 選択解除
                lower.classList.remove('selected');
            } else {
                // 選択
                lower.classList.add('selected');
                // 同じ位置の上段を解除
                upperSame.classList.remove('selected');
            }
        });
    }

    // スコアドロップダウン
    document.querySelectorAll('.dropdown-container').forEach(container => {
        const btn = container.querySelector('.score-dropdown');
        const menu = container.querySelector('.dropdown-menu');

        btn.addEventListener('click', e => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m =>
                m.classList.remove('show')
            );
            menu.classList.toggle('show');
        });

        menu.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => {
                btn.textContent = item.dataset.val || item.textContent;
                menu.classList.remove('show');
            });
        });
    });

    // 特殊な試合結果ボタン
    document.getElementById('drawButton').addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
        document.getElementById('drawMenu').classList.toggle('show');
    });

    document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById('drawButton').textContent = item.textContent;
            document.getElementById('drawMenu').classList.remove('show');
        });
    });

    // ドロップダウンを閉じる
    document.addEventListener('click', () =>
        document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m =>
            m.classList.remove('show')
        )
    );

    // リセットボタン
    document.getElementById('cancelButton').addEventListener('click', () => {
        if (confirm('試合内容をリセットしますか?')) {
            data.upper = {
                name: data.upper.name,
                number: data.upper.number,
                selected: [],
                decision: false
            };
            data.lower = {
                name: data.lower.name,
                number: data.lower.number,
                selected: [],
                decision: false
            };
            data.scores = ['▼', '▼', '▼'];
            data.special = 'none';
            load();
        }
    });

    // 決定ボタン
    document.getElementById('submitButton').onclick = async () => {
        saveLocal();
        try {
            const r = await fetch(location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const j = await r.json();
            if (j.status === 'ok') {
                window.location.href = 'match-confirm.php';
            } else {
                alert('保存失敗');
            }
        } catch (e) {
            alert('エラー発生');
            console.error(e);
        }
    };
}

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    console.log('=== 初期化開始 ===');
    console.log('oldInput:', oldInput);
    
    // データの初期値をHTMLから取得
    data.upper.name = document.querySelector('.upper-name')?.textContent.trim() || '';
    data.upper.number = document.querySelector('.upper-number')?.textContent.trim() || '';
    data.lower.name = document.querySelector('.lower-name')?.textContent.trim() || '';
    data.lower.number = document.querySelector('.lower-number')?.textContent.trim() || '';

    // oldInputがある場合はdataオブジェクトに反映
    if (oldInput && oldInput !== null && typeof oldInput === 'object') {
        console.log('oldInput復元開始');
        
        if (Array.isArray(oldInput.scores)) {
            data.scores = oldInput.scores.slice();
            console.log('復元後 data.scores:', data.scores);
        }
        
        if (oldInput.upper && typeof oldInput.upper === 'object') {
            if (Array.isArray(oldInput.upper.selected)) {
                data.upper.selected = oldInput.upper.selected.slice();
                console.log('復元後 data.upper.selected:', data.upper.selected);
            }
            if (typeof oldInput.upper.decision === 'boolean') {
                data.upper.decision = oldInput.upper.decision;
            }
        }
        
        if (oldInput.lower && typeof oldInput.lower === 'object') {
            if (Array.isArray(oldInput.lower.selected)) {
                data.lower.selected = oldInput.lower.selected.slice();
                console.log('復元後 data.lower.selected:', data.lower.selected);
            }
            if (typeof oldInput.lower.decision === 'boolean') {
                data.lower.decision = oldInput.lower.decision;
            }
        }
        
        if (oldInput.special) {
            data.special = oldInput.special;
        }
        
        console.log('最終 data:', data);
    }

    initEventListeners();
    load(); // この関数が画面に状態を反映します
    
    console.log('=== 初期化完了 ===');
});