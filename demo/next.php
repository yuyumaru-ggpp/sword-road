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
            <div class="individual">
                <h2>個人戦</h2>
                <ul>
                    <li><a href="./Individual/E_under4.php">小学生4年生以下</a></li>
                    <li><a href="./Individual/E_under4.php">小学生5年生以上</a></li>
                    <li><a href="./Individual/E_under4.php">中学生男子</a></li>
                    <li><a href="./Individual/E_under4.php">中学生女子</a></li>
                    <li><a href="./Individual/E_under4.php">高校生男子</a></li>
                    <li><a href="./Individual/E_under4.php">高校生女子</a></li>
                </ul>

            </div>
            <div class="team">
                <h2>団体戦</h2>
                <ul>
                    <li><a href="./Individual/E_under4.php">小学生団体</a></li>
                    <li><a href="./Individual/E_under4.php">中学生男子団体</a></li>
                    <li><a href="./Individual/E_under4.php">中学生女子団体</a></li>
                    <li><a href="./Individual/E_under4.php">高校生男子団体</a></li>
                    <li><a href="./Individual/E_under4.php">高校生女子団体</a></li>
                </ul>
            </div>
        </div>
    </main>


</body>

</html>