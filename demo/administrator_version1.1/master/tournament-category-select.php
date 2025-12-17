<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>部門選択</title>
    <link rel="stylesheet" href="tournament-category-select-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="tournament-register.php" class="breadcrumb-link">大会登録></a>
        <a href="#" class="breadcrumb-link">部門選択></a>
    </div>
    
    <div class="container">
        <div class="categories-grid">
            <div class="category-column">
                <h2 class="category-title">個人戦</h2>
                <div class="checkbox-list" id="individualCategories">
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" checked data-category="小学生4年以下個人">
                            <span>小学生4年以下個人</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="小学生5年以上個人">
                            <span>小学生5年以上個人</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="中学生男子個人">
                            <span>中学生男子個人</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="中学生女子個人">
                            <span>中学生女子個人</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                </div>
            </div>
            
            <div class="category-column">
                <h2 class="category-title">団体戦</h2>
                <div class="checkbox-list" id="teamCategories">
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="小学生団体(5人制)">
                            <span>小学生団体(5人制)</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" checked data-category="中学生男子団体(5人制)">
                            <span>中学生男子団体(5人制)</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="中学生女子団体(5人制)">
                            <span>中学生女子団体(5人制)</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                    <div class="checkbox-item-wrapper">
                        <label class="checkbox-item">
                            <input type="checkbox" data-category="中学生女子団体(3人制)">
                            <span>中学生女子団体(3人制)</span>
                        </label>
                        <button class="delete-button" onclick="deleteCategory(this)">削除</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">戻る</button>
            <button class="action-button" onclick="location.href='category-create.php'">部門作成</button>
            <button class="action-button" id="registerButton">登録</button>
        </div>
    </div>
    
    <script>
        function deleteCategory(button) {
            const wrapper = button.closest('.checkbox-item-wrapper');
            const categoryName = wrapper.querySelector('.checkbox-item span').textContent;
            
            if (confirm(`「${categoryName}」を削除してもよろしいですか?`)) {
                if (confirm(`本当に「${categoryName}」を削除しますか?\nこの操作は取り消せません。`)) {
                    wrapper.remove();
                }
            }
        }
        
        document.getElementById('registerButton').addEventListener('click', function() {
            // チェックされた部門を取得
            const checkedBoxes = document.querySelectorAll('.checkbox-item input[type="checkbox"]:checked');
            
            if (checkedBoxes.length === 0) {
                alert('少なくとも1つの部門を選択してください。');
                return;
            }
            
            const categories = Array.from(checkedBoxes).map(cb => cb.getAttribute('data-category'));
            
            // データをセッションストレージに保存（ページ遷移のみで使用）
            sessionStorage.setItem('selectedCategories', JSON.stringify(categories));
            
            // 確認画面に遷移
            location.href = 'tournament-register-confirm2.php';
        });
    </script>
</body>
</html>