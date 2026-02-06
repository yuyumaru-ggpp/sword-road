<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>試合番号入力 - デモ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            height: 100%;
            max-height: calc(100vh - 16px);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
            white-space: nowrap;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
            overflow-y: auto;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 20px;
            text-align: center;
            color: #2d3748;
            font-weight: 700;
            flex-shrink: 0;
        }

        .form-container {
            flex: 0 1 auto;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .input-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #4a5568;
            display: block;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 14px 18px;
            font-size: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            background-color: #f7fafc;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input[type="text"]:focus,
        select:focus {
            border-color: #667eea;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        input[type="text"]::placeholder {
            color: #a0aec0;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='%234a5568'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 48px;
        }

        .error {
            background-color: #fed7d7;
            color: #c53030;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
            border-left: 4px solid #c53030;
            animation: shake 0.4s;
        }

        .success {
            background-color: #c6f6d5;
            color: #2f855a;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            text-align: center;
            border-left: 4px solid #2f855a;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-shrink: 0;
        }

        button {
            flex: 1;
            padding: 14px 18px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-back {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .btn-back:hover {
            background-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .demo-notice {
            background-color: #fef5e7;
            color: #7d6608;
            padding: 8px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 12px;
            text-align: center;
            border-left: 4px solid #f39c12;
        }

        /* 小さい画面での調整 */
        @media (max-height: 700px) {
            .main-content {
                padding: 15px;
            }

            h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            input[type="text"],
            select {
                padding: 12px 16px;
                font-size: 15px;
            }

            button {
                padding: 12px 16px;
                font-size: 15px;
            }

            .button-group {
                margin-top: 15px;
            }

            .header {
                padding: 10px 12px;
            }

            .header-badge {
                font-size: 12px;
                padding: 5px 12px;
            }
        }

        /* 非常に小さい画面 */
        @media (max-height: 600px) {
            .main-content {
                padding: 12px;
            }

            h2 {
                font-size: 16px;
                margin-bottom: 12px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .input-label {
                font-size: 13px;
                margin-bottom: 6px;
            }

            input[type="text"],
            select,
            button {
                padding: 10px 14px;
                font-size: 14px;
            }

            .button-group {
                margin-top: 12px;
                gap: 10px;
            }

            .header {
                padding: 8px 10px;
                gap: 6px;
            }

            .header-badge {
                font-size: 11px;
                padding: 4px 10px;
            }

            .demo-notice,
            .error,
            .success {
                padding: 6px 10px;
                font-size: 11px;
                margin-bottom: 10px;
            }
        }

        /* スマートフォン横向き */
        @media (max-width: 900px) and (max-height: 500px) {
            body {
                padding: 5px;
            }

            .container {
                max-width: 95%;
                max-height: calc(100vh - 10px);
                border-radius: 15px;
            }

            .header {
                padding: 8px 10px;
                gap: 6px;
            }

            .header-badge {
                font-size: 11px;
                padding: 4px 10px;
            }

            .main-content {
                padding: 12px 15px;
            }

            h2 {
                font-size: 16px;
                margin-bottom: 10px;
            }

            .form-group {
                margin-bottom: 10px;
            }

            .input-label {
                font-size: 12px;
                margin-bottom: 5px;
            }

            input[type="text"],
            select {
                padding: 10px 12px;
                font-size: 14px;
            }

            button {
                padding: 10px 14px;
                font-size: 14px;
            }

            .button-group {
                margin-top: 10px;
                gap: 8px;
            }

            .error, .success, .demo-notice {
                padding: 6px 10px;
                font-size: 11px;
                margin-bottom: 8px;
            }
        }

        /* タブレット縦向き */
        @media (min-width: 601px) and (max-width: 900px) {
            .container {
                max-width: 500px;
            }
        }

        /* 小さいスマートフォン */
        @media (max-width: 400px) {
            .header-badge {
                font-size: 12px;
                padding: 5px 10px;
            }

            h2 {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <div class="header-badge">団体戦</div>
            <div class="header-badge">2024年度 全国選手権大会</div>
            <div class="header-badge">男子団体</div>
        </div>

        <div class="main-content">
            <h2>試合情報を入力してください</h2>

            <form id="matchForm">
                <div class="form-container">
                    <div class="demo-notice">
                        ⚠️ これはデモ画面です
                    </div>

                    <div id="messageArea"></div>

                    <div class="form-group">
                        <label class="input-label" for="match_field">試合場</label>
                        <select name="match_field" id="match_field">
                            <option value="">選択してください</option>
                            <option value="1">第1試合場</option>
                            <option value="2">第2試合場</option>
                            <option value="3">第3試合場</option>
                            <option value="4">第4試合場</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="input-label" for="match_number">試合番号</label>
                        <input type="text" 
                               name="match_number" 
                               id="match_number"
                               placeholder="試合番号を入力">
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn-back" onclick="handleBack()">戻る</button>
                    <button type="submit" class="btn-submit">決定</button>
                </div>
            </form>
        </div>

    </div>

    <script>
        // デモ用のデータストレージ（ブラウザのメモリ内のみ）
        const registeredMatches = new Set();
        let lastMatchField = '';

        // 前回の試合場を復元
        if (lastMatchField) {
            document.getElementById('match_field').value = lastMatchField;
        }

        // フォーム送信処理
        document.getElementById('matchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const matchField = document.getElementById('match_field').value;
            const matchNumber = document.getElementById('match_number').value.trim();
            const messageArea = document.getElementById('messageArea');
            
            // バリデーション
            if (matchNumber === '') {
                showMessage('試合番号を入力してください', 'error');
                return;
            }
            
            if (matchField === '') {
                showMessage('試合場を選択してください', 'error');
                return;
            }
            
            // 重複チェック
            const matchKey = `${matchField}-${matchNumber}`;
            if (registeredMatches.has(matchKey)) {
                showMessage('この試合番号と試合場の組み合わせはすでに登録されています', 'error');
                return;
            }
            
            // 登録成功
            registeredMatches.add(matchKey);
            lastMatchField = matchField;
            
            // demo-team-forfeit.phpに遷移
            window.location.href = 'demo-team-forfeit.php';
        });

        function showMessage(message, type) {
            const messageArea = document.getElementById('messageArea');
            messageArea.innerHTML = `<div class="${type}">${message}</div>`;
            
            if (type === 'error') {
                // エラーの場合は振動効果（対応ブラウザのみ）
                if ('vibrate' in navigator) {
                    navigator.vibrate(200);
                }
            }
        }

        function handleBack() {
            window.location.href = 'teamdemo.php';
        }

        // 初期フォーカス
        window.addEventListener('load', function() {
            const matchField = document.getElementById('match_field');
            if (matchField.value === '') {
                matchField.focus();
            } else {
                document.getElementById('match_number').focus();
            }
        });
    </script>
</body>

</html>