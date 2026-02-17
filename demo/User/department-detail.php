<?php
require_once __DIR__ . '/../connect/db_connect.php';

/* =========================
   params
========================= */
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$dept_id       = isset($_GET['dept']) ? (int)$_GET['dept'] : 2;
$q             = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($tournament_id <= 0 || $dept_id <= 0) {
  http_response_code(400);
  exit("大会ID と 部門ID を指定してください。");
}

function esc($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* =========================
   大会・部門取得
========================= */
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id=? LIMIT 1");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM departments WHERE id=? AND tournament_id=? LIMIT 1");
$stmt->execute([$dept_id, $tournament_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tournament || !$department) {
  http_response_code(404);
  exit("大会または部門が見つかりません。");
}

$distinction = (int)$department['distinction'];

$matches = [];

/* =========================
   個人戦データ取得（選手番号・チーム名を含む）
========================= */
if ($distinction === 2) {

  $sql = "
    SELECT
      im.*,
      pa.id AS a_id,
      pa.name AS a_name,
      pa.player_number AS a_number,
      pb.id AS b_id,
      pb.name AS b_name,
      pb.player_number AS b_number,
      ta.name AS a_team_name,
      tb.name AS b_team_name
    FROM individual_matches im
    LEFT JOIN players pa ON pa.id = im.player_a_id
    LEFT JOIN players pb ON pb.id = im.player_b_id
    LEFT JOIN teams ta ON ta.id = pa.team_id
    LEFT JOIN teams tb ON tb.id = pb.team_id
    WHERE im.department_id = ?
    ORDER BY im.match_field, im.individual_match_num
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([$dept_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if ($q !== '') {
    $qLower = mb_strtolower($q);
    foreach ($rows as $r) {
      $hay = mb_strtolower(($r['a_name'] ?? '') . ' ' . ($r['b_name'] ?? '') . ' ' . ($r['a_number'] ?? '') . ' ' . ($r['b_number'] ?? '') . ' ' . ($r['a_team_name'] ?? '') . ' ' . ($r['b_team_name'] ?? ''));
      if (mb_strpos($hay, $qLower) !== false) $matches[] = $r;
    }
  } else {
    $matches = $rows;
  }
}

/* =========================
   場ごとにグループ化
========================= */
$grouped = [];
foreach ($matches as $m) {
  $grouped[$m['match_field'] ?? '未設定'][] = $m;
}
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= esc($tournament['title']) ?></title>

  <style>
    body {
      font-family: sans-serif;
      max-width: 1100px;
      margin: auto;
      padding: 16px;
      background: #f5f5f5;
    }

    h1 {
      border-bottom: 3px solid #007bff;
      padding-bottom: 10px;
    }

    .match-card {
      background: #fff;
      padding: 12px;
      border-radius: 8px;
      margin: 10px 0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
    }

    .match-number {
      font-size: 0.9em;
      color: #666;
      margin-bottom: 8px;
    }

    /* ★ 色 */
    .tech-a {
      color: #d9534f;
      font-weight: bold;
    }

    .tech-b {
      color: #0275d8;
      font-weight: bold;
    }

    .search-bar {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #007bff;
    }

    .search-form {
      display: flex;
      gap: 8px;
      margin-bottom: 10px;
    }

    input[type="text"] {
      flex: 1;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    button {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .clear-btn {
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      display: inline-flex;
      align-items: center;
    }

    .player-info {
      font-size: 0.95em;
    }

    .team-name {
      color: #666;
      font-size: 0.9em;
    }

    @media(max-width:768px) {
      .row {
        font-size: .85em
      }

      .player-info {
        font-size: .85em
      }
    }
  </style>
</head>

<body>

  <a href="tournament-department.php?id=<?= esc($tournament_id) ?>" style="display:inline-block;padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;margin-bottom:15px;">← 部門一覧に戻る</a>

  <h1><?= esc($tournament['title']) ?> — <?= esc($department['name']) ?></h1>

  <div class="search-bar">
    <form method="get" class="search-form">
      <input type="hidden" name="id" value="<?= $tournament_id ?>">
      <input type="hidden" name="dept" value="<?= $dept_id ?>">
      <input type="text" name="q" value="<?= esc($q) ?>" placeholder="選手名、選手番号、チーム名で検索">
      <button type="submit">🔍 検索</button>
      <?php if ($q !== ''): ?>
        <a href="?id=<?= $tournament_id ?>&dept=<?= $dept_id ?>" class="clear-btn">クリア</a>
      <?php endif; ?>
    </form>
    <p style="margin:5px 0;"><strong>該当試合:</strong> <?= count($matches) ?> 件</p>
    <?php if ($q !== ''): ?>
      <div style="background:#e3f2fd;color:#1976d2;padding:8px 12px;border-radius:5px;margin-top:10px;display:inline-block;">
        🔍 検索中: "<?= esc($q) ?>"
      </div>
    <?php endif; ?>
  </div>


  <?php foreach ($grouped as $field => $list): ?>

    <?php foreach ($list as $m): ?>

      <?php
      /* =================================================
   ★★★ 技振り分け＆先取1本だけ色付けロジック ★★★
================================================= */

      $techs = [
        ['name' => $m['first_technique'],  'winner' => $m['first_winner']],
        ['name' => $m['second_technique'], 'winner' => $m['second_winner']],
        ['name' => $m['third_technique'],  'winner' => $m['third_winner']]
      ];

      $aTech = [];
      $bTech = [];

      $firstSide = '';
      $firstIndexA = -1;
      $firstIndexB = -1;

      foreach ($techs as $t) {
        if (!$t['name']) continue;

        $w = strtolower((string)$t['winner']);

        $isA = ($w === 'a' || $w === 'red' || $t['winner'] == $m['player_a_id']);
        $isB = ($w === 'b' || $w === 'white' || $t['winner'] == $m['player_b_id']);

        if ($isA) {
          if ($firstSide === '') {
            $firstSide = 'a';
            $firstIndexA = count($aTech);
          }
          $aTech[] = $t['name'];
        }

        if ($isB) {
          if ($firstSide === '') {
            $firstSide = 'b';
            $firstIndexB = count($bTech);
          }
          $bTech[] = $t['name'];
        }
      }

      /* 勝者 */
      $fw = strtolower((string)$m['final_winner']);
      $isAWinner = ($fw === 'a' || $fw === 'red' || $m['final_winner'] == $m['a_id']);
      $isBWinner = ($fw === 'b' || $fw === 'white' || $m['final_winner'] == $m['b_id']);

      /* 選手情報の組み立て */
      $aDisplay = '';
      if (!empty($m['a_number'])) $aDisplay .= $m['a_number'] . ' ';
      $aDisplay .= $m['a_name'] ?? '選手A';
      if (!empty($m['a_team_name'])) $aDisplay .= ' (' . $m['a_team_name'] . ')';

      $bDisplay = '';
      if (!empty($m['b_number'])) $bDisplay .= $m['b_number'] . ' ';
      $bDisplay .= $m['b_name'] ?? '選手B';
      if (!empty($m['b_team_name'])) $bDisplay .= ' (' . $m['b_team_name'] . ')';
      ?>

      <div class="match-card">

        <!-- 試合場と試合番号 -->
        <div class="match-number">
          試合場<?= esc($m['match_field']) ?> - 試合番号<?= esc($m['individual_match_num']) ?>
        </div>

        <div class="row" style="display:flex;align-items:center;justify-content:space-between;gap:15px;">

          <!-- A -->
          <div class="player-info" style="flex:1;text-align:left;<?= $isAWinner ? 'font-weight:bold' : '' ?>">
            <?= esc($aDisplay) ?>
          </div>

          <!-- 技表示 -->
          <div style="white-space:nowrap;flex-shrink:0;">

            <?php foreach ($aTech as $i => $t): ?>
              <span class="<?= ($i === $firstIndexA) ? 'tech-a' : '' ?>">
                <?= esc($t) ?>
              </span>
            <?php endforeach; ?>

            ー

            <?php foreach ($bTech as $i => $t): ?>
              <span class="<?= ($i === $firstIndexB) ? 'tech-b' : '' ?>">
                <?= esc($t) ?>
              </span>
            <?php endforeach; ?>

          </div>

          <!-- B -->
          <div class="player-info" style="flex:1;text-align:right;<?= $isBWinner ? 'font-weight:bold' : '' ?>">
            <?= esc($bDisplay) ?>
          </div>

        </div>

        <?php if (!empty($m['judgement'])): ?>
          <div style="text-align:center;font-size:0.85em;color:#666;margin-top:8px;">
            <?= esc($m['judgement']) ?>
          </div>
        <?php endif; ?>

      </div>

    <?php endforeach; ?>
  <?php endforeach; ?>

</body>

</html>