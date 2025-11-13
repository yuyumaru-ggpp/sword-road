<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>団体戦マッチング画面</title>
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

    .header-buttons {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .senpo-btn {
      padding: 8px 24px;
      font-size: 18px;
      font-weight: bold;
      background-color: white;
      border: 2px solid #333;
      border-radius: 8px;
      cursor: pointer;
    }

    .nav-buttons {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .next-btn, .back-btn {
      padding: 10px 20px;
      font-size: 16px;
      font-weight: bold;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .next-btn {
      background-color: #ff6b6b;
    }

    .back-btn {
      background-color: #ff6b6b;
    }

    .row {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .label {
      font-size: 18px;
      font-weight: bold;
      min-width: 100px;
    }

    .name-select {
      width: 150px;
      padding: 8px;
      font-size: 16px;
      border: 2px solid #333;
      border-radius: 4px;
      margin-right: 20px;
      background-color: white;
      cursor: pointer;
    }

    .buttons-container {
      display: flex;
      gap: 40px;
      align-items: center;
    }

    .triangle-btn {
      width: 40px;
      height: 40px;
      font-size: 20px;
      background-color: white;
      border: 2px solid #333;
      border-radius: 4px;
      cursor: pointer;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .triangle-btn:hover {
      background-color: #f0f0f0;
    }

    .dropdown-menu {
      position: absolute;
      left: 0;
      background-color: white;
      border: 2px solid #333;
      border-radius: 4px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      min-width: 60px;
    }

    .dropdown-up {
      bottom: 100%;
      margin-bottom: 5px;
    }

    .dropdown-down {
      top: 100%;
      margin-top: 5px;
    }

    .dropdown-item {
      padding: 8px 12px;
      cursor: pointer;
      font-size: 16px;
      text-align: center;
    }

    .dropdown-item:hover {
      background-color: #f0f0f0;
    }

    .divider {
      border: none;
      border-top: 2px dashed #333;
      margin: 30px 0;
    }

    .result-row {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      margin-top: 20px;
      gap: 10px;
    }

    .result-select {
      padding: 8px 16px;
      font-size: 16px;
      border: 2px solid #333;
      border-radius: 20px;
      background-color: white;
      cursor: pointer;
    }

    .cancel-btn {
      padding: 8px 24px;
      font-size: 16px;
      background-color: white;
      border: 2px solid #333;
      border-radius: 20px;
      cursor: pointer;
    }

    .cancel-btn:hover, .result-select:hover {
      background-color: #f0f0f0;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="title">団体戦　〇〇大会　〇〇部門</div>
      <div class="header-buttons">
        <button class="senpo-btn">先鋒</button>
      </div>
    </div>

    <div class="nav-buttons">
      <button class="next-btn">次へ</button>
      <button class="back-btn">戻る</button>
    </div>

    <div style="margin-top: 30px;">
      <div class="row">
        <div class="label">チーム名</div>
      </div>

      <div class="row">
        <div class="label">名前</div>
        <select class="name-select" id="topName">
          <option value="">選択してください</option>
          <option value="選手A">選手A</option>
          <option value="選手B">選手B</option>
          <option value="選手C">選手C</option>
          <option value="選手D">選手D</option>
        </select>
        <div class="buttons-container">
          <div style="position: relative;">
            <button class="triangle-btn" data-position="top" data-index="0">▲</button>
          </div>
          <div style="position: relative;">
            <button class="triangle-btn" data-position="top" data-index="1">▲</button>
          </div>
          <div style="position: relative;">
            <button class="triangle-btn" data-position="top" data-index="2">▲</button>
          </div>
        </div>
      </div>
    </div>

    <hr class="divider">

    <div>
      <div class="row">
        <div class="label">名前</div>
        <select class="name-select" id="bottomName">
          <option value="">選択してください</option>
          <option value="選手A">選手A</option>
          <option value="選手B">選手B</option>
          <option value="選手C">選手C</option>
          <option value="選手D">選手D</option>
        </select>
        <div class="buttons-container">
          <div style="position: relative;">
            <button class="triangle-btn" data-position="bottom" data-index="0">▼</button>
          </div>
          <div style="position: relative;">
            <button class="triangle-btn" data-position="bottom" data-index="1">▼</button>
          </div>
          <div style="position: relative;">
            <button class="triangle-btn" data-position="bottom" data-index="2">▼</button>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="label">チーム名</div>
      </div>
    </div>

    <div class="result-row">
      <select class="result-select" id="resultSelect">
        <option value="引分け">引分け</option>
        <option value="一本勝ち">一本勝ち</option>
        <option value="延長">延長</option>
      </select>
      <button class="cancel-btn" onclick="resetAll()">取り消し</button>
    </div>
  </div>

  <script>
    const options = ['メ', 'コ', 'ド', '反', 'ツ', '〇'];
    const state = {
      top: ['▲', '▲', '▲'],
      bottom: ['▼', '▼', '▼']
    };

    let currentDropdown = null;

    document.querySelectorAll('.triangle-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        if (currentDropdown) {
          currentDropdown.remove();
          currentDropdown = null;
        }

        const position = this.dataset.position;
        const index = parseInt(this.dataset.index);
        
        const dropdown = document.createElement('div');
        dropdown.className = `dropdown-menu ${position === 'top' ? 'dropdown-down' : 'dropdown-up'}`;
        
        options.forEach(option => {
          const item = document.createElement('div');
          item.className = 'dropdown-item';
          item.textContent = option;
          item.addEventListener('click', function(e) {
            e.stopPropagation();
            state[position][index] = option;
            btn.textContent = option;
            dropdown.remove();
            currentDropdown = null;
          });
          dropdown.appendChild(item);
        });
        
        this.parentElement.appendChild(dropdown);
        currentDropdown = dropdown;
      });
    });

    document.addEventListener('click', function() {
      if (currentDropdown) {
        currentDropdown.remove();
        currentDropdown = null;
      }
    });

    function resetAll() {
      state.top = ['▲', '▲', '▲'];
      state.bottom = ['▼', '▼', '▼'];
      
      document.querySelectorAll('.triangle-btn').forEach(btn => {
        const position = btn.dataset.position;
        const index = parseInt(btn.dataset.index);
        btn.textContent = state[position][index];
      });
      
      document.getElementById('topName').value = '';
      document.getElementById('bottomName').value = '';
      document.getElementById('resultSelect').value = '引分け';
      
      if (currentDropdown) {
        currentDropdown.remove();
        currentDropdown = null;
      }
    }
  </script>
</body>
</html>
