<?php
session_start();

//DB接続
require_once '../connect/db_connect.php'; 
/* ---------- ログイン処理 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $login_id = $_POST['user_id'] ?? '';
  $password = $_POST['password'] ?? '';

  $sql = "
    SELECT
        id,
        title,
        password,
        is_locked
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

      // ロック確認
      if ($tournament['is_locked'] == 1) {
        $error = "この大会はロックされています";
      } else {
        // セッションに大会情報保存
        $_SESSION['tournament_id'] = $tournament['id'];
        $_SESSION['tournament_title'] = $tournament['title'];

        header("Location: index.php");
        exit;
      }
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

    .error-message {
      color: #d32f2f;
      background-color: #ffebee;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 15px;
      font-size: 14px;
      border-left: 4px solid #d32f2f;
    }

    .warning-icon {
      font-size: 18px;
      margin-right: 8px;
    }
  </style>
</head>

<body>
  <div class="login-container">
    <h2>ログイン画面</h2>

    <?php if (!empty($error)): ?>
      <div class="error-message">
        <span class="warning-icon">⚠</span><?php echo htmlspecialchars($error); ?>
      </div>
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