<?php
session_start();
require_once '../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location:../login.php');
    exit;
}

$tid = (int)$_SESSION['tournament_editor']['tournament_id'];
$title = $_SESSION['tournament_editor']['tournament_title'] ?? '大会';

// 追加チェック: 大会がロックされていないか確認
$stmt = $pdo->prepare("SELECT is_locked FROM tournaments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $tid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && (int)$row['is_locked'] === 1) {
    unset($_SESSION['tournament_editor']);
    echo "<p>この大会はロックされています。編集できません。</p>";
    echo '<p><a href="tournament_editor_login.php">ログイン画面へ戻る</a></p>';
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>編集メニュー - <?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="login.css">
  <style>
    .menu-wrap{max-width:720px;margin:32px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 6px 18px rgba(16,24,40,0.06);}
    .menu-wrap h1{margin:0 0 12px 0}
    .menu-buttons{display:flex;gap:12px;flex-wrap:wrap}
    .menu-buttons button{padding:12px 16px;border-radius:10px;border:0;background:#0078d4;color:#fff;cursor:pointer}
    .logout{margin-top:16px}
  </style>
</head>
<body>
  <div class="menu-wrap">
    <h1><?= htmlspecialchars($title) ?> の編集メニュー</h1>

    <div class="menu-buttons">
      <button onclick="location.href='player_change/category_select.php?id=<?= $tid ?>'">選手の変更</button>
      <button onclick="location.href='result_change/match-category-select.php?id=<?= $tid ?>'">試合内容の変更</button>
    </div>

    <div class="logout">
      <form method="post" action="tournament_editor_logout.php">
        <button type="submit" style="background:#6b7280">ログアウト</button>
      </form>
    </div>
  </div>
</body>
</html>