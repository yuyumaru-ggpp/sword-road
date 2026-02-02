<?php
// 個人戦共通処理を読み込み
require_once 'solo_db.php';

/* ===============================
   不戦勝用セッションチェック
=============================== */
if (
    !isset(
        $_SESSION['forfeit_data'],
        $_SESSION['tournament_id'],
        $_SESSION['division_id'],
        $_SESSION['match_number']
    )
) {
    header('Location: match_input.php');
    exit;
}

$data = $_SESSION['forfeit_data'];

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
        INSERT INTO individual_matches
            (department_id, department, match_field,
             player_a_id, player_b_id,
             started_at, ended_at, final_winner)
        VALUES
            (:department_id, :department, 1,
             :player_a, :player_b,
             NOW(), NOW(), :winner)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $_SESSION['division_id'],
        ':department'    => $_SESSION['match_number'],
        ':player_a'      => $data['upper_id'],
        ':player_b'      => $data['lower_id'],
        ':winner'        => $data['winner']
    ]);

    // セッションクリア
    unset($_SESSION['forfeit_data']);
    unset($_SESSION['match_number']);
    unset($_SESSION['player_a_id']);
    unset($_SESSION['player_b_id']);
    unset($_SESSION['player_a_name']);
    unset($_SESSION['player_b_name']);
    unset($_SESSION['player_a_number']);
    unset($_SESSION['player_b_number']);

    // 完了画面表示用にフラグをセット
    $_SESSION['forfeit_complete'] = true;
    
    // 完了画面へ（POSTではなくGETでアクセス）
    header('Location: solo-forfeit-complete.php');
    exit;
}
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
            max-width: 800px;
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .result-table th,
        .result-table td {
            border: 2px solid #000;
            padding: 1.5rem;
            text-align: center;
        }

        .result-table th {
            font-size: 1.1rem;
            font-weight: normal;
            background-color: #f9fafb;
        }

        .result-table thead th {
            height: 60px;
            font-weight: bold;
        }

        .side-label {
            width: 100px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .player-info {
            text-align: left;
            padding-left: 2rem;
        }

        .player-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .player-number {
            font-size: 1rem;
            color: #6b7280;
        }

        .result-cell {
            width: 200px;
            padding: 1rem;
        }

        .forfeit-win {
            color: #ef4444;
            font-weight: bold;
            font-size: 1.3rem;
        }

        .forfeit-loss {
            color: #9ca3af;
            font-size: 1.1rem;
        }

        .total-cell {
            width: 100px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .button-container {
            display: flex;
            gap: 4rem;
            justify-content: center;
            margin-top: 3rem;
        }

        .action-button {
            padding: 1rem 3rem;
            font-size: 1.2rem;
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
        <div class="header">不戦勝　確認画面</div>

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
                        <div class="player-name"><?= htmlspecialchars($data['upper_name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['upper_number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <?php if ($data['winner'] === 'A'): ?>
                            <span class="forfeit-win">不戦勝</span>
                        <?php else: ?>
                            <span class="forfeit-loss">不戦敗</span>
                        <?php endif; ?>
                    </td>
                    <td class="total-cell"><?= $data['upper_score'] ?></td>
                </tr>

                <!-- 下段 -->
                <tr>
                    <td class="side-label">下</td>
                    <td class="player-info">
                        <div class="player-name"><?= htmlspecialchars($data['lower_name']) ?></div>
                        <div class="player-number">選手番号: <?= htmlspecialchars($data['lower_number']) ?></div>
                    </td>
                    <td class="result-cell">
                        <?php if ($data['winner'] === 'B'): ?>
                            <span class="forfeit-win">不戦勝</span>
                        <?php else: ?>
                            <span class="forfeit-loss">不戦敗</span>
                        <?php endif; ?>
                    </td>
                    <td class="total-cell"><?= $data['lower_score'] ?></td>
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