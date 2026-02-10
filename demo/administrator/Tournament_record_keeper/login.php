<?php
session_start();
require_once '../../connect/db_connect.php'; // 環境に合わせてパスを調整

$error = '';
$tournament_id = $_POST['tournament_id'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim($tournament_id) === '' || trim($password) === '') {
        $error = '大会IDとパスワードを入力してください。';
    } else {
        $stmt = $pdo->prepare("SELECT id, title, is_locked, password FROM tournaments WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$tournament_id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$t) {
            $error = '該当する大会が見つかりません。';
        } elseif (!empty($t['is_locked']) && (int)$t['is_locked'] === 1) {
            $error = 'この大会はロックされています。編集できません。';
        } elseif ($t['password'] === $password) {
            session_regenerate_id(true);
            $_SESSION['tournament_editor'] = [
                'tournament_id' => (int)$t['id'],
                'tournament_title' => $t['title'],
                'authenticated_at' => time()
            ];
            header('Location: php/tournament_editor_menu.php');
            exit;
        } else {
            $error = '大会IDまたはパスワードが違います。';
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>大会編集者ログイン</title>
  <link rel="stylesheet" href="login.css"> <!-- 管理者ログインと同じCSSを使う -->
</head>
<body>
  <div class="login-container">
    <h2>大会編集者ログイン</h2>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
      <input type="text" name="tournament_id" placeholder="大会IDを入力してください"
             value="<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" />

      <input type="password" name="password" placeholder="大会パスワードを入力してください" />

      <div class="button-group">
        <button type="submit">ログイン</button>
        <button type="button" onclick="location.href='../master.php'">戻る</button>
      </div>
    </form>

    <div class="footer-note">大会編集用のIDとパスワードでログインしてください</div>
  </div>
</body>
</html>