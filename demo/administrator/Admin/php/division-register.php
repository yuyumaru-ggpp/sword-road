<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
  header("Location: ../login.php");
  exit;
}

// 大会ID取得
$tournament_id = $_GET['tournament_id'] ?? '';
if (!$tournament_id) {
  header("Location: Admin_selection.php");
  exit;
}

// POST処理（登録）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!empty($_POST['divisions'])) {

    foreach ($_POST['divisions'] as $value) {
      list($name, $distinction) = explode('|', $value);

      $sql = "INSERT INTO departments (tournament_id, name, distinction, created_at, update_at)
            VALUES (:tournament_id, :name, :distinction, NOW(), NOW())";

      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
      $stmt->bindValue(':name', $name, PDO::PARAM_STR);
      $stmt->bindValue(':distinction', $distinction, PDO::PARAM_INT);
      $stmt->execute();
    }

    // 登録後は大会詳細へ戻る
    header("Location: tournament-detail.php?id=" . $tournament_id);
    exit;
  }

  $error = "部門が選択されていません";
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>部門登録</title>
  <link rel="stylesheet" href="../css/Admin_registration_selection_create.css">
</head>

<body>
  <div class="container">

    <!-- パンくず -->
    <nav class="breadcrumb">
      <a href="Admin_top.php">メニュー</a>
      <span>＞</span>
      <a href="Admin_selection.php">大会一覧</a>
      <span>＞</span>
      <a href="tournament-detail.php?id=<?= $tournament_id ?>">大会詳細</a>
      <span>＞</span>
      <span>部門登録</span>
    </nav>

    <div class="card">
      <h1 class="page-title">部門登録</h1>

      <?php if (!empty($error)): ?>
        <p style="color:red; text-align:center;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST">

        <div class="checkbox-grid">

          <!-- 個人戦 -->
          <div class="checkbox-group">
            <h3>個人戦</h3>
            <div class="checkbox-list">

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="individual-1" value="小学生4年以下個人|0">
                <label for="individual-1">小学生4年以下個人</label>
              </div>

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="individual-2" value="小学生5年以上個人|0">
                <label for="individual-2">小学生5年以上個人</label>
              </div>

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="individual-3" value="中学生男子個人|0">
                <label for="individual-3">中学生男子個人</label>
              </div>

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="individual-4" value="中学生女子個人|0">
                <label for="individual-4">中学生女子個人</label>
              </div>

            </div>
          </div>

          <!-- 団体戦 -->
          <div class="checkbox-group">
            <h3>団体戦</h3>
            <div class="checkbox-list">

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="team-1" value="小学生団体|1">
                <label for="team-1">小学生団体</label>
              </div>

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="team-2" value="中学生男子団体|1">
                <label for="team-2">中学生男子団体</label>
              </div>

              <div class="checkbox-item">
                <input type="checkbox" name="divisions[]" id="team-3" value="中学生女子団体|1">
                <label for="team-3">中学生女子団体</label>
              </div>

            </div>
          </div>

        </div>

        <p class="note">ここにない場合は部門を作成してください</p>

        <div class="button-group">
          <a href="tournament-detail.php?id=<?= $tournament_id ?>" class="btn btn-secondary">戻る</a>
          <a href="division-create.php?tournament_id=<?= $tournament_id ?>" class="btn btn-outline">部門作成</a>
          <button type="submit" class="btn btn-primary">登録</button>
        </div>

      </form>

    </div>
  </div>
</body>

</html>