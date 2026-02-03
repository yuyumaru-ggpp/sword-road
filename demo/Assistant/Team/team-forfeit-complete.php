<?php
session_start();

// 不戦勝完了フラグのチェック
if (!isset($_SESSION['team_forfeit_complete'])) {
    header('Location: match_input.php');
    exit;
}

// フラグをクリア
unset($_SESSION['team_forfeit_complete']);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>団体戦不戦勝完了</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 1.5rem;
        }

        .title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .message {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 2.5rem;
        }

        .button {
            padding: 1rem 3rem;
            font-size: 1.2rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .button:hover {
            background-color: #2563eb;
        }

        .button-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 1.25rem 3.5rem;
            font-size: 1.25rem;
            border: 3px solid #000;
            border-radius: 50px;
            background: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background: #f9fafb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <div class="title">団体戦不戦勝　登録完了</div>
        <div class="message">試合結果を登録しました</div>
        <div class="button-container">
            <button class="action-button" onclick="location.href='match_input.php?division_id=<?= $division_id ?>'">
                連続で入力する
            </button>
            <button class="action-button" onclick="location.href='../index.php'">
                部門選択画面に戻る
            </button>
        </div>
    </div>
</body>

</html>