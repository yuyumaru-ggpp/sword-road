<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人戦試合詳細</title>
    <link rel="stylesheet" href="individual-match-detail-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">個人戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
        </div>
        
        <div class="match-content">
            <div class="player-section">
                <div class="player-row">
                    <span class="label">選手ID</span>
                    <span class="id-arrow">▲</span>
                </div>
                
                <div class="score-row">
                    <div class="score-buttons">
                        <div class="dropdown-container">
                            <span class="score-number">1</span>
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
                            <span class="score-number">2</span>
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
                            <span class="score-number">3</span>
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
                
                <div class="player-row">
                    <span class="label">名前</span>
                </div>
            </div>
            
            <div class="right-button">
                <div class="decision-dropdown-container">
                    <button class="decision-button" id="decisionButton">一本勝</button>
                    <div class="decision-dropdown-menu" id="decisionMenu">
                        <button class="decision-item">一本勝</button>
                        <button class="decision-item">延長</button>
                        <button class="decision-item">不戦勝</button>
                    </div>
                </div>
            </div>
            
            <hr class="divider">
            
            <div class="player-section">
                <div class="player-row">
                    <span class="label">名前</span>
                </div>
                
                <div class="score-row">
                    <div class="score-buttons">
                        <div class="dropdown-container">
                            <span class="score-number">1</span>
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
                            <span class="score-number">2</span>
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
                            <span class="score-number">3</span>
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
                
                <div class="player-row">
                    <span class="label">選手ID</span>
                    <span class="id-arrow">▲</span>
                </div>
            </div>
        </div>
        
        <div class="bottom-buttons">
            <button class="action-button" onclick="alert('決定しました')">決定</button>
            <button class="action-button" onclick="history.back()">戻る</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 一本勝・延長・不戦勝ドロップダウン
            const decisionButton = document.getElementById('decisionButton');
            const decisionMenu = document.getElementById('decisionMenu');
            
            if (decisionButton && decisionMenu) {
                decisionButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // 他のドロップダウンを閉じる
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                    // トグル
                    decisionMenu.style.display = decisionMenu.style.display === 'block' ? 'none' : 'block';
                });
                
                document.querySelectorAll('.decision-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.stopPropagation();
                        decisionButton.textContent = this.textContent;
                        decisionMenu.style.display = 'none';
                    });
                });
            }
            
            // スコアドロップダウン
            document.querySelectorAll('.dropdown').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // 他のドロップダウンを閉じる
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.style.display = 'none';
                        }
                    });
                    if (decisionMenu) {
                        decisionMenu.style.display = 'none';
                    }
                    // トグル
                    const menu = this.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });
            
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.closest('.dropdown-container').querySelector('.dropdown');
                    dropdown.textContent = this.textContent;
                    this.parentElement.style.display = 'none';
                });
            });
            
            // 外側クリックで全て閉じる
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
                if (decisionMenu) {
                    decisionMenu.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>