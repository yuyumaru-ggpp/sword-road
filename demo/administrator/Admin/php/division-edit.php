<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// パラメータ確認
$id = $_GET['id'] ?? '';
$tournament_id = $_GET['tournament_id'] ?? '';
if (!$id || !$tournament_id) {
    header("Location: Admin_selection.php");
    exit;
}

// 部門取得
$sql = "SELECT * FROM departments WHERE id = :id AND tournament_id = :tournament_id AND del_flg = 0";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dept) {
    echo "部門が見つかりません";
    exit;
}

$error = '';
$success = '';

// POST処理（更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $distinction = isset($_POST['distinction']) ? (int)$_POST['distinction'] : null;

    if ($name === '' || ($distinction !== 0 && $distinction !== 1)) {
        $error = "部門名と種別を入力してください";
    } else {
        // 同名チェック（同大会内で、現在のIDを除外）
        $sqlCheck = "SELECT COUNT(*) FROM departments WHERE tournament_id = :tournament_id AND LOWER(name) = LOWER(:name) AND id != :id AND del_flg = 0";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
        $stmtCheck->bindValue(':name', $name, PDO::PARAM_STR);
        $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = (int)$stmtCheck->fetchColumn();

        if ($count > 0) {
            $error = "同じ名前の部門が既に存在します";
        } else {
            $sqlUpd = "UPDATE departments
                       SET name = :name,
                           distinction = :distinction,
                           update_at = NOW()
                       WHERE id = :id AND tournament_id = :tournament_id";
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->bindValue(':name', $name, PDO::PARAM_STR);
            $stmtUpd->bindValue(':distinction', $distinction, PDO::PARAM_INT);
            $stmtUpd->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpd->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
            $stmtUpd->execute();

            $success = "部門を更新しました";
            // 最新データを再取得
            $stmt->execute();
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>部門編集</title>
  <link rel="stylesheet" href="../css/Admin_registration_namechange.css">
  <style>
    .container { max-width:720px; margin:3rem auto; padding:1rem; }
    .breadcrumb { margin-bottom:1rem; }
    .card { background:#fff; padding:1.5rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
    .input-label { display:block; margin-bottom:0.5rem; text-align:left; }
    .name-input { width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px; }
    .button-container { display:flex; gap:1rem; justify-content:center; margin-top:1rem; }
    .btn { padding:0.6rem 1.2rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; text-decoration:none; color:#111; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .error { color:#b91c1c; text-align:center; margin-bottom:0.75rem; }
    .success { color:#065f46; text-align:center; margin-bottom:0.75rem; }
  </style>
</head>
<body>
  <div class="container">
    <nav class="breadcrumb">
      <a href="Admin_top.php">メニュー</a> ＞
      <a href="Admin_selection.php">大会一覧</a> ＞
      <a href="tournament-detail.php?id=<?= htmlspecialchars($tournament_id) ?>">大会詳細</a> ＞
      <a href="division-register.php?tournament_id=<?= htmlspecialchars($tournament_id) ?>">部門選択</a> ＞
      <span>部門編集</span>
    </nav>

    <div class="card">
      <h1 style="text-align:center;">部門編集</h1>

      <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

      <form method="POST" novalidate>
        <label class="input-label">種別</label>
        <div style="display:flex; gap:1.5rem; margin-bottom:1rem;">
          <label><input type="radio" name="distinction" value="0" <?= ((int)$dept['distinction'] === 0) ? 'checked' : '' ?>> 個人戦</label>
          <label><input type="radio" name="distinction" value="1" <?= ((int)$dept['distinction'] === 1) ? 'checked' : '' ?>> 団体戦</label>
        </div>

        <label for="name" class="input-label">部門名</label>
        <input id="name" name="name" class="name-input" type="text" value="<?= htmlspecialchars($dept['name']) ?>" required>

        <div class="button-container">
          <a class="btn" href="division-register.php?tournament_id=<?= htmlspecialchars($tournament_id) ?>">戻る</a>
          <button type="submit" class="btn btn-primary">更新</button>
        </div>
      </form>

      <div style="text-align:center; margin-top:1rem;">
        <a href="division-delete.php?id=<?= htmlspecialchars($id) ?>&tournament_id=<?= htmlspecialchars($tournament_id) ?>" style="color:#b91c1c;">この部門を削除する</a>
      </div>
    </div>
  </div>
</body>
</html>