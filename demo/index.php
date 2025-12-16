<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>大会一覧</title>
    <link rel="stylesheet" href="./index_css/style.css">

</head>

<body>
    <header>
        <div class="menu-icon" onclick="toggleMenu()">☰</div>
    </header>

    <div class="menu-links" id="menuLinks">
        <a href="./Administrater/admini_Top.php">管理者用ログイン画面</a>
        <a href="./InputForm/index.php">入力補助員用ログイン画面</a>
    </div>

    <div class="title">
        <h1>大会一覧</h1>
    </div>
    <div class="main-container">
        <div class="search-bar">
            <input type="text" placeholder="大会名や開催日で検索" />
            <button>検索</button>
        </div>

        <div class="tournament-list">
            <!-- aタグは絶対パスしてい。 -->
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
            <a href="./view/sportsSelection.php" class="tournament-item">〇〇大会 開催日</a>
        </div>

        <div class="pagination">
            <button>← 戻る</button>
            <button>次へ →</button>
        </div>
        <footer>
            <div class="school-name">MCL盛岡情報ビジネス＆デザイン専門学校</div>
        </footer>


        <script>
            function toggleMenu() {
                const menu = document.getElementById('menuLinks');
                menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
            }
        </script>
</body>

</html>