<?php
if (!is_dir('data')) mkdir('data', 0777, true);
$jsonFile = 'data/individual_match.json';
$data = [
    'upper' => ['name'=>'','number'=>'','scores'=>['▼','▼','▼'],'selected'=>-1],
    'lower' => ['name'=>'','number'=>'','scores'=>['▲','▲','▲'],'selected'=>-1],
    'special' => 'none'
];

if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $decoded = json_decode($json, true);
    if ($decoded) $data = $decoded;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        file_put_contents($jsonFile, json_encode($input, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo json_encode(['status'=>'ok']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>個人戦試合詳細</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { 
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans','Meiryo',sans-serif; 
    background:#f5f5f5; 
    padding:0.5rem; 
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.container { 
    max-width:1200px;
    width:100%;
    height:98vh;
    max-height:900px;
    background:white; 
    padding:clamp(1rem, 2vh, 2rem) clamp(0.8rem, 2vh, 1.5rem); 
    border-radius:8px; 
    box-shadow:0 10px 30px rgba(0,0,0,0.1); 
    position:relative; 
    display:flex;
    flex-direction:column;
    overflow-y:auto;
}

.header { 
    display:flex; 
    flex-wrap:wrap;
    align-items:center; 
    gap:clamp(0.5rem, 1vh, 1rem); 
    margin-bottom:clamp(1.5rem, 3vh, 2rem);
    padding-top:clamp(0.3rem, 0.8vh, 0.8rem);
    font-size:clamp(1rem, 2.5vh, 1.5rem); 
    font-weight:bold; 
}

.content-wrapper {
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:space-evenly;
    min-height:0;
}

.match-section { 
    display:flex;
    flex-direction:column;
    gap:clamp(0.2rem, 0.4vh, 0.5rem);
    flex-shrink:0;
}

.upper-section {
    margin-bottom:clamp(1.5rem, 3vh, 3rem);
}

.row { 
    display:flex; 
    align-items:center; 
    font-size:clamp(0.9rem, 2vh, 1.2rem); 
    gap:clamp(0.5rem, 1vw, 1rem);
    margin-bottom:0;
    flex-wrap:wrap;
}

.label { 
    min-width:clamp(70px, 10vw, 100px); 
    font-weight:bold; 
}

.value { 
    min-width:clamp(100px, 15vw, 150px); 
    word-break:break-all;
}

.score-display { 
    display:flex; 
    justify-content:center; 
    align-items:center; 
    width:100%;
    padding:0;
    margin:0;
    margin-bottom:clamp(1rem, 2vh, 2rem);
}

.score-group { 
    display:flex; 
    flex-direction:column; 
    align-items:center; 
    gap:clamp(0.25rem, 0.6vh, 0.5rem); 
}

.score-numbers { 
    display:flex; 
    gap:clamp(1rem, 3vw, 2.5rem); 
    font-size:clamp(1rem, 2.5vh, 1.5rem); 
    font-weight:bold; 
}

.score-numbers span { 
    width:clamp(35px, 6vw, 50px); 
    text-align:center; 
}

.radio-circles { 
    display:flex; 
    gap:clamp(1rem, 3vw, 2.5rem); 
}

.radio-circle { 
    width:clamp(28px, 5vw, 40px); 
    height:clamp(28px, 5vw, 40px); 
    border-radius:50%; 
    background:#d1d5db; 
    cursor:pointer; 
    transition:all 0.2s; 
    box-shadow:0 2px 4px rgba(0,0,0,0.1); 
    flex-shrink:0;
}
.radio-circle.selected { 
    background:#ef4444; 
    transform:scale(1.1); 
    box-shadow:0 0 0 3px rgba(239,68,68,0.3); 
}
.radio-circle:hover { opacity:0.9; }

.divider-section { 
    position:relative; 
    margin:clamp(2rem, 3.5vh, 3.5rem) 0; 
    text-align:center; 
    flex-shrink:0;
}

.divider { 
    border:none; 
    border-top:3px dashed #000; 
}

.middle-controls { 
    position:absolute; 
    top:50%; 
    left:50%; 
    transform:translate(-50%,-50%); 
    display:flex; 
    align-items:center; 
    gap:clamp(0.5rem, 1.5vw, 1.5rem); 
    background:white; 
    padding:clamp(0.8rem, 1.5vh, 1.5rem) clamp(0.8rem, 2vw, 1.5rem); 
}

.score-dropdowns { 
    display:flex; 
    gap:clamp(1rem, 3vw, 2.5rem); 
}

.dropdown-container { position:relative; }

.score-dropdown { 
    width:clamp(35px, 6vw, 50px); 
    height:clamp(35px, 6vw, 50px); 
    font-size:clamp(1rem, 2.5vh, 1.5rem); 
    font-weight:bold; 
    background:white; 
    border:2px solid #000; 
    border-radius:8px; 
    cursor:pointer; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    transition:all 0.2s; 
    flex-shrink:0;
}
.score-dropdown:hover { background:#fef3c7; }

.dropdown-menu, .draw-dropdown-menu { 
    display:none; 
    position:absolute; 
    background:white; 
    border:2px solid #000; 
    border-radius:8px; 
    min-width:70px; 
    max-height:clamp(200px, 40vh, 300px); 
    overflow-y:auto; 
    box-shadow:0 8px 20px rgba(0,0,0,0.2); 
    z-index:1000; 
    padding:8px 0; 
}
.dropdown-menu.show, .draw-dropdown-menu.show { display:block; }

.dropdown-item { 
    padding:clamp(8px, 1.5vh, 12px) clamp(12px, 2vw, 18px); 
    font-size:clamp(0.95rem, 2vh, 1.3rem); 
    font-weight:bold; 
    text-align:center; 
    cursor:pointer; 
    user-select:none; 
    transition:all 0.15s; 
}
.dropdown-item:hover { background:#fee2e2; color:#dc2626; }
.dropdown-item:active { background:#ef4444; color:white; }

.draw-container { position:relative; }

.draw-button { 
    padding:clamp(0.4rem, 1vh, 0.6rem) clamp(0.8rem, 2vw, 1.3rem); 
    font-size:clamp(0.85rem, 1.8vh, 1.1rem); 
    background:white; 
    border:2px solid #000; 
    border-radius:8px; 
    font-weight:bold; 
    cursor:pointer; 
    white-space:nowrap;
}
.draw-button:hover { background:#fef3c7; }

.draw-dropdown-menu { 
    right:auto;
    left:50%;
    transform:translateX(-50%);
    top:calc(100% + 4px);
}

.dropdown-menu::-webkit-scrollbar, .draw-dropdown-menu::-webkit-scrollbar { width:6px; }
.dropdown-menu::-webkit-scrollbar-track, .draw-dropdown-menu::-webkit-scrollbar-track { background:#f1f1f1; border-radius:10px; }
.dropdown-menu::-webkit-scrollbar-thumb, .draw-dropdown-menu::-webkit-scrollbar-thumb { background:#c0c0c0; border-radius:10px; }

.bottom-area {
    display:flex;
    flex-direction:column;
    gap:clamp(0.8rem, 1.5vh, 1.5rem);
    margin-top:clamp(0.8rem, 1.5vh, 1.5rem);
    flex-shrink:0;
}

.bottom-right-button { 
    display:flex;
    justify-content:flex-end;
}

.cancel-button { 
    padding:clamp(0.4rem, 1vh, 0.6rem) clamp(1.2rem, 3vw, 2rem); 
    font-size:clamp(0.85rem, 1.8vh, 1.1rem); 
    background:white; 
    border:2px solid #000; 
    border-radius:25px; 
    font-weight:bold; 
    cursor:pointer; 
}

.bottom-buttons { 
    display:flex; 
    justify-content:center; 
    gap:clamp(0.8rem, 2vw, 1.5rem); 
}

.bottom-button { 
    padding:clamp(0.5rem, 1.2vh, 0.7rem) clamp(1.5rem, 4vw, 2.5rem); 
    font-size:clamp(0.9rem, 2vh, 1.2rem); 
    border-radius:25px; 
    font-weight:bold; 
    cursor:pointer; 
    white-space:nowrap;
}

.back-button { background:white; border:2px solid #000; }
.submit-button { background:#3b82f6; color:white; border:2px solid #3b82f6; }
.submit-button:hover { background:#2563eb; }

/* タブレット以下 */
@media (max-width:768px) {
    .header {
        margin-bottom:2rem;
    }
    
    .middle-controls { 
        flex-wrap:wrap;
        justify-content:center;
        gap:0.5rem;
        padding:1.5rem 1rem;
    }
    
    .label {
        width:100%;
    }
    
    .value {
        width:100%;
    }
    
    .divider-section {
        margin:3rem 0;
    }
    
    .score-display {
        padding:0;
        margin-bottom:1.5rem;
    }
    
    .match-section {
        gap:0.8rem;
        padding-bottom:1rem;
    }
}

/* 小画面用の調整 */
@media (max-height:700px) {
    .container { padding:0.5rem; }
    .header { margin-bottom:0.3rem; font-size:0.9rem; }
    .row { font-size:0.85rem; gap:0.3rem; }
    .score-numbers, .radio-circles, .score-dropdowns { gap:0.8rem; }
    .divider-section { margin:0.5rem 0; }
    .bottom-area { gap:0.3rem; margin-top:0.3rem; }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <span>個人戦</span>
        <span>〇〇大会</span>
        <span>〇〇部門</span>
    </div>

    <div class="content-wrapper">
        <!-- 上段 -->
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

        <!-- 中央ライン -->
        <div class="divider-section">
            <hr class="divider">
            <div class="middle-controls">
                <div class="score-dropdowns upper-scores">
                    <?php for($i=0;$i<3;$i++): ?>
                    <div class="dropdown-container">
                        <button class="score-dropdown">▼</button>
                        <div class="dropdown-menu">
                            <?php foreach(['×','コ','ド','反','ツ','〇'] as $val): ?>
                            <div class="dropdown-item" data-val="<?=$val?>"><?=$val?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
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

        <!-- 下段 -->
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
                <button class="bottom-button submit-button" id="submitButton">送信・保存</button>
            </div>
        </div>
    </div>
</div>

<script>
const data = <?=json_encode($data, JSON_UNESCAPED_UNICODE)?>;

function load() {
    document.querySelector('.upper-name').textContent = data.upper.name||'───';
    document.querySelector('.upper-number').textContent = data.upper.number||'───';
    document.querySelector('.lower-name').textContent = data.lower.name||'───';
    document.querySelector('.lower-number').textContent = data.lower.number||'───';

    document.querySelectorAll('.upper-scores .score-dropdown').forEach((b,i)=>b.textContent=(data.upper.scores&&data.upper.scores[i])||'▼');

    document.querySelectorAll('.upper-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.upper.selected===i));
    document.querySelectorAll('.lower-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.lower.selected===i));

    const special = data.special||'none';
    const text = special==='ippon'?'一本勝':special==='extend'?'延長':special==='draw'?'引分け':'-';
    document.getElementById('drawButton').textContent=text;
}

function saveLocal() {
    data.upper.scores = Array.from(document.querySelectorAll('.upper-scores .score-dropdown')).map(b=>b.textContent);
    const uSel = document.querySelector('.upper-circles .radio-circle.selected');
    data.upper.selected = uSel? +uSel.dataset.index : -1;
    const lSel = document.querySelector('.lower-circles .radio-circle.selected');
    data.lower.selected = lSel? +lSel.dataset.index : -1;

    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='一本勝'?'ippon':dt==='延長'?'extend':dt==='引分け'?'draw':'none';
}

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
    });
});

document.addEventListener('click',()=>document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m=>m.classList.remove('show')));

document.getElementById('cancelButton').addEventListener('click',()=>{
    if(confirm('試合内容をリセットしますか？')){
        data.upper={name:'',number:'',scores:['▼','▼','▼'],selected:-1};
        data.lower={name:'',number:'',scores:['▲','▲','▲'],selected:-1};
        data.special='none';
        load();
    }
});

document.getElementById('submitButton').onclick=async()=>{
    saveLocal();
    try{
        const r=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const j=await r.json();
        alert(j.status==='ok'?'保存完了！':'保存失敗');
    }catch(e){ alert('エラー発生'); console.error(e); }
};

load();
</script>
</body>
</html>