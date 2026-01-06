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
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    <form action="../index.php" method="POST">
      <input type="text" name="user_id" placeholder="IDを入力してください" required />
        <input type="password" name="password" placeholder="大会パスワードを入力してください" required />
        <div class="button-group">
            <button type="submit">決定</button>
            <button type="button" onclick="history.back()">戻る</button>
        </div>
    </form>
  </div>
</body>
</html>