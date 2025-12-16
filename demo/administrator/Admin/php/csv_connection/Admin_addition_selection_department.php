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
    <!-- Updated breadcrumb navigation -->
    <div class="breadcrumb">
      メニュー>登録・閲覧・削除>部門選択>
    </div>

    <!-- Created two-column layout for individual and team competitions -->
    <div class="main-content">
      <div class="competition-columns">
        <!-- Individual Competition Column -->
        <div class="competition-column">
          <h2 class="competition-title">個人戦</h2>
          <div class="competition-list">
            <!-- Converted competition items to clickable links -->
            <a href="./Admin_addition_top.php" class="competition-item">小学生4年以下個人</a>
            <a href="./Admin_addition_top.php" class="competition-item">小学生5年以上個人</a>
            <a href="./Admin_addition_top.php" class="competition-item">中学生男子個人</a>
            <a href="./Admin_addition_top.php" class="competition-item">中学生女子個人</a>
          </div>
        </div>

        <!-- Team Competition Column -->
        <div class="competition-column">
          <h2 class="competition-title">団体戦</h2>
          <div class="competition-list">
            <!-- Converted competition items to clickable links -->
            <a href="./Admin_addition_top.php" class="competition-item">小学生団体(5人制)</a>
            <a href="./Admin_addition_top.php" class="competition-item">中学生男子団体(5人制)</a>
            <a href="./Admin_addition_top.php" class="competition-item">中学生女子団体(5人制)</a>
            <a href="./Admin_addition_top.php" class="competition-item">中学生女子団体(3人制)</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Single back button centered -->
    <div class="actions">
      <button class="btn btn-back" onclick="history.back()">戻る</button>
    </div>
  </div>
</body>
</html>
