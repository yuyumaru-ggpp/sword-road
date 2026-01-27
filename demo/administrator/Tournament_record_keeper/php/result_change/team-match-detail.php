<?php
if (!is_dir('data')) mkdir('data', 0777, true);
$jsonFile = 'data/matches.json';
$data = ['positions'=>[]];

if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $decoded = json_decode($json, true);
    if ($decoded) $data = $decoded;
}

$positions = ['先鋒','次鋒','中堅','副将','大将','代表決定戦'];
foreach ($positions as $pos) {
    if (!isset($data['positions'][$pos])) {
        $data['positions'][$pos] = [
            'upper' => ['team'=>'','name'=>'','scores'=>['▼','▼','▼'],'selected'=>-1],
            'lower' => ['team'=>'','name'=>'','scores'=>['▲','▲','▲'],'selected'=>-1],
            'special' => 'none'
        ];
    }
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
<title>団体戦試合詳細</title>
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
    height:100vh;
    max-height:100vh;
    background:white; 
    padding:clamp(0.5rem, 1.5vh, 2rem) clamp(0.8rem, 2vh, 1.5rem); 
    border-radius:8px; 
    box-shadow:0 10px 30px rgba(0,0,0,0.1); 
    position:relative; 
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

.rep-header { 
    position:absolute; 
    top:0;
    left:50%;
    transform:translateX(-50%);
    font-size:clamp(1.2rem, 3vh, 2rem); 
    font-weight:bold; 
    color:#dc2626; 
    background:#fee2e2;
    padding:0.5rem 2rem; 
    text-align:center; 
    display:none; 
    z-index:100; 
    border-radius:0 0 12px 12px;
    box-shadow:0 4px 8px rgba(220,38,38,0.2);
}
.rep-header.active { display:block; }

.header { 
    display:flex; 
    flex-wrap:wrap;
    align-items:center; 
    gap:clamp(0.3rem, 0.8vh, 1rem); 
    margin-bottom:clamp(0.3rem, 1vh, 1.5rem);
    padding-top:clamp(0.2rem, 0.5vh, 0.8rem);
    padding-right:clamp(100px, 20vw, 180px);
    font-size:clamp(0.85rem, 2vh, 1.5rem); 
    font-weight:bold; 
    flex-shrink:0;
}

.top-right-controls { 
    position:absolute; 
    right:clamp(0.8rem, 2vh, 1.5rem); 
    top:clamp(0.8rem, 2vh, 1.5rem); 
    display:flex; 
    flex-direction:column; 
    gap:0.5rem; 
    align-items:flex-end; 
    z-index:10;
}

.position-button { 
    padding:clamp(0.4rem, 1vh, 0.6rem) clamp(1rem, 3vw, 2rem); 
    font-size:clamp(0.9rem, 2vh, 1.3rem); 
    background:white; 
    border:2px solid #000; 
    border-radius:8px; 
    font-weight:bold; 
    white-space:nowrap;
}

.nav-buttons { display:flex; gap:0.5rem; }

.nav-button { 
    padding:clamp(0.4rem, 1vh, 0.6rem) clamp(0.8rem, 2vw, 1.2rem); 
    font-size:clamp(0.85rem, 1.8vh, 1.1rem); 
    background:#ef4444; 
    color:white; 
    border:none; 
    border-radius:8px; 
    cursor:pointer; 
    font-weight:bold; 
    white-space:nowrap;
}
.nav-button:hover { background:#dc2626; }

#repButton {
    background:#dc2626;
}
#repButton:hover {
    background:#b91c1c;
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
    gap:clamp(0.15rem, 0.3vh, 0.5rem);
    flex-shrink:0;
}

.upper-section {
    margin-bottom:clamp(0.5rem, 1.5vh, 3rem);
}

.row { 
    display:flex; 
    align-items:center; 
    font-size:clamp(0.75rem, 1.8vh, 1.2rem); 
    gap:clamp(0.3rem, 0.8vw, 1rem);
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
    margin-bottom:clamp(0.5rem, 1.2vh, 2rem);
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
    height:clamp(35px, 6vw, 50px);
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    box-sizing:border-box;
}

.radio-circles { 
    display:flex; 
    gap:clamp(1rem, 3vw, 2.5rem);
}

.radio-circle { 
    width:clamp(35px, 6vw, 50px); 
    height:clamp(35px, 6vw, 50px); 
    border-radius:50%; 
    background:#d1d5db; 
    cursor:pointer; 
    transition:all 0.2s; 
    box-shadow:0 2px 4px rgba(0,0,0,0.1); 
    flex-shrink:0;
    box-sizing:border-box;
}
.radio-circle.selected { 
    background:#ef4444; 
    transform:scale(1.1); 
    box-shadow:0 0 0 3px rgba(239,68,68,0.3); 
}
.radio-circle:hover { opacity:0.9; }

.divider-section { 
    position:relative; 
    margin:clamp(0.8rem, 2vh, 3.5rem) 0; 
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
    background:white; 
    padding:clamp(0.8rem, 1.5vh, 1.5rem) clamp(0.8rem, 2vw, 1.5rem); 
}

