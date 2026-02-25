<?php
require_once 'team_db.php';

/* セッションチェック */
if (
    !isset(
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number'],
        $_SESSION['team_red_id'],
        $_SESSION['team_white_id'],
        $_SESSION['match_results']
    )
) {
    $division_id = $_SESSION['division_id'] ?? '';
    $redirect_url = 'match_input.php';
    if ($division_id) {
        $redirect_url .= '?division_id=' . $division_id;
    }
    header('Location: ' . $redirect_url);
    exit;
}

// セッション変数を取得
$vars = getTeamVariables();
$tournament_id = $vars['tournament_id'];
$division_id   = $vars['division_id'];
$match_number  = $vars['match_number'];
$team_red_id   = $vars['team_red_id'];
$team_white_id = $vars['team_white_id'];



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

$tournamentName = $info['tournament_name'] ?? '';
$divisionName = $info['division_name'] ?? '';

// チーム名をセッションから取得
$team_red_name = $_SESSION['team_red_name'] ?? '';
$team_white_name = $_SESSION['team_white_name'] ?? '';

/* ===============================
   選手名を取得
=============================== */
$positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
$positionNumbers = [
    '先鋒' => 1,
    '次鋒' => 2,
    '中堅' => 3,
    '副将' => 4,
    '大将' => 5
];

// オーダー情報から選手を取得
$red_order = $_SESSION['team_red_order'] ?? [];
$white_order = $_SESSION['team_white_order'] ?? [];

// 赤チームの選手名を取得
$redPlayers = [];
foreach ($positions as $pos) {
    $player_id = $red_order[$pos] ?? null;
    
    if ($player_id) {
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $player_id]);
        $redPlayers[$pos] = $stmt->fetchColumn() ?: '';
    } else {
        $redPlayers[$pos] = '';
    }
}

// 白チームの選手名を取得
$whitePlayers = [];
foreach ($positions as $pos) {
    $player_id = $white_order[$pos] ?? null;
    
    if ($player_id) {
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $player_id]);
        $whitePlayers[$pos] = $stmt->fetchColumn() ?: '';
    } else {
        $whitePlayers[$pos] = '';
    }
}

// 代表戦の選手名を取得
$matchResults = $_SESSION['match_results'] ?? [];
$redRepPlayer = '';
$whiteRepPlayer = '';

if (isset($matchResults['代表決定戦'])) {
    $daihyoData = $matchResults['代表決定戦'];
    
    $redRepPlayerId = $daihyoData['red_player_id'] 
                   ?? $daihyoData['red']['player_id'] 
                   ?? $daihyoData['red_id'] 
                   ?? null;
    
    $whiteRepPlayerId = $daihyoData['white_player_id'] 
                     ?? $daihyoData['white']['player_id'] 
                     ?? $daihyoData['white_id'] 
                     ?? null;
    
    if ($redRepPlayerId) {
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $redRepPlayerId]);
        $redRepPlayer = $stmt->fetchColumn() ?: '';
    }
    
    if ($whiteRepPlayerId) {
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $whiteRepPlayerId]);
        $whiteRepPlayer = $stmt->fetchColumn() ?: '';
    }
}

