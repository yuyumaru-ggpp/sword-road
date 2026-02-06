<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// GETパラメータ取得
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
  die("大会ID または 部門ID が指定されていません");
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id);
$stmt->execute();
$tournament = $stmt->fetch();

// 部門名取得
$sql = "SELECT name FROM departments WHERE id = :did";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $department_id);
$stmt->execute();
$department = $stmt->fetch();

// チーム一覧取得
$sql = "
    SELECT 
        id,
        name,
        abbreviation,
        team_number
    FROM teams
    WHERE department_id = :dept
    ORDER BY team_number ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id);
$stmt->execute();
$teams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>団体戦チーム一覧</title>
  <link rel="stylesheet" href="../../css/csv_connection/csv_view.css">
</head>

<body>

  <div class="container">
    <div class="card">

      <!-- ヘッダー -->
      <div class="card-header">
        <div class="header-content">
          <h1 class="card-title"><?= htmlspecialchars($department['name']) ?> - 団体戦チーム一覧</h1>
          <p class="card-description"><?= htmlspecialchars($tournament['title']) ?> の登録チーム一覧です</p>
        </div>
      </div>

      <!-- テーブル -->
      <div class="card-content">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th class="col-number">チーム番号</th>
                <th>チーム名</th>
                <th>略称</th>
                <th class="col-action">操作</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($teams as $t): ?>
                <tr>
                  <td class="col-team-number">
                    <span class="badge"><?= str_pad($t['team_number'], 3, "0", STR_PAD_LEFT) ?></span>
                  </td>
                  <td class="col-team-name"><?= htmlspecialchars($t['name']) ?></td>
                  <td class="col-team-abbr">
                    <span class="badge badge-outline">
                      <?= htmlspecialchars($t['abbreviation'] ?: "－") ?>
                    </span>
                  </td>
                  <td class="col-action">
                    <a href="team_detail.php?team=<?= $t['id'] ?>&id=<?= $tournament_id ?>&dept=<?= $department_id ?>"
                      class="btn btn-outline btn-sm">
                      <span class="btn-text-full">詳細を見る</span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <button class="btn-back" onclick="location.href='./Admin_addition_selection_tournament.php'">戻る</button>
  </div>

</body>

</html>