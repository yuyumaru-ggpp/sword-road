<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$dept_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

if (!$match_id) {
    die("match_id が指定されていません");
}

// ヘルパー: DB の勝者表現を統一して 'red' / 'white' / '' にする
function normalizeWinner($v)
{
    if ($v === null) return '';
    $s = mb_strtolower(trim((string)$v));
    if ($s === '') return '';
    if (in_array($s, ['red', 'r', '赤', 'aka', 'a'], true)) return 'red';
    if (in_array($s, ['white', 'w', '白', 'shiro', 'b'], true)) return 'white';
    return '';
}

// 試合情報取得
try {
    $sql = "SELECT im.match_id, im.department_id, im.team_match_id,
                   im.match_field, im.order_id, im.player_a_id, im.player_b_id,
                   im.individual_match_num,
                   im.started_at, im.ended_at,
                   im.first_technique, im.first_winner,
                   im.second_technique, im.second_winner,
                   im.third_technique, im.third_winner,
                   im.judgement, im.final_winner,
                   pa.name AS player_a_name, pa.player_number AS player_a_number,
                   pb.name AS player_b_name, pb.player_number AS player_b_number,
                   d.name AS department_name,
                   t.title AS tournament_name
            FROM individual_matches im
            LEFT JOIN players pa ON pa.id = im.player_a_id
            LEFT JOIN players pb ON pb.id = im.player_b_id
            LEFT JOIN departments d ON d.id = im.department_id
            LEFT JOIN tournaments t ON t.id = d.tournament_id
            WHERE im.match_id = :mid
            LIMIT 1";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mid', $match_id, PDO::PARAM_INT);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("試合情報の取得に失敗しました");
}

if (!$match) {
    die("指定された試合が見つかりません");
}

