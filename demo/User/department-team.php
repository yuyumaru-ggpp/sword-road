<?php
// department-team.php - 団体戦（完全版・デザイン改善）
// 必要に応じてパスを修正
require_once __DIR__ . '/../connect/db_connect.php';

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function markWithdraw($flag){ return ((int)$flag === 1) ? '（棄権）' : ''; }
function pos_label_from_order($order_detail) {
    $map = ['1'=>'先鋒','2'=>'次鋒','3'=>'中堅','4'=>'副将','5'=>'大将','0'=>'補員',''=>'選手'];
    $k = (string)($order_detail ?? '');
    return $map[$k] ?? '選手';
}
function pos_label_from_matchnum($num) {
    $map = ['1'=>'先鋒','2'=>'次鋒','3'=>'中堅','4'=>'副将','5'=>'大将'];
    $k = (string)($num ?? '');
    return $map[$k] ?? '選手';
}

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dept_id       = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$q             = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($tournament_id <= 0 || $dept_id <= 0) {
    http_response_code(400);
    echo "大会ID と 部門ID を指定してください。例: ?id=1&dept=2";
    exit;
}

// fetch tournament & department
try {
    $stmt = $pdo->prepare("SELECT id, title FROM tournaments WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$tournament_id]);
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, name, distinction FROM departments WHERE id = :did AND tournament_id = :tid LIMIT 1");
    $stmt->execute([':did'=>$dept_id, ':tid'=>$tournament_id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "DB エラー: " . esc($e->getMessage());
    exit;
}

if (!$tournament || !$department) {
    http_response_code(404);
    echo "大会または部門が見つかりません。";
    exit;
}

// 1) individual_matches を取得（team_match_id ごと）- 技の勝者情報も取得
try {
    $sql = "SELECT im.match_id, im.team_match_id, im.individual_match_num, im.match_field, im.order_id,
                   im.player_a_id, im.player_b_id,
                   pa.name AS a_name, pa.player_number AS a_number,
                   pb.name AS b_name, pb.player_number AS b_number,
                   im.first_technique, im.first_winner,
                   im.second_technique, im.second_winner,
                   im.third_technique, im.third_winner,
                   im.judgement, im.final_winner
            FROM individual_matches im
            LEFT JOIN players pa ON pa.id = im.player_a_id
            LEFT JOIN players pb ON pb.id = im.player_b_id
            WHERE im.department_id = :dept
            ORDER BY im.team_match_id ASC, im.individual_match_num ASC, im.match_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':dept'=>$dept_id]);
    $imRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "DB エラー (individual_matches): " . esc($e->getMessage());
    exit;
}

// group by team_match_id
$cards = []; $tmIds = [];
foreach ($imRows as $r) {
    $tm = $r['team_match_id'] ?? '未設定';
    $cards[$tm][] = $r;
    if (!empty($r['team_match_id'])) $tmIds[] = (int)$r['team_match_id'];
}
$tmIds = array_values(array_unique($tmIds)); // 連番化

// 2) team_match_results 取得
$tmMap = [];
if (!empty($tmIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($tmIds), '?'));
        $stmt2 = $pdo->prepare("SELECT id, team_red_id, team_white_id, red_score, white_score, red_win_count, white_win_count, winner, wo_flg FROM team_match_results WHERE id IN ($placeholders)");
        $stmt2->execute(array_values($tmIds));
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) $tmMap[(int)$r['id']] = $r;
    } catch (PDOException $e) {
        error_log("team_match_results query failed: " . $e->getMessage());
        $tmMap = [];
    }
}

// 3) teams 取得
$teamIds = [];
foreach ($tmMap as $m) {
    if (!empty($m['team_red_id'])) $teamIds[] = (int)$m['team_red_id'];
    if (!empty($m['team_white_id'])) $teamIds[] = (int)$m['team_white_id'];
}
$teamIds = array_values(array_unique($teamIds));
$teamMap = [];
if (!empty($teamIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $stmt3 = $pdo->prepare("SELECT id, name, abbreviation, team_number, withdraw_flg FROM teams WHERE id IN ($placeholders)");
        $stmt3->execute(array_values($teamIds));
        foreach ($stmt3->fetchAll(PDO::FETCH_ASSOC) as $t) $teamMap[(int)$t['id']] = $t;
    } catch (PDOException $e) {
        error_log("teams query failed: " . $e->getMessage());
        $teamMap = [];
    }
}

