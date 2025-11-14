<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>団体戦マッチング画面</title>
  <link rel="stylesheet" href="./css/input_form.css">
</head>
<body>
  <div class="container">
    <div class="match-screen active" id="matchScreen">
      <div class="header">
        <div class="title">団体戦　〇〇大会　〇〇部門</div>
        <div class="header-buttons">
          <button class="senpo-btn" id="roleBtn">先鋒</button>
          <!-- Updated onclick to save and navigate to playoff.html -->
          <button class="playoff-btn" id="playoffBtn" style="display: none;" onclick="goToPlayoff()">代表決定戦</button>
        </div>
      </div>

      <div class="nav-buttons">
        <button class="next-btn" id="nextBtn" onclick="nextMatch()">次へ</button>
        <button class="back-btn" onclick="prevMatch()">戻る</button>
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

    <!-- Removed playoff screen - now in separate file -->
  </div>

  <script>
    const options = ['メ', 'コ', 'ド', '反', 'ツ', '〇'];
    
    const roles = ['先鋒', '次鋒', '中堅', '副将', '大将'];
    
    let currentMatchIndex = 0;
    const matchesData = [
      { top: ['▲', '▲', '▲'], bottom: ['▼', '▼', '▼'], topName: '', bottomName: '', result: '引分け' },
      { top: ['▲', '▲', '▲'], bottom: ['▼', '▼', '▼'], topName: '', bottomName: '', result: '引分け' },
      { top: ['▲', '▲', '▲'], bottom: ['▼', '▼', '▼'], topName: '', bottomName: '', result: '引分け' },
      { top: ['▲', '▲', '▲'], bottom: ['▼', '▼', '▼'], topName: '', bottomName: '', result: '引分け' },
      { top: ['▲', '▲', '▲'], bottom: ['▼', '▼', '▼'], topName: '', bottomName: '', result: '引分け' }
    ];


    let currentDropdown = null;

    function saveCurrentMatch() {
      const match = matchesData[currentMatchIndex];
      match.topName = document.getElementById('topName').value;
      match.bottomName = document.getElementById('bottomName').value;
      match.result = document.getElementById('resultSelect').value;
    }

    function loadMatch() {
      const match = matchesData[currentMatchIndex];
      
      document.getElementById('roleBtn').textContent = roles[currentMatchIndex];
      
      const nextBtn = document.getElementById('nextBtn');
      if (currentMatchIndex === roles.length - 1) {
        nextBtn.textContent = '送信';
      } else {
        nextBtn.textContent = '次へ';
      }
      
      const playoffBtn = document.getElementById('playoffBtn');
      playoffBtn.style.display = currentMatchIndex === roles.length - 1 ? 'inline-block' : 'none';
      
      document.getElementById('topName').value = match.topName;
      document.getElementById('bottomName').value = match.bottomName;
      document.getElementById('resultSelect').value = match.result;
      
      document.querySelectorAll('#matchScreen .triangle-btn').forEach(btn => {
        const position = btn.dataset.position;
        const index = parseInt(btn.dataset.index);
        btn.textContent = match[position][index];
      });
      
      document.querySelector('.back-btn').style.display = currentMatchIndex === 0 ? 'none' : 'block';
    }

    function nextMatch() {
      saveCurrentMatch();
      
      if (currentMatchIndex < roles.length - 1) {
        currentMatchIndex++;
        loadMatch();
      } else {
        localStorage.setItem('matchesData', JSON.stringify({ matches: matchesData, playoff: null }));
        localStorage.setItem('previousPage', 'index');
        window.location.href = 'check_result.php';
      }
    }

    function prevMatch() {
      saveCurrentMatch();
      
      if (currentMatchIndex > 0) {
        currentMatchIndex--;
        loadMatch();
      }
    }

    function goToPlayoff() {
      saveCurrentMatch();
      localStorage.setItem('matchesData', JSON.stringify({ matches: matchesData, playoff: null }));
      window.location.href = 'playoff.php';
    }


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
        dropdown.className = `dropdown-menu ${position.includes('bottom') ? 'dropdown-up' : 'dropdown-down'}`;
        
        options.forEach(option => {
          const item = document.createElement('div');
          item.className = 'dropdown-item';
          item.textContent = option;
          item.addEventListener('click', function(e) {
            e.stopPropagation();
            matchesData[currentMatchIndex][position][index] = option;
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

    function resetAll() {
      matchesData[currentMatchIndex] = {
        top: ['▲', '▲', '▲'],
        bottom: ['▼', '▼', '▼'],
        topName: '',
        bottomName: '',
        result: '引分け'
      };
      
      loadMatch();
      
      if (currentDropdown) {
        currentDropdown.remove();
        currentDropdown = null;
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      loadMatch();
    });
  </script>
</body>
</html>
