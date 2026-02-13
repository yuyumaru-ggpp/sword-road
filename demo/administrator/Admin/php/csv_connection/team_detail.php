<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

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
                                <?php
                                // 安全に扱う：配列でない場合はスキップ
                                if (!is_array($m)) continue;

                                // order_detail を整数で取得（存在しなければ 0 とみなす）
                                $od = isset($m['order_detail']) ? (int)$m['order_detail'] : 0;

                                // 0 は補員扱いにする（運用に合わせて変更可）
                                // roles 配列に存在しなければ補員または「未設定」を表示
                                if ($od === 0) {
                                    $roleLabel = $roles[6] ?? '補員';
                                } else {
                                    $roleLabel = $roles[$od] ?? '選手';
                                }

                                $playerName = htmlspecialchars($m['name'] ?? '', ENT_QUOTES, 'UTF-8');
                                $playerFurigana = htmlspecialchars($m['furigana'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td><?= $roleLabel ?></td>
                                    <td><?= $playerName ?></td>
                                    <td><?= $playerFurigana ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

        </div>

        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <button class="btn-destructive"
                onclick="#">
                チームを削除
            </button>

            <button class="btn-back" onclick="history.back()">戻る</button>
        </div>

    </div>

</body>

</html>