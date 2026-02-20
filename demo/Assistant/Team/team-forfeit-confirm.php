<?php
require_once 'team_db.php';

/* 不戦勝用セッションチェック */
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

// 大会・部門名取得
$sql = "
    SELECT t.title AS tournament_name, d.name AS division_name
    FROM tournaments t
    JOIN departments d ON d.tournament_id = t.id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $_SESSION['division_id']]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

/* POST処理（確定） */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 団体戦試合データを保存
    $sql = "
        INSERT INTO team_match_results
            (department_id, match_number, match_field,
             team_red_id, team_white_id,
             started_at, ended_at, winner,
             red_win_count, white_win_count,
             red_score, white_score, wo_flg)
        VALUES
            (:department_id, :match_number, :match_field,
             :team_red, :team_white,
             NOW(), NOW(), :winner,
             :red_wins, :white_wins,
             :red_score, :white_score, 1)
    ";

    // 不戦勝の場合のスコア設定
    $red_wins = ($data['winner'] === 'red') ? 5 : 0;
    $white_wins = ($data['winner'] === 'white') ? 5 : 0;
    $red_score = ($data['winner'] === 'red') ? 10 : 0;
    $white_score = ($data['winner'] === 'white') ? 10 : 0;

    $stmt = $pdo->prepare($sql);
    
    // デバッグ用ログ
    error_log('=== team-forfeit-confirm.php DEBUG ===');
    error_log('division_id: ' . ($_SESSION['division_id'] ?? 'NOT SET'));
    error_log('match_number: ' . ($_SESSION['match_number'] ?? 'NOT SET'));
    error_log('match_field: ' . ($_SESSION['match_field'] ?? 'NOT SET (defaulting to 1)'));
    
    $stmt->execute([
        ':department_id' => $_SESSION['division_id'],
        ':match_number'  => $_SESSION['match_number'],
        ':match_field'   => $_SESSION['match_field'] ?? 1,
        ':team_red'      => $data['red_team_id'],
        ':team_white'    => $data['white_team_id'],
        ':winner'        => $data['winner'],
        ':red_wins'      => $red_wins,
        ':white_wins'    => $white_wins,
        ':red_score'     => $red_score,
        ':white_score'   => $white_score
    ]);

    // セッションクリア
    unset($_SESSION['team_forfeit_data']);
    unset($_SESSION['match_number']);
    unset($_SESSION['team_red_id']);
    unset($_SESSION['team_white_id']);

    // 完了画面表示用にフラグをセット
    $_SESSION['team_forfeit_complete'] = true;
    
    // 完了画面へ
    header('Location: team-forfeit-complete.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>団体戦不戦勝確認</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 2rem;
            color: #ef4444;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .result-table th, .result-table td {
            border: 2px solid #000;
            padding: 1rem;
            text-align: center;
        }
        .side-label {
            width: 80px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .team-info {
            text-align: left;
            padding-left: 1.5rem;
        }
        .team-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .team-number {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .forfeit-win {
            color: #ef4444;
            font-size: 1.3rem;
            font-weight: bold;
        }
        .forfeit-loss {
            color: #3b82f6;
            font-size: 1.3rem;
            font-weight: bold;
        }
        .button-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
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
        <div class="header">団体戦 不戦勝　確認画面</div>

        <table class="result-table">
            <thead>
                <tr>
                    <th></th>
                    <th>チーム</th>
                    <th>結果</th>
                </tr>
            </thead>
            <tbody>
                <!-- 赤チーム -->
                <tr>
                    <td class="side-label" style="color:#ef4444;">赤</td>
                    <td class="team-info">
                        <div class="team-name"><?= htmlspecialchars($data['red_team_name']) ?></div>
                        <div class="team-number">チーム番号: <?= htmlspecialchars($data['red_team_number']) ?></div>
                    </td>
                    <td>
                        <?php if ($data['winner'] === 'red'): ?>
                            <span class="forfeit-win">不戦勝</span>
                        <?php else: ?>
                            <span class="forfeit-loss">不戦敗</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- 白チーム -->
                <tr>
                    <td class="side-label">白</td>
                    <td class="team-info">
                        <div class="team-name"><?= htmlspecialchars($data['white_team_name']) ?></div>
                        <div class="team-number">チーム番号: <?= htmlspecialchars($data['white_team_number']) ?></div>
                    </td>
                    <td>
                        <?php if ($data['winner'] === 'white'): ?>
                            <span class="forfeit-win">不戦勝</span>
                        <?php else: ?>
                            <span class="forfeit-loss">不戦敗</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="POST">
            <div class="button-container">
                <button type="button" class="action-button" onclick="history.back()">戻る</button>
                <button type="submit" class="action-button submit-button">この内容で確定</button>
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