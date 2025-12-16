<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会運営責任者メニュー</title>
    <link rel="stylesheet" href="../css/Admin_top.css">
</head>
<body>

    <div class="menu-link">
        <a href="#" class="menu-text">メニュー ></a>
    </div>

    <div class="container">
        <h1 class="title">大会運営責任者メニュー</h1>

        <div class="button-grid">
            <button class="menu-button" onclick="location.href='Admin_selection.php'">
                <span class="icon">□</span>
                <span class="button-text">大会登録・名称変更</span>
            </button>

            <button class="menu-button" onclick="location.href='Admin_unlock.php'">
                <span class="icon">🔓</span>
                <span class="button-text">大会ロック解除</span>
            </button>

            <button class="menu-button" onclick="location.href='csv-import.php'">
                <span class="icon">⤓</span>
                <span class="button-text">一斉登録</span>
            </button>
        </div>

        <div class="back-link">
            <a href="../master.php" class="back-text">← 戻る</a>
        </div>
    </div>

</body>
</html>
