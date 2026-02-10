/* ===== 初期データ ===== */
const data = {
    upper: {
        name: document.querySelector('.upper-name')?.textContent || '',
        number: document.querySelector('.upper-number')?.textContent || '',
        selected: [],
        decision: false
    },
    lower: {
        name: document.querySelector('.lower-name')?.textContent || '',
        number: document.querySelector('.lower-number')?.textContent || '',
        selected: [],
        decision: false
    },
    scores: ['▼', '▼', '▼'],
    special: 'none'
};

/**
 * データをUIに反映
 */
function load() {
    document.querySelector('.upper-name').textContent = data.upper.name || '───';
    document.querySelector('.upper-number').textContent = data.upper.number || '───';
    document.querySelector('.lower-name').textContent = data.lower.name || '───';
    document.querySelector('.lower-number').textContent = data.lower.number || '───';

    document.querySelectorAll('.middle-controls .score-dropdown').forEach((b, i) => 
        b.textContent = (data.scores && data.scores[i]) || '▼'
    );

    document.querySelectorAll('.upper-circles .radio-circle').forEach((c, i) => {
        c.classList.toggle('selected', (data.upper.selected || []).includes(i));
    });
    document.querySelectorAll('.lower-circles .radio-circle').forEach((c, i) => {
        c.classList.toggle('selected', (data.lower.selected || []).includes(i));
    });

    document.getElementById('upperDecisionBtn').classList.toggle('active', data.upper.decision || false);
    document.getElementById('lowerDecisionBtn').classList.toggle('active', data.lower.decision || false);

    const special = data.special || 'none';
    const text = special === 'nihon' ? '二本勝' : 
                 special === 'ippon' ? '一本勝' : 
                 special === 'extend' ? '延長戦' : 
                 special === 'hantei' ? '判定' : 
                 special === 'draw' ? '引き分け' : '-';
    document.getElementById('drawButton').textContent = text;
}

/**
 * UIの状態をdataオブジェクトに保存
 */
function saveLocal() {
    data.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown'))
        .map(b => b.textContent);
    
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

/* ===== モーダル開閉 ===== */
const modal = document.getElementById('endPracticeModal');

function showModal() {
    modal.classList.add('show');
}

function hideModal() {
    modal.classList.remove('show');
}

/**
 * イベントリスナーの設定
 */
function initEventListeners() {
    /* ----- 判定勝ちボタン ----- */
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

    /* ----- 赤丸クリック（複数選択対応） ----- */
    for (let i = 0; i < 3; i++) {
        const upper = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);
        const lower = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);

        upper.addEventListener('click', () => {
            const lowerSame = document.querySelector(`.lower-circles .radio-circle[data-index="${i}"]`);
            if (upper.classList.contains('selected')) {
                upper.classList.remove('selected');
            } else {
                upper.classList.add('selected');
                lowerSame.classList.remove('selected');
            }
        });

        lower.addEventListener('click', () => {
            const upperSame = document.querySelector(`.upper-circles .radio-circle[data-index="${i}"]`);
            if (lower.classList.contains('selected')) {
                lower.classList.remove('selected');
            } else {
                lower.classList.add('selected');
                upperSame.classList.remove('selected');
            }
        });
    }

    /* ----- スコアドロップダウン ----- */
    document.querySelectorAll('.dropdown-container').forEach(container => {
        const btn  = container.querySelector('.score-dropdown');
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

    /* ----- 特殊な試合結果ドロップダウン ----- */
    document.getElementById('drawButton').addEventListener('click', e => {
        e.stopPropagation();
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
        document.getElementById('drawMenu').classList.toggle('show');
    });

    document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById('drawButton').textContent = item.textContent;
            document.getElementById('drawMenu').classList.remove('show');
            // 判定勝ちボタン表示機能を削除（元はここにupdateDecisionButtonsVisibility()があった）
        });
    });

    /* ----- ドロップダウン閉じる ----- */
    document.addEventListener('click', () => 
        document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => 
            m.classList.remove('show')
        )
    );

    /* ----- リセットボタン ----- */
    document.getElementById('cancelButton').addEventListener('click', () => {
        if (confirm('試合内容をリセットしますか?')) {
            data.upper = { name: data.upper.name, number: data.upper.number, selected: [], decision: false };
            data.lower = { name: data.lower.name, number: data.lower.number, selected: [], decision: false };
            data.scores = ['▼', '▼', '▼'];
            data.special = 'none';
            load();
        }
    });

    /* ----- 決定ボタン → モーダルを表示 ----- */
    document.getElementById('submitButton').addEventListener('click', () => {
        saveLocal();
        showModal();
    });

    /* ----- モーダル・キャンセル ----- */
    document.getElementById('modalCancelBtn').addEventListener('click', () => {
        hideModal();
    });

    /* ----- モーダル・オーバーレイクリック（モーダル外をタップ） ----- */
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            hideModal();
        }
    });

    /* ----- モーダル・OK → POSTしてindex.phpに遷移 ----- */
    document.getElementById('modalOkBtn').addEventListener('click', async () => {
        try {
            const r = await fetch(location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const j = await r.json();
            if (j.status === 'ok') {
                window.location.href = '/demo/Assistant/demo/index.php';
            } else {
                hideModal();
                alert('保存に失敗しました');
            }
        } catch (e) {
            hideModal();
            alert('エラー発生');
            console.error(e);
        }
    });
}

/* ===== 初期化 ===== */
document.addEventListener('DOMContentLoaded', () => {
    data.upper.name   = document.querySelector('.upper-name')?.textContent  || '';
    data.upper.number = document.querySelector('.upper-number')?.textContent || '';
    data.lower.name   = document.querySelector('.lower-name')?.textContent  || '';
    data.lower.number = document.querySelector('.lower-number')?.textContent || '';
    
    initEventListeners();
    load();
});