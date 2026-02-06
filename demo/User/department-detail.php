<?php
// department-detail.php - 自動検出版（team_match_id を辿って teams を表示）
require_once __DIR__ . '/../connect/db_connect.php'; // 必要ならパスを修正

// params
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$dept_id       = isset($_GET['dept']) ? (int)$_GET['dept'] : 2;
$q             = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($tournament_id <= 0 || $dept_id <= 0) {
    http_response_code(400);
    echo "大会ID と 部門ID を指定してください。例: ?id=1&dept=2";
    exit;
}

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function markWithdraw($flag){ return ((int)$flag === 1) ? '（棄権）' : ''; }
function table_exists($pdo, $name){
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
    $stmt->execute([':t'=>$name]);
    return ((int)$stmt->fetchColumn()) > 0;
}
function columns_of($pdo, $table){
    try {
        return array_column($pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (Exception $e) { return []; }
}

// fetch tournament & department
$stmt = $pdo->prepare("SELECT id, title, event_date, venue FROM tournaments WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name, distinction FROM departments WHERE id = :did AND tournament_id = :tid LIMIT 1");
$stmt->execute([':did'=>$dept_id, ':tid'=>$tournament_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tournament || !$department) {
    http_response_code(404);
    echo "大会または部門が見つかりません。";
    exit;
}

$distinction = (int)($department['distinction'] ?? 0);

// prepare: which columns exist in individual_matches
$imCols = columns_of($pdo, 'individual_matches');
$has_team_red = in_array('team_red_id', $imCols, true) || in_array('team_red', $imCols, true);
$has_team_white = in_array('team_white_id', $imCols, true) || in_array('team_white', $imCols, true);
$has_team_match = in_array('team_match_id', $imCols, true);
$matchNumCol = in_array('individual_match_num', $imCols, true) ? 'individual_match_num' : (in_array('match_number', $imCols, true) ? 'match_number' : (in_array('match_id', $imCols, true) ? 'match_id' : 'match_id'));
$matchFieldCol = in_array('match_field', $imCols, true) ? 'match_field' : null;

// helper: fetch team rows by ids
function fetch_teams_map($pdo, array $ids){
    $map = [];
    if (empty($ids)) return $map;
    $ids = array_values(array_unique(array_filter($ids)));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, team_number, withdraw_flg FROM teams WHERE id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $map[(int)$r['id']] = $r;
    return $map;
}

// main: build $matches
$matches = [];
$teamMatchDetails = []; // team_match_id => array of individual match details

try {
    if ($distinction === 1) {
        // 団体戦
        if ($has_team_red && $has_team_white) {
            // direct: individual_matches has team_red_id/team_white_id (not in your schema, but keep for safety)
            $sql = "SELECT match_id, {$matchNumCol} AS match_number, " . ($matchFieldCol ? "{$matchFieldCol} AS match_field," : "NULL AS match_field,") . " team_red_id, team_white_id, red_score, white_score, red_win_count, white_win_count, winner, wo_flg FROM individual_matches WHERE department_id = :dept ORDER BY " . ($matchFieldCol ? "{$matchFieldCol} ASC, " : "") . "{$matchNumCol} ASC, match_id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dept'=>$dept_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $ids = [];
            foreach ($rows as $r) {
                if (!empty($r['team_red_id'])) $ids[] = (int)$r['team_red_id'];
                if (!empty($r['team_white_id'])) $ids[] = (int)$r['team_white_id'];
            }
            $teamMap = fetch_teams_map($pdo, $ids);

            foreach ($rows as $r) {
                $redId = (int)($r['team_red_id'] ?? 0);
                $whiteId = (int)($r['team_white_id'] ?? 0);
                $matches[] = [
                    'match_id' => $r['match_id'],
                    'match_number' => $r['match_number'] ?? $r['match_id'],
                    'match_field' => $r['match_field'] ?? '未設定',
                    'red_id' => $redId,
                    'white_id' => $whiteId,
                    'red_name' => $teamMap[$redId]['name'] ?? ($redId ? "チーム #{$redId}" : '未設定'),
                    'white_name' => $teamMap[$whiteId]['name'] ?? ($whiteId ? "チーム #{$whiteId}" : '未設定'),
                    'red_number' => $teamMap[$redId]['team_number'] ?? null,
                    'white_number' => $teamMap[$whiteId]['team_number'] ?? null,
                    'red_withdraw' => $teamMap[$redId]['withdraw_flg'] ?? 0,
                    'white_withdraw' => $teamMap[$whiteId]['withdraw_flg'] ?? 0,
                    'red_score' => $r['red_score'] ?? null,
                    'white_score' => $r['white_score'] ?? null,
                    'red_win_count' => $r['red_win_count'] ?? null,
                    'white_win_count' => $r['white_win_count'] ?? null,
                    'winner' => $r['winner'] ?? null,
                    'wo_flg' => $r['wo_flg'] ?? 0,
                ];
            }

        } elseif ($has_team_match) {
            // team_match_id exists in individual_matches. We need to resolve team_match_id -> team ids.
            // Try candidate tables that might store mapping: team_match_results, team_order, team_match (unknown)
            $candidateTables = ['team_match_results','team_order','team_matches','team_match']; // order of preference
            $found = false;
            $teamMap = [];

            // fetch rows first
            $sql = "SELECT match_id, {$matchNumCol} AS match_number, " . ($matchFieldCol ? "{$matchFieldCol} AS match_field," : "NULL AS match_field,") . " team_match_id, red_score, white_score, red_win_count, white_win_count, winner, wo_flg FROM individual_matches WHERE department_id = :dept ORDER BY " . ($matchFieldCol ? "{$matchFieldCol} ASC, " : "") . "{$matchNumCol} ASC, match_id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dept'=>$dept_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // collect team_match_id values
            $tmIds = [];
            foreach ($rows as $r) if (!empty($r['team_match_id'])) $tmIds[] = (int)$r['team_match_id'];
            $tmIds = array_values(array_unique($tmIds));

            foreach ($candidateTables as $tbl) {
                if (!table_exists($pdo, $tbl)) continue;
                $cols = columns_of($pdo, $tbl);
                // case A: table has team_red_id & team_white_id
                if (in_array('team_red_id', $cols, true) && in_array('team_white_id', $cols, true) && in_array('id', $cols, true)) {
                    // fetch mapping
                    if (!empty($tmIds)) {
                        $in = implode(',', array_fill(0, count($tmIds), '?'));
                        $stmt2 = $pdo->prepare("SELECT id, team_red_id, team_white_id FROM {$tbl} WHERE id IN ($in)");
                        $stmt2->execute($tmIds);
                        $mapRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                        $teamIds = [];
                        foreach ($mapRows as $mr) {
                            if (!empty($mr['team_red_id'])) $teamIds[] = (int)$mr['team_red_id'];
                            if (!empty($mr['team_white_id'])) $teamIds[] = (int)$mr['team_white_id'];
                        }
                        $teamMap = fetch_teams_map($pdo, $teamIds);
                        // build matches using mapRows
                        $tmMapById = [];
                        foreach ($mapRows as $mr) $tmMapById[(int)$mr['id']] = $mr;
                        foreach ($rows as $r) {
                            $tmid = (int)($r['team_match_id'] ?? 0);
                            $redId = $tmMapById[$tmid]['team_red_id'] ?? null;
                            $whiteId = $tmMapById[$tmid]['team_white_id'] ?? null;
                            $matches[] = [
                                'match_id' => $r['match_id'],
                                'match_number' => $r['match_number'] ?? $r['match_id'],
                                'match_field' => $r['match_field'] ?? '未設定',
                                'red_id' => $redId,
                                'white_id' => $whiteId,
                                'red_name' => $teamMap[$redId]['name'] ?? ($redId ? "チーム #{$redId}" : '未設定'),
                                'white_name' => $teamMap[$whiteId]['name'] ?? ($whiteId ? "チーム #{$whiteId}" : '未設定'),
                                'red_number' => $teamMap[$redId]['team_number'] ?? null,
                                'white_number' => $teamMap[$whiteId]['team_number'] ?? null,
                                'red_withdraw' => $teamMap[$redId]['withdraw_flg'] ?? 0,
                                'white_withdraw' => $teamMap[$whiteId]['withdraw_flg'] ?? 0,
                                'red_score' => $r['red_score'] ?? null,
                                'white_score' => $r['white_score'] ?? null,
                                'red_win_count' => $r['red_win_count'] ?? null,
                                'white_win_count' => $r['white_win_count'] ?? null,
                                'winner' => $r['winner'] ?? null,
                                'wo_flg' => $r['wo_flg'] ?? 0,
                            ];
                        }
                        $found = true;
                        break;
                    }
                }

                // case B: table stores team_id rows with side column (e.g., team_id + side='red'/'white' or order)
                if (in_array('team_match_id', $cols, true) && in_array('team_id', $cols, true)) {
                    // fetch rows for these team_match_id values
                    if (!empty($tmIds)) {
                        $in = implode(',', array_fill(0, count($tmIds), '?'));
                        $stmt2 = $pdo->prepare("SELECT team_match_id, team_id, IFNULL(side, '') AS side, IFNULL(`order`, 0) AS ord FROM {$tbl} WHERE team_match_id IN ($in)");
                        $stmt2->execute($tmIds);
                        $mapRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                        // group by team_match_id
                        $byTm = [];
                        $teamIds = [];
                        foreach ($mapRows as $mr) {
                            $tmid = (int)$mr['team_match_id'];
                            $byTm[$tmid][] = $mr;
                            $teamIds[] = (int)$mr['team_id'];
                        }
                        $teamMap = fetch_teams_map($pdo, $teamIds);
                        // build matches: try to detect side or order
                        foreach ($rows as $r) {
                            $tmid = (int)($r['team_match_id'] ?? 0);
                            $redId = null; $whiteId = null;
                            if (!empty($byTm[$tmid])) {
                                foreach ($byTm[$tmid] as $entry) {
                                    $tid = (int)$entry['team_id'];
                                    $side = strtolower((string)$entry['side']);
                                    if ($side === 'red' || $side === 'aka' || $side === 'r') $redId = $tid;
                                    elseif ($side === 'white' || $side === 'shiro' || $side === 'w') $whiteId = $tid;
                                }
                                // fallback by order: ord 1 -> red, ord 2 -> white
                                if ($redId === null || $whiteId === null) {
                                    foreach ($byTm[$tmid] as $entry) {
                                        if ($entry['ord'] == 1 && $redId === null) $redId = (int)$entry['team_id'];
                                        if ($entry['ord'] == 2 && $whiteId === null) $whiteId = (int)$entry['team_id'];
                                    }
                                }
                            }
                            $matches[] = [
                                'match_id' => $r['match_id'],
                                'match_number' => $r['match_number'] ?? $r['match_id'],
                                'match_field' => $r['match_field'] ?? '未設定',
                                'red_id' => $redId,
                                'white_id' => $whiteId,
                                'red_name' => $teamMap[$redId]['name'] ?? ($redId ? "チーム #{$redId}" : '未設定'),
                                'white_name' => $teamMap[$whiteId]['name'] ?? ($whiteId ? "チーム #{$whiteId}" : '未設定'),
                                'red_number' => $teamMap[$redId]['team_number'] ?? null,
                                'white_number' => $teamMap[$whiteId]['team_number'] ?? null,
                                'red_withdraw' => $teamMap[$redId]['withdraw_flg'] ?? 0,
                                'white_withdraw' => $teamMap[$whiteId]['withdraw_flg'] ?? 0,
                                'red_score' => $r['red_score'] ?? null,
                                'white_score' => $r['white_score'] ?? null,
                                'red_win_count' => $r['red_win_count'] ?? null,
                                'white_win_count' => $r['white_win_count'] ?? null,
                                'winner' => $r['winner'] ?? null,
                                'wo_flg' => $r['wo_flg'] ?? 0,
                            ];
                        }
                        $found = true;
                        break;
                    }
                }
            } // end foreach candidateTables

            if (!$found) {
                // fallback: show team_match_id as label
                foreach ($rows as $r) {
                    $matches[] = [
                        'match_id' => $r['match_id'],
                        'match_number' => $r['match_number'] ?? $r['match_id'],
                        'match_field' => $r['match_field'] ?? '未設定',
                        'red_id' => null,
                        'white_id' => null,
                        'red_name' => '未設定',
                        'white_name' => '未設定',
                        'red_number' => null,
                        'white_number' => null,
                        'red_withdraw' => 0,
                        'white_withdraw' => 0,
                        'red_score' => $r['red_score'] ?? null,
                        'white_score' => $r['white_score'] ?? null,
                        'red_win_count' => $r['red_win_count'] ?? null,
                        'white_win_count' => $r['white_win_count'] ?? null,
                        'winner' => $r['winner'] ?? null,
                        'wo_flg' => $r['wo_flg'] ?? 0,
                        'team_match_id' => $r['team_match_id'] ?? null,
                    ];
                }
            }

        } else {
            // no team info: show basic rows
            $sql = "SELECT match_id, individual_match_num AS match_number, match_field, team_match_id, red_score, white_score, winner FROM individual_matches WHERE department_id = :dept ORDER BY match_field ASC, individual_match_num ASC, match_id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dept'=>$dept_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $matches[] = [
                    'match_id' => $r['match_id'],
                    'match_number' => $r['match_number'] ?? $r['match_id'],
                    'match_field' => $r['match_field'] ?? '未設定',
                    'red_id' => null,
                    'white_id' => null,
                    'red_name' => '未設定',
                    'white_name' => '未設定',
                    'red_score' => $r['red_score'] ?? null,
                    'white_score' => $r['white_score'] ?? null,
                    'winner' => $r['winner'] ?? null,
                ];
            }
        }

        // server-side search
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $matches = array_values(array_filter($matches, function($m) use ($qLower){
                $hay = mb_strtolower(($m['red_name'] ?? '') . ' ' . ($m['white_name'] ?? '') . ' ' . ($m['red_number'] ?? '') . ' ' . ($m['white_number'] ?? '') . ' ' . ($m['team_match_id'] ?? ''));
                return mb_strpos($hay, $qLower) !== false;
            }));
        }

        // Fetch individual match details for team matches
        $teamMatchIds = [];
        foreach ($matches as $m) {
            if (!empty($m['team_match_id'])) {
                $teamMatchIds[] = (int)$m['team_match_id'];
            }
        }
        
        if (!empty($teamMatchIds)) {
            $teamMatchIds = array_values(array_unique($teamMatchIds));
            $placeholders = implode(',', array_fill(0, count($teamMatchIds), '?'));
            
            $sql = "SELECT im.match_id, im.team_match_id, im.individual_match_num, im.order_id,
                           pa.id AS a_id, pa.name AS a_name, pa.player_number AS a_number,
                           pb.id AS b_id, pb.name AS b_name, pb.player_number AS b_number,
                           im.first_technique, im.first_winner,
                           im.second_technique, im.second_winner,
                           im.third_technique, im.third_winner,
                           im.judgement, im.final_winner
                    FROM individual_matches im
                    LEFT JOIN players pa ON pa.id = im.player_a_id
                    LEFT JOIN players pb ON pb.id = im.player_b_id
                    WHERE im.team_match_id IN ($placeholders) AND im.department_id = :dept
                    ORDER BY im.order_id ASC, im.individual_match_num ASC";
            
            $stmt = $pdo->prepare($sql);
            $params = $teamMatchIds;
            $params[] = $dept_id;
            $stmt->execute($params);
            $detailRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($detailRows as $row) {
                $tmid = (int)$row['team_match_id'];
                if (!isset($teamMatchDetails[$tmid])) {
                    $teamMatchDetails[$tmid] = [];
                }
                $teamMatchDetails[$tmid][] = $row;
            }
        }

    } else {
        // 個人戦: fetch with technique winners
        $sql = "SELECT im.match_id, im.individual_match_num AS match_number, im.match_field, im.order_id,
                       pa.id AS a_id, pa.name AS a_name, pa.player_number AS a_number,
                       pb.id AS b_id, pb.name AS b_name, pb.player_number AS b_number,
                       im.first_technique, im.first_winner,
                       im.second_technique, im.second_winner,
                       im.third_technique, im.third_winner,
                       im.judgement, im.final_winner
                FROM individual_matches im
                LEFT JOIN players pa ON pa.id = im.player_a_id
                LEFT JOIN players pb ON pb.id = im.player_b_id
                WHERE im.department_id = :dept
                ORDER BY im.match_field ASC, im.individual_match_num ASC, im.match_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':dept'=>$dept_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            foreach ($rows as $r) {
                $hay = mb_strtolower(($r['a_name'] ?? '') . ' ' . ($r['b_name'] ?? '') . ' ' . ($r['a_number'] ?? '') . ' ' . ($r['b_number'] ?? ''));
                if (mb_strpos($hay, $qLower) !== false) $matches[] = $r;
            }
        } else {
            $matches = $rows;
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $matches = [];
}

// group by field
$grouped = [];
foreach ($matches as $m) {
    $field = $m['match_field'] ?? '未設定';
    $grouped[$field][] = $m;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($tournament['title']) ?> - <?= esc($department['name']) ?></title>
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
h1 {
    color: #333;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
}
h3 {
    background: #007bff;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    margin-top: 30px;
}
.match-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.match-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}
.match-id {
    font-size: 0.9em;
    color: #666;
}
.players {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.player {
    flex: 1;
    text-align: center;
}
.player-name {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 5px;
}
.player-number {
    color: #666;
    font-size: 0.9em;
}
.vs {
    font-weight: bold;
    color: #999;
    padding: 0 20px;
}
.techniques {
    background: #f9f9f9;
    padding: 12px;
    border-radius: 5px;
    margin: 10px 0;
}
.technique-item {
    margin: 8px 0;
    padding: 5px 0;
}
.technique-label {
    font-weight: bold;
    color: #555;
}
.technique-name {
    color: #333;
    margin: 0 8px;
}
.technique-winner {
    font-weight: bold;
    margin-left: 10px;
}
.winner-a {
    color: #d9534f;
}
.winner-b {
    color: #0275d8;
}
.final-result {
    text-align: center;
    margin-top: 15px;
    padding-top: 10px;
    border-top: 2px solid #f0f0f0;
}
.final-winner {
    font-size: 1.2em;
    font-weight: bold;
    color: #5cb85c;
}
.judgement {
    margin-top: 8px;
    color: #666;
    font-size: 0.95em;
}
.no-techniques {
    color: #999;
    font-style: italic;
}
.team-score {
    font-size: 1.1em;
    margin: 10px 0;
}
.score-value {
    font-weight: bold;
    font-size: 1.3em;
}
.summary {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}
</style>
</head>
<body>
  <h1><?= esc($tournament['title']) ?> — <?= esc($department['name']) ?></h1>
  
  <div class="summary">
    <p><strong>該当試合:</strong> <?= array_sum(array_map('count', $grouped)) ?> 件</p>
    <?php if ($q !== ''): ?>
      <p><strong>検索キーワード:</strong> <?= esc($q) ?></p>
    <?php endif; ?>
  </div>

  <?php if (empty($grouped)): ?>
    <div class="match-card">
      <p class="no-techniques">該当する試合はありません。</p>
    </div>
  <?php else: ?>
    <?php foreach ($grouped as $field => $list): ?>
      <h3>場 <?= esc($field) ?></h3>
      
      <?php foreach ($list as $m): ?>
        <div class="match-card">
          <div class="match-header">
            <div class="match-id">
              <strong>試合番号:</strong> <?= esc($m['match_number'] ?? '-') ?> 
              <span style="margin-left: 15px;"><strong>ID:</strong> <?= esc($m['match_id'] ?? '-') ?></span>
            </div>
          </div>

          <?php if ($distinction === 1): ?>
            <!-- 団体戦 -->
            <div class="players">
              <div class="player">
                <div class="player-name" style="color: #d9534f;">
                  <?= esc($m['red_name'] ?? '-') ?>
                  <?php if ($m['red_withdraw'] ?? 0): ?>
                    <span style="font-size: 0.8em; color: #999;">（棄権）</span>
                  <?php endif; ?>
                </div>
                <div class="player-number">No. <?= esc($m['red_number'] ?? '-') ?></div>
              </div>
              <div class="vs">VS</div>
              <div class="player">
                <div class="player-name" style="color: #0275d8;">
                  <?= esc($m['white_name'] ?? '-') ?>
                  <?php if ($m['white_withdraw'] ?? 0): ?>
                    <span style="font-size: 0.8em; color: #999;">（棄権）</span>
                  <?php endif; ?>
                </div>
                <div class="player-number">No. <?= esc($m['white_number'] ?? '-') ?></div>
              </div>
            </div>

            <!-- スコア表示 -->
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;">
              <div style="display: flex; justify-content: space-around; align-items: center; margin-bottom: 10px;">
                <div style="text-align: center; flex: 1;">
                  <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">赤チーム</div>
                  <div class="score-value" style="color: #d9534f; font-size: 2em;">
                    <?= esc($m['red_score'] ?? '-') ?>
                  </div>
                </div>
                <div style="font-size: 1.5em; font-weight: bold; color: #999;">-</div>
                <div style="text-align: center; flex: 1;">
                  <div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">白チーム</div>
                  <div class="score-value" style="color: #0275d8; font-size: 2em;">
                    <?= esc($m['white_score'] ?? '-') ?>
                  </div>
                </div>
              </div>

              <?php if (isset($m['red_win_count']) || isset($m['white_win_count'])): ?>
                <div style="text-align: center; padding-top: 10px; border-top: 1px solid #e0e0e0;">
                  <span style="color: #666;">勝ち数:</span>
                  <span style="color: #d9534f; font-weight: bold; font-size: 1.1em; margin: 0 5px;">
                    <?= esc($m['red_win_count'] ?? '-') ?>
                  </span>
                  <span style="color: #999;">-</span>
                  <span style="color: #0275d8; font-weight: bold; font-size: 1.1em; margin: 0 5px;">
                    <?= esc($m['white_win_count'] ?? '-') ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($m['winner'])): ?>
              <div class="final-result">
                <?php
                $teamWinner = $m['winner'];
                $teamWinnerLower = strtolower((string)$teamWinner);
                $teamWinnerName = '';
                $teamWinnerClass = '';
                
                if ($teamWinner == $m['red_id'] || $teamWinnerLower === 'red' || $teamWinnerLower === 'aka') {
                  $teamWinnerName = $m['red_name'] ?? '赤チーム';
                  $teamWinnerClass = 'winner-a';
                } elseif ($teamWinner == $m['white_id'] || $teamWinnerLower === 'white' || $teamWinnerLower === 'shiro') {
                  $teamWinnerName = $m['white_name'] ?? '白チーム';
                  $teamWinnerClass = 'winner-b';
                } else {
                  $teamWinnerName = $teamWinner;
                }
                ?>
                <div class="final-winner <?= $teamWinnerClass ?>">
                  ✓ 勝者: <?= esc($teamWinnerName) ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($m['wo_flg']) && $m['wo_flg'] == 1): ?>
              <div style="text-align: center; margin-top: 10px; color: #f0ad4e; font-weight: bold;">
                ⚠ 不戦勝
              </div>
            <?php endif; ?>

            <?php if (isset($m['team_match_id'])): ?>
              <div style="color: #999; font-size: 0.85em; margin-top: 10px; text-align: right;">
                team_match_id: <?= esc($m['team_match_id']) ?>
              </div>
            <?php endif; ?>

            <?php
            // Display individual match details for this team match
            if (!empty($m['team_match_id']) && isset($teamMatchDetails[$m['team_match_id']])):
            ?>
              <div style="margin-top: 20px; border-top: 2px solid #e0e0e0; padding-top: 15px;">
                <h4 style="color: #555; margin-bottom: 15px; font-size: 1.1em;">【個別対戦】</h4>
                
                <?php foreach ($teamMatchDetails[$m['team_match_id']] as $detail): ?>
                  <div style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 5px; padding: 12px; margin-bottom: 12px;">
                    <!-- 選手名 -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                      <div style="flex: 1; text-align: center;">
                        <span style="font-weight: bold; color: #d9534f;">
                          <?= esc($detail['a_name'] ?? '選手A') ?>
                        </span>
                        <span style="color: #999; font-size: 0.9em; margin-left: 5px;">
                          (<?= esc($detail['a_number'] ?? '-') ?>)
                        </span>
                      </div>
                      <div style="font-weight: bold; color: #999; padding: 0 15px;">VS</div>
                      <div style="flex: 1; text-align: center;">
                        <span style="font-weight: bold; color: #0275d8;">
                          <?= esc($detail['b_name'] ?? '選手B') ?>
                        </span>
                        <span style="color: #999; font-size: 0.9em; margin-left: 5px;">
                          (<?= esc($detail['b_number'] ?? '-') ?>)
                        </span>
                      </div>
                    </div>

                    <?php if (!empty($detail['order_id'])): ?>
                      <div style="font-size: 0.85em; color: #666; margin-bottom: 8px;">
                        順<?= esc($detail['order_id']) ?>
                      </div>
                    <?php endif; ?>

                    <!-- 技の表示 -->
                    <?php
                    $techniques = [
                      ['name' => $detail['first_technique'] ?? '', 'winner' => $detail['first_winner'] ?? ''],
                      ['name' => $detail['second_technique'] ?? '', 'winner' => $detail['second_winner'] ?? ''],
                      ['name' => $detail['third_technique'] ?? '', 'winner' => $detail['third_winner'] ?? ''],
                    ];
                    $hasAnyTech = false;
                    
                    foreach ($techniques as $i => $tech):
                      if (!empty($tech['name'])):
                        $hasAnyTech = true;
                        $techNum = $i + 1;
                        $winner = $tech['winner'] ?? '';
                        $winnerName = '';
                        $winnerClass = '';
                        $winnerLower = strtolower((string)$winner);
                        
                        if ($winner == $detail['a_id'] || $winner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red') {
                          $winnerName = $detail['a_name'] ?? '選手A';
                          $winnerClass = 'winner-a';
                        } elseif ($winner == $detail['b_id'] || $winner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white') {
                          $winnerName = $detail['b_name'] ?? '選手B';
                          $winnerClass = 'winner-b';
                        }
                    ?>
                        <div style="margin: 5px 0; padding: 5px; <?php 
                          if ($winnerName) {
                            if ($winnerClass === 'winner-a') {
                              echo 'background: #ffe6e6; border-left: 3px solid #d9534f; padding-left: 8px;';
                            } else {
                              echo 'background: #e6f2ff; border-left: 3px solid #0275d8; padding-left: 8px;';
                            }
                          }
                        ?>">
                          <span style="font-weight: bold; font-size: 0.9em; <?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;') : 'color: #555;' ?>">
                            第<?= $techNum ?>技:
                          </span>
                          <span style="font-size: 0.9em; <?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f; font-weight: bold;' : 'color: #0275d8; font-weight: bold;') : 'color: #333;' ?>">
                            <?= esc($tech['name']) ?>
                          </span>
                          <?php if ($winnerName): ?>
                            <span style="font-size: 0.85em; margin-left: 8px; <?= $winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;' ?>">
                              🏆 <?= esc($winnerName) ?>
                            </span>
                          <?php endif; ?>
                        </div>
                    <?php 
                      endif;
                    endforeach;
                    
                    if (!$hasAnyTech):
                    ?>
                      <div style="color: #999; font-style: italic; font-size: 0.9em;">技の記録なし</div>
                    <?php endif; ?>

                    <!-- 最終結果 -->
                    <?php if (!empty($detail['final_winner'])): ?>
                      <?php
                      $finalWinner = $detail['final_winner'];
                      $finalWinnerName = '';
                      $finalWinnerClass = '';
                      $finalWinnerLower = strtolower((string)$finalWinner);
                      
                      if ($finalWinner == $detail['a_id'] || $finalWinner === 'player_a' || $finalWinnerLower === 'a' || $finalWinnerLower === 'red') {
                        $finalWinnerName = $detail['a_name'] ?? '選手A';
                        $finalWinnerClass = 'winner-a';
                      } elseif ($finalWinner == $detail['b_id'] || $finalWinner === 'player_b' || $finalWinnerLower === 'b' || $finalWinnerLower === 'white') {
                        $finalWinnerName = $detail['b_name'] ?? '選手B';
                        $finalWinnerClass = 'winner-b';
                      } else {
                        $finalWinnerName = $finalWinner;
                      }
                      ?>
                      <div style="text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e0e0e0;">
                        <span style="font-weight: bold; font-size: 0.95em; <?= $finalWinnerClass === 'winner-a' ? 'color: #d9534f;' : ($finalWinnerClass === 'winner-b' ? 'color: #0275d8;' : 'color: #5cb85c;') ?>">
                          ✓ <?= esc($finalWinnerName) ?>
                        </span>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($detail['judgement'])): ?>
                      <div style="text-align: center; margin-top: 5px; color: #666; font-size: 0.85em;">
                        <?= esc($detail['judgement']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          <?php else: ?>
            <!-- 個人戦 -->
            <div class="players">
              <div class="player">
                <div class="player-name">
                  <?= esc($m['a_name'] ?? '選手A') ?>
                </div>
                <div class="player-number">No. <?= esc($m['a_number'] ?? '-') ?></div>
              </div>
              <div class="vs">VS</div>
              <div class="player">
                <div class="player-name">
                  <?= esc($m['b_name'] ?? '選手B') ?>
                </div>
                <div class="player-number">No. <?= esc($m['b_number'] ?? '-') ?></div>
              </div>
            </div>

            <!-- 技と取得者の表示 -->
            <div class="techniques">
              <?php
              $techniques = [
                ['name' => $m['first_technique'] ?? '', 'winner' => $m['first_winner'] ?? ''],
                ['name' => $m['second_technique'] ?? '', 'winner' => $m['second_winner'] ?? ''],
                ['name' => $m['third_technique'] ?? '', 'winner' => $m['third_winner'] ?? ''],
              ];
              $hasAnyTech = false;
              
              foreach ($techniques as $i => $tech):
                if (!empty($tech['name'])):
                  $hasAnyTech = true;
                  $techNum = $i + 1;
                  $winner = $tech['winner'] ?? '';
                  $winnerName = '';
                  $winnerClass = '';
                  
                  // winner判定（player_a_id, player_b_id, "player_a", "player_b", "red", "white" 形式に対応）
                  $winnerLower = strtolower((string)$winner);
                  if ($winner == $m['a_id'] || $winner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red') {
                    $winnerName = $m['a_name'] ?? '選手A';
                    $winnerClass = 'winner-a';
                  } elseif ($winner == $m['b_id'] || $winner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white') {
                    $winnerName = $m['b_name'] ?? '選手B';
                    $winnerClass = 'winner-b';
                  }
              ?>
                <div class="technique-item" style="<?php 
                  if ($winnerName) {
                    if ($winnerClass === 'winner-a') {
                      echo 'background: #ffe6e6; border-left: 4px solid #d9534f; padding-left: 8px;';
                    } else {
                      echo 'background: #e6f2ff; border-left: 4px solid #0275d8; padding-left: 8px;';
                    }
                  }
                ?>">
                  <span class="technique-label" style="font-weight: bold; <?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;') : 'color: #555;' ?>">
                    第<?= $techNum ?>技:
                  </span>
                  <span class="technique-name" style="<?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;') : 'color: #333;' ?>">
                    <?= esc($tech['name']) ?>
                  </span>
                  <?php if ($winnerName): ?>
                    <span class="technique-winner <?= $winnerClass ?>">
                      🏆 <?= esc($winnerName) ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php 
                endif;
              endforeach;
              
              if (!$hasAnyTech):
              ?>
                <div class="no-techniques">技の記録なし</div>
              <?php endif; ?>
            </div>

            <!-- 最終結果 -->
            <?php if (!empty($m['final_winner'])): ?>
              <div class="final-result">
                <?php
                $finalWinner = $m['final_winner'];
                $finalWinnerName = '';
                $finalWinnerClass = '';
                $finalWinnerLower = strtolower((string)$finalWinner);
                
                if ($finalWinner == $m['a_id'] || $finalWinner === 'player_a' || $finalWinnerLower === 'a' || $finalWinnerLower === 'red') {
                  $finalWinnerName = $m['a_name'] ?? '選手A';
                  $finalWinnerClass = 'winner-a';
                } elseif ($finalWinner == $m['b_id'] || $finalWinner === 'player_b' || $finalWinnerLower === 'b' || $finalWinnerLower === 'white') {
                  $finalWinnerName = $m['b_name'] ?? '選手B';
                  $finalWinnerClass = 'winner-b';
                } else {
                  $finalWinnerName = $finalWinner;
                }
                ?>
                <div class="final-winner <?= $finalWinnerClass ?>">
                  ✓ 勝者: <?= esc($finalWinnerName) ?>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($m['judgement'])): ?>
              <div class="judgement">
                判定: <?= esc($m['judgement']) ?>
              </div>
            <?php endif; ?>

          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>