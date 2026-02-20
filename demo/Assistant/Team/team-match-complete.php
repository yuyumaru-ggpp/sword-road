<?php
require_once 'team_db.php';

// セッションチェック
checkTeamSession();

// セッション変数を取得
$vars = getTeamVariables();
$tournament_id = $vars['tournament_id'];
$division_id = $vars['division_id'];
$match_number = $vars['match_number'];
$team_red_id = $vars['team_red_id'];
$team_white_id = $vars['team_white_id'];

// 試合結果をセッションから取得
$matchResults = $_SESSION['match_results'] ?? [];



/* ===============================
   試合結果を解析・計算
=============================== */
function calcMatchResult($posData)
{
    if (!$posData) {
        return ['winner' => 'draw', 'red_points' => 0, 'white_points' => 0];
    }

    $redPoint = 0;
    $whitePoint = 0;

    $scores = $posData['scores'] ?? [];
    $redSelected = $posData['red']['selected'] ?? [];
    $whiteSelected = $posData['white']['selected'] ?? [];

    if (!is_array($redSelected)) {
        $redSelected = [];
    }
    if (!is_array($whiteSelected)) {
        $whiteSelected = [];
    }

    // スコアから得点を計算
    foreach ($scores as $i => $score) {
        if ($score !== '▼' && $score !== '▲' && $score !== '不' && $score !== '') {
            if (in_array($i, $redSelected)) {
                $redPoint++;
            }
            if (in_array($i, $whiteSelected)) {
                $whitePoint++;
            }
        }
    }

    // 一本勝の場合、勝者を1本に固定
    if (isset($posData['special']) && $posData['special'] === 'ippon') {
        if ($redPoint > $whitePoint) {
            $redPoint = 1;
            $whitePoint = 0;
        } else if ($whitePoint > $redPoint) {
            $redPoint = 0;
            $whitePoint = 1;
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

foreach ($positions as $pos) {
    if (isset($matchResults[$pos])) {
        $result = calcMatchResult($matchResults[$pos]);

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
if (isset($matchResults['代表決定戦'])) {
    $repResult = calcMatchResult($matchResults['代表決定戦']);
    if ($repResult['winner'] === 'red') {
        $redWins++;
    } else if ($repResult['winner'] === 'white') {
        $whiteWins++;
    }
    $redTotalPoints += $repResult['red_points'];
    $whiteTotalPoints += $repResult['white_points'];
}

// 最終勝者を決定
$finalWinner = null;
if ($redWins > $whiteWins) {
    $finalWinner = 'red';
} else if ($whiteWins > $redWins) {
    $finalWinner = 'white';
} else {
    // 勝者数が同じ場合は得本数で判定
    if ($redTotalPoints > $whiteTotalPoints) {
        $finalWinner = 'red';
    } else if ($whiteTotalPoints > $redTotalPoints) {
        $finalWinner = 'white';
    } else {
        // 代表決定戦で判定
        if (isset($matchResults['代表決定戦'])) {
            $repResult = calcMatchResult($matchResults['代表決定戦']);
            $finalWinner = $repResult['winner'];
        } else {
            $finalWinner = 'draw';
        }
    }
}

/* ===============================
   試合結果 INSERT
=============================== */
try {
    $pdo->beginTransaction();

    // team_match_resultsテーブルに保存
    $sql = "
    INSERT INTO team_match_results (
        department_id,
        match_number,
        match_field,
        team_red_id,
        team_white_id,
        started_at,
        ended_at,
        winner,
        red_win_count,
        white_win_count,
        red_score,
        white_score,
        wo_flg
    ) VALUES (
        :department_id,
        :match_number,
        :match_field,
        :team_red_id,
        :team_white_id,
        NOW(),
        NOW(),
        :winner,
        :red_win_count,
        :white_win_count,
        :red_score,
        :white_score,
        0
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $_SESSION['division_id'],
        ':match_number' => $_SESSION['match_number'],
        ':match_field' => $_SESSION['match_field'] ?? 1,
        ':team_red_id' => $_SESSION['team_red_id'],
        ':team_white_id' => $_SESSION['team_white_id'],
        ':winner' => $finalWinner,
        ':red_win_count' => $redWins,
        ':white_win_count' => $whiteWins,
        ':red_score' => $redTotalPoints,
        ':white_score' => $whiteTotalPoints
    ]);

    $team_match_id = $pdo->lastInsertId();

    // individual_matchesテーブルに各ポジションの詳細を保存
    $orderMapping = [
        '先鋒' => 1,
        '次鋒' => 2,
        '中堅' => 3,
        '副将' => 4,
        '大将' => 5,
        '代表決定戦' => 6
    ];

    // 赤チームと白チームのオーダー情報を取得
    $redOrder = $_SESSION['team_red_order'] ?? [];
    $whiteOrder = $_SESSION['team_white_order'] ?? [];

    $matchField = $_SESSION['match_field'] ?? 1; // セッションから試合場番号を取得

    // INSERT文のテンプレート
    $insertSql = "
    INSERT INTO individual_matches (
        department_id, team_match_id, match_field, order_id,
        individual_match_num,
        player_a_id, player_b_id,
        started_at, ended_at,
        first_technique, first_winner,
        second_technique, second_winner,
        third_technique, third_winner,
        judgement, final_winner
    ) VALUES (
        :department_id, :team_match_id, :match_field, :order_id,
        :individual_match_num,
        :player_a_id, :player_b_id,
        NOW(), NOW(),
        :first_technique, :first_winner,
        :second_technique, :second_winner,
        :third_technique, :third_winner,
        :judgement, :final_winner
    )";

    $matchNumCounter = 1; // individual_match_num用のカウンター

    foreach ($matchResults as $posName => $posData) {
        if (!isset($orderMapping[$posName])) {
            continue;
        }

        $orderNum = $orderMapping[$posName];
        $result = calcMatchResult($posData);

        // 選手ID: 代表決定戦は別フィールドに格納される
        if ($posName === '代表決定戦') {
            $redPlayerId = $posData['red_player_id'] ?? null;
            $whitePlayerId = $posData['white_player_id'] ?? null;
        } else {
            $redPlayerId = $redOrder[$posName] ?? null;
            $whitePlayerId = $whiteOrder[$posName] ?? null;
        }

        // 選手IDがnullの場合はスキップ（外部キー制約エラー回避）
        if ($redPlayerId === null || $whitePlayerId === null) {
            error_log("警告: {$posName} の選手IDがnullのためスキップ (red={$redPlayerId}, white={$whitePlayerId})");
            continue;
        }

        // ordersテーブルから実際のorder_idを取得（赤チームの該当ポジション）
        $sqlOrder = "SELECT id FROM orders WHERE team_id = :team_id AND player_id = :player_id AND order_detail = :order_detail LIMIT 1";
        $stmtOrder = $pdo->prepare($sqlOrder);
        $stmtOrder->execute([
            ':team_id'      => $_SESSION['team_red_id'],
            ':player_id'    => $redPlayerId,
            ':order_detail' => $orderNum  // 1〜5の数値（order_detailに格納されている値）
        ]);
        $orderId = $stmtOrder->fetchColumn();

        if (!$orderId) {
            // 白チームのordersからも試みる
            $stmtOrder->execute([
                ':team_id'      => $_SESSION['team_white_id'],
                ':player_id'    => $whitePlayerId,
                ':order_detail' => $orderNum
            ]);
            $orderId = $stmtOrder->fetchColumn();
        }

        if (!$orderId) {
            error_log("警告: {$posName} のorder_idが見つかりません (red_team={$_SESSION['team_red_id']}, red_player={$redPlayerId}, order_detail={$orderNum})");
            // order_idが取得できない場合はスキップ
            continue;
        }

        $stmt = $pdo->prepare($insertSql);

        // scoresから技名を取得
        $scores = $posData['scores'] ?? [];
        $firstTech = (isset($scores[0]) && $scores[0] !== '▼' && $scores[0] !== '▲' && $scores[0] !== '不' && $scores[0] !== '') ? $scores[0] : null;
        $secondTech = (isset($scores[1]) && $scores[1] !== '▼' && $scores[1] !== '▲' && $scores[1] !== '不' && $scores[1] !== '') ? $scores[1] : null;
        $thirdTech = (isset($scores[2]) && $scores[2] !== '▼' && $scores[2] !== '▲' && $scores[2] !== '不' && $scores[2] !== '') ? $scores[2] : null;

        // selected配列から各枠の勝者を判定
        $redSelected = $posData['red']['selected'] ?? [];
        $whiteSelected = $posData['white']['selected'] ?? [];
        if (!is_array($redSelected)) {
            $redSelected = [];
        }
        if (!is_array($whiteSelected)) {
            $whiteSelected = [];
        }

        $firstWinner = in_array(0, $redSelected) ? 'red' : (in_array(0, $whiteSelected) ? 'white' : null);
        $secondWinner = in_array(1, $redSelected) ? 'red' : (in_array(1, $whiteSelected) ? 'white' : null);
        $thirdWinner = in_array(2, $redSelected) ? 'red' : (in_array(2, $whiteSelected) ? 'white' : null);

        // 判定（一本勝・延長・引分け等）
        $special = $posData['special'] ?? 'none';
        $judgement = null;
        if ($special === 'ippon') {
            $judgement = '一本勝';
        } elseif ($special === 'nihon') {
            $judgement = '二本勝';
        } elseif ($special === 'extend') {
            $judgement = '延長';
        } elseif ($special === 'draw') {
            $judgement = '引分け';
        } elseif ($special === 'hantei') {
            $judgement = '判定';
        }

        // 最終勝者
        $finalWinner = ($result['winner'] !== 'draw') ? $result['winner'] : null;

        try {
            $stmt->execute([
                ':department_id' => $_SESSION['division_id'],
                ':team_match_id' => $team_match_id,
                ':match_field' => $matchField,
                ':order_id' => $orderId,
                ':individual_match_num' => $matchNumCounter,
                ':player_a_id' => $redPlayerId,
                ':player_b_id' => $whitePlayerId,
                ':first_technique' => $firstTech,
                ':first_winner' => $firstWinner,
                ':second_technique' => $secondTech,
                ':second_winner' => $secondWinner,
                ':third_technique' => $thirdTech,
                ':third_winner' => $thirdWinner,
                ':judgement' => $judgement,
                ':final_winner' => $finalWinner
            ]);

            // デバッグ: 保存成功を記録
            error_log("成功: {$posName} (individual_match_num={$matchNumCounter}, team_match_id={$team_match_id})");

            // 成功したらカウンターをインクリメント
            $matchNumCounter++;

        } catch (PDOException $e) {
            // エラー時に詳細情報を出力
            $errorInfo = [
                'position' => $posName,
                'red_player_id' => $redPlayerId,
                'white_player_id' => $whitePlayerId,
                'order_num' => $orderNum,
                'error' => $e->getMessage()
            ];
            $pdo->rollBack();
            exit('データベースエラー (' . $posName . '): ' . $e->getMessage() .
                '<br>詳細: player_a_id=' . $redPlayerId . ', player_b_id=' . $whitePlayerId);
        }
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    exit('データベースエラー: ' . $e->getMessage());
}

/* ===============================
   二重送信防止
=============================== */
$division_id = $_SESSION['division_id'];
unset($_SESSION['match_results']);
unset($_SESSION['match_number']);
unset($_SESSION['team_red_id']);
unset($_SESSION['team_white_id']);
unset($_SESSION['team_red_name']);
unset($_SESSION['team_white_name']);
unset($_SESSION['team_red_order']);
unset($_SESSION['team_white_order']);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        .container {
            max-width: 900px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .success-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 4rem 3rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 10px 30px rgba(16, 185, 129, 0.4),
                0 0 0 10px rgba(16, 185, 129, 0.1);
            animation: checkmark 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }

            50% {
                transform: scale(1.2) rotate(5deg);
            }

            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .success-icon::before {
            content: '✓';
            color: #fff;
            font-size: 4rem;
            font-weight: bold;
            line-height: 1;
        }

        .success-message {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: 0.02em;
        }

        .success-subtitle {
            font-size: 1.125rem;
            color: #6b7280;
            margin-top: 0.75rem;
        }

        .button-container {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 1.25rem 3rem;
            font-size: 1.125rem;
            font-weight: 600;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .action-button:active {
            transform: scale(0.95);
        }

        .action-button:first-child {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .action-button:first-child:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .action-button:last-child {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        .action-button:last-child:hover {
            background: #fff;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .action-button span {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .success-box {
                padding: 3rem 2rem;
                border-radius: 24px;
            }

            .success-icon {
                width: 80px;
                height: 80px;
            }

            .success-icon::before {
                font-size: 3rem;
            }

            .success-message {
                font-size: 2rem;
            }

            .button-container {
                flex-direction: column;
                gap: 1rem;
            }

            .action-button {
                width: 100%;
                padding: 1rem 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-box">
            <div class="success-icon"></div>
            <div class="success-message">送信完了</div>
            <div class="success-subtitle">試合結果が正常に記録されました</div>
        </div>

        <div class="button-container">
            <button class="action-button" onclick="location.href='match_input.php?division_id=<?= $division_id ?>'">
                <span>連続で入力する</span>
            </button>

            <button class="action-button" onclick="location.href='../index.php'">
                <span>部門選択画面に戻る</span>
            </button>
        </div>
    </div>
</body>

</html>