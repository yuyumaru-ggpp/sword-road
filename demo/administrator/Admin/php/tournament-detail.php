<?php
session_start();
require_once '../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../login.php");
    exit;
}

// GETパラメータ確認
$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: Admin_selection.php");
    exit;
}

// 大会情報取得
try {
    $sql = "SELECT * FROM tournaments WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
    $stmt->execute();
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("tournament fetch failed: " . $e->getMessage());
    echo "データベースエラーが発生しました。";
    exit;
}

if (!$tournament) {
    echo "大会が見つかりません";
    exit;
}

// 日付を見やすく整形（例：2026-01-28 → 2026年1月28日）
$event_date = '';
if (!empty($tournament['event_date'])) {
    $ts = strtotime($tournament['event_date']);
    if ($ts !== false) $event_date = date("Y年n月j日", $ts);
}

// --- 部門一覧取得 ---
try {
    $sqlDeps = "SELECT id, name, distinction, created_at FROM departments
                WHERE tournament_id = :tournament_id AND del_flg = 0
                ORDER BY created_at ASC";
    $stmtDeps = $pdo->prepare($sqlDeps);
    $stmtDeps->bindValue(':tournament_id', (int)$id, PDO::PARAM_INT);
    $stmtDeps->execute();
    $departments = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("departments fetch failed: " . $e->getMessage());
    $departments = [];
}

// 定数（意味を明確に）
define('DIST_TEAM', 1);
define('DIST_INDIVIDUAL', 2);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tournament['title']) ?> - 詳細</title>
    <link rel="stylesheet" href="tournament-detail.css">

</head>
<body>
    <div class="container">

        <!-- パンくずリスト -->
        <nav class="breadcrumb">
            <a href="Admin_top.php">メニュー</a>
            <span class="separator">/</span>
            <a href="Admin_selection.php">大会一覧</a>
            <span class="separator">/</span>
            <span class="current"><?= htmlspecialchars($tournament['title']) ?></span>
        </nav>

        <!-- ヘッダー -->
        <header class="header">
            <h1 class="title"><?= htmlspecialchars($tournament['title']) ?></h1>
            <span class="badge">開催予定</span>
        </header>

        <!-- 大会情報カード -->
        <section class="info-card">
            <h2 class="section-title">大会情報</h2>
            <div class="info-grid">

                <!-- 開催日 -->
                <div class="info-item">
                    <div class="info-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">開催日</span>
                        <span class="info-value"><?= $event_date ?: '未設定' ?></span>
                    </div>
                </div>

                <!-- 会場 -->
                <div class="info-item">
                    <div class="info-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">会場</span>
                        <span class="info-value"><?= htmlspecialchars($tournament['venue'] ?? '未設定') ?></span>
                    </div>
                </div>

                <!-- 試合場数 -->
                <div class="info-item">
                    <div class="info-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                    <div class="info-content">
                        <span class="info-label">試合場数</span>
                        <span class="info-value"><?= htmlspecialchars($tournament['match_field'] ?? '-') ?>面</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- 部門一覧セクション -->
        <section class="departments-section" aria-labelledby="departments-title">
          <h2 id="departments-title">部門一覧</h2>

          <?php if (empty($departments)): ?>
            <p class="muted">まだ部門が登録されていません。<a href="division-create.php?tournament_id=<?= htmlspecialchars($id) ?>">部門を作成</a></p>
          <?php else: ?>
            <table class="dept-table" role="table" aria-label="登録済み部門一覧">
              <thead>
                <tr>
                  <th>部門名</th>
                  <th>種別</th>
                  <th>登録日時</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($departments as $d): ?>
                  <tr>
                    <td><?= htmlspecialchars($d['name']) ?></td>
                    <td>
                      <?php
                        $dist = (int)($d['distinction'] ?? 0);
                        if ($dist === DIST_INDIVIDUAL) {
                            echo '<span class="badge badge-individual">個人戦</span>';
                        } elseif ($dist === DIST_TEAM) {
                            echo '<span class="badge badge-team">団体戦</span>';
                        } else {
                            echo '<span class="badge badge-unknown">未設定</span>';
                        }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($d['created_at']) ?></td>
                    <td class="dept-actions">
                      <a href="division-edit.php?id=<?= urlencode($d['id']) ?>&tournament_id=<?= urlencode($id) ?>">編集</a>
                      <a href="division-delete.php?id=<?= urlencode($d['id']) ?>&tournament_id=<?= urlencode($id) ?>" class="danger" onclick="return confirm('この部門を削除しますか？')">削除</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </section>

        <!-- 管理メニュー -->
        <section class="menu-card">
            <h2 class="section-title">管理メニュー</h2>
            <div class="button-grid">

                <a href="tournament-edit-title.php?id=<?= urlencode($id) ?>" class="menu-button">
                    <span>名称変更</span>
                </a>

                <a href="tournament-edit-password.php?id=<?= urlencode($id) ?>" class="menu-button">
                    <span>パスワード変更</span>
                </a>

                <a href="division-register.php?tournament_id=<?= urlencode($id) ?>" class="menu-button">
                    <span>部門登録</span>
                </a>

                <a href="venue-edit.php?id=<?= urlencode($id) ?>" class="menu-button">
                    <span>会場・試合場編集</span>
                </a>

                <a href="event-date-edit.php?id=<?= urlencode($id) ?>" class="menu-button">
                    <span>開催日修正</span>
                </a>

            </div>
        </section>

        <!-- 戻るボタン -->
        <div class="back-section">
            <a href="Admin_selection.php" class="back-button">
                大会一覧に戻る
            </a>
        </div>

    </div>
</body>
</html>