// 4) orders -> members (order_detail を使う)
$membersByTeam = [];
if (!empty($teamIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $sql4 = "SELECT o.team_id, COALESCE(o.order_detail, '') AS ord, o.player_id, p.name AS player_name, p.player_number
                 FROM orders o
                 LEFT JOIN players p ON p.id = o.player_id
                 WHERE o.team_id IN ($placeholders)
                 ORDER BY o.team_id,
                   (CASE WHEN COALESCE(o.order_detail,0)=0 THEN 1 ELSE 0 END),
                   COALESCE(o.order_detail,0), o.id";
        $stmt4 = $pdo->prepare($sql4);
        $stmt4->execute(array_values($teamIds));
        foreach ($stmt4->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tid = (int)$row['team_id'];
            $ord = $row['ord'];
            $membersByTeam[$tid][] = [
                'order_detail' => $ord,
                'position' => pos_label_from_order($ord),
                'player_id' => $row['player_id'],
                'player_name' => $row['player_name'],
                'player_number' => $row['player_number'],
            ];
        }
    } catch (PDOException $e) {
        error_log("orders query failed: " . $e->getMessage());
        $membersByTeam = [];
    }
}

// 5) フォールバック処理（省略 - 元のコードと同じ）
foreach ($cards as $tmid => $matches) {
    $tmidInt = is_numeric($tmid) ? (int)$tmid : null;
    $meta = $tmidInt && isset($tmMap[$tmidInt]) ? $tmMap[$tmidInt] : null;
    $redId = $meta['team_red_id'] ?? null;
    $whiteId = $meta['team_white_id'] ?? null;

    foreach (['red' => $redId, 'white' => $whiteId] as $side => $teamId) {
        if (!$teamId) continue;
        if (empty($membersByTeam[$teamId])) {
            try {
                $stmtFb = $pdo->prepare(
                    "SELECT DISTINCT pid FROM (
                        SELECT im.player_a_id AS pid FROM individual_matches im WHERE im.team_match_id = :tmid AND im.department_id = :dept AND im.player_a_id IS NOT NULL
                        UNION
                        SELECT im.player_b_id AS pid FROM individual_matches im WHERE im.team_match_id = :tmid AND im.department_id = :dept AND im.player_b_id IS NOT NULL
                    ) AS t"
                );
                $stmtFb->execute([':tmid' => $tmidInt, ':dept' => $dept_id]);
                $pids = array_filter($stmtFb->fetchAll(PDO::FETCH_COLUMN));
                $pids = array_values(array_unique($pids));
                if (!empty($pids)) {
                    $placeholdersP = implode(',', array_fill(0, count($pids), '?'));
                    $stmtP = $pdo->prepare("SELECT id, name, player_number FROM players WHERE id IN ($placeholdersP)");
                    $stmtP->execute(array_values($pids));
                    $playersInfo = [];
                    foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $p) {
                        $playersInfo[(int)$p['id']] = $p;
                    }

                    $pidToMatchNum = [];
                    $stmtMap = $pdo->prepare("SELECT individual_match_num, player_a_id, player_b_id FROM individual_matches WHERE team_match_id = :tmid AND department_id = :dept");
                    $stmtMap->execute([':tmid' => $tmidInt, ':dept' => $dept_id]);
                    foreach ($stmtMap->fetchAll(PDO::FETCH_ASSOC) as $im) {
                        if (!empty($im['player_a_id'])) $pidToMatchNum[(int)$im['player_a_id']] = (int)$im['individual_match_num'];
                        if (!empty($im['player_b_id'])) $pidToMatchNum[(int)$im['player_b_id']] = (int)$im['individual_match_num'];
                    }

                    $members = [];
                    foreach ($pids as $pid) {
                        $pidInt = (int)$pid;
                        $info = $playersInfo[$pidInt] ?? ['id'=>$pidInt,'name'=>'未設定','player_number'=>null];
                        $matchNum = $pidToMatchNum[$pidInt] ?? null;
                        $positionLabel = $matchNum !== null ? pos_label_from_matchnum($matchNum) : '選手';
                        $members[] = [
                            'order_detail' => $matchNum !== null ? (string)$matchNum : '',
                            'position' => $positionLabel,
                            'player_id' => $info['id'],
                            'player_name' => $info['name'],
                            'player_number' => $info['player_number'],
                        ];
                    }
                    usort($members, function($a,$b){
                        $na = isset($a['player_number']) && $a['player_number'] !== '' ? intval(preg_replace('/\D/','',$a['player_number'])) : PHP_INT_MAX;
                        $nb = isset($b['player_number']) && $b['player_number'] !== '' ? intval(preg_replace('/\D/','',$b['player_number'])) : PHP_INT_MAX;
                        return $na <=> $nb;
                    });

                    $membersByTeam[$teamId] = $members;
                } else {
                    $membersByTeam[$teamId] = [];
                }
            } catch (PDOException $e) {
                error_log("fallback player query failed for team_match_id {$tmidInt}: " . $e->getMessage());
                $membersByTeam[$teamId] = [];
            }
        }
    }
}

