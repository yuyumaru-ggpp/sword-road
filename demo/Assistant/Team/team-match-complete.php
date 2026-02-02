<?php
require_once 'team_db.php';

// セッションチェック
checkTeamSession();

// セッション変数を取得
$vars = getTeamVariables();
$tournament_id = $vars['tournament_id'];
$division_id   = $vars['division_id'];
$match_number  = $vars['match_number'];
$team_red_id   = $vars['team_red_id'];
$team_white_id = $vars['team_white_id'];



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
        if ($score !== '▼' && $score !== '▲' && $score !== '×' && $score !== '') {
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
    
    // team_matchesテーブルに保存
    $sql = "
    INSERT INTO team_matches (
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
        1,
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
        ':department_id'   => $_SESSION['division_id'],
        ':match_number'    => $_SESSION['match_number'],
        ':team_red_id'     => $_SESSION['team_red_id'],
        ':team_white_id'   => $_SESSION['team_white_id'],
        ':winner'          => $finalWinner,
        ':red_win_count'   => $redWins,
        ':white_win_count' => $whiteWins,
        ':red_score'       => $redTotalPoints,
        ':white_score'     => $whiteTotalPoints
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
    
    $matchField = 1; // 試合場（固定値、必要に応じて変更）
    
    foreach ($matchResults as $posName => $posData) {
        if (!isset($orderMapping[$posName])) {
            continue;
        }
        
        $result = calcMatchResult($posData);
        $orderNum = $orderMapping[$posName];
        
        // 赤チームと白チームの選手ID
        $redPlayerId = $redOrder[$posName] ?? null;
        $whitePlayerId = $whiteOrder[$posName] ?? null;
        
        // 技の取得（first, second, third）
        $redScores = $posData['red']['scores'] ?? ['▼', '▼', '▼'];
        $whiteScores = $posData['white']['scores'] ?? ['▲', '▲', '▲'];
        
        $redFirstTech = ($redScores[0] !== '▼' && $redScores[0] !== '×') ? $redScores[0] : null;
        $redSecondTech = ($redScores[1] !== '▼' && $redScores[1] !== '×') ? $redScores[1] : null;
        $redThirdTech = ($redScores[2] !== '▼' && $redScores[2] !== '×') ? $redScores[2] : null;
        
        $whiteFirstTech = ($whiteScores[0] !== '▲' && $whiteScores[0] !== '×') ? $whiteScores[0] : null;
        $whiteSecondTech = ($whiteScores[1] !== '▲' && $whiteScores[1] !== '×') ? $whiteScores[1] : null;
        $whiteThirdTech = ($whiteScores[2] !== '▲' && $whiteScores[2] !== '×') ? $whiteScores[2] : null;
        
        // 勝者の技を判定
        $redWinnerIndex = $posData['red']['selected'] ?? -1;
        $whiteWinnerIndex = $posData['white']['selected'] ?? -1;
        
        $firstWinner = null;
        $secondWinner = null;
        $thirdWinner = null;
        
        if (is_array($redSelected) && in_array(0, $redSelected)) {
            $firstWinner = 'red';
        } else if (is_array($whiteSelected) && in_array(0, $whiteSelected)) {
            $firstWinner = 'white';
        }
        
        if (is_array($redSelected) && in_array(1, $redSelected)) {
            $secondWinner = 'red';
        } else if (is_array($whiteSelected) && in_array(1, $whiteSelected)) {
            $secondWinner = 'white';
        }
        
        if (is_array($redSelected) && in_array(2, $redSelected)) {
            $thirdWinner = 'red';
        } else if (is_array($whiteSelected) && in_array(2, $whiteSelected)) {
            $thirdWinner = 'white';
        }
        
        // 判定（一本勝、延長、引分け）
        $special = $posData['special'] ?? 'none';
        $judgement = null;
        if ($special === 'ippon') {
            $judgement = '一本勝';
        } else if ($special === 'extend') {
            $judgement = '延長';
        } else if ($special === 'draw') {
            $judgement = '引分け';
        }
        
        // 最終勝者
        $finalWinnerPos = $result['winner'];
        if ($finalWinnerPos === 'draw') {
            $finalWinnerPos = null;
        }
        
        // individual_matchesに保存
        $sql = "
        INSERT INTO individual_matches (
            department_id,
            team_match_id,
            match_field,
            order_id,
            player_a_id,
            player_b_id,
            started_at,
            ended_at,
            first_technique,
            first_winner,
            second_technique,
            second_winner,
            third_technique,
            third_winner,
            judgement,
            final_winner
        ) VALUES (
            :department_id,
            :team_match_id,
            :match_field,
            :order_id,
            :player_a_id,
            :player_b_id,
            NOW(),
            NOW(),
            :first_technique,
            :first_winner,
            :second_technique,
            :second_winner,
            :third_technique,
            :third_winner,
            :judgement,
            :final_winner
        )";
        
        $stmt = $pdo->prepare($sql);
        
        // 技は共通のscoresから取得
        $firstTech = $redFirstTech;
        $secondTech = $redSecondTech;
        $thirdTech = $redThirdTech;
        
        $stmt->execute([
            ':department_id'    => $_SESSION['division_id'],
            ':team_match_id'    => $team_match_id,
            ':match_field'      => $matchField,
            ':order_id'         => $orderNum,
            ':player_a_id'      => $redPlayerId,
            ':player_b_id'      => $whitePlayerId,
            ':first_technique'  => $firstTech,
            ':first_winner'     => $firstWinner,
            ':second_technique' => $secondTech,
            ':second_winner'    => $secondWinner,
            ':third_technique'  => $thirdTech,
            ':third_winner'     => $thirdWinner,
            ':judgement'        => $judgement,
            ':final_winner'     => $finalWinnerPos
        ]);
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
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans',Meiryo,sans-serif;
    background:#f5f5f5;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem;
}
.container{max-width:1100px;width:100%;}
.success-box{
    background:#fff;
    border:4px solid #22c55e;
    border-radius:24px;
    padding:4rem 3rem;
    text-align:center;
    margin-bottom:3rem;
}
.success-icon{
    width:80px;
    height:80px;
    background:#22c55e;
    border-radius:16px;
    margin:0 auto 1.5rem;
    display:flex;
    align-items:center;
    justify-content:center;
}
.success-icon::before{
    content:'✓';
    color:#fff;
    font-size:3.5rem;
    font-weight:bold;
}
.success-message{
    font-size:2.5rem;
    font-weight:bold;
}
.button-container{
    display:flex;
    gap:2rem;
    justify-content:center;
    flex-wrap:wrap;
}
.action-button{
    padding:1.25rem 3.5rem;
    font-size:1.25rem;
    border:3px solid #000;
    border-radius:50px;
    background:#fff;
    cursor:pointer;
    transition:background-color 0.2s;
}
.action-button:hover{background:#f9fafb;}
</style>
</head>
<body>
<div class="container">
    <div class="success-box">
        <div class="success-icon"></div>
        <div class="success-message">送信しました</div>
    </div>

    <div class="button-container">
        <button class="action-button"
            onclick="location.href='match_input.php?division_id=<?= $division_id ?>'">
            連続で入力する
        </button>
        <button class="action-button"
            onclick="location.href='../index.php'">
            部門選択画面に戻る
        </button>
    </div>
</div>
</body>
</html>