<?php
session_start();

/* ===============================
   セッションチェック
=============================== */
if (
    !isset(
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number'],
        $_SESSION['team_red_id'],
        $_SESSION['team_white_id']
    )
) {
    header('Location: match_input.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id   = $_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];
$team_red_id   = $_SESSION['team_red_id'];
$team_white_id = $_SESSION['team_white_id'];

/* ===============================
   DB接続
=============================== */
$dsn = "mysql:host=localhost;port=3307;dbname=kendo_support_system;charset=utf8mb4";
$pdo = new PDO($dsn, "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

/* ===============================
   大会・部門・チーム情報取得
=============================== */
$sql = "
    SELECT
        t.title AS tournament_name,
        d.name  AS division_name
    FROM tournaments t
    JOIN departments d ON d.tournament_id = t.id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $division_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    exit('試合情報が取得できません');
}

// セッションからチーム情報を取得
$team_red_name = $_SESSION['team_red_name'] ?? '';
$team_white_name = $_SESSION['team_white_name'] ?? '';

// オーダー情報から先鋒の選手を取得
$red_order = $_SESSION['team_red_order'] ?? [];
$white_order = $_SESSION['team_white_order'] ?? [];

$red_player_name = '';
$white_player_name = '';

if (isset($red_order['先鋒'])) {
    $sql = "SELECT name FROM players WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $red_order['先鋒']]);
    $red_player_name = $stmt->fetchColumn();
}

if (isset($white_order['先鋒'])) {
    $sql = "SELECT name FROM players WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $white_order['先鋒']]);
    $white_player_name = $stmt->fetchColumn();
}

/* ===============================
   POST（試合結果保存）
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'ng', 'message' => 'Invalid input']);
        exit;
    }

    // セッションに保存
    if (!isset($_SESSION['match_results'])) {
        $_SESSION['match_results'] = [];
    }
    $_SESSION['match_results']['先鋒'] = $input;
    
    echo json_encode(['status' => 'ok']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>団体戦 先鋒</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

.position-header { 
    position:absolute; 
    top:0;
    left:50%;
    transform:translateX(-50%);
    font-size:clamp(1.5rem, 3vh, 2.5rem); 
    font-weight:bold; 
    color:#dc2626; 
    background:#fee2e2;
    padding:0.8rem 3rem; 
    text-align:center; 
    z-index:100; 
    border-radius:0 0 12px 12px;
    box-shadow:0 4px 8px rgba(220,38,38,0.2);
}

.header { 
    display:flex; 
    flex-wrap:wrap;
    align-items:center; 
    gap:clamp(0.5rem, 1vh, 1rem); 
    margin-bottom:clamp(1.5rem, 3vh, 2rem);
    padding-top:clamp(3rem, 6vh, 5rem);
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
.next-button { background:#3b82f6; color:white; border:2px solid #3b82f6; }
.next-button:hover { background:#2563eb; }
</style>
</head>

<body>
<div class="container">
    <div class="position-header">先鋒</div>
    
    <div class="header">
        <span>団体戦</span>
        <span><?= htmlspecialchars($info['tournament_name']) ?></span>
        <span><?= htmlspecialchars($info['division_name']) ?></span>
    </div>

    <div class="content-wrapper">
        <!-- 上段 (赤) -->
        <div class="match-section upper-section">
            <div class="row">
                <div class="label">チーム名</div>
                <div class="value"><?= htmlspecialchars($team_red_name) ?></div>
                <span style="color:#ef4444; font-size:clamp(1.8rem, 3.5vh, 2.5rem); font-weight:bold; margin-left:1rem;">■</span>
            </div>
            
            <div class="row">
                <div class="label">名前</div>
                <div class="value"><?= htmlspecialchars($red_player_name ?: '───') ?></div>
            </div>

            <div class="score-display">
                <div class="score-group">
                    <div class="score-numbers">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                    </div>
                    <div class="radio-circles red-circles">
                        <div class="radio-circle" data-index="0"></div>
                        <div class="radio-circle" data-index="1"></div>
                        <div class="radio-circle" data-index="2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 中央 -->
        <div class="divider-section">
            <hr class="divider">
            
            <div class="middle-controls">
                <div class="score-dropdowns">
                    <div class="dropdown-container">
                        <div class="score-dropdown">▼</div>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="▼">▼</div>
                            <div class="dropdown-item" data-val="面">面</div>
                            <div class="dropdown-item" data-val="小手">小手</div>
                            <div class="dropdown-item" data-val="胴">胴</div>
                            <div class="dropdown-item" data-val="突">突</div>
                            <div class="dropdown-item" data-val="×">×</div>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <div class="score-dropdown">▼</div>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="▼">▼</div>
                            <div class="dropdown-item" data-val="面">面</div>
                            <div class="dropdown-item" data-val="小手">小手</div>
                            <div class="dropdown-item" data-val="胴">胴</div>
                            <div class="dropdown-item" data-val="突">突</div>
                            <div class="dropdown-item" data-val="×">×</div>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <div class="score-dropdown">▼</div>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" data-val="▼">▼</div>
                            <div class="dropdown-item" data-val="面">面</div>
                            <div class="dropdown-item" data-val="小手">小手</div>
                            <div class="dropdown-item" data-val="胴">胴</div>
                            <div class="dropdown-item" data-val="突">突</div>
                            <div class="dropdown-item" data-val="×">×</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="draw-container-wrapper">
                <div class="draw-container">
                    <button type="button" class="draw-button" id="drawButton">-</button>
                    <div class="draw-dropdown-menu" id="drawMenu">
                        <div class="dropdown-item">一本勝</div>
                        <div class="dropdown-item">延長</div>
                        <div class="dropdown-item">引分け</div>
                        <div class="dropdown-item">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 下段 (白) -->
        <div class="match-section lower-section">
            <div class="score-display">
                <div class="score-group">
                    <div class="radio-circles white-circles">
                        <div class="radio-circle" data-index="0"></div>
                        <div class="radio-circle" data-index="1"></div>
                        <div class="radio-circle" data-index="2"></div>
                    </div>
                    <div class="score-numbers">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="label">名前</div>
                <div class="value"><?= htmlspecialchars($white_player_name ?: '───') ?></div>
                <span style="color:#fff; font-size:clamp(1.8rem, 3.5vh, 2.5rem); font-weight:bold; margin-left:1rem; text-shadow: -3px -3px 0 #000, 3px -3px 0 #000, -3px 3px 0 #000, 3px 3px 0 #000;">■</span>
            </div>
            
            <div class="row">
                <div class="label">チーム名</div>
                <div class="value"><?= htmlspecialchars($team_white_name) ?></div>
            </div>
        </div>
    </div>

    <div class="bottom-area">
        <div class="bottom-right-button">
            <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
        </div>

        <div class="bottom-buttons">
            <button type="button" class="bottom-button back-button" onclick="history.back()">戻る</button>
            <button type="button" class="bottom-button next-button" id="nextButton">次へ（次鋒）</button>
        </div>
    </div>
</div>

<script>
const data = {
    red: {
        scores: ['▼','▼','▼'],
        selected: -1
    },
    white: {
        scores: ['▲','▲','▲'],
        selected: -1
    },
    special: 'none'
};

function load() {
    document.querySelectorAll('.middle-controls .score-dropdown').forEach((b,i)=>b.textContent=data.red.scores[i]);
    document.querySelectorAll('.red-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.red.selected===i));
    document.querySelectorAll('.white-circles .radio-circle').forEach((c,i)=>c.classList.toggle('selected',data.white.selected===i));
    
    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='一本勝'?'ippon':dt==='延長'?'extend':dt==='引分け'?'draw':'none';
}

function saveLocal() {
    data.red.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown')).map(b=>b.textContent);
    const rSel = document.querySelector('.red-circles .radio-circle.selected');
    data.red.selected = rSel? +rSel.dataset.index : -1;
    const wSel = document.querySelector('.white-circles .radio-circle.selected');
    data.white.selected = wSel? +wSel.dataset.index : -1;
    
    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='一本勝'?'ippon':dt==='延長'?'extend':dt==='引分け'?'draw':'none';
}

for (let i = 0; i < 3; i++) {
    const red = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
    const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);

    red.addEventListener('click', () => {
        if (red.classList.contains('selected')) {
            red.classList.remove('selected');
        } else {
            red.classList.add('selected');
            white.classList.remove('selected');
        }
    });

    white.addEventListener('click', () => {
        if (white.classList.contains('selected')) {
            white.classList.remove('selected');
        } else {
            white.classList.add('selected');
            red.classList.remove('selected');
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
    if(confirm('入力内容をリセットしますか?')){
        data.red={scores:['▼','▼','▼'],selected:-1};
        data.white={scores:['▲','▲','▲'],selected:-1};
        data.special='none';
        load();
    }
});

document.getElementById('nextButton').onclick=async()=>{
    saveLocal();
    try{
        const r=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const j=await r.json();
        if(j.status==='ok'){
            window.location.href = 'team-match-jiho.php';
        } else {
            alert('保存失敗');
        }
    }catch(e){ 
        alert('エラー発生'); 
        console.error(e); 
    }
};

load();
</script>
</body>
</html>