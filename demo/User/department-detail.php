<?php
// department-detail.php - 検索機能付き・モバイル改善版
require_once __DIR__ . '/../connect/db_connect.php';

// params
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$dept_id       = isset($_GET['dept']) ? (int)$_GET['dept'] : 2;
$q             = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($tournament_id <= 0 || $dept_id <= 0) {
  http_response_code(400);
  echo "大会ID と 部門ID を指定してください。例: ?id=1&dept=2";
  exit;
}

function esc($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function markWithdraw($flag)
{
  return ((int)$flag === 1) ? '（棄権）' : '';
}
function table_exists($pdo, $name)
{
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
  $stmt->execute([':t' => $name]);
  return ((int)$stmt->fetchColumn()) > 0;
}
function columns_of($pdo, $table)
{
  try {
    return array_column($pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
  } catch (Exception $e) {
    return [];
  }
}

// fetch tournament & department
$stmt = $pdo->prepare("SELECT id, title, event_date, venue FROM tournaments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name, distinction FROM departments WHERE id = :did AND tournament_id = :tid LIMIT 1");
$stmt->execute([':did' => $dept_id, ':tid' => $tournament_id]);
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
function fetch_teams_map($pdo, array $ids)
{
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
$teamMatchDetails = [];

try {
  if ($distinction === 1) {
    // 団体戦処理（省略 - 元のコードと同じ）
    // ... 元のコードをそのまま使用 ...
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
    $stmt->execute([':dept' => $dept_id]);
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
    * {
      box-sizing: border-box;
    }

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
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
      font-size: 1.2em;
    }

    .techniques {
      background: #f9f9f9;
      padding: 12px;
      border-radius: 5px;
      margin: 10px 0;
    }

    .technique-item {
      margin: 8px 0;
      padding: 8px;
      border-radius: 4px;
    }

    .technique-label {
      font-weight: bold;
    }

    .technique-name {
      margin: 0 8px;
    }

    .technique-winner {
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

    .summary {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #007bff;
    }

    .search-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 15px;
    }

    .search-input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 1em;
    }

    .search-btn {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1em;
      white-space: nowrap;
    }

    .clear-btn {
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      display: inline-flex;
      align-items: center;
      white-space: nowrap;
    }

    .search-tag {
      background: #e3f2fd;
      color: #1976d2;
      padding: 8px 12px;
      border-radius: 5px;
      margin-top: 10px;
      display: inline-block;
    }

    /* モバイル対応 */
    @media (max-width: 768px) {
      body {
        padding: 10px;
      }

      h1 {
        font-size: 1.3em;
        word-break: break-word;
      }

      h3 {
        font-size: 1em;
        padding: 8px 10px;
      }

      .match-card {
        padding: 10px;
      }

      .match-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
      }

      .match-id {
        font-size: 0.85em;
      }

      .players {
        flex-direction: column;
        gap: 15px;
      }

      .vs {
        padding: 8px 0;
        font-size: 1em;
      }

      .player-name {
        font-size: 1.1em;
      }

      .player-number {
        font-size: 0.85em;
      }

      .technique-item {
        font-size: 0.9em;
        padding: 6px;
      }

      .technique-label,
      .technique-name,
      .technique-winner {
        display: block;
        margin: 3px 0;
      }

      .technique-winner {
        margin-left: 0;
      }

      .final-winner {
        font-size: 1em;
      }

      .search-bar {
        flex-direction: column;
        gap: 10px;
      }

      .search-input {
        width: 100%;
      }

      .search-btn,
      .clear-btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>

<body>
  <div style="margin-bottom: 15px;">
    <a href="tournament-department.php?id=<?= esc($tournament_id) ?>" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 0.9em;">
      ← 部門一覧に戻る
    </a>
  </div>

  <h1><?= esc($tournament['title']) ?> — <?= esc($department['name']) ?></h1>

  <div class="summary">
    <!-- 検索バー -->
    <form method="get">
      <input type="hidden" name="id" value="<?= esc($tournament_id) ?>">
      <input type="hidden" name="dept" value="<?= esc($dept_id) ?>">
      <div class="search-bar">
        <input 
          type="text" 
          name="q" 
          class="search-input"
          placeholder="<?= $distinction === 2 ? '選手名、選手番号で検索' : 'チーム名、チーム番号で検索' ?>" 
          value="<?= esc($q) ?>">
        <button type="submit" class="search-btn">
          🔍 検索
        </button>
        <?php if ($q !== ''): ?>
          <a href="?id=<?= esc($tournament_id) ?>&dept=<?= esc($dept_id) ?>" class="clear-btn">
            クリア
          </a>
        <?php endif; ?>
      </div>
    </form>

    <p><strong>該当試合:</strong> <?= array_sum(array_map('count', $grouped)) ?> 件</p>
    <?php if ($q !== ''): ?>
      <div class="search-tag">
        🔍 検索中: "<?= esc($q) ?>"
      </div>
    <?php endif; ?>
  </div>

  <?php if (empty($grouped)): ?>
    <div class="match-card">
      <p class="no-techniques">該当する試合はありません。</p>
    </div>
  <?php else: ?>
    <?php foreach ($grouped as $field => $list): ?>
      <h3>📍 場 <?= esc($field) ?></h3>

      <?php foreach ($list as $m): ?>
        <div class="match-card">
          <div class="match-header">
            <div class="match-id">
              <strong>試合番号:</strong> <?= esc($m['match_number'] ?? '-') ?>
              <span style="margin-left: 15px;"><strong>ID:</strong> <?= esc($m['match_id'] ?? '-') ?></span>
            </div>
          </div>

          <?php if ($distinction === 2): ?>
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
                                                          echo 'background: #ffe6e6; border-left: 4px solid #d9534f;';
                                                        } else {
                                                          echo 'background: #e6f2ff; border-left: 4px solid #0275d8;';
                                                        }
                                                      }
                                                      ?>">
                    <span class="technique-label" style="<?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f;' : 'color: #0275d8;') : 'color: #555;' ?>">
                      第<?= $techNum ?>技:
                    </span>
                    <span class="technique-name" style="<?= $winnerName ? ($winnerClass === 'winner-a' ? 'color: #d9534f; font-weight: bold;' : 'color: #0275d8; font-weight: bold;') : 'color: #333;' ?>">
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