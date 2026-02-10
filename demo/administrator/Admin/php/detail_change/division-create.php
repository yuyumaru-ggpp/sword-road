<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// 大会ID取得
$tournament_id = $_GET['tournament_id'] ?? '';
if (!$tournament_id) {
    header("Location: Admin_selection.php");
    exit;
}

$error = '';
$success = '';

// POST処理（作成）
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $name = trim($_POST['name'] ?? '');
//     $distinction = isset($_POST['distinction']) ? (int)$_POST['distinction'] : null;

//     if ($name === '' || ($distinction !== 0 && $distinction !== 1)) {
//         $error = "部門名と種別を選択してください";
//     } else {
//         // 同名チェック（大会内で同じ名前が存在しないか。大文字小文字を区別しない）
//         $sqlCheck = "SELECT COUNT(*) FROM departments WHERE tournament_id = :tournament_id AND LOWER(name) = LOWER(:name) AND del_flg = 0";
//         $stmtCheck = $pdo->prepare($sqlCheck);
//         $stmtCheck->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
//         $stmtCheck->bindValue(':name', $name, PDO::PARAM_STR);
//         $stmtCheck->execute();
//         $count = (int)$stmtCheck->fetchColumn();

//         if ($count > 0) {
//             $error = "同じ名前の部門が既に登録されています";
//         } else {
//             $sql = "INSERT INTO departments (tournament_id, name, distinction, created_at, update_at, del_flg)
//                     VALUES (:tournament_id, :name, :distinction, NOW(), NOW(), 0)";
//             $stmt = $pdo->prepare($sql);
//             $stmt->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
//             $stmt->bindValue(':name', $name, PDO::PARAM_STR);
//             $stmt->bindValue(':distinction', $distinction, PDO::PARAM_INT);
//             $stmt->execute();

//             $success = "部門を作成しました";
//             // フォームの再表示時に入力をクリアする
//             $_POST = [];
//         }
//     }
// }

// 登録済み部門を取得（プレビュー用）
$sqlList = "SELECT id, name, distinction, created_at FROM departments WHERE tournament_id = :tournament_id AND del_flg = 0 ORDER BY created_at DESC";
$stmtList = $pdo->prepare($sqlList);
$stmtList->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
$stmtList->execute();
$departments = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>部門作成</title>
  <link rel="stylesheet" href="../../css/Admin_registration_namechange.css">
  <style>
    .container { max-width: 820px; margin: 3rem auto; padding: 1rem; }
    .breadcrumb { font-size: 0.95rem; color:#555; margin-bottom:1.5rem; }
    .breadcrumb a { color:#2563eb; text-decoration:none; margin-right:0.5rem; }
    .card { background:#fff; padding:2rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    .page-title { font-size:1.5rem; margin-bottom:1.25rem; text-align:center; }
    .form-row { display:flex; align-items:center; justify-content:center; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .radio-group { display:flex; gap:1.5rem; align-items:center; }
    .input-label { display:block; font-size:1rem; margin-bottom:0.5rem; color:#333; text-align:center; width:100%; max-width:620px; }
    .name-input { width:100%; max-width:620px; padding:0.9rem 1rem; border:1px solid #d1d5db; border-radius:8px; font-size:1rem; }
    .button-row { display:flex; gap:1rem; justify-content:center; margin-top:1.5rem; }
    .btn { padding:0.6rem 1.2rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; cursor:pointer; text-decoration:none; color:#111; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .error { color:#b91c1c; text-align:center; margin-bottom:1rem; }
    .success { color:#065f46; text-align:center; margin-bottom:1rem; }
    .note { text-align:center; color:#6b7280; margin-top:0.5rem; }
    /* プレビュー一覧 */
    .preview { margin-top:2rem; }
    .preview h2 { font-size:1.125rem; margin-bottom:0.75rem; text-align:center; }
    .dept-table { width:100%; border-collapse:collapse; }
    .dept-table th, .dept-table td { padding:0.6rem 0.75rem; border-bottom:1px solid #e5e7eb; text-align:left; }
    .dept-table th { background:#f9fafb; font-weight:600; }
    .distinction-badge { display:inline-block; padding:0.15rem 0.5rem; border-radius:6px; font-size:0.85rem; color:#fff; }
    .badge-individual { background:#6b7280; }
    .badge-team { background:#2563eb; }
  </style>
</head>
<body>
  <div class="container">
    <nav class="breadcrumb">
      <a href="Admin_top.php">メニュー</a> ＞
      <a href="Admin_selection.php">大会一覧</a> ＞
      <a href="tournament-detail.php?id=<?= htmlspecialchars($tournament_id) ?>">大会詳細</a> ＞
      <a href="division-register.php?tournament_id=<?= htmlspecialchars($tournament_id) ?>">部門選択</a> ＞
      <span>部門作成</span>
    </nav>

    <div class="card">
      <h1 class="page-title">部門作成</h1>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="form-row" style="flex-direction:column;">
          <label class="input-label">種別</label>
          <div class="radio-group" role="radiogroup" aria-label="種別">
            <label>
              <input type="radio" name="distinction" value="0" <?= (isset($_POST['distinction']) && $_POST['distinction'] === '0') ? 'checked' : '' ?> required> 個人戦
            </label>
            <label>
              <input type="radio" name="distinction" value="1" <?= (isset($_POST['distinction']) && $_POST['distinction'] === '1') ? 'checked' : '' ?> required> 団体戦
            </label>
          </div>
        </div>

        <div class="form-row" style="flex-direction:column;">
          <label for="name" class="input-label">部門名</label>
          <input id="name" name="name" class="name-input" type="text" placeholder="例：高校男子団体" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
        </div>

        <div class="note">よく使う部門は「部門選択」画面でチェックして登録できます</div>

        <div class="button-row">
          <a class="btn" href="division-register.php?tournament_id=<?= htmlspecialchars($tournament_id) ?>">戻る</a>
          <button type="submit" class="btn btn-primary">作成</button>
        </div>
      </form>

      <!-- 即プレビュー表示 -->
      <div class="preview">
        <h2>登録済み部門プレビュー</h2>
        <?php if (empty($departments)): ?>
          <p style="text-align:center; color:#6b7280;">まだ部門が登録されていません</p>
        <?php else: ?>
          <table class="dept-table" role="table" aria-label="登録済み部門一覧">
            <thead>
              <tr>
                <th>部門名</th>
                <th>種別</th>
                <th>登録日時</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($departments as $d): ?>
                <tr>
                  <td><?= htmlspecialchars($d['name']) ?></td>
                  <td>
                    <?php if ((int)$d['distinction'] === 0): ?>
                      <span class="distinction-badge badge-individual">個人戦</span>
                    <?php else: ?>
                      <span class="distinction-badge badge-team">団体戦</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($d['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </div>
</body>
</html>