<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$dept_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

if (!$tournament_id || !$dept_id) {
    die("大会ID または 部門ID が指定されていません");
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);
$tournament_title = $tournament['title'] ?? '大会';

// 部門情報取得
$sql = "SELECT name, distinction FROM departments WHERE id = :did LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $dept_id, PDO::PARAM_INT);
$stmt->execute();
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

$dept_name = $dept['name'] ?? "部門 {$dept_id}";

// 個人戦の試合一覧取得（選手番号・チーム名を含む）
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
    WHERE im.department_id = :did
      AND (im.team_match_id IS NULL OR im.team_match_id = 0)
    ORDER BY 
      (CASE WHEN im.individual_match_num IS NULL THEN 1 ELSE 0 END) ASC,
      im.match_field ASC,
      im.individual_match_num ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $dept_id, PDO::PARAM_INT);
$stmt->execute();
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 検索処理
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filteredMatches = [];

if ($q !== '') {
    $qLower = mb_strtolower($q);
    foreach ($matches as $m) {
        $haystack = mb_strtolower(
            ($m['a_name'] ?? '') . ' ' . 
            ($m['b_name'] ?? '') . ' ' . 
            ($m['a_number'] ?? '') . ' ' . 
            ($m['b_number'] ?? '') . ' ' . 
            ($m['a_team_name'] ?? '') . ' ' . 
            ($m['b_team_name'] ?? '')
        );
        if (mb_strpos($haystack, $qLower) !== false) {
            $filteredMatches[] = $m;
        }
    }
} else {
    $filteredMatches = $matches;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($tournament_title) ?> - <?= htmlspecialchars($dept_name) ?></title>

  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      max-width: 1100px;
      margin: auto;
      padding: 16px;
      background: #f5f5f5;
    }

    h1 {
      border-bottom: 3px solid #007bff;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .breadcrumb {
      margin-bottom: 15px;
      font-size: 0.9em;
      color: #666;
    }

    .breadcrumb a {
      color: #007bff;
      text-decoration: none;
    }

    .breadcrumb a:hover {
      text-decoration: underline;
    }

    .search-bar {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #007bff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
      font-size: 0.95em;
    }

    button {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.95em;
    }

    button:hover {
      background: #0056b3;
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

    .clear-btn:hover {
      background: #5a6268;
    }

    .back-btn {
      display: inline-block;
      padding: 10px 20px;
      background: #6c757d;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin-bottom: 15px;
    }

    .back-btn:hover {
      background: #5a6268;
    }

    .match-card {
      background: #fff;
      padding: 16px;
      border-radius: 8px;
      margin: 10px 0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
      cursor: pointer;
      transition: box-shadow 0.2s, transform 0.2s;
    }

    .match-card:hover {
      box-shadow: 0 4px 8px rgba(0, 0, 0, .15);
      transform: translateY(-2px);
    }

    .match-number {
      font-size: 0.9em;
      color: #666;
      margin-bottom: 10px;
    }

    .match-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
    }

    .player-info {
      flex: 1;
      font-size: 0.95em;
    }

    .player-left {
      text-align: left;
    }

    .player-right {
      text-align: right;
    }

    .player-winner {
      font-weight: bold;
      color: #000;
    }

    .tech-display {
      white-space: nowrap;
      flex-shrink: 0;
      font-size: 1em;
    }

    /* 技の色分け */
    .tech-a {
      color: #d9534f;
      font-weight: bold;
    }

    .tech-b {
      color: #0275d8;
      font-weight: bold;
    }

    .team-name {
      color: #666;
      font-size: 0.9em;
    }

    .judgement {
      text-align: center;
      font-size: 0.85em;
      color: #666;
      margin-top: 8px;
    }

    .search-result {
      background: #e3f2fd;
      color: #1976d2;
      padding: 8px 12px;
      border-radius: 5px;
      margin-top: 10px;
      display: inline-block;
    }

    .count-display {
      margin: 5px 0;
      font-weight: 500;
    }

    @media(max-width:768px) {
      .match-row {
        font-size: 0.85em;
      }

      .player-info {
        font-size: 0.85em;
      }

      .match-card {
        padding: 12px;
      }
    }
  </style>
