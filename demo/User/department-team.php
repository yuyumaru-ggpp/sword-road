<?php
// department-team.php - 団体戦（完全版）
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

// 1) individual_matches を取得（team_match_id ごと）
try {
    $sql = "SELECT im.match_id, im.team_match_id, im.individual_match_num, im.match_field, im.order_id,
                   im.player_a_id, im.player_b_id,
                   pa.name AS a_name, pa.player_number AS a_number,
                   pb.name AS b_name, pb.player_number AS b_number,
                   im.first_technique, im.second_technique, im.third_technique, im.judgement, im.final_winner
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
// 補員(0) を末尾にするため CASE を使った ORDER BY
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

// 5) フォールバック：orders が空のチームは individual_matches の選手を集める（ポジションは individual_match_num から推定）
foreach ($cards as $tmid => $matches) {
    $tmidInt = is_numeric($tmid) ? (int)$tmid : null;
    $meta = $tmidInt && isset($tmMap[$tmidInt]) ? $tmMap[$tmidInt] : null;
    $redId = $meta['team_red_id'] ?? null;
    $whiteId = $meta['team_white_id'] ?? null;

    foreach (['red' => $redId, 'white' => $whiteId] as $side => $teamId) {
        if (!$teamId) continue;
        if (empty($membersByTeam[$teamId])) {
            try {
                // card 内の出場選手IDを取得
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
                    // players 情報を取得
                    $placeholdersP = implode(',', array_fill(0, count($pids), '?'));
                    $stmtP = $pdo->prepare("SELECT id, name, player_number FROM players WHERE id IN ($placeholdersP)");
                    $stmtP->execute(array_values($pids));
                    $playersInfo = [];
                    foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $p) {
                        $playersInfo[(int)$p['id']] = $p;
                    }

                    // card 内の individual_match_num マップを作る
                    $pidToMatchNum = [];
                    $stmtMap = $pdo->prepare("SELECT individual_match_num, player_a_id, player_b_id FROM individual_matches WHERE team_match_id = :tmid AND department_id = :dept");
                    $stmtMap->execute([':tmid' => $tmidInt, ':dept' => $dept_id]);
                    foreach ($stmtMap->fetchAll(PDO::FETCH_ASSOC) as $im) {
                        if (!empty($im['player_a_id'])) $pidToMatchNum[(int)$im['player_a_id']] = (int)$im['individual_match_num'];
                        if (!empty($im['player_b_id'])) $pidToMatchNum[(int)$im['player_b_id']] = (int)$im['individual_match_num'];
                    }

                    // members 配列を作成（matchNum があればポジションを推定）
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
                    // player_number 昇順でソート（番号が無ければ末尾）
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

// 6) 重複除去・選手番号でソート（player_id ベースで重複排除、player_number 昇順）
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

// server-side search (optional)
if ($q !== '') {
    $qLower = mb_strtolower($q);
    $filtered = [];
    foreach ($displayCards as $k => $card) {
        $hay = mb_strtolower(($card['red_name'] ?? '') . ' ' . ($card['white_name'] ?? '') . ' ' . ($card['red_abbr'] ?? '') . ' ' . ($card['white_abbr'] ?? '') . ' ' . ($card['team_match_id'] ?? ''));
        if (mb_strpos($hay, $qLower) !== false) $filtered[$k] = $card;
    }
    $displayCards = $filtered;
}

// render
$cardCount = is_array($displayCards) ? count($displayCards) : 0;
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($tournament['title']) ?> - <?= esc($department['name']) ?>（団体）</title>
<style>
body{font-family:system-ui,-apple-system,"Segoe UI","Noto Sans JP",sans-serif;padding:18px;background:#f7f8fb;color:#111}
.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.card{background:#fff;padding:12px;border-radius:10px;margin-bottom:14px;border:1px solid #e6eefc}
.team-members{font-size:0.95rem;color:#333;margin-top:6px}
.member{font-size:0.92rem;color:#444;margin:2px 0}
.small{color:#666;font-size:0.9rem}
.withdraw{opacity:0.5}
.pos-supp{color:#d9534f;font-weight:700}
.controls{display:flex;gap:8px;align-items:center;margin-bottom:12px}
input[type="search"]{padding:8px;border-radius:8px;border:1px solid #ddd;width:100%;max-width:360px}
</style>
</head>
<body>
  <div class="header">
    <div>
      <h1><?= esc($tournament['title']) ?></h1>
      <div class="small"><?= esc($department['name']) ?>（団体戦）</div>
    </div>
    <div>
      <form method="get" action="">
        <input type="hidden" name="id" value="<?= esc($tournament_id) ?>">
        <input type="hidden" name="dept" value="<?= esc($dept_id) ?>">
        <input type="search" name="q" placeholder="チーム名・番号・カードIDで検索" value="<?= esc($q) ?>">
      </form>
    </div>
  </div>

  <div class="small" style="margin-bottom:8px">カード数: <?= $cardCount ?></div>

  <?php if ($cardCount === 0): ?>
    <div class="card"><div class="small">該当する試合はありません。</div></div>
  <?php else: ?>
    <?php foreach ($displayCards as $card): ?>
      <section class="card" aria-label="カード <?= esc($card['team_match_id']) ?>">
        <h2>カード <?= esc($card['team_match_id']) ?> — 
          <span class="<?= ((int)$card['red_withdraw']===1)?'withdraw':'' ?>"><?= esc($card['red_name']) ?> <?= markWithdraw($card['red_withdraw']) ?></span>
          <strong style="margin:0 8px">vs</strong>
          <span class="<?= ((int)$card['white_withdraw']===1)?'withdraw':'' ?>"><?= esc($card['white_name']) ?> <?= markWithdraw($card['white_withdraw']) ?></span>
        </h2>

        <?php if (!empty($card['meta'])): ?>
          <div class="small">カード情報: <?= esc($card['meta']['red_score'] ?? '-') ?> - <?= esc($card['meta']['white_score'] ?? '-') ?>　勝ち数 <?= esc($card['meta']['red_win_count'] ?? '-') ?> / <?= esc($card['meta']['white_win_count'] ?? '-') ?>　最終勝者: <?= esc($card['meta']['winner'] ?? '-') ?></div>
        <?php endif; ?>

        <div class="team-members" style="display:flex;gap:18px;margin-top:10px">
          <div style="flex:1">
            <div><strong>赤チームメンバー（選手番号順）</strong></div>
            <?php if (empty($card['members_red']) || !is_array($card['members_red'])): ?>
              <div class="member small">メンバー情報がありません</div>
            <?php else: ?>
              <?php foreach ($card['members_red'] as $m): ?>
                <?php $pos = isset($m['position']) && $m['position'] !== '' ? $m['position'] : '選手'; ?>
                <div class="member">
                  <span class="<?= (mb_strpos($pos,'補')!==false) ? 'pos-supp' : '' ?>"><?= esc($pos) ?></span>
                  : <?= esc($m['player_name'] ?? '未設定') ?>（<?= esc($m['player_number'] ?? '-') ?>）
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div style="flex:1">
            <div><strong>白チームメンバー（選手番号順）</strong></div>
            <?php if (empty($card['members_white']) || !is_array($card['members_white'])): ?>
              <div class="member small">メンバー情報がありません</div>
            <?php else: ?>
              <?php foreach ($card['members_white'] as $m): ?>
                <?php $pos = isset($m['position']) && $m['position'] !== '' ? $m['position'] : '選手'; ?>
                <div class="member">
                  <span class="<?= (mb_strpos($pos,'補')!==false) ? 'pos-supp' : '' ?>"><?= esc($pos) ?></span>
                  : <?= esc($m['player_name'] ?? '未設定') ?>（<?= esc($m['player_number'] ?? '-') ?>）
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <hr style="margin:10px 0">

        <?php foreach ($card['matches'] as $m): ?>
          <div style="padding:6px 0;border-top:1px solid #f1f5f9">
            <div><strong><?= esc($m['a_name'] ?? '選手A') ?></strong>（<?= esc($m['a_number'] ?? '-') ?>） vs <strong><?= esc($m['b_name'] ?? '選手B') ?></strong>（<?= esc($m['b_number'] ?? '-') ?>）</div>
            <div class="small">順: <?= esc($m['individual_match_num'] ?? '-') ?>　場: <?= esc($m['match_field'] ?? '-') ?>　技: <?= esc($m['first_technique'] ?? '-') ?> / <?= esc($m['second_technique'] ?? '-') ?></div>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>