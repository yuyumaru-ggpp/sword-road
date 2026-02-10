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
<link rel="stylesheet" href="./css/team.css">
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
      
      // 勝者数と得本数を動的に計算
      $redWinCount = 0;
      $whiteWinCount = 0;
      $redScore = 0;
      $whiteScore = 0;
      
      foreach ($matchesByPos as $m) {
          // 勝者をカウント
          $finalWinner = $m['final_winner'] ?? '';
          $winnerLower = strtolower((string)$finalWinner);
          if ($finalWinner == $m['player_a_id'] || $finalWinner === 'player_a' || $winnerLower === 'a' || $winnerLower === 'red' || $winnerLower === 'aka') {
              $redWinCount++;
          } elseif ($finalWinner == $m['player_b_id'] || $finalWinner === 'player_b' || $winnerLower === 'b' || $winnerLower === 'white' || $winnerLower === 'shiro') {
              $whiteWinCount++;
          }
          
          // 得本数をカウント
          if (!empty($m['first_technique'])) {
              $firstWinnerLower = strtolower((string)($m['first_winner'] ?? ''));
              if ($firstWinnerLower === 'red' || $firstWinnerLower === 'aka' || $firstWinnerLower === 'a' || $firstWinnerLower === 'player_a') {
                  $redScore++;
              } elseif ($firstWinnerLower === 'white' || $firstWinnerLower === 'shiro' || $firstWinnerLower === 'b' || $firstWinnerLower === 'player_b') {
                  $whiteScore++;
              }
          }
          if (!empty($m['second_technique'])) {
              $secondWinnerLower = strtolower((string)($m['second_winner'] ?? ''));
              if ($secondWinnerLower === 'red' || $secondWinnerLower === 'aka' || $secondWinnerLower === 'a' || $secondWinnerLower === 'player_a') {
                  $redScore++;
              } elseif ($secondWinnerLower === 'white' || $secondWinnerLower === 'shiro' || $secondWinnerLower === 'b' || $secondWinnerLower === 'player_b') {
                  $whiteScore++;
              }
          }
          if (!empty($m['third_technique'])) {
              $thirdWinnerLower = strtolower((string)($m['third_winner'] ?? ''));
              if ($thirdWinnerLower === 'red' || $thirdWinnerLower === 'aka' || $thirdWinnerLower === 'a' || $thirdWinnerLower === 'player_a') {
                  $redScore++;
              } elseif ($thirdWinnerLower === 'white' || $thirdWinnerLower === 'shiro' || $thirdWinnerLower === 'b' || $thirdWinnerLower === 'player_b') {
                  $whiteScore++;
              }
          }
      }
      ?>
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
                <th>得本数</th>
                <th>代表戦</th>
              </tr>
            </thead>

            <tbody class="team-block">
              <tr>
                <!-- チーム名列（赤チームと白チーム） -->
                <td class="team-name-cell" rowspan="1" style="padding: 0 !important;">
                  <div style="display: flex; flex-direction: column; height: 100%; min-height: 160px;">
                    <div class="team-red" style="flex: 1; display: flex; align-items: center; justify-content: center; border-bottom: 2px dashed #111; font-weight: bold; padding: 8px 4px;">
                      <?= esc($card['red_abbr'] ?: mb_substr($card['red_name'], 0, 4)) ?>
                    </div>
                    <div class="team-white" style="flex: 1; display: flex; align-items: center; justify-content: center; font-weight: bold; padding: 8px 4px;">
                      <?= esc($card['white_abbr'] ?: mb_substr($card['white_name'], 0, 4)) ?>
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
                            <div class="winner-mark">✓</div>
                          <?php endif; ?>
                        </div>
                        <div class="sub-right">
                          <?php if ($m && !empty($m['a_name'])): ?>
                            <div class="player-name"><?= esc($m['a_name']) ?></div>
                            <div class="tech-display">
                              <?php foreach ($redTechniques as $tech): ?>
                                <?php if ($tech !== null): ?>
                                  <span class="tech-badge"><?= esc($tech) ?></span>
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
                            <div class="winner-mark">✓</div>
                          <?php endif; ?>
                        </div>
                        <div class="sub-right">
                          <?php if ($m && !empty($m['b_name'])): ?>
                            <div class="player-name"><?= esc($m['b_name']) ?></div>
                            <div class="tech-display">
                              <?php foreach ($whiteTechniques as $tech): ?>
                                <?php if ($tech !== null): ?>
                                  <span class="tech-badge"><?= esc($tech) ?></span>
                                <?php else: ?>
                                  <span class="tech-badge-empty">　</span>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </div>
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
                  <div class="rep-inner">
                    <div class="rep-top"></div>
                    <div class="rep-bottom"></div>
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