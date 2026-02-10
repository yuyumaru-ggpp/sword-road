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
$distinction = (int)($dept['distinction'] ?? 0);

// distinction = 1 → 団体戦
$is_team = ($distinction === 1);

// 試合一覧取得（individual_matches を使用）
// ソートを individual_match_num -> order_id -> match_id に変更
$sql = "
    SELECT im.*,
           pa.name AS player_a_name,
           pb.name AS player_b_name
    FROM individual_matches im
    LEFT JOIN players pa ON pa.id = im.player_a_id
    LEFT JOIN players pb ON pb.id = im.player_b_id
    WHERE im.department_id = :did
    ORDER BY 
      -- individual_match_num が NULL の場合は大きな値にして後ろに回す
      (CASE WHEN im.individual_match_num IS NULL THEN 1 ELSE 0 END) ASC,
      im.individual_match_num ASC,
      im.order_id ASC,
      im.match_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $dept_id, PDO::PARAM_INT);
$stmt->execute();

$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dept_name) ?> - <?= htmlspecialchars($tournament_title) ?></title>
    <link rel="stylesheet" href="../../css/result_change/match-list-style.css">
    <style>
        /* 小さな UI 調整（必要なら外部 CSS に移してください） */
        .assign-link { font-size:0.9rem; color:#007bff; text-decoration:none; margin-left:0.5rem; }
        .assign-link:hover { text-decoration:underline; }
        .note-unassigned { color:#999; font-size:0.9rem; }
    </style>
</head>
<body>

<div class="breadcrumb">
    <a href="../tournament_editor_menu.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="breadcrumb-link">メニュー ></a>
    <a href="match-category-select.php?id=<?= $tournament_id ?>" class="breadcrumb-link">試合内容変更 ></a>
</div>

<div class="container">
    <h1 class="title"><?= htmlspecialchars($dept_name) ?></h1>
    <h2 class="tournament-name"><?= htmlspecialchars($tournament_title) ?></h2>

    <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="選手名で検索（赤・白）">
        <button id="searchBtn" class="search-button">検索</button>
    </div>

    <table class="match-table" id="matchTable">
        <thead>
            <tr>
                <th>試合番号</th>
                <th>赤</th>
                <th>白</th>
                <th>区分</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matches)): ?>
                <tr><td colspan="4" style="padding:1rem;color:#666;">試合が登録されていません。</td></tr>
            <?php else: ?>
                <?php foreach ($matches as $m):
                    $match_id = $m['match_id'];
                    // individual_match_num を優先表示。未割当なら order_id を代替表示（ただし注釈を付ける）
                    $ind_num = $m['individual_match_num'];
                    $display_num = $ind_num !== null ? $ind_num : ($m['order_id'] ?? '-');
                    $unassigned = ($ind_num === null);

                    $player_a = $m['player_a_name'] ?? "ID:{$m['player_a_id']}";
                    $player_b = $m['player_b_name'] ?? "ID:{$m['player_b_id']}";

                    // 団体戦か個人戦か
                    $entry_type = empty($m['team_match_id']) ? "個人" : "団体";

                    // 編集リンク（運営が番号を割り当てる画面へ）
                    $detail_link = "match-detail.php?match_id={$match_id}&id={$tournament_id}&dept={$dept_id}";
                ?>
                    <tr class="match-row" onclick="location.href='<?= $detail_link ?>'">
                        <td>
                            <?= htmlspecialchars($display_num) ?>
                            <?php if ($unassigned): ?>
                                <span class="note-unassigned">(未割当)</span>
                                <a href="<?= $detail_link ?>" class="assign-link" onclick="event.stopPropagation();">割り当て</a>
                            <?php else: ?>
                                <a href="<?= $detail_link ?>" class="assign-link" onclick="event.stopPropagation();">編集</a>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($player_a) ?></td>
                        <td><?= htmlspecialchars($player_b) ?></td>
                        <td><?= $entry_type ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="back-link">
        <a href="match-category-select.php?id=<?= $tournament_id ?>" class="back-text">← 戻る</a>
    </div>
</div>

<script>
// 検索機能（赤・白の選手名のみを対象にする）
document.getElementById('searchBtn').addEventListener('click', function () {
    var q = document.getElementById('searchInput').value.trim().toLowerCase();
    var rows = document.querySelectorAll('#matchTable tbody tr.match-row');
    rows.forEach(function (tr) {
        var red = tr.children[1].textContent.toLowerCase();
        var white = tr.children[2].textContent.toLowerCase();
        var matched = (red.indexOf(q) !== -1) || (white.indexOf(q) !== -1);
        tr.style.display = matched ? '' : 'none';
    });
});

// Enter キーで検索
document.getElementById('searchInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});
</script>

</body>
</html>