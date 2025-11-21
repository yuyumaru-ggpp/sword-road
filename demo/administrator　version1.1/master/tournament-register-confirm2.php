<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録確認</title>
    <link rel="stylesheet" href="tournament-register-confirm-style.css">
</head>
<body>
    <div class="container">
        <h1 class="title">以下の内容で登録します</h1>
        
        <div class="confirm-content">
            <div class="info-row">
                <span class="label">大会名</span>
                <span class="value">〇〇大会</span>
            </div>
            
            <div class="info-row">
                <span class="label">部門</span>
                <div class="value" id="categoriesList">
                    <div>小学生男子部門</div>
                    <div>小学生女子部門</div>
                </div>
            </div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">戻る</button>
            <button class="action-button" onclick="alert('登録しました'); localStorage.removeItem('selectedCategories'); location.href='tournament-list.php'">登録</button>
        </div>
    </div>
    
    <script>
        // 選択された部門を表示
        window.addEventListener('DOMContentLoaded', function() {
            const categories = JSON.parse(localStorage.getItem('selectedCategories') || '[]');
            const categoriesList = document.getElementById('categoriesList');
            
            if (categories.length > 0) {
                categoriesList.innerHTML = '';
                categories.forEach(category => {
                    const div = document.createElement('div');
                    div.textContent = category;
                    categoriesList.appendChild(div);
                });
            }
        });
    </script>
</body>
</html>