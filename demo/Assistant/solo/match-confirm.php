<?php
$jsonFile = 'data/individual_match.json';
$data = [];

if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $decoded = json_decode($json, true);
    if ($decoded) $data = $decoded;
}

// 勝者を判定する関数
function determineWinner($data) {
    $redPoints = 0;
    $whitePoints = 0;
    
    // 選択された本数をカウント
    if (isset($data['red']['selected']) && $data['red']['selected'] >= 0) {
        $redPoints++;
    }
    if (isset($data['white']['selected']) && $data['white']['selected'] >= 0) {
        $whitePoints++;
    }
    
    // 特殊な結果を考慮
    $special = $data['special'] ?? 'none';
    if ($special === 'ippon') {
        // 一本勝ちの場合、2本取ったことにする
        if ($redPoints > $whitePoints) $redPoints++;
        else if ($whitePoints > $redPoints) $whitePoints++;
    }
    
    return [
        'redPoints' => $redPoints,
        'whitePoints' => $whitePoints
    ];
}

$result = determineWinner($data);
$redName = $data['red']['name'] ?? '';
$redTeam = $data['red']['team'] ?? '';
$whiteName = $data['white']['name'] ?? '';
$whiteTeam = $data['white']['team'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人戦 変更確認</title>
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
            max-width: 800px;
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

        .result-table thead th {
            height: 50px;
        }

        .side-label {
            width: 80px;
            font-weight: bold;
        }

        .player-info {
            width: 200px;
            text-align: left;
            padding-left: 1.5rem;
        }

        .player-name {
            font-size: 1.125rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .team-name {
            font-size: 0.875rem;
            color: #6b7280;
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
            width: 100px;
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
                    <th></th>
                    <th>選手</th>
                    <th>結果</th>
                    <th>得本数</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="side-label">赤</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($redName) ?></div>
                        <div class="team-name"><?= htmlspecialchars($redTeam) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                $redScores = '';
                                $redScoreData = $data['red']['scores'] ?? ['▼','▼','▼'];
                                foreach ($redScoreData as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲') {
                                        $class = (isset($data['red']['selected']) && $data['red']['selected'] == $i) ? 'winner' : '';
                                        $redScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                $special = $data['special'] ?? 'none';
                                if ($special === 'ippon') $redScores .= ' 一本勝';
                                else if ($special === 'extend') $redScores .= ' 延長';
                                else if ($special === 'draw') $redScores .= ' 引分け';
                                else if ($special === 'red_win') $redScores = '不戦勝';
                                else if ($special === 'white_win') $redScores = '不戦敗';
                                
                                echo $redScores;
                            ?>
                        </div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="total-cell"><?= $result['redPoints'] ?></td>
                </tr>
                <tr>
                    <td class="side-label">白</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($whiteName) ?></div>
                        <div class="team-name"><?= htmlspecialchars($whiteTeam) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php 
                                $whiteScores = '';
                                $whiteScoreData = $data['white']['scores'] ?? ['▼','▼','▼'];
                                foreach ($whiteScoreData as $i => $score) {
                                    if ($score !== '▼' && $score !== '▲') {
                                        $class = (isset($data['white']['selected']) && $data['white']['selected'] == $i) ? 'winner' : '';
                                        $whiteScores .= '<span class="score-item ' . $class . '">' . htmlspecialchars($score) . '</span>';
                                    }
                                }
                                
                                if ($special === 'ippon') $whiteScores .= ' 一本勝';
                                else if ($special === 'extend') $whiteScores .= ' 延長';
                                else if ($special === 'draw') $whiteScores .= ' 引分け';
                                else if ($special === 'white_win') $whiteScores = '不戦勝';
                                else if ($special === 'red_win') $whiteScores = '不戦敗';
                                
                                echo $whiteScores;
                            ?>
                        </div>
                    </td>
                    <td class="total-cell"><?= $result['whitePoints'] ?></td>
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