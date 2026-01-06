<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦試合詳細</title>
    <link rel="stylesheet" href="../css/team-match-detail-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">団体戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
            <span class="header-text">第五試合</span>
        </div>
        
        <div class="top-right-button">
            <button class="position-button" id="positionButton">先鋒</button>
        </div>
        
        <div class="match-section">
            <div class="player-info">
                <div class="label">チームID</div>
                <div class="id-group">
                    <span class="id-number">1</span>
                    <span class="id-number">2</span>
                    <span class="id-number">3</span>
                </div>
            </div>
            
            <div class="player-info">
                <div class="label">名前</div>
                <div class="score-group">
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-buttons">
            <button class="action-button red" id="nextButton">次へ</button>
            <button class="action-button red" id="prevButton">戻る</button>
        </div>
        
        <hr class="divider">
        
        <div class="center-buttons">
            <div class="draw-dropdown-container">
                <button class="action-button outlined" id="drawButton">引分け</button>
                <div class="draw-dropdown-menu" id="drawMenu">
                    <button class="draw-dropdown-item" data-value="one">一本勝</button>
                    <button class="draw-dropdown-item" data-value="extend">延長</button>
                </div>
            </div>
            <button class="action-button outlined">取り消し</button>
        </div>
        
        <div class="match-section">
            <div class="player-info">
                <div class="label">名前</div>
                <div class="score-group">
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                    <div class="dropdown-container">
                        <button class="score-button dropdown">▼</button>
                        <div class="dropdown-menu">
                            <button class="dropdown-item">×</button>
                            <button class="dropdown-item">コ</button>
                            <button class="dropdown-item">ド</button>
                            <button class="dropdown-item">反</button>
                            <button class="dropdown-item">ツ</button>
                            <button class="dropdown-item">〇</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="player-info">
                <div class="label">チームID</div>
                <div class="id-group">
                    <span class="id-number">1</span>
                    <span class="id-number">2</span>
                    <span class="id-number">3</span>
                </div>
            </div>
        </div>
        
        <div class="bottom-buttons">
            <button class="action-button" onclick="history.back()">キャンセル</button>
            <button class="action-button" onclick="location.href='match-confirm.php'">変更</button>
        </div>
    </div>
    
    <script>
        // DOMが完全に読み込まれてから実行
        document.addEventListener('DOMContentLoaded', function() {
            // ポジションの配列
            const positions = ['先鋒', '次鋒', '中堅', '副将', '大将','代表決定戦'];
            let currentPosition = 0;
            
            const positionButton = document.getElementById('positionButton');
            const nextButton = document.getElementById('nextButton');
            const prevButton = document.getElementById('prevButton');
            
            // 次へボタン
            nextButton.addEventListener('click', function() {
                if (currentPosition < positions.length - 1) {
                    currentPosition++;
                    positionButton.textContent = positions[currentPosition];
                }
            });
            
            // 戻るボタン
            prevButton.addEventListener('click', function() {
                if (currentPosition > 0) {
                    currentPosition--;
                    positionButton.textContent = positions[currentPosition];
                }
            });
            
            // 引き分けドロップダウン
            const drawButton = document.getElementById('drawButton');
            const drawMenu = document.getElementById('drawMenu');
            
            drawButton.addEventListener('click', function(e) {
                e.stopPropagation();
                drawMenu.style.display = drawMenu.style.display === 'block' ? 'none' : 'block';
            });
            
            document.querySelectorAll('.draw-dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const text = this.textContent;
                    drawButton.textContent = text;
                    drawMenu.style.display = 'none';
                });
            });
            
            // ドロップダウンの開閉
            document.querySelectorAll('.dropdown').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // 他の開いているドロップダウンを閉じる
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.style.display = 'none';
                        }
                    });
                    const menu = this.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });
            
            // ドロップダウンアイテムの選択
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const dropdown = this.closest('.dropdown-container').querySelector('.dropdown');
                    dropdown.textContent = this.textContent;
                    this.parentElement.style.display = 'none';
                });
            });
            
            // 外側クリックでドロップダウンを閉じる
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
                drawMenu.style.display = 'none';
            });
        });
    </script>
</body>
</html>