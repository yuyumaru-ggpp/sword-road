<?php
session_start();

/* ---------- ログインチェック ---------- */
if (!isset($_SESSION['tournament_id'])) {
    header("Location: ./login/login.php");
    exit;
}

$tournament_id = $_SESSION['tournament_id'];

/* ---------- DB接続 ---------- */
$user = "root";
$pass = "root1234";
$database = "kendo_support_system";
$server = "localhost";
$port = "3307";

$dsn = "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    exit("DB接続失敗：" . $e->getMessage());
}

/* ---------- 部門取得 ---------- */
$sql = "
    SELECT
        id,
        name,
        distinction
    FROM
        departments
    WHERE
        tournament_id = :tournament_id
        AND del_flg = 0
    ORDER BY
        distinction, id
";


$stmt = $pdo->prepare($sql);
$stmt->execute([':tournament_id' => $tournament_id]);
$divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- 個人戦・団体戦に分ける ---------- */
$individual_divisions = [];
$team_divisions = [];

foreach ($divisions as $division) {
    if ((int)$division['distinction'] === 2) {
    $individual_divisions[] = $division;  // ✅ 2は個人戦
} else if ((int)$division['distinction'] === 1) {
    $team_divisions[] = $division;        // ✅ 1は団体戦
}
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>部門選択</title>
<link rel="stylesheet" href="./css/style.css">
</head>

<body>
<main>
<div class="contents">

    <!-- 個人戦 -->
    <div class="individual">
        <h2>個人戦</h2>
        <ul>
            <?php foreach ($individual_divisions as $division): ?>
                <li>
                    <a href="./solo/match_input.php?division_id=<?php echo $division['id']; ?>">
                        <?php echo htmlspecialchars($division['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- 団体戦 -->
    <div class="team">
        <h2>団体戦</h2>
        <ul>
            <?php foreach ($team_divisions as $division): ?>
                <li>
                    <a href="./Team/match_input.php?division_id=<?php echo $division['id']; ?>">
                        <?php echo htmlspecialchars($division['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="demo">
        <a href="./demo/demo.php">練習</a>
    </div>

</div>
</main>
</body>
</html>
