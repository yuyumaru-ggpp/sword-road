<?php
session_start();

/* ===============================
   セッションチェック
=============================== */
if (
    !isset(
        $_SESSION['team_forfeit_data'],
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number']
    )
) {
    header('Location: match_input.php');
    exit;
}

$data = $_SESSION['team_forfeit_data'];

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
   POST処理（確定）
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $sql = "
        INSERT INTO team_matches
            (department_id, match_number, match_field,
             team_red_id, team_white_id,
             started_at, ended_at, winner, wo_flg)
        VALUES
            (:department_id, :match_number, 1,
             :team_red_id, :team_white_id,
             NOW(), NOW(), :winner, 1)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $_SESSION['division_id'],
        ':match_number'  => $_SESSION['match_number'],
        ':team_red_id'   => $data['team_red_id'],
        ':team_white_id' => $data['team_white_id'],
        ':winner'        => $data['winner']
    ]);

    // セッションクリア
    unset($_SESSION['team_forfeit_data']);
    unset($_SESSION['match_number']);

    // 完了画面表示用にフラグをセット
    $_SESSION['team_forfeit_complete'] = true;
    
    header('Location: team-forfeit-complete-page.php');
    exit;
}

$positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>不戦勝確認</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 3rem;
        }

        .header {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 3rem;
            color: #dc2626;
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
            width: 120px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .team-name.red {
            color: #dc2626;
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
            font-weight: bold;
        }

        .action-button:hover {
            background-color: #f9fafb;
        }

        .submit-button {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .submit-button:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">団体戦　不戦勝　確認画面</div>

        <table class="result-table">
            <thead>
                <tr>
                    <th class="team-header"></th>
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
                <tr>
                    <td class="team-header team-name red">赤</td>
                    <td class="team-name red"><?= htmlspecialchars($data['team_red_name']) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <td class="result-cell">
                            <div class="cell-content">
                                <?php if ($data['winner'] === 'red'): ?>
                                    <span class="forfeit-win">不戦勝</span>
                                <?php else: ?>
                                    <span class="forfeit-loss">不戦敗</span>
                                <?php endif; ?>
                            </div>
                            <div class="cell-divider"></div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= ($data['winner'] === 'red') ? '5' : '0' ?></td>
                    <td class="total-cell"><?= ($data['winner'] === 'red') ? '5' : '0' ?></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
                <tr>
                    <td class="team-header">白</td>
                    <td class="team-name"><?= htmlspecialchars($data['team_white_name']) ?></td>
                    <?php foreach ($positions as $pos): ?>
                        <td class="result-cell">
                            <div class="cell-content">
                                <?php if ($data['winner'] === 'white'): ?>
                                    <span class="forfeit-win">不戦勝</span>
                                <?php else: ?>
                                    <span class="forfeit-loss">不戦敗</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                    <td class="total-cell"><?= ($data['winner'] === 'white') ? '5' : '0' ?></td>
                    <td class="total-cell"><?= ($data['winner'] === 'white') ? '5' : '0' ?></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <form method="POST" id="submitForm">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">戻る</button>
                <button type="submit" class="action-button submit-button">この内容で確定</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('submitForm').addEventListener('submit', function(e) {
            if (!confirm('この内容で不戦勝を登録しますか?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>