

function updateDecisionButtonsVisibility() {
    const drawText = document.getElementById('drawButton').textContent;
    const showButtons = drawText === '延長';
    
    document.getElementById('upperDecisionRow').classList.toggle('show', showButtons);
    document.getElementById('lowerDecisionRow').classList.toggle('show', showButtons);
    
    // 延長でない場合は判定勝ちの選択をリセット
    if (!showButtons) {
        document.getElementById('upperDecisionBtn').classList.remove('active');
        document.getElementById('lowerDecisionBtn').classList.remove('active');
    }
}

function load() {
    document.querySelector('.upper-name').textContent = data.upper.name||'───';
    document.querySelector('.upper-number').textContent = data.upper.number||'───';
    document.querySelector('.lower-name').textContent = data.lower.name||'───';
    document.querySelector('.lower-number').textContent = data.lower.number||'───';

    document.querySelectorAll('.upper-scores .score-dropdown').forEach((b,i)=>b.textContent=(data.upper.scores&&data.upper.scores[i])||'▼');

    document.querySelectorAll('.upper-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.upper.selected===i));
    document.querySelectorAll('.lower-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.lower.selected===i));

    document.getElementById('upperDecisionBtn').classList.toggle('active', data.upper.decision||false);
    document.getElementById('lowerDecisionBtn').classList.toggle('active', data.lower.decision||false);

    const special = data.special||'none';
    const text = special==='ippon'?'一本勝':special==='extend'?'延長':special==='draw'?'引分け':'-';
    document.getElementById('drawButton').textContent=text;
    
    updateDecisionButtonsVisibility();
}

function saveLocal() {
    data.upper.scores = Array.from(document.querySelectorAll('.upper-scores .score-dropdown')).map(b=>b.textContent);
    const uSel = document.querySelector('.upper-circles .radio-circle.selected');
    data.upper.selected = uSel? +uSel.dataset.index : -1;
    const lSel = document.querySelector('.lower-circles .radio-circle.selected');
    data.lower.selected = lSel? +lSel.dataset.index : -1;

    data.upper.decision = document.getElementById('upperDecisionBtn').classList.contains('active');
    data.lower.decision = document.getElementById('lowerDecisionBtn').classList.contains('active');

    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='一本勝'?'ippon':dt==='延長'?'extend':dt==='引分け'?'draw':'none';
}

// 判定勝ちボタンの処理
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
        updateDecisionButtonsVisibility();
    });
});

document.addEventListener('click',()=>document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m=>m.classList.remove('show')));

document.getElementById('cancelButton').addEventListener('click',()=>{
    if(confirm('試合内容をリセットしますか？')){
        data.upper={name:'',number:'',scores:['▼','▼','▼'],selected:-1,decision:false};
        data.lower={name:'',number:'',scores:['▲','▲','▲'],selected:-1,decision:false};
        data.special='none';
        load();
    }
});

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
            if (confirm('以下の内容に変更しますか？')) {
                window.location.href = 'match-confirm.php';
            }
            // キャンセル時は何もしない
        } else {
            alert('保存失敗');
        }
    } catch (e) {
        alert('エラー発生');
        console.error(e);
    }
};


load();