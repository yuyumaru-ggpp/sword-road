<?php
require_once '../db_connect.php';

$error = '';
$user_id = $_POST['user_id'] ?? '';
$pass = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (trim($user_id) === '' || trim($pass) === '') {
        $error = 'すべての項目を入力してください。';
    } else {

        // ★ SELECT で管理者を検索
        $sql = "SELECT * FROM admin_users WHERE user_id = :user_id AND password = :password";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
        $stmt->bindValue(':password', $pass, PDO::PARAM_STR);
        $stmt->execute();

        $admin = $stmt->fetch();

        if ($admin) {
            // ★ ログイン成功
            header("Location: php/tournament-detail.php");
            exit;
        } else {
            // ★ ログイン失敗
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

    <form action="php/tournament-detail.php" method="POST">
        <input type="text" name="user_id" placeholder="IDを入力してください"
               value="<?= htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') ?>"/>

        <input type="password" name="password" placeholder="パスワードを入力してください"
               value="<?= htmlspecialchars($pass, ENT_QUOTES, 'UTF-8') ?>"/>

        <div class="button-group">
            <button type="submit">決定</button>
            <button type="button" onclick="location.href='../master.php'">戻る</button>
        </div>
    </form>
  </div>
</body>
</html>