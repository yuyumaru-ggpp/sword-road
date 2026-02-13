<?php
// department-team.php - 団体戦（テーブル構造版）
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

// 1) individual_matches を取得
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
$tmIds = array_values(array_unique($tmIds));

// 2) team_match_results 取得
$tmMap = [];
if (!empty($tmIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($tmIds), '?'));
        $stmt2 = $pdo->prepare("SELECT id, team_red_id, team_white_id, red_score, white_score, red_win_count, white_win_count, winner, wo_flg, match_field, match_number FROM team_match_results WHERE id IN ($placeholders)");
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

// 7) build displayCards
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
        'match_field' => $meta['match_field'] ?? null,
        'match_number' => $meta['match_number'] ?? null,
        'meta' => $meta,
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
    max-width: 1400px;
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
input[type="search"] {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
    width: 100%;
    max-width: 400px;
}

/* スコアシート用のスタイル（テーブル版） */
.scoresheet-container {
    margin-top: 25px;
    overflow-x: auto;
    padding: 10px 0;
}

*, *::before, *::after {
  box-sizing: border-box;
}

.scoresheet {
  border: 3px solid #111;
  border-collapse: collapse;
  table-layout: fixed;
  margin: 0 auto;
  width: fit-content;
}

.col-team   { width: 120px; }
.col-pos    { width: 140px; }
.col-stat   { width: 80px; }
.col-rep    { width: 80px; }

.scoresheet thead th {
  border: 2px solid #111;
  padding: 10px 4px;
  font-weight: 700;
  font-size: 15px;
  text-align: center;
  background: #fafafa;
}

.scoresheet tbody.team-block {
  border-top: 3px solid #111;
}

.scoresheet td {
  border: 2px solid #111;
  vertical-align: top;
  padding: 0;
}

.team-name-cell {
  text-align: center;
  vertical-align: middle !important;
  font-weight: bold;
  font-size: 1em;
  padding: 8px 4px !important;
}

.team-red {
  background: #ffe6e6;
  color: #d9534f;
}

.team-white {
  background: #e6f2ff;
  color: #0275d8;
}

.pos-inner {
  display: flex;
  flex-direction: column;
  min-height: 160px;
}

.pos-top,
.pos-bottom {
  flex: 1;
  display: flex;
  min-height: 76px;
}

.pos-top {
  border-bottom: 2px dashed #111;
}

.sub-left {
  width: 30px;
  min-width: 30px;
  border-right: 1px solid #111;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sub-right {
  flex: 1;
  padding: 6px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.tech-display {
  display: flex;
  flex-wrap: wrap;
  gap: 3px;
  margin-top: 4px;
}

.tech-badge {
  background: #007bff;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.8em;
  font-weight: bold;
  line-height: 1.4;
}

.tech-badge-first {
  background: #0056b3;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.8em;
  font-weight: bold;
  line-height: 1.4;
  border: 2px solid #003d82;
}

.tech-badge-empty {
  opacity: 0;
  padding: 2px 6px;
  font-size: 0.8em;
}

.winner-mark {
  color: #28a745;
  font-weight: bold;
  font-size: 1.1em;
  margin-top: 4px;
}

.player-name {
  font-weight: bold;
  font-size: 0.85em;
  word-break: keep-all;
}

.stat-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 160px;
}

.stat-top,
.stat-bottom {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4em;
  font-weight: bold;
}

.stat-divider {
  border-top: 2px dashed #999;
  margin: 0 8px;
}

.rep-inner {
  display: flex;
  flex-direction: column;
  min-height: 160px;
}

.rep-top,
.rep-bottom {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  font-size: 0.85em;
}

.rep-top {
  border-bottom: 2px dashed #111;
}

