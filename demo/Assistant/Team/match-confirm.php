<?php
$jsonFile = 'data/matches.json';
$data = ['positions'=>[]];

if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $decoded = json_decode($json, true);
    if ($decoded) $data = $decoded;
}

// 勝者数と得本数を計算する関数
function calculateResults($data) {
    $positions = ['先鋒','次鋒','中堅','副将','大将'];
    $upperWins = 0;
    $lowerWins = 0;
    $upperPoints = 0;
    $lowerPoints = 0;
    
    foreach ($positions as $pos) {
        if (!isset($data['positions'][$pos])) continue;
        $p = $data['positions'][$pos];
        
        // 選択された本数をカウント
        if ($p['upper']['selected'] >= 0) {
            $upperPoints++;
        }
        if ($p['lower']['selected'] >= 0) {
            $lowerPoints++;
        }
        
        // 勝者を判定
        $upperCount = $p['upper']['selected'] >= 0 ? 1 : 0;
        $lowerCount = $p['lower']['selected'] >= 0 ? 1 : 0;
        
        // 特殊な結果を考慮
        $special = $p['special'] ?? 'none';
        if ($special === 'ippon') {
            // 一本勝ちの場合、2本取ったことにする
            if ($upperCount > $lowerCount) $upperPoints++;
            else if ($lowerCount > $upperCount) $lowerPoints++;
        } else if ($special === 'red_win') {
            $upperWins++;
            continue;
        } else if ($special === 'white_win') {
            $lowerWins++;
            continue;
        } else if ($special === 'draw') {
            continue;
        }
        
        // 通常の勝敗判定
        if ($upperCount > $lowerCount) {
            $upperWins++;
        } else if ($lowerCount > $upperCount) {
            $lowerWins++;
        }
    }
    
    return [
        'upperWins' => $upperWins,
        'lowerWins' => $lowerWins,
        'upperPoints' => $upperPoints,
        'lowerPoints' => $lowerPoints
    ];
}

$results = calculateResults($data);
$upperTeam = '';
$lowerTeam = '';

// チーム名を取得
$positions = ['先鋒','次鋒','中堅','副将','大将'];
foreach ($positions as $pos) {
    if (isset($data['positions'][$pos])) {
        if (empty($upperTeam) && !empty($data['positions'][$pos]['upper']['team'])) {
            $upperTeam = $data['positions'][$pos]['upper']['team'];
        }
        if (empty($lowerTeam) && !empty($data['positions'][$pos]['lower']['team'])) {
            $lowerTeam = $data['positions'][$pos]['lower']['team'];
        }
        if (!empty($upperTeam) && !empty($lowerTeam)) break;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>変更確認</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 3rem;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            margin-bottom: 3rem;
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

        .team-header {
            width: 80px;
        }

        .result-table thead th {
            height: 50px;
        }

        .team-name {
            width: 80px;
            height: 120px;
            font-weight: bold;
        }

        .result-cell {
            width: 100px;
            padding: 0;
        }

        .cell-content {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
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

        .score-item {
            display: inline-block;
            margin: 0 2px;
        }

        .winner {
            color: #ef4444;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="result-table">
            <thead>
                <tr>
                    <th class="team-header"></th>
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
                <tr>
                    <td class="team-name"><?= htmlspecialchars($upperTeam) ?></td>
                    <?php foreach (['先鋒','次鋒','中堅','副将','大将'] as $pos): ?>
                        <?php 
                            $p = $data['positions'][$pos] ?? null;
                            $upperScores = '';
                            $special = '';
                            if ($p) {
                                $scores = $p['upper']['scores'] ?? ['▼','▼','▼'];
                                foreach ($scores as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲') {
                                        $class = ($p['upper']['selected'] == $i) ? 'winner' : '';
                                        $upperScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                $special = $p['special'] ?? 'none';
                                if ($special === 'ippon') $upperScores .= ' 一本勝';
                                else if ($special === 'extend') $upperScores .= ' 延長';
                                else if ($special === 'draw') $upperScores .= ' 引分け';
                                else if ($special === 'red_win') $upperScores = '不戦勝';
                                else if ($special === 'white_win') $upperScores = '不戦敗';
                            }
                        ?>
                        <td class="result-cell">
                            <div class="cell-content"><?= $upperScores ?></div>
                            <div class="cell-divider"></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $results['upperWins'] ?></td>
                    <td class="total-cell"><?= $results['upperPoints'] ?></td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                $rep = $data['positions']['代表決定戦'] ?? null;
                                if ($rep && $rep['upper']['selected'] >= 0) {
                                    $score = $rep['upper']['scores'][0] ?? '▼';
                                    echo '<span class="winner">' . htmlspecialchars($score) . '</span>';
                                }
                            ?>
                        </div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
                <tr>
                    <td class="team-name"><?= htmlspecialchars($lowerTeam) ?></td>
                    <?php foreach (['先鋒','次鋒','中堅','副将','大将'] as $pos): ?>
                        <?php 
                            $p = $data['positions'][$pos] ?? null;
                            $lowerScores = '';
                            if ($p) {
                                $scores = $p['upper']['scores'] ?? ['▼','▼','▼'];
                                foreach ($scores as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲') {
                                        $class = ($p['lower']['selected'] == $i) ? 'winner' : '';
                                        $lowerScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                $special = $p['special'] ?? 'none';
                                if ($special === 'ippon') $lowerScores .= ' 一本勝';
                                else if ($special === 'extend') $lowerScores .= ' 延長';
                                else if ($special === 'draw') $lowerScores .= ' 引分け';
                                else if ($special === 'white_win') $lowerScores = '不戦勝';
                                else if ($special === 'red_win') $lowerScores = '不戦敗';
                            }
                        ?>
                        <td class="result-cell">
                            <div class="cell-content"><?= $lowerScores ?></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $results['lowerWins'] ?></td>
                    <td class="total-cell"><?= $results['lowerPoints'] ?></td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                if ($rep && $rep['lower']['selected'] >= 0) {
                                    $score = $rep['upper']['scores'][0] ?? '▼';
                                    echo '<span class="winner">' . htmlspecialchars($score) . '</span>';
                                }
                            ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <form method="POST" action="complete.php" id="submitForm">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
                <button type="submit" class="action-button">この結果で送信</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('submitForm').addEventListener('submit', function(e) {
            if (!confirm('この内容で送信してもよろしいですか？')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>