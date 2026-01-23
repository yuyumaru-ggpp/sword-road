<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../../db_connect.php';

$team_id = $_GET['team'] ?? null;
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$team_id || !$tournament_id || !$department_id) {
    die("必要な情報が不足しています");
}

// チーム情報取得
$sql = "SELECT name, abbreviation, team_number 
        FROM teams 
        WHERE id = :team";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':team', $team_id);
$stmt->execute();
$team = $stmt->fetch();

if (!$team) {
    die("チームが存在しません");
}

// メンバー一覧取得（order_detail順）
$sql = "
    SELECT 
        o.order_detail,
        p.name,
        p.furigana
    FROM orders o
    JOIN players p ON o.player_id = p.id
    WHERE o.team_id = :team
    ORDER BY o.order_detail ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':team', $team_id);
$stmt->execute();
$members = $stmt->fetchAll();

// 役割名
$roles = [
    1 => "先鋒",
    2 => "次鋒",
    3 => "中堅",
    4 => "副将",
    5 => "大将",
    6 => "補員"
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>チーム詳細</title>
    <link rel="stylesheet" href="../../css/csv_connection/csv_view.css">
</head>

<body>

    <div class="container">
        <div class="card">

            <div class="card-header">
                <div class="header-content">
                    <h1 class="card-title">
                        <?= htmlspecialchars($team['name']) ?>（チーム番号：<?= str_pad($team['team_number'], 3, "0", STR_PAD_LEFT) ?>）
                    </h1>
                    <p class="card-description">略称：<?= htmlspecialchars($team['abbreviation'] ?: "－") ?></p>
                </div>
            </div>

            <div class="card-content">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>役割</th>
                                <th>選手名</th>
                                <th>フリガナ</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <tr>
                                    <td><?= $roles[$m['order_detail']] ?></td>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                    <td><?= htmlspecialchars($m['furigana']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

        </div>

        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <button class="btn-destructive"
                onclick="if(confirm('このチームを削除しますか？')) location.href='delete_team.php?team=<?= $team_id ?>&id=<?= $tournament_id ?>&dept=<?= $department_id ?>'">
                チームを削除
            </button>

            <button class="btn-back" onclick="history.back()">戻る</button>
        </div>

    </div>

</body>

</html>