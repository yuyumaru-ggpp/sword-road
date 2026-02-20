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

/* DB接続 */

/* 大会・部門情報取得 */
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

// チーム名を取得
$sql = "SELECT name FROM teams WHERE id = :team_id";
$stmt = $pdo->prepare($sql);

$stmt->execute([':team_id' => $team_red_id]);
$team_red_name = $stmt->fetchColumn();

$stmt->execute([':team_id' => $team_white_id]);
$team_white_name = $stmt->fetchColumn();

// オーダー情報から次鋒の選手を取得
$red_order = $_SESSION['team_red_order'] ?? [];
$white_order = $_SESSION['team_white_order'] ?? [];

$red_player_name = '';
$white_player_name = '';

if (isset($red_order['次鋒'])) {
    $sql = "SELECT name FROM players WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $red_order['次鋒']]);
    $red_player_name = $stmt->fetchColumn();
}

if (isset($white_order['次鋒'])) {
    $sql = "SELECT name FROM players WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $white_order['次鋒']]);
    $white_player_name = $stmt->fetchColumn();
}

// セッションから保存済みデータを取得
$savedData = $_SESSION['match_results']['次鋒'] ?? null;

/* POST処理 */
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
    $_SESSION['match_results']['次鋒'] = $input;
    
    echo json_encode(['status' => 'ok']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>団体戦 次鋒</title>
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

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 20px 15px;
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

.white-circles .radio-circle.selected {
    background: #2563eb;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
}

.white-circles .radio-circle:hover {
    opacity: 0.8;
    background: rgba(59, 130, 246, 0.3);
}

.divider-section {
    position: relative;
    z-index: 10;
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

.score-dropdowns {
    display: flex;
    gap: 20px;
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

.dropdown-menu,
.draw-dropdown-menu {
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

.dropdown-menu.show,
.draw-dropdown-menu.show {
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

.position-label {
    position: absolute;
    top: 50%;
    left: 20px;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.5);
    z-index: 150;
    white-space: nowrap;
}

.draw-container-wrapper {
    position: absolute;
    top: 50%;
    right: 65px;
    transform: translateY(-50%);
    background: white;
    padding: 10px 0;
}

.draw-container {
    position: relative;
}

.draw-button {
    padding: 8px 16px;
    font-size: 14px;
    background: white;
    border: 2px solid #cbd5e0;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}

.draw-button:hover {
    border-color: #667eea;
    background: #f7fafc;
}

.draw-button.required-empty {
    border-color: #ef4444;
    background: #fef2f2;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.draw-dropdown-menu {
    right: auto;
    left: 50%;
    transform: translateX(-50%);
    top: calc(100% + 4px);
}

.bottom-area {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.bottom-right-button {
    display: flex;
    justify-content: flex-end;
}

.cancel-button {
    padding: 8px 20px;
    font-size: 13px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    color: #4a5568;
    transition: all 0.2s;
}

.cancel-button:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
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

.next-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.next-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.next-button:active,
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

    .header {
        padding: 15px 15px 12px;
    }

    .header-badge {
        font-size: 12px;
        padding: 5px 12px;
    }

    .position-label {
        font-size: 14px;
        padding: 6px 16px;
        left: 15px;
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

    .score-display {
        padding: 12px 0;
    }

    .score-numbers,
    .radio-circles {
        gap: 16px;
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

    .draw-container-wrapper {
        right: 58px;
    }

    .draw-button {
        padding: 6px 14px;
        font-size: 13px;
    }

    .bottom-area {
        gap: 10px;
        padding-top: 12px;
    }

    .cancel-button {
        padding: 7px 18px;
        font-size: 12px;
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

    .header {
        padding: 12px 12px 10px;
    }

    .header-badge {
        font-size: 11px;
        padding: 4px 10px;
    }

    .position-label {
        font-size: 13px;
        padding: 5px 14px;
        left: 12px;
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

    .score-display {
        padding: 10px 0;
    }

    .score-numbers {
        font-size: 12px;
        gap: 14px;
    }

    .radio-circles {
        gap: 14px;
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

    .score-dropdowns {
        gap: 14px;
    }

    .dropdown-container {
        width: 32px;
        height: 32px;
    }

    .score-dropdown {
        font-size: 13px;
        border-width: 1px;
    }

    .draw-container-wrapper {
        right: 50px;
    }

    .draw-button {
        padding: 5px 12px;
        font-size: 12px;
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

    .cancel-button {
        padding: 6px 14px;
        font-size: 11px;
        border-width: 1px;
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

    .header {
        padding: 10px 10px 8px;
    }

    .header-badge {
        font-size: 10px;
        padding: 3px 8px;
    }

    .position-label {
        font-size: 12px;
        padding: 4px 12px;
        left: 10px;
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

    .score-display {
        padding: 8px 0;
    }

    .score-numbers {
        font-size: 11px;
        gap: 12px;
    }

    .radio-circles {
        gap: 12px;
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

    .score-dropdowns {
        gap: 12px;
    }

    .dropdown-container {
        width: 28px;
        height: 28px;
    }

    .score-dropdown {
        font-size: 12px;
        border-width: 1px;
    }

    .draw-container-wrapper {
        right: 43px;
    }

    .draw-button {
        padding: 4px 10px;
        font-size: 11px;
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

    .cancel-button {
        padding: 5px 12px;
        font-size: 10px;
        border-width: 1px;
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
                        <div class="info-label">名前</div>
                        <div class="info-value"><?= htmlspecialchars($red_player_name ?: '───') ?></div>
                    </div>
                </div>
                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                        <div class="radio-circles red-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider-section">
                <div class="position-label">次鋒</div>
                <hr class="divider">
                <div class="middle-controls">
                    <div class="score-dropdowns">
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="不">不</div>
                            </div>
                        </div>
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="不">不</div>
                            </div>
                        </div>
                        <div class="dropdown-container">
                            <div class="score-dropdown">▼</div>
                            <div class="dropdown-menu">
                                <div class="dropdown-item" data-val="▼">▼</div>
                                <div class="dropdown-item" data-val="メ">メ</div>
                                <div class="dropdown-item" data-val="コ">コ</div>
                                <div class="dropdown-item" data-val="ド">ド</div>
                                <div class="dropdown-item" data-val="ツ">ツ</div>
                                <div class="dropdown-item" data-val="反">反</div>
                                <div class="dropdown-item" data-val="判">判</div>
                                <div class="dropdown-item" data-val="不">不</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="draw-container-wrapper">
                    <div class="draw-container">
                        <button type="button" class="draw-button" id="drawButton">-</button>
                        <div class="draw-dropdown-menu" id="drawMenu">
                            <div class="dropdown-item">二本勝</div>
                            <div class="dropdown-item">一本勝</div>
                            <div class="dropdown-item">延長戦</div>
                            <div class="dropdown-item">判定</div>
                            <div class="dropdown-item">引き分け</div>
                            <div class="dropdown-item">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="match-section lower-section">
                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles white-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                        <div class="score-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                    </div>
                </div>
                <div class="player-info" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value"><?= htmlspecialchars($team_white_name) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">名前</div>
                        <div class="info-value"><?= htmlspecialchars($white_player_name ?: '───') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-area">
            <div class="bottom-right-button">
                <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
            </div>

            <div class="bottom-buttons">
                <button type="button" class="bottom-button back-button" onclick="history.back()">戻る</button>
                <button type="button" class="bottom-button next-button" id="nextButton">中堅へ</button>
            </div>
        </div>
    </div>
</div>

<script>
const savedData = <?= json_encode($savedData) ?>;

const data = savedData ? {
    red: savedData.red || { selected: [] },
    white: savedData.white || { selected: [] },
    scores: savedData.scores || ['▼','▼','▼'],
    special: savedData.special || 'none'
} : {
    red: { selected: [] },
    white: { selected: [] },
    scores: ['▼','▼','▼'],
    special: 'none'
};

function load() {
    document.querySelectorAll('.middle-controls .score-dropdown').forEach((b,i) => {
        b.textContent = data.scores[i];
    });
    document.querySelectorAll('.red-circles .radio-circle').forEach((c,i) => {
        c.classList.toggle('selected', (data.red.selected || []).includes(i));
    });
    document.querySelectorAll('.white-circles .radio-circle').forEach((c,i) => {
        c.classList.toggle('selected', (data.white.selected || []).includes(i));
    });
    const drawButton = document.getElementById('drawButton');
    if (data.special === 'nihon') {
        drawButton.textContent = '二本勝';
    } else if (data.special === 'ippon') {
        drawButton.textContent = '一本勝';
    } else if (data.special === 'extend') {
        drawButton.textContent = '延長戦';
    } else if (data.special === 'hantei') {
        drawButton.textContent = '判定';
    } else if (data.special === 'draw') {
        drawButton.textContent = '引き分け';
    } else {
        drawButton.textContent = '-';
    }
}

function saveLocal() {
    data.scores = Array.from(document.querySelectorAll('.middle-controls .score-dropdown')).map(b=>b.textContent);
    data.red.selected = Array.from(document.querySelectorAll('.red-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));
    data.white.selected = Array.from(document.querySelectorAll('.white-circles .radio-circle.selected'))
        .map(el => parseInt(el.dataset.index));
    const dt = document.getElementById('drawButton').textContent;
    data.special = dt==='二本勝'?'nihon':dt==='一本勝'?'ippon':dt==='延長戦'?'extend':dt==='判定'?'hantei':dt==='引き分け'?'draw':'none';
}

function validateInput() {
    const drawButton = document.getElementById('drawButton');
    const drawText = drawButton.textContent;
    
    // 勝敗が選択されていない場合（「-」のまま）
    if (drawText === '-') {
        drawButton.classList.add('required-empty');
        setTimeout(() => {
            drawButton.classList.remove('required-empty');
        }, 500);
        alert('試合結果を選択してください（二本勝、一本勝、延長戦、判定、引き分けのいずれか）');
        return false;
    }
    
    return true;
}

for (let i = 0; i < 3; i++) {
    const red = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
    const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);
    red.addEventListener('click', () => {
        if (red.classList.contains('selected')) red.classList.remove('selected');
        else { red.classList.add('selected'); white.classList.remove('selected'); }
    });
    white.addEventListener('click', () => {
        if (white.classList.contains('selected')) white.classList.remove('selected');
        else { white.classList.add('selected'); red.classList.remove('selected'); }
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
        data.red = { selected: [] };
        data.white = { selected: [] };
        data.scores = ['▼','▼','▼'];
        data.special = 'none';
        load();
    }
});

document.getElementById('nextButton').onclick=async()=>{
    // バリデーションチェック
    if (!validateInput()) {
        return;
    }
    
    saveLocal();
    try{
        const r=await fetch(location.href,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
        const j=await r.json();
        if(j.status==='ok'){
            window.location.href = 'team-match-chuken.php';
        } else { alert('保存失敗'); }
    }catch(e){ alert('エラー発生'); console.error(e); }
};

load();
</script>
</body>
</html>