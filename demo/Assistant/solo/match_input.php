<!-- match_input.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合番号入力</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #ffffff;
            padding: 40px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            gap: 40px;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 80px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 100px;
        }

        h2 {
            font-size: 32px;
            margin-bottom: 50px;
            text-align: center;
        }

        .input-wrapper {
            width: 100%;
            max-width: 500px;
            margin-bottom: 50px;
        }

        input[type="text"] {
            width: 100%;
            padding: 20px 30px;
            font-size: 20px;
            text-align: center;
            border: 2px solid #999;
            border-radius: 50px;
            outline: none;
            background-color: #f5f5f5;
        }

        input[type="text"]:focus {
            border-color: #666;
        }

        input[type="text"]::placeholder {
            color: #999;
        }

        .button-group {
            display: flex;
            gap: 60px;
            margin-top: 40px;
        }

        button {
            padding: 15px 70px;
            font-size: 22px;
            border: 2px solid #000;
            border-radius: 50px;
            background-color: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #f0f0f0;
        }

        .error {
            color: #d32f2f;
            margin-top: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    $error = '';

    // フォーム送信の処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'back') {
                // 戻る処理
                header('Location: ../index.php');
                exit;
            } elseif ($_POST['action'] === 'submit') {
                // 決定処理
                $match_number = trim($_POST['match_number'] ?? '');
                
                if (empty($match_number)) {
                    $error = '試合番号を入力してください';
                } else {
                    // セッションに試合番号を保存
                    $_SESSION['match_number'] = $match_number;
                    // 次の画面に遷移
                    header('Location: solo-forfeit.php');
                    exit;
                }
            }
        }
    }
    ?>

    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <span>個人戦</span>
            <span>○○大会</span>
            <span>○○部門</span>
        </div>

        <!-- メインコンテンツ -->
        <div class="main-content">
            <h2>試合番号を入力してください</h2>

            <form method="POST" action="">
                <div class="input-wrapper">
                    <input type="text" 
                           name="match_number" 
                           placeholder="試合番号"
                           value="<?php echo isset($_POST['match_number']) ? htmlspecialchars($_POST['match_number']) : ''; ?>"
                           autofocus>
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                </div>

                <div class="button-group">
                    <button type="submit" name="action" value="back">戻る</button>
                    <button type="submit" name="action" value="submit">決定</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<!-- ============================================ -->
<!-- match_detail.php (次の画面) -->
<!-- ============================================ -->
<?php exit; // 以下は別ファイルとして保存してください ?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合詳細</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #ffffff;
            padding: 40px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            gap: 40px;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 80px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        h2 {
            font-size: 32px;
            margin-bottom: 50px;
            text-align: center;
        }

        .match-info {
            background-color: #f5f5f5;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 50px;
            width: 100%;
            max-width: 600px;
        }

        .match-info p {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        .match-number {
            font-size: 48px;
            font-weight: bold;
            color: #1976d2;
            text-align: center;
            margin: 30px 0;
        }

        button {
            padding: 15px 70px;
            font-size: 22px;
            border: 2px solid #000;
            border-radius: 50px;
            background-color: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 30px;
        }

        button:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    
    // セッションに試合番号がない場合は入力画面に戻す
    if (!isset($_SESSION['match_number'])) {
        header('Location: match_input.php');
        exit;
    }
    
    $match_number = htmlspecialchars($_SESSION['match_number']);
    ?>

    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <span>個人戦</span>
            <span>○○大会</span>
            <span>○○部門</span>
        </div>

        <!-- メインコンテンツ -->
        <div class="main-content">
            <h2>試合情報</h2>

            <div class="match-info">
                <p>試合番号</p>
                <div class="match-number"><?php echo $match_number; ?></div>
                <p>試合が登録されました</p>
            </div>

            <form method="POST" action="match_input.php">
                <button type="submit">戻る</button>
            </form>
        </div>
    </div>
</body>
</html