@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    h1 {
        font-size: 1.5em;
    }
    .match-card {
        padding: 12px;
    }
    .team-name-text {
        font-size: 1em;
    }
    .score-value {
        font-size: 1.8em;
    }
    .team-vs {
        flex-direction: column;
        gap: 10px;
    }
    .vs-divider {
        padding: 10px 0;
    }
    .toggle-details-btn {
        padding: 10px 20px;
        font-size: 0.9em;
    }
    input[type="search"] {
        max-width: 100%;
    }
    .col-team { width: 50px; }
    .col-pos  { width: 100px; }
    .col-stat { width: 60px; }
    .col-rep  { width: 60px; }
    .sub-left { width: 22px; min-width: 22px; }
}
</style>
</head>
<body>
  <div style="margin-bottom: 15px;">
    <a href="tournament-department.php?id=<?= esc($tournament_id) ?>" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 0.9em;">
      ← 部門一覧に戻る
    </a>
  </div>

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
      <?php
      // スコアシート用のデータを整理
      $positions = ['1'=>'先鋒', '2'=>'次鋒', '3'=>'中堅', '4'=>'副将', '5'=>'大将'];
      $matchesByPos = [];
      foreach ($card['matches'] as $m) {
          $num = $m['individual_match_num'] ?? null;
          if ($num && isset($positions[$num])) {
              $matchesByPos[$num] = $m;
          }
      }
      
      // 勝者数と取得本数を動的に計算
      $redWinCount = 0;
      $whiteWinCount = 0;
      $redScore = 0;
      $whiteScore = 0;
      
      // デバッグ用
      $debugInfo = [];
      
      // 代表決定戦のデータを取得
      $playoffMatch = null;
      foreach ($card['matches'] as $m) {
          if (isset($m['order_id']) && (int)$m['order_id'] === 6) {
              $playoffMatch = $m;
              break;
          }
      }
      
      // 有効な技のリスト
      $validTechniques = ['メ', 'コ', 'ド', 'ツ', '判', '×'];
      
      foreach ($matchesByPos as $posNum => $m) {
          // 勝者をカウント
          $finalWinner = $m['final_winner'] ?? '';
          $winnerLower = strtolower((string)$finalWinner);
          if ($finalWinner == $m['player_a_id'] || $finalWinner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red' || $winnerLower === 'aka') {
              $redWinCount++;
          } elseif ($finalWinner == $m['player_b_id'] || $finalWinner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white' || $winnerLower === 'shiro') {
              $whiteWinCount++;
          }
          
          $posDebug = ['pos' => $posNum, 'red' => [], 'white' => [], 'raw' => []];
          
          // 各技について1回だけチェック
          $techniques = [
              ['tech' => $m['first_technique'] ?? '', 'winner' => $m['first_winner'] ?? ''],
              ['tech' => $m['second_technique'] ?? '', 'winner' => $m['second_winner'] ?? ''],
              ['tech' => $m['third_technique'] ?? '', 'winner' => $m['third_winner'] ?? '']
          ];
          
          foreach ($techniques as $t) {
              if (!empty($t['tech']) && in_array($t['tech'], $validTechniques)) {
                  $winnerLowerTech = strtolower((string)$t['winner']);
                  $posDebug['raw'][] = "技:{$t['tech']}, 勝者:{$t['winner']}";
                  
                  if ($winnerLowerTech === 'red' || $winnerLowerTech === 'aka' || $winnerLowerTech === 'a' || $winnerLowerTech === 'player_a') {
                      $redScore++;
                      $posDebug['red'][] = $t['tech'];
                  } elseif ($winnerLowerTech === 'white' || $winnerLowerTech === 'shiro' || $winnerLowerTech === 'b' || $winnerLowerTech === 'player_b') {
                      $whiteScore++;
                      $posDebug['white'][] = $t['tech'];
                  }
              }
          }
          
          $debugInfo[] = $posDebug;
      }
      
      // 代表決定戦の技と勝者を処理
      $playoffRedTechnique = null;
      $playoffWhiteTechnique = null;
      $playoffRedWinner = false;
      $playoffWhiteWinner = false;
      
      if ($playoffMatch) {
          // 代表戦は1本勝負なので、first_techniqueのみを見る
          if (!empty($playoffMatch['first_technique'])) {
              $firstWinnerLower = strtolower((string)($playoffMatch['first_winner'] ?? ''));
              if ($firstWinnerLower === 'red' || $firstWinnerLower === 'aka' || $firstWinnerLower === 'a' || $firstWinnerLower === 'player_a') {
                  $playoffRedTechnique = $playoffMatch['first_technique'];
              } elseif ($firstWinnerLower === 'white' || $firstWinnerLower === 'shiro' || $firstWinnerLower === 'b' || $firstWinnerLower === 'player_b') {
                  $playoffWhiteTechnique = $playoffMatch['first_technique'];
              }
          }
          
          $finalWinner = $playoffMatch['final_winner'] ?? '';
          $winnerLower = strtolower((string)$finalWinner);
          if ($finalWinner == $playoffMatch['player_a_id'] || $finalWinner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red' || $winnerLower === 'aka') {
              $playoffRedWinner = true;
          } elseif ($finalWinner == $playoffMatch['player_b_id'] || $finalWinner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white' || $winnerLower === 'shiro') {
              $playoffWhiteWinner = true;
          }
      }
      ?>
      <div class="match-card">
        <div class="card-header">
          <div style="color: #666; font-size: 0.9em;">
            <?php if (!empty($card['match_field'])): ?>
              <strong>試合会場:</strong> <?= esc($card['match_field']) ?>
            <?php endif; ?>
            <?php if (!empty($card['match_number'])): ?>
              <?php if (!empty($card['match_field'])): ?> | <?php endif; ?>
              <strong>試合番号:</strong> <?= esc($card['match_number']) ?>
            <?php endif; ?>
            <?php if (empty($card['match_field']) && empty($card['match_number'])): ?>
              <strong>試合情報:</strong> 未設定
            <?php endif; ?>
          </div>
        </div>

        <!-- デバッグ情報 -->
        <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; font-size: 0.9em; display: none;" id="debug-<?= esc($card['team_match_id']) ?>">
          <strong>取得本数デバッグ情報:</strong><br>
          赤: <?= $redScore ?>本, 白: <?= $whiteScore ?>本<br>
          <?php foreach ($debugInfo as $info): ?>
            <?= esc($info['pos']) ?>: 赤[<?= implode(', ', $info['red']) ?>] 白[<?= implode(', ', $info['white']) ?>]<br>
            　生データ: <?= implode(' | ', $info['raw']) ?><br>
          <?php endforeach; ?>
        </div>

        <!-- チーム名表示（結果付き） -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px; font-size: 1.2em;">
          <?php
          // 勝敗を判定
          $redResult = '';
          $whiteResult = '';
          $displayRedScore = $redWinCount;
          $displayWhiteScore = $whiteWinCount;
          
          if ($redWinCount > $whiteWinCount) {
              $redResult = '勝';
              $whiteResult = '負';
          } elseif ($whiteWinCount > $redWinCount) {
              $redResult = '負';
              $whiteResult = '勝';
          } elseif ($redWinCount === $whiteWinCount) {
              if ($playoffRedWinner) {
                  $redResult = '勝';
                  $whiteResult = '負';
                  $displayRedScore .= '代表';
              } elseif ($playoffWhiteWinner) {
                  $redResult = '負';
                  $whiteResult = '勝';
                  $displayWhiteScore .= '代表';
              } else {
                  $redResult = '引分';
                  $whiteResult = '引分';
              }
          }
          ?>
          
          <span style="font-weight: bold; color: #d9534f; font-size: 1.3em;"><?= esc($redResult) ?></span>
          <span style="font-weight: bold; color: #333;"><?= esc($card['red_name']) ?></span>
          
          <span style="font-size: 2em; font-weight: bold; color: #999; margin: 0 10px;">
            <?= $displayRedScore ?> <span style="font-size: 0.6em;">vs</span> <?= $displayWhiteScore ?>
          </span>
          
          <span style="font-weight: bold; color: #333;"><?= esc($card['white_name']) ?></span>
          <span style="font-weight: bold; color: #0275d8; font-size: 1.3em;"><?= esc($whiteResult) ?></span>
        </div>

        <!-- 詳細を見るボタン -->
        <div style="text-align: center; margin: 20px 0;">
          <button class="toggle-details-btn" onclick="toggleDetails('<?= esc($card['team_match_id']) ?>')" id="btn-<?= esc($card['team_match_id']) ?>">
            📋 詳細を見る
          </button>
        </div>

        <!-- スコアシート（テーブル版） -->
        <div class="scoresheet-container collapsible-content" id="scoresheet-<?= esc($card['team_match_id']) ?>" style="display: none;">
          <table class="scoresheet">
            <colgroup>
              <col class="col-team">
              <col class="col-pos">
              <col class="col-pos">
              <col class="col-pos">
              <col class="col-pos">
              <col class="col-pos">
              <col class="col-stat">
              <col class="col-stat">
              <col class="col-rep">
            </colgroup>

            <thead>
              <tr>
                <th>&nbsp;</th>
                <th>先鋒</th>
                <th>次鋒</th>
                <th>中堅</th>
                <th>副将</th>
                <th>大将</th>
                <th>勝者数</th>
                <th>取得本数</th>
                <th>代表戦</th>
              </tr>
            </thead>

            <tbody class="team-block">
              <tr>
                <!-- チーム名列（赤チームと白チーム） -->
                <td class="team-name-cell" rowspan="1" style="padding: 0 !important;">
                  <div style="display: flex; height: 100%; min-height: 160px;">
                    <!-- 勝敗表記列 -->
                    <div style="width: 40px; min-width: 40px; display: flex; flex-direction: column; border-right: 2px solid #111;">
                      <div style="flex: 1; display: flex; align-items: center; justify-content: center; background: #ffe6e6; color: #d9534f; font-weight: bold; border-bottom: 2px dashed #111; writing-mode: vertical-rl; text-orientation: upright; font-size: 1.2em;">
                        <?= esc($redResult) ?>
                      </div>
                      <div style="flex: 1; display: flex; align-items: center; justify-content: center; background: #e6f2ff; color: #0275d8; font-weight: bold; writing-mode: vertical-rl; text-orientation: upright; font-size: 1.2em;">
                        <?= esc($whiteResult) ?>
                      </div>
                    </div>
                    <!-- チーム名列 -->
                    <div style="flex: 1; display: flex; flex-direction: column;">
                      <div class="team-red" style="flex: 1; display: flex; align-items: center; justify-content: center; border-bottom: 2px dashed #111; font-weight: bold; padding: 8px 4px;">
                        <?= esc($card['red_abbr'] ?: mb_substr($card['red_name'], 0, 4)) ?>
                      </div>
                      <div class="team-white" style="flex: 1; display: flex; align-items: center; justify-content: center; font-weight: bold; padding: 8px 4px;">
                        <?= esc($card['white_abbr'] ?: mb_substr($card['white_name'], 0, 4)) ?>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- 各ポジション列 -->
                <?php foreach (['1', '2', '3', '4', '5'] as $posNum): ?>
                  <?php 
                  $m = $matchesByPos[$posNum] ?? null;
                  $redTechniques = [null, null, null];
                  $whiteTechniques = [null, null, null];
                  $redWinner = false;
                  $whiteWinner = false;
                  
                  if ($m) {
                      // 各技がどちらの選手が取ったかを判定
                      if (!empty($m['first_technique'])) {
                          $firstWinnerLower = strtolower((string)($m['first_winner'] ?? ''));
                          if ($firstWinnerLower === 'red' || $firstWinnerLower === 'aka' || $firstWinnerLower === 'a' || $firstWinnerLower === 'player_a') {
                              $redTechniques[0] = $m['first_technique'];
                          } elseif ($firstWinnerLower === 'white' || $firstWinnerLower === 'shiro' || $firstWinnerLower === 'b' || $firstWinnerLower === 'player_b') {
                              $whiteTechniques[0] = $m['first_technique'];
                          }
                      }
                      if (!empty($m['second_technique'])) {
                          $secondWinnerLower = strtolower((string)($m['second_winner'] ?? ''));
                          if ($secondWinnerLower === 'red' || $secondWinnerLower === 'aka' || $secondWinnerLower === 'a' || $secondWinnerLower === 'player_a') {
                              $redTechniques[1] = $m['second_technique'];
                          } elseif ($secondWinnerLower === 'white' || $secondWinnerLower === 'shiro' || $secondWinnerLower === 'b' || $secondWinnerLower === 'player_b') {
                              $whiteTechniques[1] = $m['second_technique'];
                          }
                      }
                      if (!empty($m['third_technique'])) {
                          $thirdWinnerLower = strtolower((string)($m['third_winner'] ?? ''));
                          if ($thirdWinnerLower === 'red' || $thirdWinnerLower === 'aka' || $thirdWinnerLower === 'a' || $thirdWinnerLower === 'player_a') {
                              $redTechniques[2] = $m['third_technique'];
                          } elseif ($thirdWinnerLower === 'white' || $thirdWinnerLower === 'shiro' || $thirdWinnerLower === 'b' || $thirdWinnerLower === 'player_b') {
                              $whiteTechniques[2] = $m['third_technique'];
                          }
                      }
                      
                      $finalWinner = $m['final_winner'] ?? '';
                      $winnerLower = strtolower((string)$finalWinner);
                      if ($finalWinner == $m['player_a_id'] || $finalWinner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red' || $winnerLower === 'aka') {
                          $redWinner = true;
                      } elseif ($finalWinner == $m['player_b_id'] || $finalWinner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white' || $winnerLower === 'shiro') {
                          $whiteWinner = true;
                      }
                  }
                  ?>
                  <td>
                    <div class="pos-inner">
                      <!-- 上半分: 赤チーム選手 -->
                      <div class="pos-top">
                        <div class="sub-left">
                          <?php if ($redWinner): ?>
                            <div class="winner-mark">勝</div>
                          <?php elseif ($m && (!$redWinner && !$whiteWinner)): ?>
                            <div style="color: #666; font-weight: bold; font-size: 0.9em;">引</div>
                          <?php else: ?>
                            <div style="color: #999; font-weight: bold; font-size: 0.9em;">負</div>
                          <?php endif; ?>
                        </div>
                        <div class="sub-right">
                          <?php if ($m && !empty($m['a_name'])): ?>
                            <div class="player-name"><?= esc($m['a_name']) ?></div>
                            <div class="tech-display">
                              <?php foreach ($redTechniques as $idx => $tech): ?>
                                <?php if ($tech !== null): ?>
                                  <span class="<?= $idx === 0 ? 'tech-badge-first' : 'tech-badge' ?>"><?= esc($tech) ?></span>
                                <?php else: ?>
                                  <span class="tech-badge-empty">　</span>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                      
                      <!-- 下半分: 白チーム選手 -->
                      <div class="pos-bottom">
                        <div class="sub-left">
                          <?php if ($whiteWinner): ?>
                            <div class="winner-mark">勝</div>
                          <?php elseif ($m && (!$redWinner && !$whiteWinner)): ?>
                            <div style="color: #666; font-weight: bold; font-size: 0.9em;">引</div>
                          <?php else: ?>
                            <div style="color: #999; font-weight: bold; font-size: 0.9em;">負</div>
                          <?php endif; ?>
                        </div>
                        <div class="sub-right">
                          <?php if ($m && !empty($m['b_name'])): ?>
                            <div class="tech-display" style="margin-bottom: 4px;">
                              <?php foreach ($whiteTechniques as $idx => $tech): ?>
                                <?php if ($tech !== null): ?>
                                  <span class="<?= $idx === 0 ? 'tech-badge-first' : 'tech-badge' ?>"><?= esc($tech) ?></span>
                                <?php else: ?>
                                  <span class="tech-badge-empty">　</span>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </div>
                            <div class="player-name"><?= esc($m['b_name']) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>
                <?php endforeach; ?>

                <!-- 勝者数列 -->
                <td>
                  <div class="stat-inner">
                    <div class="stat-top"><?= $redWinCount ?></div>
                    <div class="stat-divider"></div>
                    <div class="stat-bottom"><?= $whiteWinCount ?></div>
                  </div>
                </td>

                <!-- 得本数列 -->
                <td>
                  <div class="stat-inner">
                    <div class="stat-top"><?= $redScore ?></div>
                    <div class="stat-divider"></div>
                    <div class="stat-bottom"><?= $whiteScore ?></div>
                  </div>
                </td>

                <!-- 代表戦列 -->
                <td>
                  <div class="pos-inner">
                    <!-- 上半分: 赤チーム選手 -->
                    <div class="pos-top">
                      <div class="sub-left">
                        <?php if ($playoffRedWinner): ?>
                          <div class="winner-mark">勝</div>
                        <?php elseif ($playoffMatch): ?>
                          <div style="color: #999; font-weight: bold; font-size: 0.9em;">負</div>
                        <?php endif; ?>
                      </div>
                      <div class="sub-right">
                        <?php if ($playoffMatch && !empty($playoffMatch['a_name'])): ?>
                          <div class="player-name"><?= esc($playoffMatch['a_name']) ?></div>
                          <?php if ($playoffRedTechnique !== null): ?>
                            <div class="tech-display">
                              <span class="tech-badge-first"><?= esc($playoffRedTechnique) ?></span>
                            </div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    
                    <!-- 下半分: 白チーム選手 -->
                    <div class="pos-bottom">
                      <div class="sub-left">
                        <?php if ($playoffWhiteWinner): ?>
                          <div class="winner-mark">勝</div>
                        <?php elseif ($playoffMatch): ?>
                          <div style="color: #999; font-weight: bold; font-size: 0.9em;">負</div>
                        <?php endif; ?>
                      </div>
                      <div class="sub-right">
                        <?php if ($playoffMatch && !empty($playoffMatch['b_name'])): ?>
                          <?php if ($playoffWhiteTechnique !== null): ?>
                            <div class="tech-display" style="margin-bottom: 4px;">
                              <span class="tech-badge-first"><?= esc($playoffWhiteTechnique) ?></span>
                            </div>
                          <?php endif; ?>
                          <div class="player-name"><?= esc($playoffMatch['b_name']) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <script>
    function toggleDetails(teamMatchId) {
      const scoresheetSection = document.getElementById('scoresheet-' + teamMatchId);
      const button = document.getElementById('btn-' + teamMatchId);
      
      const isHidden = scoresheetSection.style.display === 'none';
      
      if (isHidden) {
        // Show details
        scoresheetSection.style.display = 'block';
        button.textContent = '📁 詳細を閉じる';
        button.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
      } else {
        // Hide details
        scoresheetSection.style.display = 'none';
        button.textContent = '📋 詳細を見る';
        button.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
      }
    }

    function toggleAll(show) {
      const buttons = document.querySelectorAll('.toggle-details-btn');
      buttons.forEach(btn => {
        const teamMatchId = btn.id.replace('btn-', '');
        const scoresheetSection = document.getElementById('scoresheet-' + teamMatchId);
        
        if (show) {
          scoresheetSection.style.display = 'block';
          btn.textContent = '📁 詳細を閉じる';
          btn.style.background = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)';
        } else {
          scoresheetSection.style.display = 'none';
          btn.textContent = '📋 詳細を見る';
          btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        }
      });
    }
  </script>
</body>
</html>