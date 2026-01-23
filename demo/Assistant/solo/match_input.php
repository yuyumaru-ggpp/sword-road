<?php
session_start();

/* ---------- ログイン & パラメータチェック ---------- */
if (!isset($_SESSION['tournament_id'], $_GET['division_id'])) {
    header('Location: ../index.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id = (int)$_GET['division_id'];

/* ---------- DB接続 ---------- */
$user = "root";
$pass = "";
$database = "kendo_support_system";
$server = "localhost";
$port = "3307";

$dsn = "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    exit("DB接続失敗:" . $e->getMessage());
}

/* ---------- 大会・部門情報取得 ---------- */
$sql = "
    SELECT
        t.title AS tournament_name,
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

$error = '';

/* ---------- フォーム処理 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'back') {
        header('Location: ../index.php');
        exit;
    }

    if ($_POST['action'] === 'submit') {

        $match_number = trim($_POST['match_number']);

        if ($match_number === '') {
            $error = '試合番号を入力してください';
        } else {

            // 重複チェック(departmentカラムを使用)
            $sql = "
                SELECT COUNT(*)
                FROM individual_matches
                WHERE department_id = :department_id
                  AND department = :match_number
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':department_id' => $division_id,
                ':match_number' => $match_number
            ]);

            if ($stmt->fetchColumn() > 0) {
                $error = 'この試合番号はすでに登録されています';
            } else {
                // セッションに保持
                $_SESSION['division_id'] = $division_id;
                $_SESSION['match_number'] = $match_number;

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

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: clamp(20px, 5vw, 40px);
        }

        .container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            gap: clamp(15px, 3vw, 40px);
            font-size: clamp(18px, 3.5vw, 28px);
            font-weight: bold;
            margin-bottom: clamp(40px, 8vh, 80px);
            align-items: center;
        }

        .header span {
            white-space: nowrap;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
        }

        h2 {
            font-size: clamp(24px, 5vw, 32px);
            margin-bottom: clamp(30px, 5vh, 50px);
            text-align: center;
            line-height: 1.4;
        }

        .input-wrapper {
            width: 100%;
            max-width: 500px;
            margin-bottom: clamp(30px, 5vh, 50px);
        }

        input[type="text"] {
            width: 100%;
            padding: clamp(15px, 3vw, 20px) clamp(20px, 4vw, 30px);
            font-size: clamp(18px, 3vw, 20px);
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
            gap: clamp(30px, 6vw, 60px);
            justify-content: center;
            margin-top: clamp(20px, 4vh, 40px);
            flex-wrap: wrap;
        }

        button {
            padding: clamp(12px, 2vh, 15px) clamp(40px, 8vw, 70px);
            font-size: clamp(18px, 3vw, 22px);
            border: 2px solid #000;
            border-radius: 50px;
            background-color: #fff;
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
        }

        button:hover {
            background-color: #f0f0f0;
        }

        button:active {
            transform: scale(0.98);
        }

        .error {
            color: #d32f2f;
            margin-top: 10px;
            font-size: clamp(14px, 2.5vw, 16px);
            text-align: center;
        }

        /* タブレット以下 */
        @media (max-width: 768px) {
            .header {
                font-size: clamp(16px, 4vw, 20px);
                gap: 10px 20px;
            }

            h2 {
                font-size: clamp(20px, 5vw, 28px);
            }

            .button-group {
                width: 100%;
                max-width: 400px;
            }

            button {
                flex: 1;
                min-width: 120px;
            }
        }

        /* スマートフォン */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .header {
                gap: 8px 15px;
                margin-bottom: 30px;
            }

            h2 {
                margin-bottom: 25px;
            }

            .input-wrapper {
                margin-bottom: 25px;
            }

            .button-group {
                gap: 15px;
            }

            button {
                padding: 12px 30px;
            }
        }

        /* 極小スマートフォン */
        @media (max-width: 360px) {
            .button-group {
                flex-direction: column;
                width: 100%;
            }

            button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <span><?php echo ((int)$info['distinction'] === 2) ? '個人戦' : '団体戦'; ?></span>
        <span><?php echo htmlspecialchars($info['tournament_name']); ?></span>
        <span><?php echo htmlspecialchars($info['division_name']); ?></span>
    </div>

    <div class="main-content">
        <h2>試合番号を入力してください</h2>

        <form method="POST">
            <div class="input-wrapper">
                <input type="text" name="match_number" placeholder="試合番号" value="<?php echo htmlspecialchars($_POST['match_number'] ?? ''); ?>" autofocus>
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