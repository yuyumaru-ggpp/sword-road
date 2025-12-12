<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>団体戦 - チーム選択</title>
  <link rel="stylesheet" href="./css/style.css">

  
</head>
<body>
  <div class="container">
    <div class="header">
      団体戦　○○大会　○○部門　5人制用
    </div>

    <div class="match-container">
      <div class="team-dropdown" id="team1-dropdown">
        <div class="team-label" id="team1-label">チーム名</div>
        <div class="dropdown-menu" id="team1-menu">
          <div class="dropdown-item" data-value="チームA">チームA</div>
          <div class="dropdown-item" data-value="チームB">チームB</div>
          <div class="dropdown-item" data-value="チームC">チームC</div>
          <div class="dropdown-item" data-value="チームD">チームD</div>
          <div class="dropdown-item" data-value="チームE">チームE</div>
        </div>
      </div>

      <div class="vs-text">対</div>

      <div class="team-dropdown" id="team2-dropdown">
        <div class="team-label" id="team2-label">チーム名</div>
        <div class="dropdown-menu" id="team2-menu">
          <div class="dropdown-item" data-value="チームA">チームA</div>
          <div class="dropdown-item" data-value="チームB">チームB</div>
          <div class="dropdown-item" data-value="チームC">チームC</div>
          <div class="dropdown-item" data-value="チームD">チームD</div>
          <div class="dropdown-item" data-value="チームE">チームE</div>
        </div>
      </div>
    </div>

    <div class="button-group">
      <button class="btn" onclick="handleDecision()">決定</button>
      <button class="btn" onclick="handleBack()">戻る</button>
    </div>
  </div>

  <script>
    // ページの説明
    alert('団体戦の入力フォームになります。では、チームを選んで決定を押してみましょう。');

    let selectedTeam1 = 'チーム名';
    let selectedTeam2 = 'チーム名';

    // プルダウンの開閉処理
    function setupDropdown(labelId, menuId, teamNumber) {
      const label = document.getElementById(labelId);
      const menu = document.getElementById(menuId);
      const items = menu.querySelectorAll('.dropdown-item');

      label.addEventListener('click', (e) => {
        e.stopPropagation();
        // 他のドロップダウンを閉じる
        document.querySelectorAll('.dropdown-menu').forEach(m => {
          if (m !== menu) m.classList.remove('show');
        });
        document.querySelectorAll('.team-label').forEach(l => {
          if (l !== label) l.classList.remove('active');
        });
        
        menu.classList.toggle('show');
        label.classList.toggle('active');
      });

      items.forEach(item => {
        item.addEventListener('click', () => {
          const value = item.getAttribute('data-value');
          label.textContent = value;
          
          if (teamNumber === 1) {
            selectedTeam1 = value;
          } else {
            selectedTeam2 = value;
          }
          
          menu.classList.remove('show');
          label.classList.remove('active');
        });
      });
    }

    // 外側をクリックしたらプルダウンを閉じる
    document.addEventListener('click', () => {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
      });
      document.querySelectorAll('.team-label').forEach(label => {
        label.classList.remove('active');
      });
    });

    // ドロップダウンのセットアップ
    setupDropdown('team1-label', 'team1-menu', 1);
    setupDropdown('team2-label', 'team2-menu', 2);

    // 決定ボタン
    function handleDecision() {
      if (selectedTeam1 === 'チーム名' || selectedTeam2 === 'チーム名') {
        alert('両方のチーム名を選択してください');
        return;
      }
      
      if (selectedTeam1 === selectedTeam2) {
        alert('異なるチームを選択してください');
        return;
      }

      // マッチング画面に遷移
      window.location.href = './Input_form.php';
    }

    // 戻るボタン
    function handleBack() {
      window.history.back();
    }
  </script>
</body>
</html>