.score-dropdowns { 
    display:flex; 
    gap:clamp(1rem, 3vw, 2.5rem);
}

.dropdown-container { 
    position:relative; 
    width:clamp(35px, 6vw, 50px);
    height:clamp(35px, 6vw, 50px);
    flex-shrink:0;
    box-sizing:border-box;
}

.score-dropdown { 
    width:100%;
    height:100%;
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
    box-sizing:border-box;
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

.draw-container-wrapper {
    position:absolute;
    top:50%;
    left:calc(50% + clamp(8rem, 15vw, 12rem));
    transform:translateY(-50%);
    background:white;
    padding:clamp(0.8rem, 1.5vh, 1.5rem) 0;
}

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
    gap:clamp(0.4rem, 1vh, 1.5rem);
    margin-top:clamp(0.4rem, 1vh, 1.5rem);
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

@media (max-width:768px) {
    .header {
        padding-right:0;
        margin-bottom:3rem;
    }
    
    .top-right-controls {
        position:relative;
        right:auto;
        top:auto;
        margin:0 auto 1rem;
        width:100%;
        align-items:center;
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

@media (max-height:700px) {
    .container { padding:0.3rem; }
    .header { margin-bottom:0.2rem; font-size:0.75rem; }
    .row { font-size:0.7rem; gap:0.2rem; }
    .score-numbers, .radio-circles, .score-dropdowns { gap:0.5rem; }
    .divider-section { margin:0.3rem 0; }
    .bottom-area { gap:0.2rem; margin-top:0.2rem; }
    .middle-controls { padding:0.5rem; }
    .rep-header { font-size:1rem; padding:0.3rem 1rem; }
}

@media (max-height:600px) {
    .container { padding:0.2rem; }
    .header { margin-bottom:0.1rem; font-size:0.7rem; gap:0.2rem; }
    .row { font-size:0.65rem; gap:0.15rem; }
    .label { min-width:60px; }
    .value { min-width:80px; }
    .score-numbers { font-size:0.9rem; gap:0.4rem; }
    .score-numbers span { width:28px; height:28px; }
    .radio-circles { gap:0.4rem; }
    .radio-circle { width:28px; height:28px; }
    .score-dropdowns { gap:0.4rem; }
    .score-dropdown { font-size:0.9rem; }
    .dropdown-container { width:28px; height:28px; }
    .divider-section { margin:0.2rem 0; }
    .middle-controls { padding:0.3rem; gap:0.3rem; }
    .bottom-area { gap:0.15rem; margin-top:0.15rem; }
    .bottom-button { padding:0.3rem 1rem; font-size:0.8rem; }
    .rep-header { font-size:0.9rem; padding:0.2rem 0.8rem; }
}
</style>
</head>
<body>
<div class="container">
    <div class="rep-header" id="repHeader">代表決定戦</div>
    
    <div class="header">
        <span>団体戦</span>
        <span>練習</span>
    </div>

    <div class="top-right-controls">
        <button class="position-button" id="positionButton">先鋒</button>
        <div class="nav-buttons">
            <button class="nav-button" id="nextButton">次へ</button>
            <button class="nav-button" id="prevButton">戻る</button>
            <button class="nav-button" id="repButton" style="display:none;">代表決定戦</button>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="match-section upper-section">
            <div class="row">
                <div class="label">チーム名</div>
                <div class="value upper-team">───</div>
            </div>
            
            <div class="row">
                <div class="label">名前</div>
                <div class="value upper-name">───</div>
                <span style="color:#ef4444; font-size:clamp(1.8rem, 3.5vh, 2.5rem); font-weight:bold; margin-left:1rem;">■</span>
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

        <div class="divider-section">
            <hr class="divider">
            <div class="middle-controls">
                <div class="score-dropdowns upper-scores">
                    <?php for($i=0;$i<3;$i++): ?>
                    <div class="dropdown-container">
                        <button class="score-dropdown">▼</button>
                        <div class="dropdown-menu">
                            <?php foreach(['▼','×','メ','コ','ド','反','ツ','〇'] as $val): ?>
                            <div class="dropdown-item" data-val="<?=$val?>"><?=$val?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="draw-container-wrapper">
                <div class="draw-container">
                    <button class="draw-button" id="drawButton">-</button>
                    <div class="draw-dropdown-menu" id="drawMenu">
                        <div class="dropdown-item">-</div>
                        <div class="dropdown-item">引分け</div>
                        <div class="dropdown-item">一本勝</div>
                        <div class="dropdown-item">延長</div>
                        <div class="dropdown-item">赤不戦勝</div>
                        <div class="dropdown-item">白不戦勝</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="match-section">
            <div class="row">
                <div class="label">名前</div>
                <div class="value lower-name">───</div>
                <span style="color:#fff; font-size:clamp(1.8rem, 3.5vh, 2.5rem); font-weight:bold; margin-left:1rem; text-shadow: -3px -3px 0 #000, 3px -3px 0 #000, -3px 3px 0 #000, 3px 3px 0 #000;">■</span>
            </div>
            
            <div class="row">
                <div class="label">チーム名</div>
                <div class="value lower-team">───</div>
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
                <button class="bottom-button submit-button" id="submitButton" style="display:none;">変更</button>
            </div>
        </div>
    </div>
</div>

<script>
const positions = ['先鋒','次鋒','中堅','副将','大将','代表決定戦'];
let current = 0;
const data = <?=json_encode($data, JSON_UNESCAPED_UNICODE)?>;

function load() {
    const key = positions[current];
    const p = data.positions[key];
    const isRepMatch = (key === '代表決定戦');

    document.getElementById('repHeader').classList.toggle('active', isRepMatch);
    document.getElementById('positionButton').textContent = key;

    document.getElementById('nextButton').style.display = (current < 4) ? 'block' : 'none';
    document.getElementById('prevButton').style.display = (current > 0) ? 'block' : 'none';
    document.getElementById('repButton').style.display = (current === 4) ? 'block' : 'none';
    document.getElementById('submitButton').style.display = (current >= 4) ? 'block' : 'none';

    document.querySelector('.upper-team').textContent = p.upper.team||'───';
    document.querySelector('.upper-name').textContent = p.upper.name||'───';
    document.querySelector('.lower-team').textContent = p.lower.team||'───';
    document.querySelector('.lower-name').textContent = p.lower.name||'───';

    const upperNumbers = document.querySelectorAll('.upper-numbers span');
    const lowerNumbers = document.querySelectorAll('.lower-numbers span');
    const upperCircles = document.querySelectorAll('.upper-circles .radio-circle');
    const lowerCircles = document.querySelectorAll('.lower-circles .radio-circle');
    const upperDropdowns = document.querySelectorAll('.upper-scores .dropdown-container');

    if (isRepMatch) {
        upperNumbers.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
        lowerNumbers.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
        upperCircles.forEach((el, i) => el.style.display = i === 0 ? 'flex' : 'none');
        lowerCircles.forEach((el, i) => el.style.display = i === 0 ? 'flex' : 'none');
        upperDropdowns.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
    } else {
        upperNumbers.forEach(el => el.style.display = 'block');
        lowerNumbers.forEach(el => el.style.display = 'block');
        upperCircles.forEach(el => el.style.display = 'flex');
        lowerCircles.forEach(el => el.style.display = 'flex');
        upperDropdowns.forEach(el => el.style.display = 'block');
    }

    document.querySelectorAll('.upper-scores .score-dropdown').forEach((b,i)=>b.textContent=(p.upper.scores&&p.upper.scores[i])||'▼');

    document.querySelectorAll('.upper-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',p.upper.selected===i));
    document.querySelectorAll('.lower-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',p.lower.selected===i));

    const special = p.special||'none';
    const text = special==='ippon'?'一本勝':special==='extend'?'延長':special==='draw'?'引分け':special==='red_win'?'赤不戦勝':special==='white_win'?'白不戦勝':'-';
    document.getElementById('drawButton').textContent=text;
}

function saveLocal() {
    const key = positions[current];
    data.positions[key].upper.scores = Array.from(document.querySelectorAll('.upper-scores .score-dropdown')).map(b=>b.textContent);
    const uSel = document.querySelector('.upper-circles .radio-circle.selected');
    data.positions[key].upper.selected = uSel? +uSel.dataset.index : -1;
    const lSel = document.querySelector('.lower-circles .radio-circle.selected');
    data.positions[key].lower.selected = lSel? +lSel.dataset.index : -1;

    const dt = document.getElementById('drawButton').textContent;
    data.positions[key].special = dt==='一本勝'?'ippon':dt==='延長'?'extend':dt==='引分け'?'draw':dt==='赤不戦勝'?'red_win':dt==='白不戦勝'?'white_win':'none';
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
    if(confirm(positions[current]+' をリセットしますか？')){
        data.positions[positions[current]]={
            upper:{team:'',name:'',scores:['▼','▼','▼'],selected:-1},
            lower:{team:'',name:'',scores:['▲','▲','▲'],selected:-1},
            special:'none'
        };
        load();
    }
});

document.getElementById('nextButton').onclick=()=>{ saveLocal(); if(current<5) current++; load(); };
document.getElementById('prevButton').onclick=()=>{ saveLocal(); if(current>0) current--; load(); };
document.getElementById('repButton').onclick=()=>{ saveLocal(); current=5; load(); };

document.getElementById('submitButton').onclick=async()=>{
    saveLocal();

    if(!confirm('以下の内容に変更しますか？')){
    return;
}
    try{
        const r=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const j=await r.json();
        if(j.status==='ok'){
            window.location.href = 'match-confirm.php';
        }else{
            alert('保存失敗');
        }
    }catch(e){ alert('エラー発生'); console.error(e); }
};

load();
</script>
</body>
</html>