// 6) 重複除去・選手番号でソート
foreach ($membersByTeam as $tid => $list) {
    $seen = [];
    $normalized = [];
    foreach ($list as $m) {
        $pid = (int)($m['player_id'] ?? 0);
        if ($pid && isset($seen[$pid])) continue;
        if ($pid) $seen[$pid] = true;
        $num = PHP_INT_MAX;
        if (isset($m['player_number']) && $m['player_number'] !== null && $m['player_number'] !== '') {
            if (is_numeric($m['player_number'])) {
                $num = intval($m['player_number']);
            } else {
                preg_match('/\d+/', (string)$m['player_number'], $matches);
                if (!empty($matches)) $num = intval($matches[0]);
            }
        }
        $normalized[] = ['num' => $num, 'member' => $m];
    }
    usort($normalized, function($a, $b){
        if ($a['num'] === $b['num']) return 0;
        return ($a['num'] < $b['num']) ? -1 : 1;
    });
    $membersByTeam[$tid] = array_map(function($x){ return $x['member']; }, $normalized);
}

// 7) build displayCards with members
$displayCards = [];
foreach ($cards as $tmid => $matches) {
    $tmidInt = is_numeric($tmid) ? (int)$tmid : null;
    $meta = $tmidInt && isset($tmMap[$tmidInt]) ? $tmMap[$tmidInt] : null;
    $redId = $meta['team_red_id'] ?? null;
    $whiteId = $meta['team_white_id'] ?? null;
    $displayCards[$tmid] = [
        'team_match_id' => $tmid,
        'red_id' => $redId,
        'white_id' => $whiteId,
        'red_name' => $teamMap[$redId]['name'] ?? ($redId ? "チーム #{$redId}" : '未設定'),
        'red_abbr' => $teamMap[$redId]['abbreviation'] ?? '',
        'white_name' => $teamMap[$whiteId]['name'] ?? ($whiteId ? "チーム #{$whiteId}" : '未設定'),
        'white_abbr' => $teamMap[$whiteId]['abbreviation'] ?? '',
        'red_number' => $teamMap[$redId]['team_number'] ?? null,
        'white_number' => $teamMap[$whiteId]['team_number'] ?? null,
        'red_withdraw' => $teamMap[$redId]['withdraw_flg'] ?? 0,
        'white_withdraw' => $teamMap[$whiteId]['withdraw_flg'] ?? 0,
        'meta' => $meta,
        'members_red' => $membersByTeam[$redId] ?? [],
        'members_white' => $membersByTeam[$whiteId] ?? [],
        'matches' => $matches,
    ];
}

// server-side search
if ($q !== '') {
    $qLower = mb_strtolower($q);
    $filtered = [];
    foreach ($displayCards as $k => $card) {
        $hay = mb_strtolower(($card['red_name'] ?? '') . ' ' . ($card['white_name'] ?? '') . ' ' . ($card['red_abbr'] ?? '') . ' ' . ($card['white_abbr'] ?? '') . ' ' . ($card['team_match_id'] ?? ''));
        if (mb_strpos($hay, $qLower) !== false) $filtered[$k] = $card;
    }
    $displayCards = $filtered;
}

