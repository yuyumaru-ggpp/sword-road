<?php
session_start();
require_once '../../../../connect/db_connect.php';

// セッションチェック
if (!isset($_SESSION['tournament_editor'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// JSON データ取得
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['team_match_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$team_match_id = (int)$data['team_match_id'];

// スコア対象の技セット（メ/コ/ド/ツ/反/判 = 各1点）
$score_techs = ['メ', 'コ', 'ド', 'ツ', '反', '判'];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // team_match_idに属する全個別試合を取得
    $sql = "
        SELECT 
            final_winner,
            first_technique,  first_winner,
            second_technique, second_winner,
            third_technique,  third_winner,
            judgement
        FROM individual_matches
        WHERE team_match_id = :team_match_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
    $stmt->execute();
    $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $red_wins    = 0;
    $white_wins  = 0;
    $red_score   = 0;
    $white_score = 0;

    foreach ($all_matches as $im) {
        $fw = strtolower($im['final_winner'] ?? '');

        // 勝利数カウント
        if ($fw === 'red' || $fw === 'a') $red_wins++;
        if ($fw === 'white' || $fw === 'b') $white_wins++;

        // 技ごとにスコアを加算
        $tech_slots = [
            ['tech' => $im['first_technique'],  'winner' => strtolower($im['first_winner']  ?? '')],
            ['tech' => $im['second_technique'], 'winner' => strtolower($im['second_winner'] ?? '')],
            ['tech' => $im['third_technique'],  'winner' => strtolower($im['third_winner']  ?? '')],
        ];

        foreach ($tech_slots as $slot) {
            $tech   = $slot['tech'] ?? '';
            $winner = $slot['winner'];
            if (empty($tech)) continue;

            // 判定（判）は final_winner の側に加算
            if ($tech === '判') {
                if ($fw === 'red' || $fw === 'a') {
                    $red_score++;
                } elseif ($fw === 'white' || $fw === 'b') {
                    $white_score++;
                }
                continue;
            }

            // それ以外の技はそれぞれの勝者カラムで判定
            if (in_array($tech, $score_techs)) {
                if ($winner === 'red' || $winner === 'a') {
                    $red_score++;
                } elseif ($winner === 'white' || $winner === 'b') {
                    $white_score++;
                }
            }
        }

        // judgement カラムに技が入っている場合（保険）
        $jt = $im['judgement'] ?? '';
        if (!empty($jt) && in_array($jt, $score_techs) && $jt !== '判') {
            if ($fw === 'red' || $fw === 'a') {
                $red_score++;
            } elseif ($fw === 'white' || $fw === 'b') {
                $white_score++;
            }
        }
    }

    // 最終勝者を決定（勝利数ベース）
    $team_winner = null;
    if ($red_wins > $white_wins) {
        $team_winner = 'red';
    } elseif ($white_wins > $red_wins) {
        $team_winner = 'white';
    }
    // 同数の場合はNULL（引き分け）

    // team_match_resultsを更新
    $sql_update = "
        UPDATE team_match_results
        SET red_win_count  = :red_wins,
            white_win_count = :white_wins,
            red_score      = :red_score,
            white_score    = :white_score,
            winner         = :winner
        WHERE id = :team_match_id
    ";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->bindValue(':red_wins',      $red_wins,    PDO::PARAM_INT);
    $stmt_update->bindValue(':white_wins',    $white_wins,  PDO::PARAM_INT);
    $stmt_update->bindValue(':red_score',     $red_score,   PDO::PARAM_INT);
    $stmt_update->bindValue(':white_score',   $white_score, PDO::PARAM_INT);

    if ($team_winner === null) {
        $stmt_update->bindValue(':winner', null, PDO::PARAM_NULL);
    } else {
        $stmt_update->bindValue(':winner', $team_winner, PDO::PARAM_STR);
    }

    $stmt_update->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
    $stmt_update->execute();

    echo json_encode([
        'status'       => 'ok',
        'message'      => 'Team match updated successfully',
        'red_wins'     => $red_wins,
        'white_wins'   => $white_wins,
        'red_score'    => $red_score,
        'white_score'  => $white_score,
        'winner'       => $team_winner
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>