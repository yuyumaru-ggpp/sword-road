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
            'lower' => ['team'=>'','name'=>'','scores'=>['▼','▼','▼'],'selected'=>-1],
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
    <link rel="stylesheet" href="../css/team-match-detail-style.css">
</head>
<body>
<div class="container">
    <div class="rep-header" id="repHeader">代表決定戦</div>
    
    <div class="header">
        <span>団体戦</span>
        <span>〇〇大会</span>
        <span>〇〇部門</span>
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
        <!-- 上段 -->
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
                        <div class="dropdown-item">赤不戦勝</div>
                        <div class="dropdown-item">白不戦勝</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 下段 -->
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
                <button class="bottom-button submit-button" id="submitButton">変更</button>
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

    // ナビゲーションボタンの表示制御
    document.getElementById('nextButton').style.display = (current < 4) ? 'block' : 'none';
    document.getElementById('prevButton').style.display = (current > 0) ? 'block' : 'none';
    document.getElementById('repButton').style.display = (current === 4) ? 'block' : 'none';

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
            lower:{team:'',name:'',scores:['▼','▼','▼'],selected:-1},
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
    try{
        const r=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const j=await r.json();
        if(j.status==='ok'){
            alert('保存完了！');
            // 次の画面に遷移（match-confirm.phpに変更）
            window.location.href='match-confirm.php';
        } else {
            alert('保存失敗');
        }
    }catch(e){ alert('エラー発生'); console.error(e); }
};

load();
</script>
</body>
</html>