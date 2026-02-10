<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}
// 大会ID取得
$tournament_id = filter_input(INPUT_GET, 'tournament_id', FILTER_VALIDATE_INT);
if (!$tournament_id) {
  header("Location: Admin_selection.php");
  exit;
}

// --- 追加: 既に登録されている部門を取得 ---
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlExist = "SELECT name, distinction FROM departments WHERE tournament_id = :tournament_id AND del_flg = 0";
    $stmtExist = $pdo->prepare($sqlExist);
    $stmtExist->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
    $stmtExist->execute();
    $existingRows = $stmtExist->fetchAll(PDO::FETCH_ASSOC);

    // 正規化してキー化（trimしてそのまま結合）
    $existingMap = [];
    foreach ($existingRows as $r) {
        $key = trim($r['name']) . '|' . (string)$r['distinction'];
        $existingMap[$key] = true;
    }
} catch (PDOException $e) {
    error_log("Failed to fetch existing departments: " . $e->getMessage());
    $existingMap = []; // エラー時は空にして続行
}
// --- ここまで追加 ---

// POST処理（登録） - 安全版
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!empty($_POST['divisions']) && is_array($_POST['divisions'])) {

    $inserted = 0;
    $pdo->beginTransaction();
    try {
      $sql = "INSERT INTO departments (tournament_id, name, distinction, created_at, update_at)
        VALUES (:tournament_id, :name, :distinction, NOW(), NOW())";
      $stmt = $pdo->prepare($sql);

      foreach ($_POST['divisions'] as $value) {
        // skip empty values
        if (!is_string($value) || trim($value) === '') continue;

        // explode with limit 2 and validate
        $parts = explode('|', $value, 2);
        if (count($parts) < 2) {
          // malformed value: skip or collect error
          continue;
        }
        $name = trim($parts[0]);
        $distinction = (int)trim($parts[1]); // cast to int for safety

        if ($name === '') continue; // skip empty name

        // **重複登録防止（サーバ側）**
        $checkKey = $name . '|' . (string)$distinction;
        if (isset($existingMap[$checkKey])) {
            // 既に存在するならスキップ（またはエラー扱いにする）
            continue;
        }

        // bind and execute
        $stmt->bindValue(':tournament_id', (int)$tournament_id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':distinction', $distinction, PDO::PARAM_INT);
        $stmt->execute();
        $inserted++;

        // 追加したものを既存マップに追加して二重登録を防ぐ（同一リクエスト内）
        $existingMap[$checkKey] = true;
      }

      $pdo->commit();

      if ($inserted > 0) {
        header("Location: tournament-detail.php?id=" . $tournament_id);
        exit;
      } else {
        $error = "有効な部門が選択されていません。";
      }
    } catch (PDOException $e) {
      $pdo->rollBack();
      // 開発用：詳細をログに残す
      error_log("departments insert failed: " . $e->getMessage());
      // 本番では詳細を出さないほうが安全
      $error = "登録中にエラーが発生しました。";
    }
  } else {
    $error = "部門が選択されていません";
  }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>部門登録</title>
  <link rel="stylesheet" href="../../css/Admin_registration_selection_create.css">
</head>

<body>
  <div class="container">

    <!-- パンくず -->
    <nav class="breadcrumb">
      <a href="Admin_top.php">メニュー</a>
      <span>＞</span>
      <a href="Admin_selection.php">大会一覧</a>
      <span>＞</span>
      <a href="tournament-detail.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>">大会詳細</a>
      <span>＞</span>
      <span>部門登録</span>
    </nav>

    <div class="card">
      <h1 class="page-title">部門登録</h1>

      <?php if (!empty($error)): ?>
        <p style="color:red; text-align:center;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <form method="POST">

        <div class="checkbox-grid">

          <!-- 個人戦 -->
          <div class="checkbox-group">
            <h3>個人戦</h3>
            <div class="checkbox-list">

              <?php
                // ここで既存チェックの挙動を切り替え可能
                // true = 既存はチェック済みかつ選択不可（disabled）
                // false = 既存はチェック済みだが選択可能
                $disableRegistered = true;
              ?>

              <?php
                $items = [
                  ['id'=>'individual-1','label'=>'小学生4年以下個人','value'=>'小学生4年以下個人|2'],
                  ['id'=>'individual-2','label'=>'小学生5年以上個人','value'=>'小学生5年以上個人|2'],
                  ['id'=>'individual-3','label'=>'中学生男子個人','value'=>'中学生男子個人|2'],
                  ['id'=>'individual-4','label'=>'中学生女子個人','value'=>'中学生女子個人|2'],
                ];
                foreach ($items as $it) {
                  $val = $it['value'];
                  $isRegistered = isset($existingMap[trim($val)]);
                  $checked = $isRegistered ? 'checked' : '';
                  $disabled = ($isRegistered && $disableRegistered) ? 'disabled' : '';
              ?>
                <div class="checkbox-item">
                  <input type="checkbox"
                         name="divisions[]"
                         id="<?= htmlspecialchars($it['id'], ENT_QUOTES, 'UTF-8') ?>"
                         value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                         <?= $checked ?> <?= $disabled ?>>
                  <label for="<?= htmlspecialchars($it['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') ?></label>
                </div>
              <?php } ?>

            </div>
          </div>

          <!-- 団体戦 -->
          <div class="checkbox-group">
            <h3>団体戦</h3>
            <div class="checkbox-list">

              <?php
                $items = [
                  ['id'=>'team-1','label'=>'小学生団体','value'=>'小学生団体|1'],
                  ['id'=>'team-2','label'=>'中学生男子団体','value'=>'中学生男子団体|1'],
                  ['id'=>'team-3','label'=>'中学生女子団体','value'=>'中学生女子団体|1'],
                ];
                foreach ($items as $it) {
                  $val = $it['value'];
                  $isRegistered = isset($existingMap[trim($val)]);
                  $checked = $isRegistered ? 'checked' : '';
                  $disabled = ($isRegistered && $disableRegistered) ? 'disabled' : '';
              ?>
                <div class="checkbox-item">
                  <input type="checkbox"
                         name="divisions[]"
                         id="<?= htmlspecialchars($it['id'], ENT_QUOTES, 'UTF-8') ?>"
                         value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                         <?= $checked ?> <?= $disabled ?>>
                  <label for="<?= htmlspecialchars($it['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') ?></label>
                </div>
              <?php } ?>

            </div>
          </div>

        </div>

        <p class="note">ここにない場合は部門を作成してください</p>

        <div class="button-group">
          <a href="tournament-detail.php?id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">戻る</a>
          <a href="division-create.php?tournament_id=<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline">部門作成</a>
          <button type="submit" class="btn btn-primary">登録</button>
        </div>

      </form>

    </div>
  </div>
</body>

</html>