<?php
// index.php - 保護者向け 大会一覧（あなたの既存構成に動的一覧を統合）
session_start();
require_once 'connect/db_connect.php';

// ページパラメータ
$perPage = 12;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $perPage;
$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// SQL 構築（is_locked は保護者表示に関係ないため無視）
$params = [];
$where = "WHERE 1=1";
if ($keyword !== '') {
    $where .= " AND (title LIKE :kw OR CAST(event_date AS CHAR) LIKE :kw OR venue LIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}

try {
    // 件数取得
    $countSql = "SELECT COUNT(*) FROM tournaments {$where}";
    $stmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    // データ取得
    $sql = "SELECT id, title, venue, event_date, match_field, created_at FROM tournaments {$where} ORDER BY event_date DESC, id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $tournaments = [];
    $total = 0;
    $totalPages = 1;
    $errorMessage = '大会一覧の取得に失敗しました。';
}
$menuClass = (isset($_SESSION['admin_user']) && $_SESSION['admin_user'] === true) ? 'menu-links open' : 'menu-links';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>大会一覧</title>
    <link rel="stylesheet" href="./index_css/style.css">
    <style>
        /* 最低限の補正（既存 CSS がある場合は不要） */
        .tournament-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .tournament-item {
            display: block;
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .tournament-item h3 {
            margin: 0 0 6px 0;
            font-size: 1rem;
        }

        .tournament-item p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
            margin: 14px 0;
        }

        .pagination a,
        .pagination button {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .notice {
            color: #b45309;
            background: #fff7ed;
            padding: 8px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <!-- ヘッダーに佐藤様から提示された説明文を追加予定 -->
    <header>
        <div class="menu-icon" onclick="toggleMenu()">☰</div>
    </header>

    <!-- HTML -->
    <div class="menu-links" id="menuLinks">
        <a href="./administrator/master.php">管理者用ログイン画面</a>
        <a href="./Assistant/login.php">入力補助員用ログイン画面</a>
    </div>

    <!-- CSS（外部または head 内） -->
    <style>
        .menu-links {
            display: none;
            flex-direction: column;
            /* 既存スタイル */
        }

        .menu-links.open {
            display: flex;
        }
    </style>

    <!-- JS -->
    <script>
        function toggleMenu() {
            const menu = document.getElementById('menuLinks');
            menu.classList.toggle('open');
        }
    </script>

    <div class="title">
        <h1>大会一覧</h1>
    </div>

    <div class="main-container">
        <?php if (!empty($errorMessage)): ?>
            <div class="notice"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="get" style="display:flex;gap:8px;width:100%;">
                <input type="text" name="q" placeholder="大会名や開催日で検索" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" style="flex:1;padding:8px;border-radius:6px;border:1px solid #ddd;" />
                <button type="submit" style="padding:8px 12px;border-radius:6px;background:#2b7be4;color:#fff;border:0;">検索</button>
            </form>
        </div>

        <div class="tournament-list">
            <?php if (empty($tournaments)): ?>
                <div style="grid-column:1/-1;color:#666;padding:12px">大会が見つかりません。</div>
            <?php else: ?>
                <?php foreach ($tournaments as $t):
                    $url = './User/tournament-department.php?id=' . urlencode($t['id']); // 詳細ページへのリンク
                    $title = htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8');
                    $date = htmlspecialchars(substr($t['event_date'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8');
                    $venue = htmlspecialchars($t['venue'] ?? '', ENT_QUOTES, 'UTF-8');
                    $match_field = htmlspecialchars((string)($t['match_field'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                    <a class="tournament-item" href="<?= $url ?>" target="_blank" rel="noopener noreferrer">
                        <h3><?= $title ?></h3>
                        <p>開催日: <?= $date ?: '未定' ?></p>
                        <div style="margin-top:8px;font-size:0.9rem;color:#666;">
                            <?php if ($venue): ?><span>会場: <?= $venue ?></span><?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="pagination">
            <?php
            $prevP = max(1, $page - 1);
            $nextP = min($totalPages, $page + 1);
            $baseQuery = [];
            if ($keyword !== '') $baseQuery['q'] = $keyword;
            ?>
            <a href="?<?= http_build_query(array_merge($baseQuery, ['p' => $prevP])) ?>" class="pagination-btn" aria-label="前のページ">← 戻る</a>
            <div id="pageInfo" style="min-width:160px;text-align:center;color:#666">
                <?= htmlspecialchars((string)$total, ENT_QUOTES, 'UTF-8') ?> 件 / <?= htmlspecialchars((string)$page, ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string)$totalPages, ENT_QUOTES, 'UTF-8') ?> ページ
            </div>
            <a href="?<?= http_build_query(array_merge($baseQuery, ['p' => $nextP])) ?>" class="pagination-btn" aria-label="次のページ">次へ →</a>
        </div>

        <footer>
            <div class="school-name">MCL盛岡情報ビジネス＆デザイン専門学校</div>
        </footer>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('menuLinks');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }
    </script>
</body>

</html>