<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>代表決定戦</title>
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

    .playoff-title {
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 30px;
    }

    .playoff-row {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .playoff-label {
      font-size: 18px;
      font-weight: bold;
      min-width: 120px;
    }

    .name-dropdown {
      width: 150px;
      padding: 8px;
      font-size: 16px;
      border: 2px solid #333;
      border-radius: 4px;
      background-color: white;
      cursor: pointer;
      margin-right: 20px;
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
      margin-top: 30px;
      gap: 10px;
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

    .next-btn:hover, .back-btn:hover {
      background-color: #ff5252;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="playoff-title">代表決定戦</div>

    <div style="margin-top: 30px;">
      <div class="playoff-row">
        <div class="playoff-label">チーム名</div>
      </div>

      <div class="playoff-row">
        <div class="playoff-label">選手名</div>
        <select class="name-dropdown" id="topPlayerName">
          <option value="">選択してください</option>
          <option value="選手1">選手1</option>
          <option value="選手2">選手2</option>
          <option value="選手3">選手3</option>
          <option value="選手4">選手4</option>
          <option value="選手5">選手5</option>
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
      <div class="playoff-row">
        <div class="playoff-label">選手名</div>
        <select class="name-dropdown" id="bottomPlayerName">
          <option value="">選択してください</option>
          <option value="選手1">選手1</option>
          <option value="選手2">選手2</option>
          <option value="選手3">選手3</option>
          <option value="選手4">選手4</option>
          <option value="選手5">選手5</option>
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

      <div class="playoff-row">
        <div class="playoff-label">チーム名</div>
      </div>
    </div>

    <div class="result-row">
      <button class="next-btn" onclick="submitPlayoff()">送信</button>
      <button class="back-btn" onclick="backToMatch()">戻る</button>
    </div>
  </div>

  <script>
    const options = ['メ', 'コ', 'ド', '反', 'ツ', '〇'];
    
    const playoffData = {
      topName: '',
      top: ['▲', '▲', '▲'],
      bottomName: '',
      bottom: ['▼', '▼', '▼']
    };

    let currentDropdown = null;

    document.getElementById('topPlayerName').addEventListener('change', function() {
      playoffData.topName = this.value;
    });

    document.getElementById('bottomPlayerName').addEventListener('change', function() {
      playoffData.bottomName = this.value;
    });

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
        dropdown.className = `dropdown-menu ${position === 'bottom' ? 'dropdown-up' : 'dropdown-down'}`;
        
        options.forEach(option => {
          const item = document.createElement('div');
          item.className = 'dropdown-item';
          item.textContent = option;
          item.addEventListener('click', function(e) {
            e.stopPropagation();
            playoffData[position][index] = option;
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

    function submitPlayoff() {
      const savedData = JSON.parse(localStorage.getItem('matchesData') || '{}');
      savedData.playoff = playoffData;
      localStorage.setItem('matchesData', JSON.stringify(savedData));
      localStorage.setItem('previousPage', 'playoff');
      window.location.href = 'check_result.php';
    }

    function backToMatch() {
      window.location.href = 'Input_form.php';
    }

    document.addEventListener('click', function(e) {
      if (currentDropdown && !e.target.classList.contains('triangle-btn')) {
        currentDropdown.remove();
        currentDropdown = null;
      }
    });
  </script>
</body>
</html>
