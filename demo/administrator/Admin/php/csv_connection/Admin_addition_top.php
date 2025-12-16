<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>登録・閲覧・削除画面</title>
  <link rel="stylesheet" href="../../css/csv_connection/addtion_top.css">
</head>
<body>
  <div class="container">
    <!-- Updated breadcrumb navigation for registration screen -->
    <div class="breadcrumb">
      メニュー>大会選択>部門選択>登録・閲覧・削除
    </div>

    <!-- Created new layout for registration screen -->
    <div class="main-content">
      <h1 class="title">○○大会　○○部門</h1>

      <!-- Added three action buttons in a row -->
      <div class="action-buttons">
        <a href="#" class="action-btn"onclick="location.href='Admin_addition.php'">登録</a>
        <a href="#" class="action-btn"onclick="location.href='Admin_addition_view.php'">閲覧</a>
        <a href="#" class="action-btn"onclick="location.href='Admin_addition_deleate.php'">>削除</a>
      </div>
    </div>

    <!-- Updated actions with two buttons: back and return to menu -->
    <div class="actions">
      <button class="btn btn-back" onclick="history.back()">戻る</button>
      <button class="btn btn-menu">メニューへ戻る</button>
    </div>
  </div>
</body>
</html>