/* ===============================
   勝敗計算
=============================== */
function calcMatchResult($posData)
{
    if (!$posData) {
        return ['winner' => 'draw', 'red_points' => 0, 'white_points' => 0];
    }
    
    $redPoint = 0;
    $whitePoint = 0;
    
    $scores = $posData['scores'] ?? [];
    if (!is_array($scores)) { $scores = []; }
    $redSelected = $posData['red']['selected'] ?? [];
    $whiteSelected = $posData['white']['selected'] ?? [];
    
    if (!is_array($redSelected)) { $redSelected = []; }
    if (!is_array($whiteSelected)) { $whiteSelected = []; }
    
    foreach ($scores as $i => $score) {
        if ($score !== '▼' && $score !== '▲' && $score !== '') {
            if (in_array($i, $redSelected)) { $redPoint++; }
            if (in_array($i, $whiteSelected)) { $whitePoint++; }
        }
    }
    
    if (isset($posData['special']) && $posData['special'] === 'ippon') {
        if ($redPoint > $whitePoint) {
            $redPoint = 1; $whitePoint = 0;
        } else if ($whitePoint > $redPoint) {
            $redPoint = 0; $whitePoint = 1;
        }
    }
    
    if ($redPoint > $whitePoint) {
        return ['winner' => 'red', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    } else if ($whitePoint > $redPoint) {
        return ['winner' => 'white', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    } else {
        return ['winner' => 'draw', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    }
}

$redWins = 0;
$whiteWins = 0;
$redTotalPoints = 0;
$whiteTotalPoints = 0;

$posResults = [];

foreach ($positions as $pos) {
    if (isset($matchResults[$pos])) {
        $result = calcMatchResult($matchResults[$pos]);
        $posResults[$pos] = $result;
        
        if ($result['winner'] === 'red') { $redWins++; }
        else if ($result['winner'] === 'white') { $whiteWins++; }
        
        $redTotalPoints += $result['red_points'];
        $whiteTotalPoints += $result['white_points'];
    }
}

$repResult = null;
if (isset($matchResults['代表決定戦'])) {
    $repResult = calcMatchResult($matchResults['代表決定戦']);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>試合結果 確認</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding-top: 3rem;
        }

        .header-info {
            background-color: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header-info h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            color: #666;
            margin: 0.25rem 0;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            margin-bottom: 3rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .result-table th,
        .result-table td {
            border: 2px solid #000;
            padding: 1rem;
            text-align: center;
        }

        .result-table th {
            font-size: 1rem;
            font-weight: normal;
            background-color: white;
            vertical-align: middle;
        }

        .result-table thead th {
            min-height: 70px;
            line-height: 1.4;
        }

        .player-name-label {
            color: #ef4444;
            font-size: 0.9rem;
            display: block;
            margin-top: 0.25rem;
        }

        .team-label {
            width: 80px;
            font-weight: bold;
        }

        .team-name {
            width: 150px;
            font-weight: bold;
        }

        .result-cell {
            width: 150px;
            padding: 0;
        }

        .cell-content {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.5rem;
            border-bottom: 2px solid #000;
        }
        
        /* 赤チームは cell-content が下側なので border-bottom → border-top に */
        .red-row .cell-content {
            border-bottom: none;
            border-top: 2px solid #000;
        }
        
        .scores-line {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .player-name-cell {
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #ef4444;
            padding: 0.25rem;
        }

        .total-cell {
            width: 80px;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .score-item {
            display: inline-block;
            margin: 0 2px;
        }

        .cell-content small {
            display: block;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .winner {
            color: #ef4444;
            font-weight: bold;
        }

        .button-container {
            display: flex;
            gap: 4rem;
            justify-content: center;
            margin-top: 3rem;
        }

        .action-button {
            padding: 0.75rem 2.5rem;
            font-size: 1.125rem;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #f9fafb;
        }

        .action-button.primary {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .action-button.primary:hover {
            background-color: #2563eb;
        }

        .first-point {
            position: relative;
            padding-left: 1.8rem;
        }
        
        .first-point::before {
            content: '○';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #ef4444;
            font-size: 1.3rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-info">
            <h1>試合結果確認</h1>
            <p><strong>大会名:</strong> <?= htmlspecialchars($tournamentName) ?></p>
            <p><strong>部門:</strong> <?= htmlspecialchars($divisionName) ?></p>
            <p><strong>試合番号:</strong> <?= htmlspecialchars($match_number) ?></p>
        </div>

        <table class="result-table">
            <thead>
                <tr>
                    <th class="team-label"></th>
                    <th class="team-name">チーム名</th>
                    <th>先鋒</th>
                    <th>次鋒</th>
                    <th>中堅</th>
                    <th>副将</th>
                    <th>大将</th>
                    <th>勝者数</th>
                    <th>得本数</th>
                    <th>代表戦</th>
                </tr>
            </thead>
            <tbody>
                <!-- 赤：選手名(上) → 詳細(下) -->
                <tr class="red-row">
                    <td class="team-label" style="color:#ef4444; font-weight:bold;">赤</td>
                    <td class="team-name"><?= htmlspecialchars($team_red_name) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <?php 
                            $p = $matchResults[$pos] ?? null;
                            $redScores = '';
                            $redSpecial = '';
                            
                            if ($p) {
                                $scores = $p['scores'] ?? [];
                                $redSelected = $p['red']['selected'] ?? [];
                                if (!is_array($redSelected)) { $redSelected = []; }
                                
                                $posResult = calcMatchResult($p);
                                $redPosPoints = $posResult['red_points'];
                                
                                foreach ($scores as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲' && $score !== '' && 
                                        in_array($i, $redSelected)) {
                                        $class = ($redPosPoints > 0 && $i === 0 && in_array(0, $redSelected)) ? 'score-item winner first-point' : 'score-item winner';
                                        $redScores .= '<span class="' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                $special = $p['special'] ?? 'none';
                                
                                if ($special === 'nihon' && $posResult['winner'] === 'red') {
                                    $redSpecial = '<small>二本勝</small>';
                                } else if ($special === 'ippon' && $posResult['winner'] === 'red') {
                                    $redSpecial = '<small>一本勝</small>';
                                } else if ($special === 'extend') {
                                    $redSpecial = '<small>延長</small>';
                                } else if ($special === 'draw') {
                                    $redSpecial = '<small>引分け</small>';
                                } else if ($special === 'hantei') {
                                    $redSpecial = '<small>判定</small>';
                                }
                            }
                        ?>
                        <td class="result-cell">
                            <!-- 赤：名前が上、詳細が下 -->
                            <div class="player-name-cell"><?= htmlspecialchars($redPlayers[$pos] ?? '') ?></div>
                            <div class="cell-content">
                                <?php if ($redScores || isset($matchResults[$pos])): ?>
                                    <div class="scores-line"><?= $redScores ?: '-' ?></div>
                                    <?php if (!empty($redSpecial)): ?>
                                        <?= $redSpecial ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $redWins ?></td>
                    <td class="total-cell"><?= $redTotalPoints ?></td>
                    <td class="result-cell">
                        <!-- 赤代表戦：名前が上、詳細が下 -->
                        <div class="player-name-cell"><?= htmlspecialchars($redRepPlayer) ?></div>
                        <div class="cell-content">
                            <?php 
                                if (isset($matchResults['代表決定戦'])) {
                                    $rep = $matchResults['代表決定戦'];
                                    $scores = $rep['scores'] ?? [];
                                    $redSelected = $rep['red']['selected'] ?? [];
                                    if (!is_array($redSelected)) { $redSelected = []; }
                                    
                                    $redRepPoints = 0;
                                    foreach ($redSelected as $idx) {
                                        if (isset($scores[$idx]) && $scores[$idx] !== '▼' && $scores[$idx] !== '▲' && $scores[$idx] !== '不' && $scores[$idx] !== '') {
                                            $redRepPoints++;
                                        }
                                    }
                                    
                                    foreach ($scores as $i => $score) {
                                        if ($score !== '▼' && $score !== '▲' && $score !== '' && 
                                            in_array($i, $redSelected)) {
                                            $class = ($redRepPoints > 0 && $i === 0 && in_array(0, $redSelected)) ? 'winner first-point' : 'winner';
                                            echo '<span class="' . $class . '">' . htmlspecialchars($score) . '</span>';
                                        }
                                    }
                                    
                                    if ($repResult && $repResult['winner'] === 'red') {
                                        echo ' <span class="winner">勝</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                            ?>
                        </div>
                    </td>
                </tr>
                <!-- 白：詳細(上) → 選手名(下) （元のまま） -->
                <tr>
                    <td class="team-label" style="font-weight:bold;">白</td>
                    <td class="team-name"><?= htmlspecialchars($team_white_name) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <?php 
                            $p = $matchResults[$pos] ?? null;
                            $whiteScores = '';
                            $whiteSpecial = '';
                            
                            if ($p) {
                                $scores = $p['scores'] ?? [];
                                $whiteSelected = $p['white']['selected'] ?? [];
                                if (!is_array($whiteSelected)) { $whiteSelected = []; }
                                
                                $posResult = calcMatchResult($p);
                                $whitePosPoints = $posResult['white_points'];
                                
                                foreach ($scores as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲' && $score !== '' && 
                                        in_array($i, $whiteSelected)) {
                                        $class = ($whitePosPoints > 0 && $i === 0 && in_array(0, $whiteSelected)) ? 'score-item winner first-point' : 'score-item winner';
                                        $whiteScores .= '<span class="' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                $special = $p['special'] ?? 'none';
                                
                                if ($special === 'nihon' && $posResult['winner'] === 'white') {
                                    $whiteSpecial = '<small>二本勝</small>';
                                } else if ($special === 'ippon' && $posResult['winner'] === 'white') {
                                    $whiteSpecial = '<small>一本勝</small>';
                                } else if ($special === 'extend') {
                                    $whiteSpecial = '<small>延長</small>';
                                } else if ($special === 'draw') {
                                    $whiteSpecial = '<small>引分け</small>';
                                } else if ($special === 'hantei') {
                                    $whiteSpecial = '<small>判定</small>';
                                }
                            }
                        ?>
                        <td class="result-cell">
                            <div class="cell-content">
                                <?php if ($whiteScores || isset($matchResults[$pos])): ?>
                                    <div class="scores-line"><?= $whiteScores ?: '-' ?></div>
                                    <?php if (!empty($whiteSpecial)): ?>
                                        <?= $whiteSpecial ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                            <div class="player-name-cell"><?= htmlspecialchars($whitePlayers[$pos] ?? '') ?></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $whiteWins ?></td>
                    <td class="total-cell"><?= $whiteTotalPoints ?></td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                if (isset($matchResults['代表決定戦'])) {
                                    $rep = $matchResults['代表決定戦'];
                                    $scores = $rep['scores'] ?? [];
                                    $whiteSelected = $rep['white']['selected'] ?? [];
                                    if (!is_array($whiteSelected)) { $whiteSelected = []; }
                                    
                                    $whiteRepPoints = 0;
                                    foreach ($whiteSelected as $idx) {
                                        if (isset($scores[$idx]) && $scores[$idx] !== '▼' && $scores[$idx] !== '▲' && $scores[$idx] !== '不' && $scores[$idx] !== '') {
                                            $whiteRepPoints++;
                                        }
                                    }
                                    
                                    foreach ($scores as $i => $score) {
                                        if ($score !== '▼' && $score !== '▲' && $score !== '' && 
                                            in_array($i, $whiteSelected)) {
                                            $class = ($whiteRepPoints > 0 && $i === 0 && in_array(0, $whiteSelected)) ? 'winner first-point' : 'winner';
                                            echo '<span class="' . $class . '">' . htmlspecialchars($score) . '</span>';
                                        }
                                    }
                                    
                                    if ($repResult && $repResult['winner'] === 'white') {
                                        echo ' <span class="winner">勝</span>';
                                    }
                                } else {
                                    echo '-';
                                }
                            ?>
                        </div>
                        <div class="player-name-cell"><?= htmlspecialchars($whiteRepPlayer) ?></div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <form method="POST" action="team-match-complete.php">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">戻る</button>
                <button type="submit" class="action-button primary">この内容で確定</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', e => {
            if (!confirm('この内容で試合結果を確定しますか?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>