</head>

<body>

  <div class="breadcrumb">
    <a href="../tournament_editor_menu.php?id=<?= htmlspecialchars($tournament_id) ?>">メニュー</a> &gt; 
    <a href="match-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>">試合内容変更</a> &gt;
  </div>

  <a href="match-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>" class="back-btn">← 戻る</a>

  <h1><?= htmlspecialchars($tournament_title) ?> — <?= htmlspecialchars($dept_name) ?></h1>

  <!-- 検索バー -->
  <div class="search-bar">
    <form method="get" class="search-form">
      <input type="hidden" name="id" value="<?= $tournament_id ?>">
      <input type="hidden" name="dept" value="<?= $dept_id ?>">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="選手名、選手番号、チーム名で検索">
      <button type="submit">🔍 検索</button>
      <?php if ($q !== ''): ?>
        <a href="?id=<?= $tournament_id ?>&dept=<?= $dept_id ?>" class="clear-btn">クリア</a>
      <?php endif; ?>
    </form>
    <p class="count-display"><strong>該当試合:</strong> <?= count($filteredMatches) ?> 件</p>
    <?php if ($q !== ''): ?>
      <div class="search-result">
        🔍 検索中: "<?= htmlspecialchars($q) ?>"
      </div>
    <?php endif; ?>
  </div>

  <!-- 試合一覧 -->
  <?php if (empty($filteredMatches)): ?>
    <div style="background:white;padding:30px;text-align:center;border-radius:8px;color:#666;">
      試合が登録されていません。
    </div>
  <?php else: ?>
    <?php foreach ($filteredMatches as $m): ?>
      <?php
      /* 技振り分け＆先取1本だけ色付けロジック */
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

      /* 勝者判定 */
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

      /* 編集リンク */
      $detail_link = "match-detail.php?match_id={$m['match_id']}&id={$tournament_id}&dept={$dept_id}";
      ?>

      <div class="match-card" onclick="location.href='<?= htmlspecialchars($detail_link) ?>'">

        <!-- 試合場と試合番号 -->
        <div class="match-number">
          試合場<?= htmlspecialchars($m['match_field']) ?> - 試合番号<?= htmlspecialchars($m['individual_match_num'] ?? '-') ?>
        </div>

        <div class="match-row">

          <!-- 選手A（赤） -->
          <div class="player-info player-left <?= $isAWinner ? 'player-winner' : '' ?>">
            <?= htmlspecialchars($aDisplay) ?>
          </div>

          <!-- 技表示 -->
          <div class="tech-display">
            <?php foreach ($aTech as $i => $t): ?>
              <span class="<?= ($i === $firstIndexA) ? 'tech-a' : '' ?>">
                <?= htmlspecialchars($t) ?>
              </span>
            <?php endforeach; ?>

            <?php if (!empty($aTech) || !empty($bTech)): ?>
              <span style="margin:0 8px;color:#999;">ー</span>
            <?php endif; ?>

            <?php foreach ($bTech as $i => $t): ?>
              <span class="<?= ($i === $firstIndexB) ? 'tech-b' : '' ?>">
                <?= htmlspecialchars($t) ?>
              </span>
            <?php endforeach; ?>
          </div>

          <!-- 選手B（白） -->
          <div class="player-info player-right <?= $isBWinner ? 'player-winner' : '' ?>">
            <?= htmlspecialchars($bDisplay) ?>
          </div>

        </div>

        <?php if (!empty($m['judgement'])): ?>
          <div class="judgement">
            <?= htmlspecialchars($m['judgement']) ?>
          </div>
        <?php endif; ?>

      </div>

    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>