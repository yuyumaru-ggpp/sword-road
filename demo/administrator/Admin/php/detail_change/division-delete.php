<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// パラメータ確認
$id = $_GET['id'] ?? '';
$tournament_id = $_GET['tournament_id'] ?? '';
if (!$id || !$tournament_id) {
    header("Location: Admin_selection.php");
    exit;
}

// 部門取得（確認用）
$sql = "SELECT id, name, distinction FROM departments WHERE id = :id AND tournament_id = :tournament_id AND del_flg = 0";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dept) {
    echo "部門が見つかりません、または既に削除されています";
    exit;
}

$error = '';

// POSTで削除実行（ソフトデリート）
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $sqlDel = "UPDATE departments SET del_flg = 1, update_at = NOW() WHERE id = :id AND tournament_id = :tournament_id";
//     $stmtDel = $pdo->prepare($sqlDel);
//     $stmtDel->bindValue(':id', $id, PDO::PARAM_INT);
//     $stmtDel->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
//     $stmtDel->execute();

//     // 削除後は大会詳細へ戻る
//     header("Location: tournament-detail.php?id=" . $tournament_id);
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>部門削除確認</title>
  <link rel="stylesheet" href="../../css/Admin_registration_namechange.css">
  <style>
    .container { max-width:640px; margin:3rem auto; padding:1rem; }
    .card { background:#fff; padding:1.5rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.04); text-align:center; }
    .danger { color:#b91c1c; font-weight:600; margin-bottom:1rem; }
    .button-row { display:flex; gap:1rem; justify-content:center; margin-top:1rem; }
    .btn { padding:0.6rem 1.2rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; cursor:pointer; text-decoration:none; color:#111; }
    .btn-danger { background:#b91c1c; color:#fff; border-color:#b91c1c; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>部門削除確認</h1>
      <p class="danger">この操作は一覧から見えなくします。データは完全には削除されません。</p>

      <p>以下の部門を削除しますか？</p>
      <p><strong><?= htmlspecialchars($dept['name']) ?></strong></p>
      <p>種別：<?= ((int)$dept['distinction'] === 0) ? '個人戦' : '団体戦' ?></p>

      <form method="POST" style="margin-top:1rem;">
        <div class="button-row">
          <a class="btn" href="division-edit.php?id=<?= htmlspecialchars($id) ?>&tournament_id=<?= htmlspecialchars($tournament_id) ?>">キャンセル</a>
          <button type="submit" class="btn btn-danger">削除する</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>