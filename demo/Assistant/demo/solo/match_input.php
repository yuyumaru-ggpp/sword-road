<?php
/* ---------- モックデータ（DB代用） ---------- */

// 大会・部門情報のモック
$mock_divisions = [
    1 => [
        'tournament_name' => 'テスト大会2025',
        'match_field_count' => 3,
        'division_name'    => 'A部門',
        'distinction'      => 2,   // 2=個人戦, それ以外=団体戦
    ],
    2 => [
        'tournament_name' => 'テスト大会2025',
        'match_field_count' => 2,
        'division_name'    => 'B部門',
        'distinction'      => 1,
    ],
];

// 既に登録されている試合の組み合わせモック
// [部門ID] => [ ['match_number', 'match_field'], ... ]
$mock_registered_matches = [
    1 => [],  // 空にする
    2 => [],
];


/* ---------- ログイン & パラメータチェック ---------- */
// セッション開始（モック用、なければ開始する）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// テスト用：セッション未設定の場合はモック値で埋める
if (!isset($_SESSION['tournament_id'])) {
    $_SESSION['tournament_id'] = 1;
}
if (!isset($_GET['division_id'])) {
    $_GET['division_id'] = '1';   // テスト用デフォルト
}

if (!isset($_SESSION['tournament_id'], $_GET['division_id'])) {
    header('Location: ../index.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id   = (int)$_GET['division_id'];


/* ---------- 大会・部門情報取得（モック） ---------- */
$info = $mock_divisions[$division_id] ?? null;

if (!$info) {
    exit('部門情報が取得できません');
}

$match_field_count = (int)($info['match_field_count'] ?? 1);

$error = '';

/* ---------- 前回の試合場番号を取得 ---------- */
$previous_match_field = isset($_SESSION['last_match_field']) ? $_SESSION['last_match_field'] : '';

/* ---------- フォーム処理 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'back') {
        header('Location: solodemo.php');
        exit;
    }

    if ($_POST['action'] === 'submit') {

        $match_number = trim($_POST['match_number']);
        $match_field  = (int)$_POST['match_field'];

        if ($match_number === '') {
            $error = '試合番号を入力してください';
        } elseif ($match_field < 1 || $match_field > $match_field_count) {
            $error = '試合場を選択してください';
        } else {

            // 重複チェック（モック配列で検索）
            $registered = $mock_registered_matches[$division_id] ?? [];
            $is_duplicate = false;
            foreach ($registered as $entry) {
                if ((string)$entry['match_number'] === (string)$match_number
                    && (int)$entry['match_field']    === $match_field) {
                    $is_duplicate = true;
                    break;
                }
            }

            if ($is_duplicate) {
                $error = 'この試合番号と試合場の組み合わせはすでに登録されています';
            } else {
                // セッションに保持
                $_SESSION['division_id']   = $division_id;
                $_SESSION['match_number']  = $match_number;
                $_SESSION['match_field']   = $match_field;

                // 次回の入力のために試合場番号を記憶
                $_SESSION['last_match_field'] = $match_field;

                header('Location: solo-forfeit.php');
                exit;
            }
        }
    }
}
?>

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

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: backgroundMove 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 15px;
            position: relative;
            z-index: 1;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 
                0 10px 40px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            margin-bottom: 15px;
            animation: slideDown 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-content {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            white-space: nowrap;
        }

        .header-text {
            color: #1f2937;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px 25px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            animation: fadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        form {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        h2 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.4;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.02em;
        }

        .input-wrapper {
            width: 100%;
            margin-bottom: 25px;
        }

        .input-label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #333;
        }

        input[type="text"], select {
            width: 100%;
            padding: 16px 20px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            border: 3px solid transparent;
            border-radius: 14px;
            outline: none;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #667eea, #764ba2) border-box;
            box-shadow: 
                0 10px 30px rgba(102, 126, 234, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.8) inset;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            color: #1f2937;
        }

        input[type="text"]:focus, select:focus {
            border-color: #666;
            box-shadow: 
                0 15px 40px rgba(102, 126, 234, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.9) inset;
            transform: translateY(-2px);
        }

        input[type="text"]::placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            padding-right: 50px;
        }

        select option {
            text-align: center;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            width: 100%;
            margin-top: 30px;
        }

        button {
            flex: 1;
            padding: 14px 25px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.05em;
        }

        button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        button:active::before {
            width: 300px;
            height: 300px;
        }

        button:active {
            transform: scale(0.95);
        }

        button[value="back"] {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        button[value="back"]:active {
            background: #fff;
            border-color: #667eea;
        }

        button[value="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .error {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 12px 16px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            border: 2px solid rgba(239, 68, 68, 0.3);
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* タブレット以上 */
        @media (min-width: 768px) {
            .container {
                padding: 20px;
                justify-content: center;
            }

            .header {
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .main-content {
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
                padding: 40px 35px;
            }

            h2 {
                font-size: 26px;
                margin-bottom: 35px;
            }

            .input-label {
                font-size: 17px;
            }

            input[type="text"], select {
                font-size: 20px;
                padding: 18px 24px;
            }

            button {
                font-size: 18px;
                padding: 16px 30px;
            }

            button:hover::before {
                width: 300px;
                height: 300px;
            }

            button[value="back"]:hover {
                background: #fff;
                border-color: #667eea;
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
                transform: translateY(-2px);
            }

            button[value="submit"]:hover {
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
                transform: translateY(-2px);
            }
        }

        /* 小型スマホ */
        @media (max-width: 360px) {
            .container {
                padding: 12px;
            }

            .header {
                padding: 12px 16px;
                border-radius: 12px;
            }

            .header-content {
                gap: 8px;
                font-size: 13px;
            }

            .badge {
                font-size: 12px;
                padding: 5px 12px;
            }

            .main-content {
                padding: 25px 20px;
                border-radius: 20px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 25px;
            }

            .input-label {
                font-size: 15px;
            }

            input[type="text"], select {
                font-size: 16px;
                padding: 14px 18px;
            }

            button {
                font-size: 15px;
                padding: 12px 20px;
            }

            .button-group {
                gap: 12px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <div class="header-content">
            <span class="badge"><?php echo ((int)$info['distinction'] === 2) ? '個人戦' : '団体戦'; ?></span>
            <span class="header-text"><?php echo htmlspecialchars($info['tournament_name']); ?></span>
            <span class="header-text"><?php echo htmlspecialchars($info['division_name']); ?></span>
        </div>
    </div>

    <div class="main-content">
        <form method="POST">
            <h2>試合情報を入力してください</h2>

            <div class="input-wrapper">
                <div class="input-label">試合場</div>
                <select name="match_field">
                    <option value="">選択してください</option>
                    <?php for ($i = 1; $i <= $match_field_count; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($_POST['match_field']) && $_POST['match_field'] == $i) || (!isset($_POST['match_field']) && $previous_match_field == $i) ? 'selected' : '' ?>>
                            第<?= $i ?>試合場
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="input-wrapper">
                <div class="input-label">試合番号</div>
                <input type="text" name="match_number" placeholder="試合番号" value="<?php echo htmlspecialchars($_POST['match_number'] ?? ''); ?>">
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="button-group">
                <button type="submit" name="action" value="back">戻る</button>
                <button type="submit" name="action" value="submit">決定</button>
            </div>
        </form>
    </div>

</div>
</body>
</html>