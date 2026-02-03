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
    
    // INSERT文のテンプレート
    $insertSql = "
    INSERT INTO individual_matches (
        department_id, team_match_id, match_field, order_id,
        player_a_id, player_b_id,
        started_at, ended_at,
        first_technique, first_winner,
        second_technique, second_winner,
        third_technique, third_winner,
        judgement, final_winner
    ) VALUES (
        :department_id, :team_match_id, :match_field, :order_id,
        :player_a_id, :player_b_id,
        NOW(), NOW(),
        :first_technique, :first_winner,
        :second_technique, :second_winner,
        :third_technique, :third_winner,
        :judgement, :final_winner
    )";

    foreach ($matchResults as $posName => $posData) {
        if (!isset($orderMapping[$posName])) {
            continue;
        }

        $orderNum = $orderMapping[$posName];
        $stmt = $pdo->prepare($insertSql);

        $result = calcMatchResult($posData);

        // 選手ID: 代表決定戦は別フィールドに格納される
        if ($posName === '代表決定戦') {
            $redPlayerId   = $posData['red_player_id']  ?? null;
            $whitePlayerId = $posData['white_player_id'] ?? null;
        } else {
            $redPlayerId   = $redOrder[$posName]  ?? null;
            $whitePlayerId = $whiteOrder[$posName] ?? null;
        }

        // scoresから技名を取得
        $scores = $posData['scores'] ?? [];
        $firstTech  = (isset($scores[0]) && $scores[0] !== '▼' && $scores[0] !== '▲' && $scores[0] !== '×' && $scores[0] !== '') ? $scores[0] : null;
        $secondTech = (isset($scores[1]) && $scores[1] !== '▼' && $scores[1] !== '▲' && $scores[1] !== '×' && $scores[1] !== '') ? $scores[1] : null;
        $thirdTech  = (isset($scores[2]) && $scores[2] !== '▼' && $scores[2] !== '▲' && $scores[2] !== '×' && $scores[2] !== '') ? $scores[2] : null;

        // selected配列から各枠の勝者を判定
        $redSelected   = $posData['red']['selected']   ?? [];
        $whiteSelected = $posData['white']['selected'] ?? [];
        if (!is_array($redSelected))   { $redSelected   = []; }
        if (!is_array($whiteSelected)) { $whiteSelected = []; }

        $firstWinner  = in_array(0, $redSelected) ? 'red' : (in_array(0, $whiteSelected) ? 'white' : null);
        $secondWinner = in_array(1, $redSelected) ? 'red' : (in_array(1, $whiteSelected) ? 'white' : null);
        $thirdWinner  = in_array(2, $redSelected) ? 'red' : (in_array(2, $whiteSelected) ? 'white' : null);

        // 判定（一本勝・延長・引分け等）
        $special = $posData['special'] ?? 'none';
        $judgement = null;
        if      ($special === 'ippon')  { $judgement = '一本勝'; }
        elseif  ($special === 'nihon')  { $judgement = '二本勝'; }
        elseif  ($special === 'extend') { $judgement = '延長';   }
        elseif  ($special === 'draw')   { $judgement = '引分け'; }
        elseif  ($special === 'hantei') { $judgement = '判定';   }

        // 最終勝者
        $finalWinner = ($result['winner'] !== 'draw') ? $result['winner'] : null;

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
            ':final_winner'     => $finalWinner
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