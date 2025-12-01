<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>送信完了</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Hiragino Kaku Gothic Pro', 'ヒラギノ角ゴ Pro', 'Meiryo', 'メイリオ', sans-serif;
      padding: 20px;
      background-color: #f5f5f5;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      background-color: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .title {
      font-size: 24px;
      font-weight: bold;
    }

    .complete-message {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      margin: 40px 0;
      color: #4CAF50;
    }

    .button-group {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }

    .continue-btn, .return-btn {
      padding: 12px 30px;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .continue-btn {
      background-color: #2196F3;
      color: white;
    }

    .return-btn {
      background-color: #ff9800;
      color: white;
    }

    .continue-btn:hover {
      background-color: #0b7dda;
    }

    .return-btn:hover {
      background-color: #e68900;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">送信完了</div>
    </div>

    <div class="complete-message">
      データを送信しました
    </div>

    <div class="button-group">
      <button class="continue-btn" onclick="continueInput()">連続で入力する</button>
      <button class="return-btn" onclick="returnToSelection()">部門選択画面へ戻る</button>
    </div>
  </div>

  <script>
    function continueInput() {
      // localStorageをクリアしてindex.htmlに戻る
      localStorage.removeItem('matchesData');
      window.location.href = 'Input_form.php';
    }

    function returnToSelection() {
      // localStorageをクリアしてteam-selection.htmlに戻る
      localStorage.removeItem('matchesData');
      window.location.href = '../../index.php';
    }
  </script>
</body>
</html>
