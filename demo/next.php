<?php

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>部門選択</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <header>
        <h1>完了画面</h1>
        <p><?php echo $_POST["pass"]; ?></p>
    </header>
    <main>
        <div class="contents">
            <div class="assistant">
                <p>補助員用</p>
                <a href="./InputForm/index.php">入力フォーム</a>
            </div>
            <div class="administrater">
                <p>管理者用</p>
                <a href="./Administrater/index.php">参照・管理サイト</a>
            </div>
        </div>
    </main>


</body>

</html>