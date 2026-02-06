<?php
session_start();
require_once '../../connect/db_connect.php';

$error = '';
$user_id = $_POST['user_id'] ?? '';
$pass = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (trim($user_id) === '' || trim($pass) === '') {
        $error = 'すべての項目を入力してください。';
    } else {
        // role を参照しない（テーブルに存在しない場合に対応）
        $sql = "SELECT id, user_id, password_hash FROM managers WHERE user_id = :user_id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->execute();

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && !empty($admin['password_hash']) && password_verify($pass, $admin['password_hash'])) {

            // セッションに必要な情報だけ保存（role は含めない）
            $_SESSION['admin_user'] = [
                'id' => $admin['id'],
                'user_id' => $admin['user_id']
            ];

            header("Location: php/Admin_top.php");
            exit;
        } else {
            $error = "IDまたはパスワードが違います。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ログイン画面</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="login-container">
    <h2>ログイン画面</h2>

    <?php if ($error): ?>
      <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
        <input type="text" name="user_id" placeholder="IDを入力してください"
               value="<?= htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') ?>" required />

        <input type="password" name="password" placeholder="パスワードを入力してください" required />

        <div class="button-group">
            <button type="submit">決定</button>
            <button type="button" onclick="location.href='../master.php'">戻る</button>
        </div>
    </form>
  </div>
</body>
</html>