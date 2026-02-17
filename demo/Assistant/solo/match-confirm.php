<?php
// 個人戦共通処理を読み込み
require_once 'solo_db.php';

/* ===============================
   セッションチェック
=============================== */
if (
    !isset(
    $_SESSION['tournament_id'],
    $_SESSION['division_id'],
    $_SESSION['match_number']
)
) {
    header('Location: match_input.php');
    exit;
}

// 通常の試合データまたは不戦勝データを取得
if (isset($_SESSION['match_input'])) {
    $data = $_SESSION['match_input'];
} else if (isset($_SESSION['forfeit_data'])) {
    $data = $_SESSION['forfeit_data'];
} else {
    header('Location: match_input.php');
    exit;
}

/* ===============================
   大会・部門名取得
=============================== */
$sql = "
    SELECT t.title AS tournament_name, d.name AS division_name
    FROM tournaments t
    JOIN departments d ON d.tournament_id = t.id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $_SESSION['division_id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===============================
   勝敗計算（solo_db.phpのcalcPoints関数を使用）
=============================== */
$scores = $data['scores'] ?? ['▼', '▼', '▼'];
$upperPoints = calcPoints($scores, $data['upper']['selected'] ?? []);
$lowerPoints = calcPoints($scores, $data['lower']['selected'] ?? []);

// 一本勝ちの場合は、勝者を1本に固定
$isIppon = $data['special'] === 'ippon';
if ($isIppon) {
    if ($upperPoints > $lowerPoints) {
        $upperPoints = 1;
        $lowerPoints = 0;
    } else if ($lowerPoints > $upperPoints) {
        $upperPoints = 0;
        $lowerPoints = 1;
    }
}
/* ===============================
   引分判定
=============================== */
$isDraw = false;

/* ===============================
   勝者判定
=============================== */
$winner = null;

if ($upperPoints > $lowerPoints) {
    $winner = 'upper';
} elseif ($lowerPoints > $upperPoints) {
    $winner = 'lower';
}

if ($upperPoints === $lowerPoints) {
    $isDraw = true;
}
$resultText = '';

if ($upperPoints === 1 && $lowerPoints === 1) {
    $resultText = '引分';
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

        .player-number {
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

        .draw-label {
            margin-left: 10px;
            font-weight: bold;
            color: #2563eb;
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
                <!-- 赤 -->
                <tr>
                    <td class="side-label">赤</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($data['upper']['name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['upper']['number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php
                            $upperSelected = $data['upper']['selected'] ?? [];
                            if (!is_array($upperSelected)) {
                                $upperSelected = [];
                            }

                            $scores = $data['scores'] ?? ($data['upper']['scores'] ?? []);

                            // 選択された技のみを表示
                            foreach ($scores as $i => $s) {
                                if ($s !== '▼' && $s !== '▲' && $s !== '不' && $s !== '' && in_array($i, $upperSelected)) {
                                    $class = 'winner';
                                    // インデックス0（1番目の枠）に入力された技に〇マーク
                                    if ($upperPoints > 0 && $i === 0 && in_array(0, $upperSelected)) {
                                        $class .= ' first-point';
                                    }
                                    echo "<span class='score-item {$class}'>" . htmlspecialchars($s) . "</span>";
                                }
                            }

                            // 二本勝ちは勝者のみ表示
                            if ($data['special'] === 'nihon' && $upperPoints >= 2) {
                                echo ' <span class="winner">(二本勝)</span>';
                            }
                            // 一本勝ちは勝者のみ表示
                            else if ($data['special'] === 'ippon' && $upperPoints > 0) {
                                echo ' <span class="winner">(一本勝)</span>';
                            }
                            // 判定勝
                            else if ($data['upper']['decision']) {
                                echo ' <span class="winner">判定勝</span>';
                            }

                            // 延長は両者に表示
                            if ($data['special'] === 'extend' && $winner === 'upper') {
                                echo ' <span class="winner">(延長勝)</span>';
                            }
                            if ($isDraw) {
                                echo ' <span class="draw-label">引分</span>';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="total-cell"><?= $upperPoints ?></td>
                </tr>

                <!-- 白 -->
                <tr>
                    <td class="side-label">白</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($data['lower']['name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['lower']['number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php
                            $lowerSelected = $data['lower']['selected'] ?? [];
                            if (!is_array($lowerSelected)) {
                                $lowerSelected = [];
                            }

                            $scores = $data['scores'] ?? ($data['lower']['scores'] ?? []);

                            // 選択された技のみを表示
                            foreach ($scores as $i => $s) {
                                if ($s !== '▼' && $s !== '▲' && $s !== '不' && $s !== '' && in_array($i, $lowerSelected)) {
                                    $class = 'winner';
                                    // インデックス0（1番目の枠）に入力された技に〇マーク
                                    if ($lowerPoints > 0 && $i === 0 && in_array(0, $lowerSelected)) {
                                        $class .= ' first-point';
                                    }
                                    echo "<span class='score-item {$class}'>" . htmlspecialchars($s) . "</span>";
                                }
                            }

                            // 二本勝ちは勝者のみ表示
                            if ($data['special'] === 'nihon' && $lowerPoints >= 2) {
                                echo ' <span class="winner">(二本勝)</span>';
                            }
                            // 一本勝ちは勝者のみ表示
                            else if ($data['special'] === 'ippon' && $lowerPoints > 0) {
                                echo ' <span class="winner">(一本勝)</span>';
                            }
                            // 判定勝
                            else if ($data['lower']['decision']) {
                                echo ' <span class="winner">(判定勝)</span>';
                            }

                            // 延長は両者に表示
                            if ($data['special'] === 'extend' && $winner === 'lower') {
                                echo ' <span class="winner">(延長勝)</span>';
                            }
                            if ($isDraw) {
                                echo ' <span class="draw-label">(引分)</span>';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="total-cell"><?= $lowerPoints ?></td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="complete.php">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">戻る</button>
                <button type="submit" class="action-button">この内容で確定</button>
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