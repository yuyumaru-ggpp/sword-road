<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}
$uid = $_SESSION['admin_user']['user_id'];

$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 入力を取得してトリム
    $new_id = trim($_POST['new_id'] ?? '');
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password_confirm'] ?? '';

    // // パスワード一致チェック
    // if ($new !== $new2) {
    //     $err = '新しいパスワードが一致しません';
    // } elseif ($current === '') {
    //     $err = '現在のパスワードを入力してください';
    // } else {
    //     // 現在のパスワード確認（display_name を参照しない）
    //     $stmt = $pdo->prepare("SELECT id, user_id, password_hash FROM managers WHERE user_id = :uid LIMIT 1");
    //     $stmt->execute([':uid' => $uid]);
    //     $r = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$r || !password_verify($current, $r['password_hash'])) {
    //         $err = '現在のパスワードが違います';
    //     } else {
    //         // 変更処理開始
    //         try {
    //             $pdo->beginTransaction();

    //             // 1) ID変更が要求されている場合は重複チェックして更新
    //             if ($new_id !== '' && $new_id !== $uid) {
    //                 $chk = $pdo->prepare("SELECT id FROM managers WHERE user_id = :newid LIMIT 1");
    //                 $chk->execute([':newid' => $new_id]);
    //                 if ($chk->fetch()) {
    //                     $pdo->rollBack();
    //                     $err = '指定したIDは既に使用されています。別のIDを選んでください。';
    //                 } else {
    //                     $updId = $pdo->prepare("UPDATE managers SET user_id = :newid WHERE id = :id");
    //                     $updId->execute([':newid' => $new_id, ':id' => (int)$r['id']]);
    //                     // セッションの user_id を更新
    //                     $_SESSION['admin_user']['user_id'] = $new_id;
    //                 }
    //             }

    //             // 2) パスワード変更（空でなければ更新）
    //             if ($new !== '') {
    //                 $hash = password_hash($new, PASSWORD_DEFAULT);
    //                 $updPw = $pdo->prepare("UPDATE managers SET password_hash = :ph WHERE id = :id");
    //                 $updPw->execute([':ph' => $hash, ':id' => (int)$r['id']]);
    //             }

    //             if ($err === '') {
    //                 $pdo->commit();
    //                 $msg = 'アカウント情報を更新しました。';
    //             }
    //         } catch (Exception $e) {
    //             $pdo->rollBack();
    //             error_log("admin account update error: " . $e->getMessage());
    //             $err = '更新中にエラーが発生しました。サーバログを確認してください。';
    //         }
    //     }
    // }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>アカウント編集</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "メイリオ", sans-serif; padding:20px;}
    .container{max-width:640px;margin:0 auto;}
    label{display:block;margin:0.6rem 0;}
    input[type="text"], input[type="password"]{width:100%;padding:0.5rem;border:1px solid #ccc;border-radius:4px;}
    .btn{display:inline-block;padding:0.5rem 0.8rem;border-radius:6px;background:#0078d4;color:#fff;text-decoration:none;border:none;cursor:pointer;}
    .msg{color:green;}
    .err{color:#b00;}
  </style>
</head>
<body>
  <div class="container">
    <h1>アカウント編集</h1>

    <?php if ($err): ?><p class="err"><?= htmlspecialchars($err) ?></p><?php endif; ?>
    <?php if ($msg): ?><p class="msg"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <form method="post" autocomplete="off">
      <label>新しいログインID（空欄なら変更しません）
        <input type="text" name="new_id" value="<?= htmlspecialchars($_POST['new_id'] ?? '') ?>" maxlength="100" pattern="[A-Za-z0-9_\-@.]+" title="英数字と _ - @ . が使えます">
      </label>

      <label>現在のパスワード（必須）
        <input type="password" name="current_password" required>
      </label>

      <label>新しいパスワード（空欄ならパスワードは変更されません）
        <input type="password" name="new_password">
      </label>

      <label>新しいパスワード（確認）
        <input type="password" name="new_password_confirm">
      </label>

      <div style="margin-top:1rem;">
        <button type="submit" class="btn">変更</button>
        <a href="../Admin_top.php" style="margin-left:0.5rem;">戻る</a>
      </div>
    </form>
  </div>
</body>
</html>