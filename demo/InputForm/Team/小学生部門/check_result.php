<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>入力内容の確認</title>
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

    .results-summary {
      margin: 30px 0;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
    }

    .result-item {
      padding: 15px;
      margin-bottom: 10px;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .result-role {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
      color: #333;
    }

    .result-details {
      font-size: 16px;
      line-height: 1.8;
    }

    .button-group {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }

    .submit-btn, .cancel-submit-btn {
      padding: 12px 30px;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .submit-btn {
      background-color: #4CAF50;
      color: white;
    }

    .cancel-submit-btn {
      background-color: #999;
      color: white;
    }

    .submit-btn:hover {
      background-color: #45a049;
    }

    .cancel-submit-btn:hover {
      background-color: #888;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">入力内容の確認</div>
    </div>

    <div class="results-summary" id="resultsSummary">
      <!-- 結果がここに表示される -->
    </div>

    <div class="button-group">
      <button class="submit-btn" onclick="submitData()">送信</button>
      <button class="cancel-submit-btn" onclick="cancelSubmit()">キャンセル</button>
    </div>
  </div>

  <script>
    const roles = ['先鋒', '次鋒', '中堅', '副将', '大将'];

    function displayResults() {
      const matchesData = JSON.parse(localStorage.getItem('matchesData') || '[]');
      const summaryDiv = document.getElementById('resultsSummary');
      summaryDiv.innerHTML = '';
      
      matchesData.forEach((match, index) => {
        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        
        const roleDiv = document.createElement('div');
        roleDiv.className = 'result-role';
        roleDiv.textContent = roles[index];
        
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'result-details';
        detailsDiv.innerHTML = `
          上: ${match.topName || '未選択'} - ${match.top.join(', ')}<br>
          下: ${match.bottomName || '未選択'} - ${match.bottom.join(', ')}<br>
          結果: ${match.result}
        `;
        
        resultItem.appendChild(roleDiv);
        resultItem.appendChild(detailsDiv);
        summaryDiv.appendChild(resultItem);
      });
    }

    function submitData() {
      const matchesData = JSON.parse(localStorage.getItem('matchesData') || '[]');
      console.log('データを送信:', matchesData);
      
      // completion.htmlに遷移
      window.location.href = 'completion.php';
    }

    function cancelSubmit() {
      // index.htmlに戻る
      window.location.href = 'Input_form.php';
    }

    document.addEventListener('DOMContentLoaded', function() {
      displayResults();
    });
  </script>
</body>
</html>
