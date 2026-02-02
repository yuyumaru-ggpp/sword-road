<?php
require_once 'team_db.php';

// セッションチェック
checkTeamSessionWithResults();

// セッション変数を取得
$vars = getTeamVariables();
$tournament_id = $vars['tournament_id'];
$division_id   = $vars['division_id'];
$match_number  = $vars['match_number'];
$team_red_id   = $vars['team_red_id'];
$team_white_id = $vars['team_white_id'];


/* 大会・部門情報取得 */
$sql = "SELECT t.title AS tournament_name, d.name AS division_name
        FROM tournaments t JOIN departments d ON d.tournament_id = t.id
        WHERE d.id = :division_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $_SESSION['division_id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// チーム名を取得
$sql = "SELECT name FROM teams WHERE id = :team_id";
$stmt = $pdo->prepare($sql);

$stmt->execute([':team_id' => $team_red_id]);
$team_red_name = $stmt->fetchColumn();

$stmt->execute([':team_id' => $team_white_id]);
$team_white_name = $stmt->fetchColumn();

// 試合に出た選手を取得
$red_order = $_SESSION['team_red_order'] ?? [];
$white_order = $_SESSION['team_white_order'] ?? [];

$positions = ['先鋒', '次鋒', '代表決定戦', '副将', '大将'];
$red_players = [];
$white_players = [];

foreach ($positions as $pos) {
    if (isset($red_order[$pos]) && $red_order[$pos]) {
        $sql = "SELECT id, name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $red_order[$pos]]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($player) {
            $red_players[] = ['id' => $player['id'], 'name' => $player['name'], 'position' => $pos];
        }
    }
    
    if (isset($white_order[$pos]) && $white_order[$pos]) {
        $sql = "SELECT id, name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $white_order[$pos]]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($player) {
            $white_players[] = ['id' => $player['id'], 'name' => $player['name'], 'position' => $pos];
        }
    }
}

// セッションから保存済みデータを取得
$savedData = $_SESSION['match_results']['代表決定戦'] ?? null;

/* POST処理 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'ng']);
        exit;
    }
    $_SESSION['match_results']['代表決定戦'] = $input;
    echo json_encode(['status' => 'ok']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>代表決定戦</title>
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
    color:#fff;
    background:#dc2626;
    padding:0.8rem 3rem; 
    text-align:center; 
    z-index:100; 
    border-radius:0 0 12px 12px;
    box-shadow:0 4px 8px rgba(220,38,38,0.5);
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
    gap:clamp(0.5rem, 1vh, 1rem);
    flex-shrink:0;
}
.upper-section { margin-bottom:clamp(1.5rem, 3vh, 3rem); }
.row { 
    display:flex; 
    align-items:center; 
    font-size:clamp(0.9rem, 2vh, 1.2rem); 
    gap:clamp(0.5rem, 1vw, 1rem);
    flex-wrap:wrap;
}
.label { 
    min-width:clamp(100px, 12vw, 120px); 
    font-weight:bold; 
}
.player-select {
    flex:1;
    max-width:300px;
    padding:0.75rem 1rem;
    font-size:1.1rem;
    border:2px solid #d1d5db;
    border-radius:8px;
    cursor:pointer;
}
.player-select:focus {
    outline:none;
    border-color:#3b82f6;
}
.score-display { 
    display:flex; 
    justify-content:center; 
    align-items:center; 
    width:100%;
    margin:clamp(1.5rem, 3vh, 2rem) 0;
}
.score-group { 
    display:flex; 
    flex-direction:column; 
    align-items:center; 
    gap:clamp(0.5rem, 1vh, 1rem);
}
.score-number { 
    width:clamp(50px, 8vw, 70px); 
    height:clamp(50px, 8vw, 70px);
    font-size:clamp(1.5rem, 3vh, 2rem); 
    font-weight:bold;
    display:flex;
    align-items:center;
    justify-content:center;
}
.radio-circle { 
    width:clamp(50px, 8vw, 70px); 
    height:clamp(50px, 8vw, 70px); 
    border-radius:50%; 
    background:#d1d5db; 
    cursor:pointer; 
    transition:all 0.2s; 
    box-shadow:0 2px 4px rgba(0,0,0,0.1); 
}
.radio-circle.selected { 
    background:#ef4444; 
    transform:scale(1.15); 
    box-shadow:0 0 0 4px rgba(239,68,68,0.3); 
}
.radio-circle:hover { opacity:0.9; }
.divider-section { 
    position:relative; 
    margin:clamp(2rem, 4vh, 4rem) 0; 
    text-align:center; 
}
.divider { 
    border:none; 
    border-top:4px dashed #000; 
}
.middle-controls { 
    position:absolute; 
    top:50%; 
    left:50%; 
    transform:translate(-50%,-50%); 
    background:white; 
    padding:clamp(1rem, 2vh, 2rem); 
}
.dropdown-container { 
    position:relative; 
    width:clamp(50px, 8vw, 70px);
    height:clamp(50px, 8vw, 70px);
}
.score-dropdown { 
    width:100%;
    height:100%;
    font-size:clamp(1.5rem, 3vh, 2rem); 
    font-weight:bold; 
    background:white; 
    border:3px solid #000; 
    border-radius:8px; 
    cursor:pointer; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
}
.score-dropdown:hover { background:#fef3c7; }
.dropdown-menu { 
    display:none; 
    position:absolute; 
    background:white; 
    border:3px solid #000; 
    border-radius:8px; 
    min-width:80px; 
    box-shadow:0 8px 20px rgba(0,0,0,0.3); 
    z-index:1000; 
    padding:8px 0; 
}
.dropdown-menu.show { display:block; }
.dropdown-item { 
    padding:clamp(10px, 2vh, 14px) clamp(14px, 2.5vw, 20px); 
    font-size:clamp(1.1rem, 2.2vh, 1.5rem); 
    font-weight:bold; 
    text-align:center; 
    cursor:pointer; 
}
.dropdown-item:hover { background:#fee2e2; color:#dc2626; }
.bottom-area {
    display:flex;
    flex-direction:column;
    gap:clamp(1rem, 2vh, 2rem);
    margin-top:clamp(1rem, 2vh, 2rem);
}
.bottom-buttons { 
    display:flex; 
    justify-content:center; 
    gap:clamp(1rem, 2vw, 2rem); 
}
.bottom-button { 
    padding:clamp(0.6rem, 1.5vh, 0.9rem) clamp(2rem, 5vw, 3rem); 
    font-size:clamp(1rem, 2.2vh, 1.3rem); 
    border-radius:25px; 
    font-weight:bold; 
    cursor:pointer; 
}
.back-button { background:white; border:3px solid #000; }
.submit-button { background:#22c55e; color:white; border:3px solid #22c55e; }
.submit-button:hover { background:#16a34a; }

/* 途中経過表示 */
.score-summary {
    position: fixed;
    top: 1rem;
    right: 1rem;
    background: white;
    border: 3px solid #000;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 200px;
}

