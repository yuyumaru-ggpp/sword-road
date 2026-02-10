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

// チーム名をセッションから取得
$team_red_name = $_SESSION['team_red_name'] ?? '';
$team_white_name = $_SESSION['team_white_name'] ?? '';

// 試合に出た選手を取得
$red_order = $_SESSION['team_red_order'] ?? [];
$white_order = $_SESSION['team_white_order'] ?? [];

$positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
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
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    overflow: hidden;
}

body {
    font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.container {
    width: 100%;
    max-width: 1000px;
    height: calc(100vh - 16px);
    max-height: 900px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

.position-header {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    font-size: 24px;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    padding: 12px 40px;
    text-align: center;
    z-index: 200;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 20px 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.header-badge {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.main-content {
    flex: 1;
    padding: 20px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    min-height: 0;
}

.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
}

.match-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.player-info {
    background: #f7fafc;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 10px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    font-size: 14px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    min-width: 80px;
    font-weight: 600;
    color: #4a5568;
}

.info-value {
    font-weight: 700;
    color: #2d3748;
    font-size: 16px;
}

.player-select {
    flex: 1;
    padding: 8px 12px;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    cursor: pointer;
    background: white;
    color: #2d3748;
    transition: all 0.2s;
}

.player-select:focus {
    outline: none;
    border-color: #667eea;
    background: #f7fafc;
}

.player-select:hover {
    border-color: #667eea;
}

.score-display {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 15px 0;
}

.score-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.score-numbers {
    display: flex;
    gap: 20px;
    font-size: 14px;
    font-weight: bold;
    color: #4a5568;
}

.score-numbers span {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.radio-circles {
    display: flex;
    gap: 20px;
}

.radio-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.radio-circle.selected {
    background: #ef4444;
    transform: scale(1.15);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2);
}

.radio-circle:hover {
    opacity: 0.8;
}

.divider-section {
    position: relative;
    margin: 20px 0;
    text-align: center;
}

.divider {
    border: none;
    border-top: 2px dashed #cbd5e0;
}

.middle-controls {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    background: white;
    padding: 10px 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dropdown-container {
    position: relative;
    width: 40px;
    height: 40px;
}

.score-dropdown {
    width: 100%;
    height: 100%;
    font-size: 16px;
    font-weight: bold;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.score-dropdown:hover {
    border-color: #667eea;
    background: #f7fafc;
}

.dropdown-menu {
    display: none;
    position: absolute;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    min-width: 70px;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    padding: 6px 0;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 10px 16px;
    font-size: 15px;
    font-weight: bold;
    text-align: center;
    cursor: pointer;
    user-select: none;
    transition: all 0.15s;
}

.dropdown-item:hover {
    background: #eef2ff;
    color: #667eea;
}

.dropdown-item:active {
    background: #667eea;
    color: white;
}

.bottom-area {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.bottom-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.bottom-button {
    flex: 1;
    max-width: 200px;
    padding: 14px 20px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.back-button {
    background-color: #e2e8f0;
    color: #4a5568;
}

.back-button:hover {
    background-color: #cbd5e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.submit-button {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
}

.submit-button:active,
.back-button:active {
    transform: translateY(0);
}

/* スクロールバー */
.main-content::-webkit-scrollbar {
    width: 6px;
}

.main-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.main-content::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* 小さい画面での調整 */
@media (max-height: 750px) {
    body {
        padding: 5px;
    }

    .container {
        max-height: calc(100vh - 10px);
        border-radius: 16px;
    }

    .position-header {
        font-size: 20px;
        padding: 10px 32px;
    }

    .header {
        padding: 50px 15px 12px;
    }

    .header-badge {
        font-size: 12px;
        padding: 5px 12px;
    }

    .main-content {
        padding: 15px;
    }

    .player-info {
        padding: 10px;
        margin-bottom: 8px;
    }

    .info-row {
        font-size: 13px;
        gap: 8px;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 14px;
    }

    .player-select {
        padding: 7px 10px;
        font-size: 13px;
    }

    .score-display {
        padding: 12px 0;
    }

    .score-numbers span,
    .radio-circle {
        width: 36px;
        height: 36px;
    }

    .dropdown-container {
        width: 36px;
        height: 36px;
    }

    .score-dropdown {
        font-size: 14px;
    }

    .divider-section {
        margin: 15px 0;
    }

    .bottom-area {
        gap: 10px;
        padding-top: 12px;
    }

    .bottom-button {
        padding: 12px 18px;
        font-size: 15px;
    }
}

/* スマートフォン縦向き */
@media (max-width: 600px) {
    body {
        padding: 4px;
    }

    .container {
        max-height: calc(100vh - 8px);
        border-radius: 12px;
    }

    .position-header {
        font-size: 18px;
        padding: 8px 24px;
        border-radius: 0 0 12px 12px;
    }

    .header {
        padding: 46px 12px 10px;
    }

    .header-badge {
        font-size: 11px;
        padding: 4px 10px;
    }

    .main-content {
        padding: 12px;
    }

    .player-info {
        padding: 8px;
        margin-bottom: 6px;
    }

    .info-row {
        font-size: 12px;
        gap: 6px;
        margin-bottom: 4px;
    }

    .info-label {
        min-width: 70px;
    }

    .info-value {
        font-size: 13px;
    }

    .player-select {
        padding: 6px 10px;
        font-size: 12px;
    }

    .score-display {
        padding: 10px 0;
    }

    .score-numbers {
        font-size: 12px;
    }

    .score-numbers span,
    .radio-circle {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }

    .divider-section {
        margin: 12px 0;
    }

    .middle-controls {
        padding: 8px 12px;
    }

    .dropdown-container {
        width: 32px;
        height: 32px;
    }

    .score-dropdown {
        font-size: 13px;
        border-width: 1px;
    }

    .dropdown-item {
        padding: 8px 14px;
        font-size: 13px;
    }

    .bottom-area {
        gap: 8px;
        padding-top: 10px;
    }

    .bottom-button {
        padding: 10px 16px;
        font-size: 14px;
        max-width: 180px;
    }

    .bottom-buttons {
        gap: 12px;
    }
}

/* スマートフォン横向き */
@media (max-width: 900px) and (max-height: 500px) {
    body {
        padding: 3px;
    }

    .container {
        max-height: calc(100vh - 6px);
        border-radius: 10px;
    }

    .position-header {
        font-size: 16px;
        padding: 6px 20px;
    }

    .header {
        padding: 40px 10px 8px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .main-content {
        padding: 10px;
    }

    .player-info {
        padding: 6px;
        margin-bottom: 5px;
    }

    .info-row {
        font-size: 11px;
        gap: 5px;
        margin-bottom: 3px;
    }

    .info-label {
        min-width: 60px;
    }

    .info-value {
        font-size: 12px;
    }

    .player-select {
        padding: 5px 8px;
        font-size: 11px;
    }

    .score-display {
        padding: 8px 0;
    }

    .score-numbers {
        font-size: 11px;
    }

    .score-numbers span,
    .radio-circle {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }

    .divider-section {
        margin: 10px 0;
    }

    .middle-controls {
        padding: 6px 10px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .dropdown-item {
        padding: 6px 12px;
        font-size: 12px;
    }

    .bottom-area {
        gap: 6px;
        padding-top: 8px;
    }

    .bottom-button {
        padding: 8px 14px;
        font-size: 13px;
        max-width: 160px;
    }

    .bottom-buttons {
        gap: 10px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="position-header">代表決定戦</div>
    
    <div class="header">
        <div class="header-badge">団体戦</div>
        <div class="header-badge"><?= htmlspecialchars($info['tournament_name']) ?></div>
        <div class="header-badge"><?= htmlspecialchars($info['division_name']) ?></div>
    </div>

    <div class="main-content">
        <div class="content-wrapper">
            <div class="match-section upper-section">
                <div class="player-info" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value"><?= htmlspecialchars($team_red_name) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">選手選択</div>
                        <select class="player-select" id="redPlayerSelect">
                            <option value="">選手を選択</option>
                            <?php foreach ($red_players as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['position']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers">
                            <span>1</span>
                        </div>
                        <div class="radio-circles red-circles">
                            <div class="radio-circle red-circle"></div>
                        </div>
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

            <div class="match-section lower-section">
                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles white-circles">
                            <div class="radio-circle white-circle"></div>
                        </div>
                        <div class="score-numbers">
                            <span>1</span>
                        </div>
                    </div>
                </div>
                <div class="player-info" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
                    <div class="info-row">
                        <div class="info-label">選手選択</div>
                        <select class="player-select" id="whitePlayerSelect">
                            <option value="">選手を選択</option>
                            <?php foreach ($white_players as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['position']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value"><?= htmlspecialchars($team_white_name) ?></div>
                    </div>
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
</div>

<script>
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
    
    // 技名を取得
    const scoreText = document.querySelector('.score-dropdown').textContent;
    
    // 先鋒～大将と同じデータ構造に変換してから送信
    const sendData = {
        scores: [scoreText, '▼', '▼'],
        red:    { selected: data.red.selected   ? [0] : [] },
        white:  { selected: data.white.selected  ? [0] : [] },
        special: 'none',
        // 代表戦用の選手情報はそのまま付与
        red_player_id:   data.red.player_id,
        white_player_id: data.white.player_id
    };
    
    try {
        const r = await fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(sendData)
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