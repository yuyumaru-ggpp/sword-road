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

if (!$data || !isset($data['match_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// ヘルパー関数: 空文字・▼・×を NULL に変換
function toNullIfEmpty($value)
{
    if ($value === null || $value === '' || $value === '▼' || $value === '×') {
        return null;
    }
    return trim($value);
}

// ヘルパー関数: 技のスコアを返す（メ=2点、コ/ド/ツ/反/判=1点、それ以外=0点）
function getTechScore($tech)
{
    if (empty($tech)) return 0;
    if ($tech === 'メ') return 2;
    if (in_array($tech, ['コ', 'ド', 'ツ', '反', '判'])) return 1;
    return 0;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ① individual_matches を更新
    $sql = "UPDATE individual_matches 
            SET first_technique  = :first_technique,
                first_winner     = :first_winner,
                second_technique = :second_technique,
                second_winner    = :second_winner,
                third_technique  = :third_technique,
                third_winner     = :third_winner,
                judgement        = :judgement,
                final_winner     = :final_winner,
                started_at       = :started_at,
                ended_at         = :ended_at
            WHERE match_id = :match_id";

    $stmt = $pdo->prepare($sql);

    // パラメータを処理
    $first_technique  = toNullIfEmpty($data['first_technique']  ?? null);
    $first_winner     = toNullIfEmpty($data['first_winner']     ?? null);
    $second_technique = toNullIfEmpty($data['second_technique'] ?? null);
    $second_winner    = toNullIfEmpty($data['second_winner']    ?? null);
    $third_technique  = toNullIfEmpty($data['third_technique']  ?? null);
    $third_winner     = toNullIfEmpty($data['third_winner']     ?? null);
    $judgement        = toNullIfEmpty($data['judgement']        ?? null);
    $final_winner     = toNullIfEmpty($data['final_winner']     ?? null);
    $started_at       = !empty($data['started_at']) ? $data['started_at'] : null;
    $ended_at         = !empty($data['ended_at'])   ? $data['ended_at']   : null;

    $stmt->bindValue(':match_id',         $data['match_id'], PDO::PARAM_INT);
    $stmt->bindValue(':first_technique',  $first_technique,  $first_technique  === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':first_winner',     $first_winner,     $first_winner     === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':second_technique', $second_technique, $second_technique === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':second_winner',    $second_winner,    $second_winner    === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':third_technique',  $third_technique,  $third_technique  === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':third_winner',     $third_winner,     $third_winner     === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':judgement',        $judgement,        $judgement        === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':final_winner',     $final_winner,     $final_winner     === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':started_at',       $started_at,       $started_at       === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':ended_at',         $ended_at,         $ended_at         === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

    $stmt->execute();
    $updated_rows = $stmt->rowCount();

    // ② team_match_results を更新
    // この個別試合が属する team_match_id を取得
    $sql_get_team = "SELECT team_match_id FROM individual_matches WHERE match_id = :match_id";
    $stmt_team = $pdo->prepare($sql_get_team);
    $stmt_team->bindValue(':match_id', $data['match_id'], PDO::PARAM_INT);
    $stmt_team->execute();
    $team_row = $stmt_team->fetch(PDO::FETCH_ASSOC);

    if ($team_row && !empty($team_row['team_match_id'])) {
        $team_match_id = $team_row['team_match_id'];

        // チーム戦に属する全個人試合を取得
        $sql_all = "
            SELECT
                final_winner,
                first_technique,  first_winner,
                second_technique, second_winner,
                third_technique,  third_winner,
                judgement
            FROM individual_matches
            WHERE team_match_id = :team_match_id
        ";
        $stmt_all = $pdo->prepare($sql_all);
        $stmt_all->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
        $stmt_all->execute();
        $all_matches = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        $red_wins    = 0;
        $white_wins  = 0;
        $red_score   = 0;
        $white_score = 0;

        foreach ($all_matches as $im) {
            $fw = strtolower($im['final_winner'] ?? '');
            $is_red   = ($fw === 'red' || $fw === 'a');
            $is_white = ($fw === 'white' || $fw === 'b');

            // 勝利数カウント
            if ($is_red)   $red_wins++;
            if ($is_white) $white_wins++;

            // 技スロットごとにスコアを加算
            // スコアは「その技を取った選手の側」に加算
            $tech_slots = [
                ['tech' => $im['first_technique'],  'winner' => strtolower($im['first_winner']  ?? '')],
                ['tech' => $im['second_technique'], 'winner' => strtolower($im['second_winner'] ?? '')],
                ['tech' => $im['third_technique'],  'winner' => strtolower($im['third_winner']  ?? '')],
            ];

            foreach ($tech_slots as $slot) {
                $tech        = $slot['tech']   ?? '';
                $tech_winner = $slot['winner'];
                if (empty($tech)) continue;

                $points = getTechScore($tech);
                if ($points === 0) continue;

                // 「判」は final_winner 側に加算
                if ($tech === '判') {
                    if ($is_red)   $red_score   += $points;
                    if ($is_white) $white_score += $points;
                    continue;
                }

                // その他の技は tech_winner 側に加算
                if ($tech_winner === 'red' || $tech_winner === 'a') {
                    $red_score   += $points;
                } elseif ($tech_winner === 'white' || $tech_winner === 'b') {
                    $white_score += $points;
                }
            }
        }

        // チーム最終勝者：勝利数で判定、同数ならスコア差で判定
        $team_winner = null;
        if ($red_wins > $white_wins) {
            $team_winner = 'red';
        } elseif ($white_wins > $red_wins) {
            $team_winner = 'white';
        } elseif ($red_score > $white_score) {
            $team_winner = 'red';
        } elseif ($white_score > $red_score) {
            $team_winner = 'white';
        }
        // 完全同点は NULL（引き分け）

        // team_match_results を更新
        $sql_update = "
            UPDATE team_match_results
            SET red_win_count   = :red_wins,
                white_win_count = :white_wins,
                red_score       = :red_score,
                white_score     = :white_score,
                winner          = :winner
            WHERE id = :team_match_id
        ";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindValue(':red_wins',      $red_wins,      PDO::PARAM_INT);
        $stmt_update->bindValue(':white_wins',    $white_wins,    PDO::PARAM_INT);
        $stmt_update->bindValue(':red_score',     $red_score,     PDO::PARAM_INT);
        $stmt_update->bindValue(':white_score',   $white_score,   PDO::PARAM_INT);
        $stmt_update->bindValue(':winner',        $team_winner,   $team_winner === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt_update->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
        $stmt_update->execute();
    }

    echo json_encode([
        'status'       => 'ok',
        'message'      => 'Match updated successfully',
        'updated_rows' => $updated_rows
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
