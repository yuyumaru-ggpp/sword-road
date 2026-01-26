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
    header('Location: team-match-senpo.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id   = $_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];
$team_red_id   = $_SESSION['team_red_id'];
$team_white_id = $_SESSION['team_white_id'];

/* DB接続 */
$dsn = "mysql:host=localhost;port=3307;dbname=kendo_support_system;charset=utf8mb4";
$pdo = new PDO($dsn, "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

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

// チーム情報
$team_red_name = $_SESSION['team_red_name'] ?? '';
$team_white_name = $_SESSION['team_white_name'] ?? '';

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
    
    // スコアから得点を計算
    if (isset($posData['red']['scores']) && isset($posData['red']['selected'])) {
        foreach ($posData['red']['scores'] as $i => $score) {
            if ($score !== '▼' && $score !== '×' && $posData['red']['selected'] == $i) {
                $redPoint++;
            }
        }
    }
    
    if (isset($posData['white']['scores']) && isset($posData['white']['selected'])) {
        foreach ($posData['white']['scores'] as $i => $score) {
            if ($score !== '▲' && $score !== '×' && $posData['white']['selected'] == $i) {
                $whitePoint++;
            }
        }
    }
    
    // 一本勝の場合、勝者に+1
    if (isset($posData['special']) && $posData['special'] === 'ippon') {
        if ($redPoint > $whitePoint) {
            $redPoint++;
        } else if ($whitePoint > $redPoint) {
            $whitePoint++;
        }
    }
    
    // 勝者判定
    if ($redPoint > $whitePoint) {
        return ['winner' => 'red', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    } else if ($whitePoint > $redPoint) {
        return ['winner' => 'white', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    } else {
        return ['winner' => 'draw', 'red_points' => $redPoint, 'white_points' => $whitePoint];
    }
}

$positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
$redWins = 0;
$whiteWins = 0;
$redTotalPoints = 0;
$whiteTotalPoints = 0;

$posResults = [];
$matchResults = $_SESSION['match_results'] ?? [];

foreach ($positions as $pos) {
    if (isset($matchResults[$pos])) {
        $result = calcMatchResult($matchResults[$pos]);
        $posResults[$pos] = $result;
        
        if ($result['winner'] === 'red') {
            $redWins++;
        } else if ($result['winner'] === 'white') {
            $whiteWins++;
        }
        
        $redTotalPoints += $result['red_points'];
        $whiteTotalPoints += $result['white_points'];
    }
}

// 代表決定戦のチェック
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
        }

        .result-table thead th {
            height: 50px;
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
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0.5rem;
        }

        .cell-divider {
            height: 2px;
            background-color: #000;
            background-image: repeating-linear-gradient(
                to right,
                #000 0,
                #000 8px,
                transparent 8px,
                transparent 16px
            );
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
                <!-- 赤 -->
                <tr>
                    <td class="team-label" style="color:#ef4444; font-weight:bold;">赤</td>
                    <td class="team-name"><?= htmlspecialchars($team_red_name) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <?php 
                            $p = $matchResults[$pos] ?? null;
                            $redScores = '';
                            
                            if ($p && isset($p['red']['scores'])) {
                                foreach ($p['red']['scores'] as $i => $score) {
                                    if ($score !== '▼' && $score !== '×') {
                                        $class = (isset($p['red']['selected']) && $p['red']['selected'] == $i) ? 'winner' : '';
                                        $redScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                $special = $p['special'] ?? 'none';
                                $posResult = calcMatchResult($p);
                                
                                // 一本勝は勝った方にのみ表示
                                if ($special === 'ippon' && $posResult['winner'] === 'red') {
                                    $redScores .= ' <small>一本勝</small>';
                                }
                                // 延長・引分けは両方に表示
                                if ($special === 'extend') {
                                    $redScores .= ' <small>延長</small>';
                                } else if ($special === 'draw') {
                                    $redScores .= ' <small>引分け</small>';
                                }
                            }
                        ?>
                        <td class="result-cell">
                            <div class="cell-content"><?= $redScores ?: '-' ?></div>
                            <div class="cell-divider"></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $redWins ?></td>
                    <td class="total-cell"><?= $redTotalPoints ?></td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                if ($repResult && $repResult['winner'] === 'red') {
                                    $rep = $matchResults['代表決定戦'];
                                    if (isset($rep['red']['scores'])) {
                                        foreach ($rep['red']['scores'] as $i => $score) {
                                            if ($score !== '▼' && $score !== '×' && isset($rep['red']['selected']) && $rep['red']['selected'] == $i) {
                                                echo '<span class="winner">' . htmlspecialchars($score) . '</span>';
                                            }
                                        }
                                    }
                                }
                            ?>
                        </div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
                <!-- 白 -->
                <tr>
                    <td class="team-label" style="font-weight:bold;">白</td>
                    <td class="team-name"><?= htmlspecialchars($team_white_name) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <?php 
                            $p = $matchResults[$pos] ?? null;
                            $whiteScores = '';
                            
                            if ($p && isset($p['white']['scores'])) {
                                foreach ($p['white']['scores'] as $i => $score) {
                                    if ($score !== '▲' && $score !== '×') {
                                        $class = (isset($p['white']['selected']) && $p['white']['selected'] == $i) ? 'winner' : '';
                                        $whiteScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                $special = $p['special'] ?? 'none';
                                $posResult = calcMatchResult($p);
                                
                                // 一本勝は勝った方にのみ表示
                                if ($special === 'ippon' && $posResult['winner'] === 'white') {
                                    $whiteScores .= ' <small>一本勝</small>';
                                }
                                // 延長・引分けは両方に表示
                                if ($special === 'extend') {
                                    $whiteScores .= ' <small>延長</small>';
                                } else if ($special === 'draw') {
                                    $whiteScores .= ' <small>引分け</small>';
                                }
                            }
                        ?>
                        <td class="result-cell">
                            <div class="cell-content"><?= $whiteScores ?: '-' ?></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $whiteWins ?></td>
                    <td class="total-cell"><?= $whiteTotalPoints ?></td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                if ($repResult && $repResult['winner'] === 'white') {
                                    $rep = $matchResults['代表決定戦'];
                                    if (isset($rep['white']['scores'])) {
                                        foreach ($rep['white']['scores'] as $i => $score) {
                                            if ($score !== '▲' && $score !== '×' && isset($rep['white']['selected']) && $rep['white']['selected'] == $i) {
                                                echo '<span class="winner">' . htmlspecialchars($score) . '</span>';
                                            }
                                        }
                                    }
                                }
                            ?>
                        </div>
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