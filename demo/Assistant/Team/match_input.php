<!-- match_input.php -->
<?php
session_start();

/* ---------- ログイン & パラメータチェック ---------- */
if (!isset($_SESSION['tournament_id'], $_GET['division_id'])) {
    header('Location: ../index.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id = (int) $_GET['division_id'];

/* ---------- DB接続 ---------- */
require_once '../../connect/db_connect.php';

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

$match_field_count = (int) ($info['match_field_count'] ?? 1);

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
        $match_field = (int) $_POST['match_field'];

        if ($match_number === '') {
            $error = '試合番号を入力してください';
        } elseif ($match_field < 1 || $match_field > $match_field_count) {
            $error = '試合場を選択してください';
        } else {

            // 重複チェック（individual_match_num を使用）
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

                header('Location: team-forfeit.php');
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
            overflow: hidden;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            height: 100vh;
            max-height: 900px;
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
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .main-content {
            flex: 1;
            padding: 30px 25px 25px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        h2 {
            font-size: 22px;
            margin-bottom: 30px;
            text-align: center;
            color: #2d3748;
            font-weight: 700;
            flex-shrink: 0;
        }

        .form-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .input-label {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #4a5568;
            display: block;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 16px 20px;
            font-size: 17px;
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
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            border-left: 4px solid #c53030;
            animation: shake 0.4s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-shrink: 0;
        }

        button {
            flex: 1;
            padding: 15px 20px;
            font-size: 17px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        button[value="back"] {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        button[value="back"]:hover {
            background-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        button[value="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        button[value="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        /* 小さい画面での調整 */
        @media (max-height: 700px) {
            .container {
                max-height: 100vh;
            }

            .header {
                padding: 15px;
            }

            .header-badge {
                font-size: 13px;
                padding: 6px 14px;
            }

            .main-content {
                padding: 20px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            input[type="text"],
            select {
                padding: 14px 18px;
                font-size: 16px;
            }

            button {
                padding: 13px 18px;
                font-size: 16px;
            }

            .button-group {
                margin-top: 20px;
            }
        }

        /* スマートフォン横向き */
        @media (max-width: 900px) and (max-height: 500px) {
            .container {
                max-width: 90%;
                max-height: 95vh;
            }

            .header {
                padding: 10px;
                gap: 8px;
            }

            .header-badge {
                font-size: 12px;
                padding: 5px 12px;
            }

            .main-content {
                padding: 15px;
            }

            h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .input-label {
                font-size: 14px;
                margin-bottom: 8px;
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

            .error {
                padding: 10px 14px;
                font-size: 13px;
                margin-bottom: 15px;
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
                font-size: 13px;
                padding: 6px 12px;
            }

            h2 {
                font-size: 19px;
            }

            input[type="text"],
            select,
            button {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <div class="header-badge">
                <?php echo ((int) $info['distinction'] === 2) ? '個人戦' : '団体戦'; ?>
            </div>
            <div class="header-badge">
                <?php echo htmlspecialchars($info['tournament_name']); ?>
            </div>
            <div class="header-badge">
                <?php echo htmlspecialchars($info['division_name']); ?>
            </div>
        </div>

        <div class="main-content">
            <h2>試合情報を入力してください</h2>

            <form method="POST">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="input-label" for="match_field">試合場</label>
                        <select name="match_field" id="match_field">
                            <option value="">選択してください</option>
                            <?php for ($i = 1; $i <= $match_field_count; $i++): ?>
                                <option value="<?= $i ?>" <?= (isset($_POST['match_field']) && $_POST['match_field'] == $i) || (!isset($_POST['match_field']) && $previous_match_field == $i) ? 'selected' : '' ?>>
                                    第<?= $i ?>試合場
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="input-label" for="match_number">試合番号</label>
                        <input type="text" 
                               name="match_number" 
                               id="match_number"
                               placeholder="試合番号を入力"
                               value="<?php echo htmlspecialchars($_POST['match_number'] ?? ''); ?>">
                    </div>
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