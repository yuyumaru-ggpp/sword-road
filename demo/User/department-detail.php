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

    } else {
        // 個人戦: existing logic
        $sql = "SELECT im.match_id, im.individual_match_num AS match_number, im.match_field, im.order_id,
                       pa.id AS a_id, pa.name AS a_name, pa.player_number AS a_number,
                       pb.id AS b_id, pb.name AS b_name, pb.player_number AS b_number,
                       im.first_technique, im.second_technique, im.third_technique, im.judgement, im.final_winner
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

// Below: render HTML (same as previous template). For brevity, render a simple view:
?>
<!doctype html>
<html lang="ja">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($tournament['title']) ?> - <?= esc($department['name']) ?></title></head>
<body>
  <h1><?= esc($tournament['title']) ?> — <?= esc($department['name']) ?></h1>
  <p>該当試合: <?= array_sum(array_map('count', $grouped)) ?> 件</p>
  <?php if (empty($grouped)): ?>
    <p>該当する試合はありません。</p>
  <?php else: ?>
    <?php foreach ($grouped as $field => $list): ?>
      <h3>場 <?= esc($field) ?></h3>
      <?php foreach ($list as $m): ?>
        <div style="border:1px solid #ccc;padding:8px;margin:6px 0;">
          <div>順: <?= esc($m['match_number'] ?? '-') ?> / ID: <?= esc($m['match_id'] ?? '-') ?></div>
          <?php if ($distinction === 1): ?>
            <div><strong><?= esc($m['red_name'] ?? '-') ?></strong> (No: <?= esc($m['red_number'] ?? '-') ?>) vs <strong><?= esc($m['white_name'] ?? '-') ?></strong> (No: <?= esc($m['white_number'] ?? '-') ?>)</div>
            <div>スコア: <?= esc($m['red_score'] ?? '-') ?> - <?= esc($m['white_score'] ?? '-') ?>　勝ち数: <?= esc($m['red_win_count'] ?? '-') ?> / <?= esc($m['white_win_count'] ?? '-') ?></div>
            <?php if (isset($m['team_match_id'])): ?><div>team_match_id: <?= esc($m['team_match_id']) ?></div><?php endif; ?>
          <?php else: ?>
            <div><strong><?= esc($m['a_name'] ?? '選手A') ?></strong> vs <strong><?= esc($m['b_name'] ?? '選手B') ?></strong></div>
            <div>技: <?= esc($m['first_technique'] ?? '-') ?> / <?= esc($m['second_technique'] ?? '-') ?> / <?= esc($m['third_technique'] ?? '-') ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>