<?php
// POSTリクエストの場合、データを保存する処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ここにデータ保存処理を追加できます
    // 例: データベースへの保存、ログの記録など
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            max-width: 1100px;
            width: 100%;
        }

        .success-box {
            background-color: white;
            border: 4px solid #22c55e;
            border-radius: 24px;
            padding: 4rem 3rem;
            text-align: center;
            margin-bottom: 3rem;
        }

        .success-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background-color: #22c55e;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }

        .success-icon::before {
            content: '✓';
            color: white;
            font-size: 3.5rem;
            font-weight: bold;
            line-height: 1;
        }

        .success-message {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1f2937;
            letter-spacing: 0.05em;
        }

        .button-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
        }

        .action-button {
            padding: 1.25rem 3.5rem;
            font-size: 1.25rem;
            background-color: white;
            border: 3px solid #000;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            letter-spacing: 0.05em;
        }

        .action-button:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
        }

        .action-button:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .success-box {
                padding: 3rem 2rem;
            }

            .success-icon {
                width: 60px;
                height: 60px;
            }

            .success-icon::before {
                font-size: 2.5rem;
            }

            .success-message {
                font-size: 1.75rem;
            }

            .button-container {
                flex-direction: column;
                gap: 1rem;
            }

            .action-button {
                width: 100%;
                padding: 1rem 2rem;
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-box">
            <div class="success-icon"></div>
            <div class="success-message">送信しました</div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="location.href='match_input.php'">連続で入力する</button>
            <button class="action-button" onclick="location.href='../index.php'">部門選択画面に戻る</button>
        </div>
    </div>
</body>
</html>