<!-- match_input.php -->
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

            // 重複チェック（departmentカラムを使用）
            $sql = "
                SELECT COUNT(*)
                FROM individual_matches
                WHERE department_id = :department_id
                  AND department = :match_number
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
            margin-bottom: 30px;
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
            padding: 20px 30px;
            font-size: 20px;
            text-align: center;
            border: 2px solid #999;
            border-radius: 50px;
            outline: none;
            background-color: #f5f5f5;
        }

        input[type="text"]:focus, select:focus {
            border-color: #666;
        }

        input[type="text"]::placeholder {
            color: #999;
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
            text-align: center;
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
        <h2>試合情報を入力してください</h2>

        <form method="POST">
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