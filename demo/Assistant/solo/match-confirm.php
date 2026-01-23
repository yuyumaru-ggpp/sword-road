<?php
session_start();

/* ===============================
   セッションチェック
=============================== */
if (
    !isset(
        $_SESSION['match_input'],
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number']
    )
) {
    header('Location: match_input.php');
    exit;
}

$data = $_SESSION['match_input'];

/* ===============================
   DB接続
=============================== */
$dsn = "mysql:host=localhost;port=3307;dbname=kendo_support_system;charset=utf8mb4";

$pdo = new PDO($dsn, "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

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
   勝敗計算
=============================== */
function calcPoints($scores, $selected)
{
    $point = 0;
    foreach ($scores as $i => $s) {
        if ($s !== '▼' && $s !== '▲' && $s !== '×' && $selected == $i) {
            $point++;
        }
    }
    return $point;
}

$upperPoints = calcPoints($data['upper']['scores'], $data['upper']['selected']);
$lowerPoints = calcPoints($data['lower']['scores'], $data['lower']['selected']);

// 判定勝ちの場合
if ($data['upper']['decision']) {
    $upperPoints = 1;
    $lowerPoints = 0;
}
if ($data['lower']['decision']) {
    $upperPoints = 0;
    $lowerPoints = 1;
}

// 一本勝の場合
if ($data['special'] === 'ippon') {
    if ($upperPoints > $lowerPoints) {
        $upperPoints++;
    } else if ($lowerPoints > $upperPoints) {
        $lowerPoints++;
    }
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
                <!-- 上段 -->
                <tr>
                    <td class="side-label">上</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($data['upper']['name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['upper']['number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php
                            foreach ($data['upper']['scores'] as $i => $s) {
                                if ($s !== '▼' && $s !== '▲' && $s !== '×') {
                                    $class = ($data['upper']['selected'] == $i) ? 'winner' : '';
                                    echo "<span class='score-item {$class}'>" . htmlspecialchars($s) . "</span>";
                                }
                            }

                            if ($data['upper']['decision']) {
                                echo ' <span class="winner">判定勝</span>';
                            }

                            if ($data['special'] === 'ippon')
                                echo ' 一本勝';
                            if ($data['special'] === 'extend')
                                echo ' 延長';
                            if ($data['special'] === 'draw')
                                echo ' 引分け';
                            ?>
                        </div>
                    </td>
                    <td class="total-cell"><?= $upperPoints ?></td>
                </tr>

                <!-- 下段 -->
                <tr>
                    <td class="side-label">下</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($data['lower']['name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['lower']['number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content">
                            <?php
                            foreach ($data['lower']['scores'] as $i => $s) {
                                if ($s !== '▼' && $s !== '▲' && $s !== '×') {
                                    $class = ($data['lower']['selected'] == $i) ? 'winner' : '';
                                    echo "<span class='score-item {$class}'>" . htmlspecialchars($s) . "</span>";
                                }
                            }

                            if ($data['lower']['decision']) {
                                echo ' <span class="winner">判定勝</span>';
                            }

                            if ($data['special'] === 'ippon')
                                echo ' 一本勝';
                            if ($data['special'] === 'extend')
                                echo ' 延長';
                            if ($data['special'] === 'draw')
                                echo ' 引分け';
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