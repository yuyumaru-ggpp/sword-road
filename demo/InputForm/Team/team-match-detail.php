<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦試合詳細</title>
    <link rel="stylesheet" href="team-match-detail-style.css">

</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">団体戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
        </div>
        
        <div class="top-right-button">
            <button class="position-button" id="positionButton">先鋒</button>
        </div>
        
        <div class="match-section">
            <div class="player-info">
                <div class="label">チーム名</div>
                <div class="team-name-display"></div>
                <div class="score-group-wrapper">
                    <div class="numbers-group">
                        <span class="number">1</span>
                        <span class="number">2</span>
                        <span class="number">3</span>
                    </div>
                    <div class="score-group">
                        <div class="dropdown-container">
                            <button class="score-button dropdown">▲</button>
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
                            <button class="score-button dropdown">▲</button>
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
                            <button class="score-button dropdown">▲</button>
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
            
            <div class="player-info">
                <div class="label">名前</div>
                <div class="names-display"></div>
            </div>
        </div>
        
        <div class="right-buttons">
            <button class="action-button red" id="nextButton">次へ</button>
            <button class="action-button red" id="prevButton">戻る</button>
        </div>
        
        <hr class="divider">
        
        <div class="right-buttons-bottom">
            <div class="draw-dropdown-container">
                <button class="action-button outlined" id="drawButton">引分け</button>
                <div class="draw-dropdown-menu" id="drawMenu">
                    <button class="draw-dropdown-item" data-value="ippon">引分け</button>
                    <button class="draw-dropdown-item" data-value="ippon">一本勝</button>
                    <button class="draw-dropdown-item" data-value="extend">延長</button>
                    
                </div>
            </div>
            <button class="action-button outlined" id="cancelButton">取り消し</button>
        </div>
        
        <div class="match-section">
            <div class="player-info">
                <div class="label">名前</div>
                <div class="names-display"></div>
            </div>
            
            <div class="player-info">
                <div class="label">チーム名</div>
                <div class="team-name-display"></div>
                <div class="score-group-wrapper">
                    <div class="numbers-group">
                        <span class="number">1</span>
                        <span class="number">2</span>
                        <span class="number">3</span>
                    </div>
                    <div class="score-group">
                        <div class="dropdown-container">
                            <button class="score-button dropdown">▼</button>
                            <div class="dropdown-menu bottom">
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
                            <div class="dropdown-menu bottom">
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
                            <div class="dropdown-menu bottom">
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
        </div>
        
        <div class="bottom-buttons">
            <button class="action-button" onclick="history.back()">キャンセル</button>
            <button class="action-button submit-button" id="submitButton" style="display: none;">送信</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
            let currentPosition = 0;
            
            const positionButton = document.getElementById('positionButton');
            const nextButton = document.getElementById('nextButton');
            const prevButton = document.getElementById('prevButton');
            const submitButton = document.getElementById('submitButton');
            const drawButton = document.getElementById('drawButton');
            const drawMenu = document.getElementById('drawMenu');
            
            function updateSubmitButtonVisibility() {
                if (currentPosition === positions.length - 1) {
                    submitButton.style.display = 'block';
                } else {
                    submitButton.style.display = 'none';
                }
            }
            
            nextButton.addEventListener('click', function() {
                if (currentPosition < positions.length - 1) {
                    currentPosition++;
                    positionButton.textContent = positions[currentPosition];
                    updateSubmitButtonVisibility();
                }
            });
            
            prevButton.addEventListener('click', function() {
                if (currentPosition > 0) {
                    currentPosition--;
                    positionButton.textContent = positions[currentPosition];
                    updateSubmitButtonVisibility();
                }
            });
            
            // 引き分けドロップダウン
            if (drawButton && drawMenu) {
                drawButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // 他のドロップダウンを閉じる
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                    drawMenu.style.display = drawMenu.style.display === 'block' ? 'none' : 'block';
                });
            }
            
            document.querySelectorAll('.draw-dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const text = this.textContent;
                    drawButton.textContent = text;
                    drawMenu.style.display = 'none';
                });
            });
            
            // スコアドロップダウン
            document.querySelectorAll('.dropdown').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.style.display = 'none';
                        }
                    });
                    drawMenu.style.display = 'none';
                    const menu = this.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });
            
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const dropdown = this.closest('.dropdown-container').querySelector('.dropdown');
                    dropdown.textContent = this.textContent;
                    this.parentElement.style.display = 'none';
                });
            });
            
            // 外側クリックで全てのドロップダウンを閉じる
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