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

    // 棄権（選手個人にフラグを立てる）
    if (isset($_POST['withdraw'])) {
        // 選手の substitute_flg を 1 にする（棄権扱い）
        $sql = "UPDATE players SET substitute_flg = 1 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();

        // 選手名を取得
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();
        $playerRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $playerName = $playerRow['name'] ?? '';

        $message = htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8') . " 選手を棄権にしました。";
    }

    // 棄権解除（選手個人のフラグを戻す）
    if (isset($_POST['unwithdraw'])) {
        // 選手の substitute_flg を 0 に戻す
        $sql = "UPDATE players SET substitute_flg = 0 WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();

        // 選手名を取得
        $sql = "SELECT name FROM players WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $player_id, PDO::PARAM_INT);
        $stmt->execute();
        $playerRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $playerName = $playerRow['name'] ?? '';

        $message = htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8') . " 選手の棄権を解除しました。";
    }
}

// 選手情報取得（表示用）
$sql = "
    SELECT p.id, p.name, p.furigana, p.player_number, p.team_id, p.substitute_flg, 
           t.name AS team_name, t.withdraw_flg AS team_withdraw
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
        <a href="../tournament_editor_menu.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="breadcrumb-link">メニュー ></a> 
        <a href="category_select.php?id=<?= $tournament_id ?>" class="breadcrumb-link">選手変更></a>
        <a href="individual.php?id=<?= $tournament_id ?>&dept=<?= $department_id ?>" class="breadcrumb-link">個人戦></a>
        <a href="#" class="breadcrumb-link">選手編集</a>
    </div>

    <div class="container">
        <h1 class="title">選手編集</h1>

        <?php if ($message): ?>
            <p style="color:green;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <div class="player-meta">
            <p><strong>選手番号</strong> <?= htmlspecialchars($player['player_number']) ?> <?php if (!empty($player['substitute_flg']) && $player['substitute_flg'] == 1): ?>
                    <span style="color:red; font-weight:bold;">棄権</span>
                <?php else: ?>
                    <span style="color:green;">出場</span>
                <?php endif; ?>
            </p>
            <p><strong>所属チーム</strong> <?= htmlspecialchars($player['team_name'] ?? 'なし') ?>
                <?php if (!empty($player['team_withdraw']) && $player['team_withdraw'] == 1): ?>
                    <span style="color:red;">（チーム棄権）</span>
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
                <?php if (!empty($player['substitute_flg']) && $player['substitute_flg'] == 1): ?>
                    <!-- 棄権フラグが立っている → 棄権解除ボタン -->
                    <button type="submit" name="unwithdraw" class="action-button"
                        onclick="return confirm('この選手の棄権を解除します。よろしいですか？')">棄権解除</button>
                <?php else: ?>
                    <!-- 棄権フラグが立っていない → 棄権ボタン -->
                    <button type="submit" name="withdraw" class="action-button"
                        onclick="return confirm('この選手を棄権扱いにします。よろしいですか？')">棄権</button>
                <?php endif; ?>

                <button type="submit" name="update" class="action-button">修正</button>
                <button type="button" class="action-button" onclick="location.href='individual.php?id=<?= htmlspecialchars($tournament_id) ?>&dept=<?= htmlspecialchars($department_id) ?>'">戻る</button>
            </div>
        </form>
    </div>
</body>

</html>