<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合詳細</title>
    <link rel="stylesheet" href="match-detail-style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">個人戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
        </div>
        
        <div class="match-section">
            <div class="player-info">
                <div class="label">選手ID</div>
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
        
        <div class="right-button">
            <button class="action-button small">引</button>
        </div>
        
        <hr class="divider">
        
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
                <div class="label">選手ID</div>
                <div class="id-group">
                    <span class="id-number">1</span>
                    <span class="id-number">2</span>
                    <span class="id-number">3</span>
                </div>
            </div>
        </div>
        
        <div class="bottom-buttons">
            <button class="action-button" onclick="history.back()">キャンセル</button>
            <button class="action-button" onclick="alert('変更を保存')">変更</button>
        </div>
    </div>
    
    <script>
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
        });
    </script>
</body>
</html>