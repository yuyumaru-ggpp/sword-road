<?php
// index.php - 保護者向け 大会一覧（検索機能強化版）
session_start();
require_once 'connect/db_connect.php';

// ページパラメータ
$perPage = 10;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $perPage;

// 検索パラメータ
$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';
$venue = isset($_GET['venue']) ? trim((string)$_GET['venue']) : '';
$sortBy = isset($_GET['sort']) ? (string)$_GET['sort'] : 'date_desc'; // date_desc, date_asc, created_desc

// SQL 構築
$params = [];
$where = "WHERE 1=1";

// キーワード検索
if ($keyword !== '') {
    $where .= " AND (title LIKE :kw OR CAST(event_date AS CHAR) LIKE :kw OR venue LIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}

// 開催日範囲
if ($dateFrom !== '') {
    $where .= " AND event_date >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where .= " AND event_date <= :date_to";
    $params[':date_to'] = $dateTo;
}

// 会場フィルター
if ($venue !== '') {
    $where .= " AND venue LIKE :venue";
    $params[':venue'] = '%' . $venue . '%';
}

// ソート順
$orderBy = match($sortBy) {
    'date_asc' => 'ORDER BY event_date ASC, id ASC',
    'created_desc' => 'ORDER BY created_at DESC, id DESC',
    default => 'ORDER BY event_date DESC, id DESC',
};

try {
    // 件数取得
    $countSql = "SELECT COUNT(*) FROM tournaments {$where}";
    $stmt = $pdo->prepare($countSql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    // データ取得
    $sql = "SELECT id, title, venue, event_date, match_field, created_at FROM tournaments {$where} {$orderBy} LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 会場リスト取得（フィルター用）
    $venueStmt = $pdo->query("SELECT DISTINCT venue FROM tournaments WHERE venue IS NOT NULL AND venue != '' ORDER BY venue");
    $venues = $venueStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $tournaments = [];
    $venues = [];
    $total = 0;
    $totalPages = 1;
    $errorMessage = '大会一覧の取得に失敗しました。';
}

$menuClass = (isset($_SESSION['admin_user']) && $_SESSION['admin_user'] === true) ? 'menu-links open' : 'menu-links';

// ハイライト関数
function highlightKeyword($text, $keyword) {
    if ($keyword === '') return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $kwEscaped = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');
    return preg_replace('/(' . preg_quote($kwEscaped, '/') . ')/iu', '<mark>$1</mark>', $escaped);
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>大会一覧</title>
    <link rel="stylesheet" href="./style.css">
    <style>
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
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .tournament-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .search-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .search-field label {
            font-size: 0.85em;
            color: #555;
            font-weight: 500;
        }

        .search-field input,
        .search-field select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 0.9em;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #2b7be4;
            color: white;
        }

        .btn-primary:hover {
            background: #1e5bb8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .filter-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-tag .remove {
            cursor: pointer;
            font-weight: bold;
            color: #1976d2;
        }

        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .sort-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: white;
            font-size: 0.9em;
        }

        mark {
            background: #fff176;
            padding: 2px 4px;
            border-radius: 2px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 6px;
        }

        .badge-upcoming {
            background: #d1f4e0;
            color: #0d7d3e;
        }

        .badge-past {
            background: #e0e0e0;
            color: #616161;
        }

        .badge-today {
            background: #fff176;
            color: #f57c00;
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }

            .search-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .results-info {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="menu-icon" onclick="toggleMenu()">☰</div>
    </header>

    <div class="menu-links" id="menuLinks">
        <a href="./administrator/master.php">管理者用ログイン画面</a>
        <a href="./Assistant/login.php">入力補助員用ログイン画面</a>
    </div>

    <div class="title">
        <h1>大会一覧</h1>
    </div>

    <div class="main-container">
        <?php if (!empty($errorMessage)): ?>
            <div class="notice"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- 検索バー -->
        <div class="search-bar">
            <form method="get" id="searchForm">
                <div style="display:flex;gap:8px;width:100%;margin-bottom:10px;">
                    <input type="text" name="q" placeholder="大会名や開催日で検索" value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>" style="flex:1;padding:8px;border-radius:6px;border:1px solid #ddd;" />
                    <button type="submit" style="padding:8px 12px;border-radius:6px;background:#2b7be4;color:#fff;border:0;">検索</button>
                    <button type="button" onclick="toggleAdvancedSearch()" style="padding:8px 12px;border-radius:6px;background:#6c757d;color:#fff;border:0;white-space:nowrap;">詳細検索</button>
                </div>

                <!-- 詳細検索（折りたたみ） -->
                <div id="advancedSearch" style="display:none;background:#f8f9fa;padding:15px;border-radius:8px;margin-top:10px;">
                    <div class="search-grid">
                        <div class="search-field">
                            <label for="dateFrom">📅 開催日（開始）</label>
                            <input type="date" id="dateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="search-field">
                            <label for="venueFilter">📍 会場</label>
                            <select id="venueFilter" name="venue">
                                <option value="">すべて</option>
                                <?php foreach ($venues as $v): ?>
                                    <option value="<?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>" <?= $venue === $v ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:15px;">
                        <button type="button" class="btn btn-secondary" onclick="clearSearch()">クリア</button>
                        <button type="submit" class="btn btn-primary">🔍 検索</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- アクティブフィルター表示 -->
        <?php
        $activeFilters = [];
        if ($keyword !== '') $activeFilters[] = ['label' => "キーワード: {$keyword}", 'param' => 'q'];
        if ($dateFrom !== '') $activeFilters[] = ['label' => "開始日: {$dateFrom}", 'param' => 'date_from'];
        if ($dateTo !== '') $activeFilters[] = ['label' => "終了日: {$dateTo}", 'param' => 'date_to'];
        if ($venue !== '') $activeFilters[] = ['label' => "会場: {$venue}", 'param' => 'venue'];
        ?>

        <?php if (!empty($activeFilters)): ?>
            <div class="filter-tags">
                <?php foreach ($activeFilters as $filter): ?>
                    <div class="filter-tag">
                        <?= htmlspecialchars($filter['label'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="remove" onclick="removeFilter('<?= $filter['param'] ?>')">×</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 結果情報とソート -->
        <div class="results-info">
            <div>
                <strong><?= number_format($total) ?></strong> 件の大会が見つかりました
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="sortBy" style="font-size: 0.9em; color: #666;">並び順:</label>
                <select id="sortBy" name="sort" class="sort-select" onchange="changeSort(this.value)">
                    <option value="date_desc" <?= $sortBy === 'date_desc' ? 'selected' : '' ?>>開催日が新しい順</option>
                    <option value="date_asc" <?= $sortBy === 'date_asc' ? 'selected' : '' ?>>開催日が古い順</option>
                    <option value="created_desc" <?= $sortBy === 'created_desc' ? 'selected' : '' ?>>登録が新しい順</option>
                </select>
            </div>
        </div>

        <!-- 大会一覧 -->
        <div class="tournament-list">
            <?php if (empty($tournaments)): ?>
                <div style="grid-column:1/-1;color:#666;padding:12px;text-align:center;">
                    検索条件に一致する大会が見つかりませんでした。
                </div>
            <?php else: ?>
                <?php 
                $today = date('Y-m-d');
                foreach ($tournaments as $t):
                    $url = './User/tournament-department.php?id=' . urlencode($t['id']);
                    $title = $t['title'];
                    $eventDate = $t['event_date'] ?? '';
                    $date = substr($eventDate, 0, 10);
                    $venueText = $t['venue'] ?? '';

                    // バッジ判定
                    $badge = '';
                    if ($eventDate) {
                        if ($eventDate === $today) {
                            $badge = '<span class="badge badge-today">本日開催</span>';
                        } elseif ($eventDate > $today) {
                            $badge = '<span class="badge badge-upcoming">開催予定</span>';
                        } else {
                            $badge = '<span class="badge badge-past">終了</span>';
                        }
                    }
                ?>
                    <a class="tournament-item" href="<?= $url ?>" target="_blank" rel="noopener noreferrer">
                        <h3><?= highlightKeyword($title, $keyword) ?> <?= $badge ?></h3>
                        <p>📅 <?= $date ?: '未定' ?></p>
                        <?php if ($venueText): ?>
                            <p>📍 <?= highlightKeyword($venueText, $keyword) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ページネーション -->
        <div class="pagination">
            <?php
            $prevP = max(1, $page - 1);
            $nextP = min($totalPages, $page + 1);
            $baseQuery = array_filter([
                'q' => $keyword,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'venue' => $venue,
                'sort' => $sortBy !== 'date_desc' ? $sortBy : null,
            ]);
            ?>
            <a href="?<?= http_build_query(array_merge($baseQuery, ['p' => $prevP])) ?>" class="pagination-btn">← 戻る</a>
            <div style="min-width:160px;text-align:center;color:#666">
                <?= $page ?> / <?= $totalPages ?> ページ
            </div>
            <a href="?<?= http_build_query(array_merge($baseQuery, ['p' => $nextP])) ?>" class="pagination-btn">次へ →</a>
        </div>

        <footer class="school-name">
            <div>企画：盛岡剣道協会</div>
            <div>制作：情報ビジネス＆デザイン専門学校</div>
        </footer>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('menuLinks');
            menu.classList.toggle('open');
        }

        function toggleAdvancedSearch() {
            const advanced = document.getElementById('advancedSearch');
            const isHidden = advanced.style.display === 'none';
            advanced.style.display = isHidden ? 'block' : 'none';
        }

        function clearSearch() {
            window.location.href = window.location.pathname;
        }

        function removeFilter(param) {
            const form = document.getElementById('searchForm');
            const input = form.querySelector(`[name="${param}"]`);
            if (input) {
                if (input.tagName === 'SELECT') {
                    input.value = input.querySelector('option').value;
                } else {
                    input.value = '';
                }
            }
            form.submit();
        }

        function changeSort(value) {
            const url = new URL(window.location.href);
            if (value === 'date_desc') {
                url.searchParams.delete('sort');
            } else {
                url.searchParams.set('sort', value);
            }
            url.searchParams.delete('p');
            window.location.href = url.toString();
        }

        // 詳細検索フィルターが設定されている場合は自動で開く
        window.addEventListener('DOMContentLoaded', function() {
            const hasAdvancedFilters = <?= json_encode($dateFrom !== '' || $dateTo !== '' || $venue !== '') ?>;
            if (hasAdvancedFilters) {
                document.getElementById('advancedSearch').style.display = 'block';
            }
        });
    </script>
</body>

</html>