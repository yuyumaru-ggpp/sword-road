<?php
session_start();
require_once '../../db_connect.php';

// 管理者チェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// CSRFトークン生成（なければ作成）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// パラメータ検証
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tournament_id = filter_input(INPUT_GET, 'tournament_id', FILTER_VALIDATE_INT);
if (!$id || !$tournament_id) {
    header("Location: Admin_selection.php");
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 部門取得
    $sql = "SELECT id, tournament_id, name, distinction, created_at, update_at
            FROM departments
            WHERE id = :id AND tournament_id = :tournament_id AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
    $stmt->execute();
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dept) {
        http_response_code(404);
        echo "部門が見つかりません";
        exit;
    }

} catch (PDOException $e) {
    error_log("DB error (fetch dept): " . $e->getMessage());
    echo "データベースエラーが発生しました。";
    exit;
}

$error = '';
$success = '';

// POST処理（更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRFチェック
    $posted_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $posted_csrf)) {
        $error = '不正なリクエストです。';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        // distinction: '' -> 未設定 (NULL), '1' -> 団体戦, '2' -> 個人戦
        $raw_dist = isset($_POST['distinction']) ? (string)$_POST['distinction'] : '';

        if ($name === '') {
            $error = "部門名を入力してください";
        } elseif (!in_array($raw_dist, ['', '1', '2'], true)) {
            $error = "種別の値が不正です";
        } else {
            try {
                // 同名チェック（同大会内、現在のIDを除外）
                $sqlCheck = "SELECT COUNT(*) FROM departments
                             WHERE tournament_id = :tournament_id
                               AND LOWER(name) = LOWER(:name)
                               AND id != :id
                               AND del_flg = 0";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);
                $stmtCheck->bindValue(':name', $name, PDO::PARAM_STR);
                $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
                $stmtCheck->execute();
                $count = (int)$stmtCheck->fetchColumn();

                if ($count > 0) {
                    $error = "同じ名前の部門が既に存在します";
                } else {
                    // distinction を NULL または 1/2 にする
                    $distinction = ($raw_dist === '') ? null : (int)$raw_dist;

                    $sqlUpd = "UPDATE departments
                               SET name = :name,
                                   distinction = :distinction,
                                   update_at = NOW()
                               WHERE id = :id AND tournament_id = :tournament_id";
                    $stmtUpd = $pdo->prepare($sqlUpd);
                    $stmtUpd->bindValue(':name', $name, PDO::PARAM_STR);
                    if ($distinction === null) {
                        $stmtUpd->bindValue(':distinction', null, PDO::PARAM_NULL);
                    } else {
                        $stmtUpd->bindValue(':distinction', $distinction, PDO::PARAM_INT);
                    }
                    $stmtUpd->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmtUpd->bindValue(':tournament_id', $tournament_id, PDO::PARAM_INT);

                    $stmtUpd->execute();

                    // 更新が反映されたか確認（任意）
                    // $updatedRows = $stmtUpd->rowCount();

                    $success = "部門を更新しました";

                    // 最新データを再取得
                    $stmt->execute();
                    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                error_log("DB error (update dept): " . $e->getMessage());
                $error = "更新中にエラーが発生しました。";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>部門編集</title>
  <link rel="stylesheet" href="../css/Admin_registration_namechange.css">
  <style>
    .container { max-width:720px; margin:3rem auto; padding:1rem; }
    .breadcrumb { margin-bottom:1rem; }
    .card { background:#fff; padding:1.5rem; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
    .input-label { display:block; margin-bottom:0.5rem; text-align:left; }
    .name-input { width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:6px; }
    .button-container { display:flex; gap:1rem; justify-content:center; margin-top:1rem; }
    .btn { padding:0.6rem 1.2rem; border-radius:6px; border:1px solid #d1d5db; background:#fff; text-decoration:none; color:#111; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .error { color:#b91c1c; text-align:center; margin-bottom:0.75rem; }
    .success { color:#065f46; text-align:center; margin-bottom:0.75rem; }
    .radio-group label { display:inline-flex; align-items:center; gap:0.4rem; }
    .danger-link { color:#b91c1c; text-decoration:none; font-weight:600; }
  </style>
</head>
<body>
  <div class="container">
    <nav class="breadcrumb">
      <a href="Admin_top.php">メニュー</a> ＞
      <a href="Admin_selection.php">大会一覧</a> ＞
      <a href="tournament-detail.php?id=<?= urlencode($tournament_id) ?>">大会詳細</a> ＞
      <a href="division-register.php?tournament_id=<?= urlencode($tournament_id) ?>">部門選択</a> ＞
      <span>部門編集</span>
    </nav>

    <div class="card">
      <h1 style="text-align:center;">部門編集</h1>

      <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($success): ?><div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

        <label class="input-label">種別</label>
        <div class="radio-group" style="display:flex; gap:1.5rem; margin-bottom:1rem;">
          <?php
            // 現在の値を判定（NULL -> 未設定）
            $currentDist = array_key_exists('distinction', $dept) && $dept['distinction'] !== null ? (string)$dept['distinction'] : '';
          ?>
          <label>
            <input type="radio" name="distinction" value="" <?= ($currentDist === '') ? 'checked' : '' ?>> 未設定
          </label>
          <label>
            <input type="radio" name="distinction" value="1" <?= ($currentDist === '1') ? 'checked' : '' ?>> 団体戦
          </label>
          <label>
            <input type="radio" name="distinction" value="2" <?= ($currentDist === '2') ? 'checked' : '' ?>> 個人戦
          </label>
        </div>

        <label for="name" class="input-label">部門名</label>
        <input id="name" name="name" class="name-input" type="text" value="<?= htmlspecialchars($dept['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <div class="button-container">
          <a class="btn" href="tournament-detail.php?id=<?= urlencode($tournament_id) ?>">戻る</a>
          <button type="submit" class="btn btn-primary">更新</button>
        </div>
      </form>

      <div style="text-align:center; margin-top:1rem;">
        <form method="post" action="division-delete.php" onsubmit="return confirm('この部門を削除しますか？ この操作は取り消せません。');" style="display:inline;">
          <input type="hidden" name="id" value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="tournament_id" value="<?= htmlspecialchars($tournament_id, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="danger-link" style="background:none;border:none;padding:0;cursor:pointer;">この部門を削除する</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>