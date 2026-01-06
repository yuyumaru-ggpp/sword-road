<?php
session_start();

// セッションに試合番号がない場合は入力画面に戻す
if (!isset($_SESSION['match_number'])) {
    header('Location: match_input.php');
    exit;
}

$match_number = htmlspecialchars($_SESSION['match_number']);

// サンプルデータ（実際にはセッションやPOSTから取得）
$upperTeam = $_SESSION['upper_team'] ?? 'チームA';
$lowerTeam = $_SESSION['lower_team'] ?? 'チームB';
$winner = $_SESSION['forfeit_winner'] ?? 'upper'; // 'upper' or 'lower'

// 勝者数と得本数を計算
if ($winner === 'upper') {
    $upperWins = 5;
    $lowerWins = 0;
    $upperPoints = 5;
    $lowerPoints = 0;
} else {
    $upperWins = 0;
    $lowerWins = 5;
    $upperPoints = 0;
    $lowerPoints = 5;
}

$positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>不戦勝結果確認</title>
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

        .forfeit-win {
            color: #ef4444;
            font-weight: bold;
        }

        .forfeit-loss {
            color: #6b7280;
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
                    <?php foreach ($positions as $pos): ?>
                        <td class="result-cell">
                            <div class="cell-content">
                                <?php if ($winner === 'upper'): ?>
                                    <span class="forfeit-win">不戦勝</span>
                                <?php else: ?>
                                    <span class="forfeit-loss">不戦敗</span>
                                <?php endif; ?>
                            </div>
                            <div class="cell-divider"></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $upperWins ?></td>
                    <td class="total-cell"><?= $upperPoints ?></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
                <tr>
                    <td class="team-name"><?= htmlspecialchars($lowerTeam) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <td class="result-cell">
                            <div class="cell-content">
                                <?php if ($winner === 'lower'): ?>
                                    <span class="forfeit-win">不戦勝</span>
                                <?php else: ?>
                                    <span class="forfeit-loss">不戦敗</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= $lowerWins ?></td>
                    <td class="total-cell"><?= $lowerPoints ?></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <form method="POST" action="complete.php" id="submitForm">
            <input type="hidden" name="forfeit" value="1">
            <input type="hidden" name="winner" value="<?= htmlspecialchars($winner) ?>">
            <input type="hidden" name="match_number" value="<?= htmlspecialchars($match_number) ?>">
            <input type="hidden" name="upper_team" value="<?= htmlspecialchars($upperTeam) ?>">
            <input type="hidden" name="lower_team" value="<?= htmlspecialchars($lowerTeam) ?>">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">キャンセル</button>
                <button type="submit" class="action-button">この結果で送信</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('submitForm').addEventListener('submit', function(e) {
            if (!confirm('この内容で送信してもよろしいですか?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>