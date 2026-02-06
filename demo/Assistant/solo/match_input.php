<?php
// 個人戦共通処理を読み込み
require_once 'solo_db.php';

/* ---------- ログイン & パラメータチェック ---------- */
if (!isset($_SESSION['tournament_id'], $_GET['division_id'])) {
    header('Location: ../index.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id = (int)$_GET['division_id'];


/* ---------- 大会・部門情報取得 ---------- */
$sql = "
    SELECT
        t.title AS tournament_name,
        t.match_field AS match_field_count,
        d.name AS division_name,
        d.distinction
    FROM
        departments d
    JOIN
        tournaments t ON d.tournament_id = t.id
    WHERE
        d.id = :division_id
      AND
        t.id = :tournament_id
      AND
        d.del_flg = 0
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':division_id' => $division_id,
    ':tournament_id' => $tournament_id
]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);

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
        header('Location: ../index.php');
        exit;
    }

    if ($_POST['action'] === 'submit') {

        $match_number = trim($_POST['match_number']);
        $match_field = (int)$_POST['match_field'];

        if ($match_number === '') {
            $error = '試合番号を入力してください';
        } elseif ($match_field < 1 || $match_field > $match_field_count) {
            $error = '試合場を選択してください';
        } else {

            // 重複チェック(individual_match_num を使用)
            $sql = "
            SELECT COUNT(*)
            FROM individual_matches
            WHERE department_id = :department_id
            AND individual_match_num = :match_number
            AND match_field = :match_field
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':department_id' => $division_id,
                ':match_number' => $match_number,
                ':match_field' => $match_field
            ]);

            if ($stmt->fetchColumn() > 0) {
                $error = 'この試合番号と試合場の組み合わせはすでに登録されています';
            } else {
                // セッションに保持
                $_SESSION['division_id'] = $division_id;
                $_SESSION['match_number'] = $match_number;
                $_SESSION['match_field'] = $match_field;
                
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
            overflow: auto; /* hidden から auto に変更 */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            position: relative;
            min-height: 100vh; /* 追加 */
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: backgroundMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            width: 100%;
            min-height: 100vh; /* height から min-height に変更 */
            display: flex;
            flex-direction: column;
            padding: min(3vh, 20px);
            position: relative;
            z-index: 1;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: min(3vw, 16px);
            padding: min(2.5vh, 18px) min(3vw, 25px);
            box-shadow: 
                0 10px 40px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            margin-bottom: min(2vh, 15px);
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
            gap: min(2vw, 12px);
            align-items: center;
            font-size: clamp(14px, 2.5vh, 20px);
            font-weight: 700;
            line-height: 1.3;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: min(1.2vh, 8px) min(2.5vw, 18px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: min(2vw, 10px);
            font-size: clamp(12px, 2vh, 16px);
            font-weight: 700;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            white-space: nowrap;
        }

        .header-text {
            color: #1f2937;
            white-space: nowrap;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: min(4vw, 24px);
            padding: min(5vh, 40px) min(5vw, 35px) min(4vh, 30px);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            animation: fadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h2 {
            font-size: clamp(20px, 4vh, 32px);
            font-weight: 800;
            margin-bottom: min(5vh, 40px);
            text-align: center;
            line-height: 1.3;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.02em;
        }

        .input-wrapper {
            width: 100%;
            max-width: 500px;
            margin-bottom: min(4vh, 30px);
            position: relative;
        }

        .input-label {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            color: #333;
        }

        input[type="text"], select {
            width: 100%;
            padding: min(2.5vh, 18px) min(4vw, 28px);
            font-size: clamp(18px, 3.5vh, 26px);
            font-weight: 600;
            text-align: center;
            border: 3px solid transparent;
            border-radius: min(3vw, 16px);
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
            gap: min(3vw, 20px);
            justify-content: center;
            width: 100%;
        }

        button {
            flex: 1;
            padding: min(2vh, 14px) min(4vw, 30px);
            font-size: clamp(16px, 2.5vh, 20px);
            font-weight: 700;
            border: none;
            border-radius: min(2.5vw, 14px);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.05em;
            white-space: nowrap;
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

        button:hover::before {
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

        button[value="back"]:hover {
            background: #fff;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        button[value="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        button[value="submit"]:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .error {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: min(1.5vh, 10px) min(2.5vw, 16px);
            border-radius: min(2vw, 10px);
            margin-top: min(1.5vh, 10px);
            font-size: clamp(12px, 2vh, 14px);
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

        /* 縦長画面(スマホ縦向き) */
        @media (max-height: 700px) {
            .container {
                padding: 2vh 2vw;
            }

            .header {
                padding: 1.5vh 3vw;
                margin-bottom: 1.5vh;
            }

            .main-content {
                padding: 3vh 4vw 2.5vh;
            }

            h2 {
                font-size: clamp(18px, 3.5vh, 26px);
                margin-bottom: 3vh;
            }

            .input-wrapper {
                margin-bottom: 2.5vh;
            }

            input[type="text"], select {
                padding: 2vh 3vw;
                font-size: clamp(16px, 3vh, 22px);
            }

            button {
                padding: 1.5vh 3vw;
                font-size: clamp(14px, 2.2vh, 18px);
            }
        }

        /* 極端に縦長の画面 */
        @media (max-height: 600px) {
            h2 {
                font-size: clamp(16px, 3vh, 22px);
                margin-bottom: 2vh;
            }

            .input-wrapper {
                margin-bottom: 2vh;
            }

            .error {
                padding: 1vh 2vw;
                margin-top: 1vh;
                font-size: clamp(11px, 1.8vh, 13px);
            }
        }

        /* 横長画面(タブレット横向きなど) */
        @media (min-aspect-ratio: 4/3) and (max-height: 800px) {
            .container {
                max-width: 900px;
                margin: 0 auto;
            }

            .header-content {
                font-size: clamp(14px, 2.2vh, 18px);
            }

            h2 {
                font-size: clamp(20px, 3.5vh, 28px);
            }
        }

        /* 小型スマホ */
        @media (max-width: 360px) {
            .header-content {
                gap: 8px;
            }

            .badge {
                font-size: clamp(11px, 1.8vh, 14px);
                padding: 6px 12px;
            }

            .header-text {
                font-size: clamp(12px, 2.2vh, 16px);
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