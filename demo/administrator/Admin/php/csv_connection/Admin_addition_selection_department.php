<?php

session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// 大会IDを取得
$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id || !ctype_digit($tournament_id)) {
    die("大会IDが指定されていません");
}

// 部門一覧取得（削除されていないもの）
$sql = "SELECT id, name, distinction FROM departments
        WHERE tournament_id = :tid AND del_flg = 0
        ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// distinction: 1 = 団体戦, 2 = 個人戦
$individuals = array_filter($departments, fn($d) => (int)$d['distinction'] === 2);
$teams = array_filter($departments, fn($d) => (int)$d['distinction'] === 1);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>部門選択画面</title>
  <link rel="stylesheet" href="../../css/csv_connection/selection_department.css">
</head>
<body>
  <div class="container">
    <div class="breadcrumb">
      メニュー > 登録・閲覧・削除 > 部門選択 >
    </div>

    <div class="main-content">
      <div class="competition-columns">

        <!-- 個人戦 -->
        <div class="competition-column">
          <h2 class="competition-title">個人戦</h2>
          <div class="competition-list">
            <?php if (empty($individuals)): ?>
              <p>個人戦の部門は登録されていません。</p>
            <?php else: ?>
              <?php foreach ($individuals as $dept): ?>
                <a href="./Admin_addition_top.php?id=<?= $tournament_id ?>&dept=<?= $dept['id'] ?>"
                   class="competition-item"><?= htmlspecialchars($dept['name']) ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- 団体戦 -->
        <div class="competition-column">
          <h2 class="competition-title">団体戦</h2>
          <div class="competition-list">
            <?php if (empty($teams)): ?>
              <p>団体戦の部門は登録されていません。</p>
            <?php else: ?>
              <?php foreach ($teams as $dept): ?>
                <a href="./Admin_addition_top.php?id=<?= $tournament_id ?>&dept=<?= $dept['id'] ?>"
                   class="competition-item"><?= htmlspecialchars($dept['name']) ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

    <div class="actions">
      <button class="back-button" onclick="location.href='./Admin_addition_selection_tournament.php?id=<?= urlencode($tournament_id) ?>'">戻る</button>
    </div>
  </div>
</body>
</html>