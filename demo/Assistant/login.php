<?php
session_start();

$user = "root";
$pass = "";
$database = "kendo_support_system";
$server = "localhost";
$port = "3308";

$dsn = "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $user, $pass);
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
  exit("DB接続失敗：" . $e->getMessage());
}

$error = "";

/* ---------- ログイン処理 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $login_id = $_POST['user_id'] ?? '';
  $password = $_POST['password'] ?? '';

  $sql = "
    SELECT
        id,
        title,
        password
    FROM
        tournaments
    WHERE
        id = :id
    LIMIT 1
";


  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':id', $login_id, PDO::PARAM_INT);
  $stmt->execute();

  $tournament = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$tournament) {
    $error = "IDまたはパスワードが違います";
  } else {
    // ▼ 平文比較(hashなら password_verify を使う)
    if ($password === $tournament['password']) {

      // セッションに大会情報保存
      $_SESSION['tournament_id'] = $tournament['id'];
      $_SESSION['tournament_title'] = $tournament['title'];

      header("Location: index.php");
      exit;
    } else {
      $error = "IDまたはパスワードが違います";
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
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fdfaf5;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background-color: #fff;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .login-container h2 {
      margin-bottom: 20px;
      font-size: 22px;
      color: #333;
    }

    .login-container input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    .button-group {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
    }

    .button-group button {
      flex: 1;
      padding: 10px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .button-group button:first-child {
      background-color: #0078d4;
      color: white;
      margin-right: 10px;
    }

    .button-group button:last-child {
      background-color: #ccc;
      color: #333;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <h2>ログイン画面</h2>

    <?php if (!empty($error)): ?>
      <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="user_id" placeholder="IDを入力してください" required>
      <input type="password" name="password" placeholder="大会パスワードを入力してください" required>

      <div class="button-group">
        <button type="submit">決定</button>
        <button type="button" onclick="location.href='../'">戻る</button>
      </div>
    </form>
  </div>
</body>


</html>