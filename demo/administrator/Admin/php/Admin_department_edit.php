<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>部門編集画面</title>
  <link rel="stylesheet" href="../css/Admin_department_edit.css">
</head>
<body>
  <div class="container">
    <!-- ブレッドクラム -->
    <nav class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="Admin_selection.php" class="breadcrumb-link">大会、部門登録・名称変更></a>
        <a href="Admin_registration.php" class="breadcrumb-link">〇〇大会></a>
        <a href="#" class="breadcrumb-link">部門編集></a>
    </nav>
    <!-- メインコンテンツ -->
    <div class="main-content">
      <div class="title">団体戦</div>
      
      <div class="form-section">
        <label class="form-label">部門名</label>
        <input type="text" class="form-input" placeholder="">
      </div>
    </div>

    <!-- アクション -->
    <div class="actions">
      <button class="btn btn-back" onclick="location.href='Admin_registration.php'">戻る</button>
      <button class="btn btn-change">変更</button>
      <button class="btn btn-delete">削除</button>
    </div>
  </div>
</body>
</html>
