<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

$tournament_id = $_REQUEST['id'] ?? null;
$department_id = $_REQUEST['dept'] ?? null;
$player_id = $_REQUEST['player'] ?? null;
$team_id = $_REQUEST['team'] ?? null;

if (!$player_id) {
    die("選手IDが指定されていません");
}

$message = "";

// POST: 名前・フリガナ保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $furigana = trim($_POST['furigana'] ?? '');

    $sql = "UPDATE players SET name = :name, furigana = :furigana WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':furigana' => $furigana === '' ? null : $furigana,
        ':id' => (int)$player_id
    ]);

    $message = "選手情報を保存しました。";

    // 保存後はチーム編集画面に戻す（元のチームに戻る）
    $redirect = 'team-edit.php?team=' . urlencode($team_id ?? '') . '&id=' . urlencode($tournament_id ?? '') . '&dept=' . urlencode($department_id ?? '');
    header("Location: {$redirect}");
    exit;
}

// 表示用データ取得
$sql = "SELECT id, name, furigana, player_number, team_id FROM players WHERE id = :id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => (int)$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) die("選手が見つかりませんでした");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>選手編集</title>
<link rel="stylesheet" href="../../css/player_change/player-edit.css"> <!-- 任意のCSS -->
<style>
/* 最低限のスタイル（必要なら外部CSSに移す） */
.container{max-width:720px;margin:28px auto;padding:18px;background:#fff;border:1px solid #e6e9ee;border-radius:10px}
h1{margin:0 0 12px 0}
.form-row{display:flex;flex-direction:column;gap:6px;margin-bottom:12px}
.label{font-weight:700;color:#374151}
.input{padding:10px;border:1px solid #d1d5db;border-radius:8px}
.button-row{display:flex;gap:10px;margin-top:12px}
.btn{padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
.btn.primary{background:#0b74de;color:#fff}
.btn.secondary{background:#fff;border:1px solid #e6e9ee}
.message{margin-bottom:12px;padding:10px;border-radius:8px;background:rgba(46,125,50,0.06);color:#2e7d32}
</style>
</head>
<body>
  <div class="container">
    <h1>選手編集</h1>

    <?php if ($message): ?>
      <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row">
        <label class="label">選手番号</label>
        <div class="input" style="background:#f8fafc;"><?= htmlspecialchars($player['player_number']) ?></div>
      </div>

      <div class="form-row">
        <label class="label">選手名</label>
        <input type="text" name="name" class="input" value="<?= htmlspecialchars($player['name']) ?>" required>
      </div>

      <div class="form-row">
        <label class="label">フリガナ</label>
        <input type="text" name="furigana" class="input" value="<?= htmlspecialchars($player['furigana']) ?>">
      </div>

      <div class="button-row">
        <button type="submit" class="btn primary">保存して戻る</button>
        <button type="button" class="btn secondary" onclick="location.href='team-edit.php?team=<?= urlencode($team_id ?? $player['team_id']) ?>&id=<?= urlencode($tournament_id ?? '') ?>&dept=<?= urlencode($department_id ?? '') ?>'">戻る</button>
      </div>
    </form>
  </div>
</body>
</html>