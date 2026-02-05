<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}
// パラメータ取得
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;
$player_id = $_GET['player'] ?? null;

if (!$tournament_id || !$department_id || !$player_id) {
    die("必要なパラメータが指定されていません");
}

$message = "";

// 更新処理（POST）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST 側の player_id があればそれを優先
    $post_player_id = $_POST['player_id'] ?? null;
    if ($post_player_id) {
        $player_id = $post_player_id;
    }

    // 修正処理
    if (isset($_POST['update'])) {
        $name = trim($_POST['name'] ?? '');
        $furigana = trim($_POST['furigana'] ?? '');

        $sql = "UPDATE players SET name = :name, furigana = :furigana WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':furigana', $furigana, PDO::PARAM_STR);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();

        $message = "選手情報を修正しました。";
    }

    // 棄権（チームにフラグを立てる）
    if (isset($_POST['withdraw'])) {
        // 選手情報（名前と所属チーム）を取得
        $sql = "SELECT name, team_id FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();
        $playerRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($playerRow && !empty($playerRow['team_id'])) {
            $sql = "UPDATE teams SET withdraw_flg = 1 WHERE id = :tid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':tid', $playerRow['team_id'], PDO::PARAM_INT);
            $stmt->execute();

            $playerName = $playerRow['name'] ?? '';
            $message = htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8') . " 選手の所属チームを棄権にしました。";
        } else {
            $message = "所属チームが見つかりませんでした。";
        }
    }

    // 棄権解除（チームのフラグを戻す）
    if (isset($_POST['unwithdraw'])) {
        // 選手情報（名前と所属チーム）を取得
        $sql = "SELECT name, team_id FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();
        $playerRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($playerRow && !empty($playerRow['team_id'])) {
            $sql = "UPDATE teams SET withdraw_flg = 0 WHERE id = :tid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':tid', $playerRow['team_id'], PDO::PARAM_INT);
            $stmt->execute();

            $playerName = $playerRow['name'] ?? '';
            $message = htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8') . " 選手の所属チームの棄権を解除しました。";
        } else {
            $message = "所属チームが見つかりませんでした。";
        }
    }
}

// 選手情報取得（表示用）
$sql = "
    SELECT p.id, p.name, p.furigana, p.player_number, p.team_id, t.name AS team_name, t.withdraw_flg AS team_withdraw
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    WHERE p.id = :pid
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':pid', $player_id, PDO::PARAM_INT);
$stmt->execute();
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    die("選手が見つかりませんでした");
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手編集</title>
    <link rel="stylesheet" href="../../css/player_change/individual-edit.css">
</head>

<body>
    <div class="breadcrumb">
        <a href="../tournament-detail.php?id=<?= $tournament_id ?>" class="breadcrumb-link">メニュー></a>
        <a href="player-category-select.php?id=<?= $tournament_id ?>" class="breadcrumb-link">選手変更></a>
        <a href="individual.php?id=<?= $tournament_id ?>&dept=<?= $department_id ?>" class="breadcrumb-link">個人戦></a>
        <a href="#" class="breadcrumb-link">選手編集</a>
    </div>

    <div class="container">
        <h1 class="title">選手編集</h1>

        <?php if ($message): ?>
            <p style="color:green;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div class="player-meta">
            <p><strong>選手番号</strong> <?= htmlspecialchars($player['player_number']) ?></p>
            <p><strong>所属チーム</strong> <?= htmlspecialchars($player['team_name'] ?? 'なし') ?>
                <?php if (!empty($player['team_withdraw']) && $player['team_withdraw'] == 1): ?>
                    <span style="color:red;">（棄権）</span>
                <?php endif; ?>
            </p>
        </div>

        <form method="POST">
            <input type="hidden" name="player_id" value="<?= htmlspecialchars($player['id']) ?>">

            <div class="player-info">
                <label class="player-label">選手名</label>
                <input type="text" name="name" class="player-input" value="<?= htmlspecialchars($player['name']) ?>" required>
            </div>

            <div class="player-info">
                <label class="player-label">フリガナ</label>
                <input type="text" name="furigana" class="player-input" value="<?= htmlspecialchars($player['furigana']) ?>">
            </div>

            <div class="button-container">
                <?php if (!empty($player['team_withdraw']) && $player['team_withdraw'] == 1): ?>
                    <!-- 棄権フラグが立っている → 棄権解除ボタン -->
                    <button type="submit" name="unwithdraw" class="action-button"
                        onclick="return confirm('この選手の所属チームの棄権を解除します。よろしいですか？')">棄権解除</button>
                <?php else: ?>
                    <!-- 棄権フラグが立っていない → 棄権ボタン -->
                    <button type="submit" name="withdraw" class="action-button"
                        onclick="return confirm('この選手の所属チームを棄権扱いにします。よろしいですか？')">棄権</button>
                <?php endif; ?>

                <button type="submit" name="update" class="action-button">修正</button>
                <button type="button" class="action-button" onclick="location.href='individual.php?id=<?= htmlspecialchars($tournament_id) ?>&dept=<?= htmlspecialchars($department_id) ?>'">戻る</button>
            </div>
        </form>
    </div>
</body>

</html>