.score-summary-title {
    font-size: 1rem;
    font-weight: bold;
    text-align: center;
    margin-bottom: 0.75rem;
    border-bottom: 2px solid #000;
    padding-bottom: 0.5rem;
}

.score-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.score-summary-label {
    font-weight: bold;
    color: #374151;
}

.score-summary-values {
    display: flex;
    gap: 1rem;
    font-weight: bold;
}

.score-red {
    color: #dc2626;
}

.score-white {
    color: #374151;
}

@media (max-width: 768px) {
    .score-summary {
        position: static;
        margin: 1rem auto;
        max-width: 300px;
    }
}


</style>
</head>
<body>
<div class="container">
    <div class="position-header">代表決定戦</div>
    
    <div class="header">
        <span>団体戦</span>
        <span><?= htmlspecialchars($info['tournament_name']) ?></span>
        <span><?= htmlspecialchars($info['division_name']) ?></span>
    </div>

    <div class="content-wrapper">
        <div class="match-section upper-section">
            <div class="row">
                <div class="label">チーム名</div>
                <div style="font-size:1.2rem; font-weight:bold;"><?= htmlspecialchars($team_red_name) ?></div>
                <span style="color:#ef4444; font-size:2rem; margin-left:1rem;">■</span>
            </div>
            <div class="row">
                <div class="label">選手選択</div>
                <select class="player-select" id="redPlayerSelect">
                    <option value="">選手を選択</option>
                    <?php foreach ($red_players as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['position']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="score-display">
                <div class="score-group">
                    <div class="score-number">1</div>
                    <div class="radio-circle red-circle"></div>
                </div>
            </div>
        </div>

        <div class="divider-section">
            <hr class="divider">
            <div class="middle-controls">
                <div class="dropdown-container">
                    <div class="score-dropdown">▼</div>
                    <div class="dropdown-menu">
                        <div class="dropdown-item" data-val="▼">▼</div>
                        <div class="dropdown-item" data-val="メ">メ</div>
                        <div class="dropdown-item" data-val="コ">コ</div>
                        <div class="dropdown-item" data-val="ド">ド</div>
                        <div class="dropdown-item" data-val="ツ">ツ</div>
                        <div class="dropdown-item" data-val="×">×</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="match-section">
            <div class="score-display">
                <div class="score-group">
                    <div class="radio-circle white-circle"></div>
                    <div class="score-number">1</div>
                </div>
            </div>
            <div class="row">
                <div class="label">選手選択</div>
                <select class="player-select" id="whitePlayerSelect">
                    <option value="">選手を選択</option>
                    <?php foreach ($white_players as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['position']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="label">チーム名</div>
                <div style="font-size:1.2rem; font-weight:bold;"><?= htmlspecialchars($team_white_name) ?></div>
                <span style="color:#fff; font-size:2rem; margin-left:1rem; text-shadow: -2px -2px 0 #000, 2px -2px 0 #000, -2px 2px 0 #000, 2px 2px 0 #000;">■</span>
            </div>
        </div>
    </div>

    <div class="bottom-area">
        <div class="bottom-buttons">
            <button type="button" class="bottom-button back-button" onclick="history.back()">戻る</button>
            <button type="button" class="bottom-button submit-button" id="submitButton">送信（確認へ）</button>
        </div>
    </div>
</div>

<script>

// 先取技の〇マーク表示更新
function updateFirstPointDisplay() {
    // すべての技から〇マークを削除
    document.querySelectorAll('.score-dropdown').forEach(dropdown => {
        dropdown.classList.remove('first-point');
    });
    
    // 赤チームの先取技を探す
    const redCircles = document.querySelectorAll('.red-circles .radio-circle');
    const dropdowns = document.querySelectorAll('.middle-controls .score-dropdown');
    
    for (let i = 0; i < redCircles.length; i++) {
        if (redCircles[i].classList.contains('selected')) {
            if (dropdowns[i]) {
                dropdowns[i].classList.add('first-point');
            }
            break; // 最初の1つだけ
        }
    }
    
    // 白チームの先取技を探す（赤チームで既に見つかっていない場合）
    const whiteCircles = document.querySelectorAll('.white-circles .radio-circle');
    let redHasFirst = false;
    
    for (let i = 0; i < redCircles.length; i++) {
        if (redCircles[i].classList.contains('selected')) {
            redHasFirst = true;
            break;
        }
    }
    
    if (!redHasFirst) {
        for (let i = 0; i < whiteCircles.length; i++) {
            if (whiteCircles[i].classList.contains('selected')) {
                if (dropdowns[i]) {
                    dropdowns[i].classList.add('first-point');
                }
                break; // 最初の1つだけ
            }
        }
    }
}

// セッションから保存済みデータを復元
const savedData = <?= json_encode($savedData) ?>;

const data = savedData ? {
    red: savedData.red || { player_id: null, player_name: '', score: '▼', selected: false },
    white: savedData.white || { player_id: null, player_name: '', score: '▲', selected: false }
} : {
    red: { player_id: null, player_name: '', score: '▼', selected: false },
    white: { player_id: null, player_name: '', score: '▲', selected: false }
};

const redSelect = document.getElementById('redPlayerSelect');
const whiteSelect = document.getElementById('whitePlayerSelect');
const redCircle = document.querySelector('.red-circle');
const whiteCircle = document.querySelector('.white-circle');

// データを画面に復元する関数
function load() {
    // 赤の選手を復元
    if (data.red.player_id) {
        redSelect.value = data.red.player_id;
    }
    
    // 白の選手を復元
    if (data.white.player_id) {
        whiteSelect.value = data.white.player_id;
    }
    
    // スコアを復元
    const scoreDropdown = document.querySelector('.score-dropdown');
    scoreDropdown.textContent = data.red.score;
    
    // 勝者サークルを復元
    if (data.red.selected) {
        redCircle.classList.add('selected');
    }
    if (data.white.selected) {
        whiteCircle.classList.add('selected');
    }
}

redSelect.addEventListener('change', (e) => {
    data.red.player_id = e.target.value;
    data.red.player_name = e.target.options[e.target.selectedIndex].text;
});

whiteSelect.addEventListener('change', (e) => {
    data.white.player_id = e.target.value;
    data.white.player_name = e.target.options[e.target.selectedIndex].text;
});

redCircle.addEventListener('click', () => {
    if (redCircle.classList.contains('selected')) {
        redCircle.classList.remove('selected');
        data.red.selected = false;
    } else {
        redCircle.classList.add('selected');
        whiteCircle.classList.remove('selected');
        data.red.selected = true;
        data.white.selected = false;
    }
});

whiteCircle.addEventListener('click', () => {
    if (whiteCircle.classList.contains('selected')) {
        whiteCircle.classList.remove('selected');
        data.white.selected = false;
    } else {
        whiteCircle.classList.add('selected');
        redCircle.classList.remove('selected');
        data.white.selected = true;
        data.red.selected = false;
    }
});

const dropdown = document.querySelector('.dropdown-container');
const btn = dropdown.querySelector('.score-dropdown');
const menu = dropdown.querySelector('.dropdown-menu');

btn.addEventListener('click', e => {
    e.stopPropagation();
    menu.classList.toggle('show');
});

menu.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', () => {
        btn.textContent = item.dataset.val || item.textContent;
        data.red.score = btn.textContent;
        menu.classList.remove('show');
    });
});

document.addEventListener('click', () => menu.classList.remove('show'));

document.getElementById('submitButton').onclick = async () => {
    if (!data.red.player_id || !data.white.player_id) {
        alert('両チームの選手を選択してください');
        return;
    }
    if (!data.red.selected && !data.white.selected) {
        alert('勝者を選択してください');
        return;
    }
    
    try {
        const r = await fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const j = await r.json();
        if (j.status === 'ok') {
            window.location.href = 'team-match-confirm.php';
        } else { alert('保存失敗'); }
    } catch (e) { alert('エラー発生'); console.error(e); }
};

// ページ読み込み時にデータを復元
load();
</script>
</body>
</html>