$cardCount = is_array($displayCards) ? count($displayCards) : 0;
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($tournament['title']) ?> - <?= esc($department['name']) ?>（団体）</title>
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
.summary {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #007bff;
}
.match-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}
.team-vs {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.team-name {
    flex: 1;
    text-align: center;
}
.team-name-text {
    font-size: 1.3em;
    font-weight: bold;
    margin-bottom: 5px;
}
.team-number {
    color: #666;
    font-size: 0.9em;
}
.vs-divider {
    font-weight: bold;
    color: #999;
    padding: 0 20px;
    font-size: 1.2em;
}
.score-display {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 5px;
    margin: 15px 0;
}
.score-row {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-bottom: 15px;
}
.score-team {
    text-align: center;
    flex: 1;
}
.score-label {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}
.score-value {
    font-size: 2.5em;
    font-weight: bold;
}
.win-count-row {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}
.members-section {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    padding: 15px;
    background: #fafafa;
    border-radius: 5px;
}
.members-col {
    flex: 1;
}
.members-title {
    font-weight: bold;
    margin-bottom: 10px;
    color: #555;
}
.member-item {
    padding: 5px 0;
    font-size: 0.95em;
}
.position {
    display: inline-block;
    min-width: 50px;
    font-weight: bold;
}
.position-supp {
    color: #d9534f;
}
.matches-section {
    margin-top: 25px;
    border-top: 3px solid #e0e0e0;
    padding-top: 20px;
}
.matches-title {
    font-size: 1.2em;
    font-weight: bold;
    color: #555;
    margin-bottom: 15px;
}
.individual-match {
    background: #fafafa;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}
.match-players {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.player {
    flex: 1;
    text-align: center;
}
.player-name {
    font-weight: bold;
    font-size: 1.1em;
}
.player-number {
    color: #666;
    font-size: 0.9em;
    margin-top: 3px;
}
.match-vs {
    font-weight: bold;
    color: #999;
    padding: 0 15px;
}
.match-info {
    font-size: 0.85em;
    color: #666;
    margin-bottom: 10px;
}
.techniques {
    margin-top: 10px;
}
.technique-item {
    margin: 6px 0;
    padding: 8px;
    border-radius: 4px;
}
.final-winner {
    text-align: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e0e0e0;
    font-weight: bold;
}
.winner-a { color: #d9534f; }
.winner-b { color: #0275d8; }
input[type="search"] {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
    width: 100%;
    max-width: 400px;
}
.toggle-details-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.toggle-details-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.toggle-details-btn:active {
    transform: translateY(0);
}
.collapsible-content {
    overflow: hidden;
    transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
}
</style>
</head>
<body>
  <h1><?= esc($tournament['title']) ?> — <?= esc($department['name']) ?>（団体戦）</h1>
  
  <div class="summary">
    <form method="get" action="">
      <input type="hidden" name="id" value="<?= esc($tournament_id) ?>">
      <input type="hidden" name="dept" value="<?= esc($dept_id) ?>">
      <input type="search" name="q" placeholder="チーム名・番号で検索" value="<?= esc($q) ?>">
    </form>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
      <p style="margin: 0;"><strong>カード数:</strong> <?= $cardCount ?></p>
      <?php if ($cardCount > 0): ?>
        <div style="display: flex; gap: 10px;">
          <button onclick="toggleAll(true)" style="padding: 8px 16px; border-radius: 5px; border: 1px solid #007bff; background: white; color: #007bff; cursor: pointer; font-size: 0.9em;">
            すべて展開
          </button>
          <button onclick="toggleAll(false)" style="padding: 8px 16px; border-radius: 5px; border: 1px solid #6c757d; background: white; color: #6c757d; cursor: pointer; font-size: 0.9em;">
            すべて閉じる
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($cardCount === 0): ?>
    <div class="match-card">
      <p>該当する試合はありません。</p>
    </div>
  <?php else: ?>
    <?php foreach ($displayCards as $card): ?>
      <div class="match-card">
        <div class="card-header">
          <div style="color: #666; font-size: 0.9em;">
            <strong>カード ID:</strong> <?= esc($card['team_match_id']) ?>
          </div>
        </div>

        <!-- チーム名表示 -->
        <div class="team-vs">
          <div class="team-name">
            <div class="team-name-text" style="color: #d9534f;">
              <?= esc($card['red_name']) ?>
              <?php if ($card['red_withdraw']): ?>
                <span style="font-size: 0.7em; color: #999;">（棄権）</span>
              <?php endif; ?>
            </div>
            <div class="team-number">No. <?= esc($card['red_number'] ?? '-') ?></div>
          </div>
          <div class="vs-divider">VS</div>
          <div class="team-name">
            <div class="team-name-text" style="color: #0275d8;">
              <?= esc($card['white_name']) ?>
              <?php if ($card['white_withdraw']): ?>
                <span style="font-size: 0.7em; color: #999;">（棄権）</span>
              <?php endif; ?>
            </div>
            <div class="team-number">No. <?= esc($card['white_number'] ?? '-') ?></div>
          </div>
        </div>

        <!-- スコア表示 -->
        <?php if (!empty($card['meta'])): ?>
          <div class="score-display">
            <div class="score-row">
              <div class="score-team">
                <div class="score-label">赤チーム</div>
                <div class="score-value" style="color: #d9534f;">
                  <?= esc($card['meta']['red_score'] ?? '-') ?>
                </div>
              </div>
              <div style="font-size: 1.5em; font-weight: bold; color: #999;">-</div>
              <div class="score-team">
                <div class="score-label">白チーム</div>
                <div class="score-value" style="color: #0275d8;">
                  <?= esc($card['meta']['white_score'] ?? '-') ?>
                </div>
              </div>
            </div>

            <?php if (isset($card['meta']['red_win_count']) || isset($card['meta']['white_win_count'])): ?>
              <div class="win-count-row">
                <span style="color: #666;">勝ち数:</span>
                <span style="color: #d9534f; font-weight: bold; font-size: 1.2em; margin: 0 5px;">
                  <?= esc($card['meta']['red_win_count'] ?? '-') ?>
                </span>
                <span style="color: #999;">-</span>
                <span style="color: #0275d8; font-weight: bold; font-size: 1.2em; margin: 0 5px;">
                  <?= esc($card['meta']['white_win_count'] ?? '-') ?>
                </span>
              </div>
            <?php endif; ?>

            <?php if (!empty($card['meta']['winner'])): ?>
              <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                <?php
                $winner = $card['meta']['winner'];
                $winnerLower = strtolower((string)$winner);
                $winnerName = '';
                $winnerClass = '';
                
                if ($winner == $card['red_id'] || $winnerLower === 'red' || $winnerLower === 'aka') {
                  $winnerName = $card['red_name'];
                  $winnerClass = 'winner-a';
                } elseif ($winner == $card['white_id'] || $winnerLower === 'white' || $winnerLower === 'shiro') {
                  $winnerName = $card['white_name'];
                  $winnerClass = 'winner-b';
                } else {
                  $winnerName = $winner;
                }
                ?>
                <span class="final-winner <?= $winnerClass ?>" style="font-size: 1.3em;">
                  ✓ 勝者: <?= esc($winnerName) ?>
                </span>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- メンバー表示 -->
        <div class="members-section collapsible-content" id="members-<?= esc($card['team_match_id']) ?>" style="display: none;">
          <div class="members-col">
            <div class="members-title" style="color: #d9534f;">赤チームメンバー</div>
            <?php if (empty($card['members_red'])): ?>
              <div style="color: #999; font-size: 0.9em;">メンバー情報なし</div>
            <?php else: ?>
              <?php foreach ($card['members_red'] as $m): ?>
                <div class="member-item">
                  <span class="position <?= mb_strpos($m['position'],'補')!==false ? 'position-supp' : '' ?>">
                    <?= esc($m['position']) ?>:
                  </span>
                  <?= esc($m['player_name'] ?? '未設定') ?>
                  <span style="color: #999;">(<?= esc($m['player_number'] ?? '-') ?>)</span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="members-col">
            <div class="members-title" style="color: #0275d8;">白チームメンバー</div>
            <?php if (empty($card['members_white'])): ?>
              <div style="color: #999; font-size: 0.9em;">メンバー情報なし</div>
            <?php else: ?>
              <?php foreach ($card['members_white'] as $m): ?>
                <div class="member-item">
                  <span class="position <?= mb_strpos($m['position'],'補')!==false ? 'position-supp' : '' ?>">
                    <?= esc($m['position']) ?>:
                  </span>
                  <?= esc($m['player_name'] ?? '未設定') ?>
                  <span style="color: #999;">(<?= esc($m['player_number'] ?? '-') ?>)</span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- 詳細を見るボタン -->
        <div style="text-align: center; margin: 20px 0;">
          <button class="toggle-details-btn" onclick="toggleDetails('<?= esc($card['team_match_id']) ?>')" id="btn-<?= esc($card['team_match_id']) ?>">
            📋 詳細を見る
          </button>
        </div>

        <!-- 個別対戦 -->
        <div class="matches-section collapsible-content" id="matches-<?= esc($card['team_match_id']) ?>" style="display: none;">
          <div class="matches-title">【個別対戦】</div>
          
          <?php foreach ($card['matches'] as $m): ?>
            <div class="individual-match">
              <!-- 選手名 -->
              <div class="match-players">
                <div class="player">
                  <div class="player-name" style="color: #d9534f;">
                    <?= esc($m['a_name'] ?? '選手A') ?>
                  </div>
                  <div class="player-number">(<?= esc($m['a_number'] ?? '-') ?>)</div>
                </div>
                <div class="match-vs">VS</div>
                <div class="player">
                  <div class="player-name" style="color: #0275d8;">
                    <?= esc($m['b_name'] ?? '選手B') ?>
                  </div>
                  <div class="player-number">(<?= esc($m['b_number'] ?? '-') ?>)</div>
                </div>
              </div>

              <div class="match-info">
                順<?= esc($m['individual_match_num'] ?? '-') ?> | 
                場<?= esc($m['match_field'] ?? '-') ?>
              </div>

              <!-- 技の表示 -->
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
                    $winnerLower = strtolower((string)$winner);
                    
                    if ($winner == $m['player_a_id'] || $winner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red') {
                      $winnerName = $m['a_name'] ?? '選手A';
                      $winnerClass = 'winner-a';
                    } elseif ($winner == $m['player_b_id'] || $winner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white') {
                      $winnerName = $m['b_name'] ?? '選手B';
                      $winnerClass = 'winner-b';
                    }
                ?>
                  <div class="technique-item" style="<?php 
                    if ($winnerName) {
                      if ($winnerClass === 'winner-a') {
                        echo 'background: #ffe6e6; border-left: 3px solid #d9534f;';
                      } else {
                        echo 'background: #e6f2ff; border-left: 3px solid #0275d8;';
                      }
                    }
                  ?>">
                    <span style="font-weight: bold; <?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;') : 'color: #555;' ?>">
                      第<?= $techNum ?>技:
                    </span>
                    <span style="<?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f; font-weight: bold;' : 'color: #0275d8; font-weight: bold;') : 'color: #333;' ?>">
                      <?= esc($tech['name']) ?>
                    </span>
                    <?php if ($winnerName): ?>
                      <span style="margin-left: 10px; font-size: 0.9em; <?= $winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;' ?>">
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
              </div>

              <!-- 最終結果 -->
              <?php if (!empty($m['final_winner'])): ?>
                <?php
                $finalWinner = $m['final_winner'];
                $finalWinnerName = '';
                $finalWinnerClass = '';
                $finalWinnerLower = strtolower((string)$finalWinner);
                
                if ($finalWinner == $m['player_a_id'] || $finalWinner === 'player_a' || $finalWinnerLower === 'a' || $finalWinnerLower === 'red') {
                  $finalWinnerName = $m['a_name'] ?? '選手A';
                  $finalWinnerClass = 'winner-a';
                } elseif ($finalWinner == $m['player_b_id'] || $finalWinner === 'player_b' || $finalWinnerLower === 'b' || $finalWinnerLower === 'white') {
                  $finalWinnerName = $m['b_name'] ?? '選手B';
                  $finalWinnerClass = 'winner-b';
                } else {
                  $finalWinnerName = $finalWinner;
                }
                ?>
                <div class="final-winner <?= $finalWinnerClass ?>">
                  ✓ <?= esc($finalWinnerName) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($m['judgement'])): ?>
                <div style="text-align: center; margin-top: 5px; color: #666; font-size: 0.85em;">
                  <?= esc($m['judgement']) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <script>
    function toggleDetails(teamMatchId) {
      const membersSection = document.getElementById('members-' + teamMatchId);
      const matchesSection = document.getElementById('matches-' + teamMatchId);
      const button = document.getElementById('btn-' + teamMatchId);
      
      const isHidden = membersSection.style.display === 'none';
      
      if (isHidden) {
        // Show details
        membersSection.style.display = 'flex';
        matchesSection.style.display = 'block';
        button.textContent = '📁 詳細を閉じる';
        button.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
      } else {
        // Hide details
        membersSection.style.display = 'none';
        matchesSection.style.display = 'none';
        button.textContent = '📋 詳細を見る';
        button.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
      }
    }

    // Optional: Add "expand all" / "collapse all" functionality
    function toggleAll(show) {
      const buttons = document.querySelectorAll('.toggle-details-btn');
      buttons.forEach(btn => {
        const teamMatchId = btn.id.replace('btn-', '');
        const membersSection = document.getElementById('members-' + teamMatchId);
        const matchesSection = document.getElementById('matches-' + teamMatchId);
        
        if (show) {
          membersSection.style.display = 'flex';
          matchesSection.style.display = 'block';
          btn.textContent = '📁 詳細を閉じる';
          btn.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
        } else {
          membersSection.style.display = 'none';
          matchesSection.style.display = 'none';
          btn.textContent = '📋 詳細を見る';
          btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        }
      });
    }
  </script>
</body>
</html>