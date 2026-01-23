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
$pass = "";
$database = "kendo_support_system";
$server = "localhost";
$port = "3308";

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
        $individual_divisions[] = $division;  // 2は個人戦
    } else if ((int)$division['distinction'] === 1) {
        $team_divisions[] = $division;        // 1は団体戦
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>部門選択</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: clamp(20px, 4vh, 40px);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: clamp(30px, 5vh, 50px);
    flex-wrap: wrap;
    gap: 20px;
}

.page-title {
    font-size: clamp(24px, 4vw, 36px);
    font-weight: bold;
    color: #1f2937;
}

.back-button {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #4b5563;
    font-size: clamp(14px, 2vw, 16px);
    transition: all 0.2s;
}

.back-button:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.divisions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: clamp(20px, 3vw, 30px);
    margin-bottom: clamp(30px, 5vh, 50px);
}

.division-section {
    background: white;
    border-radius: 12px;
    padding: clamp(20px, 3vh, 30px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.division-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: clamp(15px, 2vh, 25px);
    padding-bottom: 15px;
    border-bottom: 3px solid;
}

.individual .section-header {
    border-color: #3b82f6;
}

.team .section-header {
    border-color: #10b981;
}

.section-icon {
    font-size: clamp(24px, 4vw, 32px);
    font-weight: bold;
}

.individual .section-icon {
    color: #3b82f6;
}

.team .section-icon {
    color: #10b981;
}

.section-title {
    font-size: clamp(20px, 3vw, 26px);
    font-weight: bold;
    color: #1f2937;
}

.division-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.division-list li {
    width: 100%;
}

.division-link {
    display: block;
    padding: clamp(12px, 2vh, 16px) clamp(16px, 2vw, 20px);
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #1f2937;
    font-size: clamp(15px, 2.5vw, 18px);
    font-weight: 500;
    transition: all 0.2s;
    text-align: center;
}

.individual .division-link:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1e40af;
}

.team .division-link:hover {
    background: #ecfdf5;
    border-color: #10b981;
    color: #065f46;
}

.division-link:active {
    transform: scale(0.98);
}

.empty-state {
    padding: clamp(20px, 3vh, 30px);
    text-align: center;
    color: #9ca3af;
    font-size: clamp(14px, 2vw, 16px);
}

.demo-section {
    display: flex;
    justify-content: center;
    margin-top: clamp(30px, 5vh, 50px);
}

.demo-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: clamp(14px, 2vh, 18px) clamp(40px, 6vw, 60px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    font-size: clamp(16px, 2.5vw, 20px);
    font-weight: bold;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    transition: all 0.3s;
}

.demo-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.demo-link:active {
    transform: translateY(0);
}

/* タブレット以下 */
@media (max-width: 768px) {
    .divisions-grid {
        grid-template-columns: 1fr;
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* スマートフォン */
@media (max-width: 480px) {
    body {
        padding: 15px;
    }

    .division-section {
        padding: 20px 15px;
    }

    .back-button {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>

<body>
<div class="container">
    
    <div class="header">
        <h1 class="page-title">部門選択</h1>
        <a href="../Assistant/login.php" class="back-button">
            ← 前の画面に戻る
        </a>
    </div>

    <div class="divisions-grid">
        
        <!-- 個人戦 -->
        <div class="division-section individual">
            <div class="section-header">
                <span class="section-icon">👤</span>
                <h2 class="section-title">個人戦</h2>
            </div>
            <?php if (count($individual_divisions) > 0): ?>
                <ul class="division-list">
                    <?php foreach ($individual_divisions as $division): ?>
                        <li>
                            <a href="./solo/match_input.php?division_id=<?php echo $division['id']; ?>" class="division-link">
                                <?php echo htmlspecialchars($division['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">登録されている部門がありません</div>
            <?php endif; ?>
        </div>

        <!-- 団体戦 -->
        <div class="division-section team">
            <div class="section-header">
                <span class="section-icon">👥</span>
                <h2 class="section-title">団体戦</h2>
            </div>
            <?php if (count($team_divisions) > 0): ?>
                <ul class="division-list">
                    <?php foreach ($team_divisions as $division): ?>
                        <li>
                            <a href="./Team/match_input.php?division_id=<?php echo $division['id']; ?>" class="division-link">
                                <?php echo htmlspecialchars($division['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">登録されている部門がありません</div>
            <?php endif; ?>
        </div>

    </div>

    <!-- 練習モード -->
    <div class="demo-section">
        <a href="./demo/demo.php" class="demo-link">
            🎯 練習モード
        </a>
    </div>

</div>
</body>
</html>