// チーム情報の取得（団体戦の場合）
$team_red_name = '';
$team_white_name = '';
if (!empty($match['team_match_id'])) {
    try {
        $sql = "SELECT team_red_id, team_white_id FROM team_match_results WHERE id = :tmid LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tmid' => $match['team_match_id']]);
        $teamMatch = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($teamMatch) {
            $sql = "SELECT id, name FROM teams WHERE id IN (:red_id, :white_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':red_id' => $teamMatch['team_red_id'], ':white_id' => $teamMatch['team_white_id']]);
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($teams as $team) {
                if ($team['id'] == $teamMatch['team_red_id']) {
                    $team_red_name = $team['name'];
                } elseif ($team['id'] == $teamMatch['team_white_id']) {
                    $team_white_name = $team['name'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// 正規化して JS に渡す winners 配列を作る
$uiWinners = [
    normalizeWinner($match['first_winner'] ?? null),
    normalizeWinner($match['second_winner'] ?? null),
    normalizeWinner($match['third_winner'] ?? null)
];
$uiFinalWinner = normalizeWinner($match['final_winner'] ?? null);

// ポジション名の決定
$positionMap = ['1' => '先鋒', '2' => '次鋒', '3' => '中堅', '4' => '副将', '5' => '大将'];
$positionName = $positionMap[$match['individual_match_num']] ?? '試合';

// JS に渡すデータ整形
$uiData = [
    'match_id' => (int)$match['match_id'],
    'team_match_id' => isset($match['team_match_id']) ? (int)$match['team_match_id'] : null,
    'player_a' => [
        'id' => isset($match['player_a_id']) ? (int)$match['player_a_id'] : null,
        'name' => $match['player_a_name'] ?? '',
        'number' => $match['player_a_number'] ?? ''
    ],
    'player_b' => [
        'id' => isset($match['player_b_id']) ? (int)$match['player_b_id'] : null,
        'name' => $match['player_b_name'] ?? '',
        'number' => $match['player_b_number'] ?? ''
    ],
    'scores' => [
        $match['first_technique'] ?? '▼',
        $match['second_technique'] ?? '▼',
        $match['third_technique'] ?? '▼'
    ],
    'winners' => $uiWinners,
    'final_winner' => $uiFinalWinner,
    'judgement' => $match['judgement'] ?? '',
    'started_at' => $match['started_at'] ?? null,
    'ended_at' => $match['ended_at'] ?? null
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>試合詳細編集 - <?= htmlspecialchars($positionName) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Meiryo', sans-serif;
            background: #f5f5f5;
            padding: 0.5rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            height: 100vh;
            max-height: 100vh;
            background: white;
            padding: clamp(0.5rem, 1.5vh, 2rem) clamp(0.8rem, 2vh, 1.5rem);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .position-header {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            font-size: clamp(1.2rem, 3vh, 2rem);
            font-weight: bold;
            color: #dc2626;
            background: #fee2e2;
            padding: 0.5rem 2rem;
            text-align: center;
            z-index: 100;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.2);
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: clamp(0.3rem, 0.8vh, 1rem);
            margin-bottom: clamp(0.3rem, 1vh, 1.5rem);
            padding-top: clamp(2rem, 4vh, 3rem);
            font-size: clamp(0.85rem, 2vh, 1.1rem);
            flex-shrink: 0;
        }

        .header-badge {
            background: #e5e7eb;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            min-height: 0;
        }

        .match-section {
            display: flex;
            flex-direction: column;
            gap: clamp(0.15rem, 0.3vh, 0.5rem);
            flex-shrink: 0;
        }

        .upper-section {
            margin-bottom: clamp(0.5rem, 1.5vh, 3rem);
        }

        .player-info {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            font-size: clamp(0.75rem, 1.8vh, 1.2rem);
            gap: clamp(0.3rem, 0.8vw, 1rem);
            margin-bottom: 0.3rem;
        }

        .info-label {
            min-width: clamp(70px, 10vw, 100px);
            font-weight: bold;
        }

        .info-value {
            min-width: clamp(100px, 15vw, 150px);
            word-break: break-all;
        }

        .score-display {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 0;
            margin: 0;
            margin-bottom: clamp(0.5rem, 1.2vh, 2rem);
        }

        .score-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: clamp(0.25rem, 0.6vh, 0.5rem);
        }

        .score-numbers {
            display: flex;
            gap: clamp(1rem, 3vw, 2.5rem);
            font-size: clamp(1rem, 2.5vh, 1.5rem);
            font-weight: bold;
        }

        .score-numbers span {
            width: clamp(35px, 6vw, 50px);
            height: clamp(35px, 6vw, 50px);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .radio-circles {
            display: flex;
            gap: clamp(1rem, 3vw, 2.5rem);
        }

        .radio-circle {
            width: clamp(35px, 6vw, 50px);
            height: clamp(35px, 6vw, 50px);
            border-radius: 50%;
            background: #d1d5db;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .radio-circle.selected {
            background: #ef4444;
            transform: scale(1.1);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3);
        }

        .radio-circle:hover {
            opacity: 0.9;
        }

        .divider-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(1rem, 3vw, 2rem);
            margin: clamp(0.5rem, 1.5vh, 1.5rem) 0;
            flex-shrink: 0;
        }

        .divider-line {
            flex: 1;
            height: 0;
            border: none;
            border-top: 3px dashed #000;
        }

        .middle-controls {
            display: flex;
            align-items: center;
            gap: clamp(1rem, 3vw, 2rem);
            flex-shrink: 0;
        }

        .score-dropdowns {
            display: flex;
            gap: clamp(1rem, 3vw, 2.5rem);
        }

        .dropdown-container {
            position: relative;
            width: clamp(35px, 6vw, 50px);
            height: clamp(35px, 6vw, 50px);
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .score-dropdown {
            width: 100%;
            height: 100%;
            font-size: clamp(1rem, 2.5vh, 1.5rem);
            font-weight: bold;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .score-dropdown:hover {
            background: #fef3c7;
        }

        .dropdown-menu,
        .draw-dropdown-menu {
            display: none;
            position: absolute;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            min-width: 70px;
            max-height: clamp(200px, 40vh, 300px);
            overflow-y: auto;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            padding: 8px 0;
        }

        .dropdown-menu.show,
        .draw-dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: clamp(8px, 1.5vh, 12px) clamp(12px, 2vw, 18px);
            font-size: clamp(0.95rem, 2vh, 1.3rem);
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s;
        }

        .dropdown-item:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .dropdown-item:active {
            background: #ef4444;
            color: white;
        }

        .draw-container {
            position: relative;
            flex-shrink: 0;
        }

        .draw-button {
            padding: clamp(0.4rem, 1vh, 0.6rem) clamp(0.8rem, 2vw, 1.3rem);
            font-size: clamp(0.85rem, 1.8vh, 1.1rem);
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }

        .draw-button:hover {
            background: #fef3c7;
        }

        .draw-dropdown-menu {
            right: auto;
            left: 50%;
            transform: translateX(-50%);
            top: calc(100% + 4px);
        }

        .dropdown-menu::-webkit-scrollbar,
        .draw-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .dropdown-menu::-webkit-scrollbar-track,
        .draw-dropdown-menu::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .dropdown-menu::-webkit-scrollbar-thumb,
        .draw-dropdown-menu::-webkit-scrollbar-thumb {
            background: #c0c0c0;
            border-radius: 10px;
        }

        .bottom-area {
            display: flex;
            flex-direction: column;
            gap: clamp(0.4rem, 1vh, 1.5rem);
            margin-top: clamp(0.4rem, 1vh, 1.5rem);
            flex-shrink: 0;
        }

        .bottom-right-button {
            display: flex;
            justify-content: flex-end;
        }

        .cancel-button {
            padding: clamp(0.4rem, 1vh, 0.6rem) clamp(1.2rem, 3vw, 2rem);
            font-size: clamp(0.85rem, 1.8vh, 1.1rem);
            background: white;
            border: 2px solid #000;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
        }

        .bottom-buttons {
            display: flex;
            justify-content: center;
            gap: clamp(0.8rem, 2vw, 1.5rem);
        }

        .bottom-button {
            padding: clamp(0.5rem, 1.2vh, 0.7rem) clamp(1.5rem, 4vw, 2.5rem);
            font-size: clamp(0.9rem, 2vh, 1.2rem);
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }

        .back-button {
            background: white;
            border: 2px solid #000;
        }

        .submit-button {
            background: #3b82f6;
            color: white;
            border: 2px solid #3b82f6;
        }

        .submit-button:hover {
            background: #2563eb;
        }

        @media (max-width:768px) {
            .header {
                padding-top: 3rem;
            }

            .divider-section {
                flex-wrap: wrap;
            }

            .middle-controls {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="position-header"><?= htmlspecialchars($positionName) ?></div>

        <div class="header">
            <div class="header-badge"><?= !empty($match['team_match_id']) ? '団体戦' : '個人戦' ?></div>
            <div class="header-badge"><?= htmlspecialchars($match['tournament_name'] ?? '大会') ?></div>
            <div class="header-badge"><?= htmlspecialchars($match['department_name'] ?? '部門') ?></div>
        </div>

        <div class="content-wrapper">
            <div class="match-section upper-section">
                <div class="player-info" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                    <?php if (!empty($team_red_name)): ?>
                        <div class="info-row">
                            <div class="info-label">チーム名</div>
                            <div class="info-value"><?= htmlspecialchars($team_red_name) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">名前</div>
                        <div class="info-value upper-name"><?= htmlspecialchars($match['player_a_name'] ?? '───') ?></div>
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
                <hr class="divider-line">
                <div class="middle-controls">
                    <div class="score-dropdowns">
                        <?php for ($i = 0; $i < 3; $i++): ?>
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
                                    <div class="dropdown-item" data-val="×">×</div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
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
                <hr class="divider-line">
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
                        <div class="info-label">名前</div>
                        <div class="info-value lower-name"><?= htmlspecialchars($match['player_b_name'] ?? '───') ?></div>
                    </div>
                    <?php if (!empty($team_white_name)): ?>
                        <div class="info-row">
                            <div class="info-label">チーム名</div>
                            <div class="info-value"><?= htmlspecialchars($team_white_name) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bottom-area">
                <div class="bottom-right-button">
                    <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
                </div>

                <div class="bottom-buttons">
                    <button type="button" class="bottom-button back-button" onclick="history.back()">戻る</button>
                    <button type="button" class="bottom-button submit-button" id="submitButton">変更</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 安全に JS に埋める
        const UI_DATA = <?= json_encode($uiData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script>
        (function() {
            let data = (typeof UI_DATA !== 'undefined' && UI_DATA) ? UI_DATA : {
                match_id: null,
                team_match_id: null,
                player_a: {
                    id: null,
                    name: '',
                    number: ''
                },
                player_b: {
                    id: null,
                    name: '',
                    number: ''
                },
                scores: ['▼', '▼', '▼'],
                winners: ['', '', ''],
                final_winner: '',
                judgement: '',
                started_at: null,
                ended_at: null
            };

            // DOM 要素取得
            const redCircles = document.querySelectorAll('.red-circles .radio-circle');
            const whiteCircles = document.querySelectorAll('.white-circles .radio-circle');
            const scoreDropdowns = document.querySelectorAll('.score-dropdowns .score-dropdown');
            const drawButton = document.getElementById('drawButton');
            const drawMenu = document.getElementById('drawMenu');
            const submitButton = document.getElementById('submitButton');
            const cancelButton = document.getElementById('cancelButton');

            function closeAllMenus() {
                document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => m.classList.remove('show'));
            }

            // 自動判定関数
            function autoCalculateFinalWinner() {
                const winners = ['', '', ''];
                redCircles.forEach((item, i) => {
                    if (item.classList.contains('selected')) winners[i] = 'red';
                });
                whiteCircles.forEach((item, i) => {
                    if (item.classList.contains('selected')) winners[i] = 'white';
                });

                let redWins = 0;
                let whiteWins = 0;
                winners.forEach(w => {
                    if (w === 'red') redWins++;
                    if (w === 'white') whiteWins++;
                });

                console.log('=== 自動判定実行 ===');
                console.log('技の勝者:', winners);
                console.log('赤の勝利数:', redWins);
                console.log('白の勝利数:', whiteWins);

                const dt = drawButton ? drawButton.textContent.trim() : '-';
                const isExtension = dt === '延長戦';
                console.log('判定状態:', dt, '/ 延長戦?', isExtension);

                if (isExtension) {
                    // 延長の場合は技の数で判定（判定ボタンは使わない）
                    if (redWins > whiteWins) {
                        data.final_winner = 'red';
                    } else if (whiteWins > redWins) {
                        data.final_winner = 'white';
                    } else {
                        data.final_winner = '';
                    }
                } else {
                    if (redWins > whiteWins) {
                        data.final_winner = 'red';
                    } else if (whiteWins > redWins) {
                        data.final_winner = 'white';
                    } else {
                        data.final_winner = '';
                    }
                }

                console.log('最終勝者:', data.final_winner);
                console.log('==================');

                return winners;
            }

            function load() {
                // スコアドロップダウンに初期値を設定
                scoreDropdowns.forEach((btn, i) => {
                    if (btn) btn.textContent = (data.scores && data.scores[i]) ? data.scores[i] : '▼';
                });

                // 勝者サークルに初期値を設定
                redCircles.forEach((c, i) => {
                    c.classList.toggle('selected', data.winners[i] === 'red');
                });
                whiteCircles.forEach((c, i) => {
                    c.classList.toggle('selected', data.winners[i] === 'white');
                });

                // 判定ボタンに初期値を設定
                if (drawButton && data.judgement) {
                    if (j.indexOf('二本') !== -1) drawButton.textContent = '二本勝';
                    else if (j.indexOf('一本') !== -1) drawButton.textContent = '一本勝';
                    else if (j.indexOf('延長') !== -1) drawButton.textContent = '延長戦';
                    else if (j.indexOf('判定') !== -1) drawButton.textContent = '判定';
                    else if (j.indexOf('引') !== -1) drawButton.textContent = '引き分け';
                }
            }

            function saveLocal() {
                data.scores = Array.from(scoreDropdowns).map(b => b ? b.textContent.trim() : '▼');
                const winners = autoCalculateFinalWinner();
                data.winners = winners;

                const dt = drawButton ? drawButton.textContent.trim() : '-';
                data.judgement = dt === '二本勝' ? '二本勝' :
                    dt === '一本勝' ? '一本勝' :
                    dt === '延長戦' ? '延長戦' :
                    dt === '判定' ? '判定' :
                    dt === '引き分け' ? '引き分け' : '';
            }

            // 勝者サークルクリック処理
            redCircles.forEach((circle, i) => {
                circle.addEventListener('click', () => {
                    const was = circle.classList.contains('selected');
                    if (was) {
                        circle.classList.remove('selected');
                    } else {
                        circle.classList.add('selected');
                        whiteCircles[i].classList.remove('selected');
                    }
                    saveLocal();
                });
            });

            whiteCircles.forEach((circle, i) => {
                circle.addEventListener('click', () => {
                    const was = circle.classList.contains('selected');
                    if (was) {
                        circle.classList.remove('selected');
                    } else {
                        circle.classList.add('selected');
                        redCircles[i].classList.remove('selected');
                    }
                    saveLocal();
                });
            });

            // ドロップダウン（技選択）
            document.querySelectorAll('.dropdown-container').forEach(container => {
                const btn = container.querySelector('.score-dropdown');
                const menu = container.querySelector('.dropdown-menu');
                if (!btn || !menu) return;
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    closeAllMenus();
                    menu.classList.toggle('show');
                });
                menu.querySelectorAll('.dropdown-item').forEach(item => {
                    item.addEventListener('click', () => {
                        btn.textContent = item.dataset.val || item.textContent;
                        menu.classList.remove('show');
                        saveLocal();
                    });
                });
            });

            // 引き分け/延長メニュー
            if (drawButton && drawMenu) {
                drawButton.addEventListener('click', e => {
                    e.stopPropagation();
                    closeAllMenus();
                    drawMenu.classList.toggle('show');
                });
                drawMenu.querySelectorAll('.dropdown-item').forEach(item => {
                    item.addEventListener('click', () => {
                        drawButton.textContent = item.textContent;
                        drawMenu.classList.remove('show');
                        saveLocal();
                    });
                });
            }

            document.addEventListener('click', () => closeAllMenus());

            // 取り消し（リセット）
            if (cancelButton) {
                cancelButton.addEventListener('click', () => {
                    if (!confirm('試合内容をリセットしますか？')) return;
                    data.scores = ['▼', '▼', '▼'];
                    data.winners = ['', '', ''];
                    data.final_winner = '';
                    data.judgement = '';
                    load();
                });
            }

            // 送信処理
            if (submitButton) {
                submitButton.addEventListener('click', async () => {
                    saveLocal();
                    const winners = (data.winners || ['', '', '']).map(v => v || '');
                    const payload = {
                        match_id: data.match_id,
                        first_technique: (data.scores && data.scores[0]) ? data.scores[0] : '',
                        second_technique: (data.scores && data.scores[1]) ? data.scores[1] : '',
                        third_technique: (data.scores && data.scores[2]) ? data.scores[2] : '',
                        first_winner: winners[0] || '',
                        second_winner: winners[1] || '',
                        third_winner: winners[2] || '',
                        final_winner: data.final_winner || '',
                        judgement: data.judgement || '',
                        started_at: data.started_at || null,
                        ended_at: data.ended_at || null
                    };

                    if (!payload.match_id) {
                        alert('match_id が設定されていません。ページを再読み込みしてください。');
                        return;
                    }

                    const btn = submitButton;
                    btn.disabled = true;
                    const originalText = btn.textContent;
                    btn.textContent = '保存中...';

                    try {
                        const res = await fetch('match-update.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const json = await res.json();
                        if (json.status === 'ok') {
                            alert('保存しました');
                            history.back();
                        } else {
                            alert('保存に失敗しました: ' + (json.message || 'サーバーエラー'));
                        }
                    } catch (err) {
                        console.error(err);
                        alert('通信エラーが発生しました');
                    } finally {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                });
            }

            // 初期化
            load();
        })();
    </script>

</body>

</html>