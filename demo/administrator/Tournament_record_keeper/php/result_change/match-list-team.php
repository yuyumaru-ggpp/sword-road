<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$dept_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

if (!$tournament_id || !$dept_id) {
    die("大会ID または 部門ID が指定されていません");
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id, PDO::PARAM_INT);
$stmt->execute();
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);
$tournament_title = $tournament['title'] ?? '大会';

// 部門情報取得
$sql = "SELECT name, distinction FROM departments WHERE id = :did LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $dept_id, PDO::PARAM_INT);
$stmt->execute();
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

$dept_name = $dept['name'] ?? "部門 {$dept_id}";

// 団体戦の試合一覧取得
$sql = "
    SELECT DISTINCT
        tmr.id AS team_match_id,
        tmr.match_field,
        tmr.match_number,
        tr.name AS team_red_name,
        tw.name AS team_white_name
    FROM team_match_results tmr
    LEFT JOIN teams tr ON tr.id = tmr.team_red_id
    LEFT JOIN teams tw ON tw.id = tmr.team_white_id
    WHERE tmr.department_id = :did
    ORDER BY tmr.match_field ASC, tmr.match_number ASC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $dept_id, PDO::PARAM_INT);
$stmt->execute();
$teamMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 各団体戦の個別試合と勝敗数を取得
$matchesData = [];
foreach ($teamMatches as $tm) {
    $team_match_id = $tm['team_match_id'];

    // 個別試合取得
    $sql = "
        SELECT 
            im.match_id,
            im.individual_match_num,
            im.first_technique, im.first_winner,
            im.second_technique, im.second_winner,
            im.third_technique, im.third_winner,
            im.judgement, im.final_winner,
            pa.name AS player_a_name,
            pb.name AS player_b_name
        FROM individual_matches im
        LEFT JOIN players pa ON pa.id = im.player_a_id
        LEFT JOIN players pb ON pb.id = im.player_b_id
        WHERE im.team_match_id = :tmid
        ORDER BY im.individual_match_num ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tmid' => $team_match_id]);
    $individualMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 勝敗数カウント
    $redWins = 0;
    $whiteWins = 0;

    foreach ($individualMatches as $im) {
        $winner = strtolower($im['final_winner'] ?? '');
        if ($winner === 'red' || $winner === 'a') $redWins++;
        if ($winner === 'white' || $winner === 'b') $whiteWins++;
    }

    // 結果表示用に整形
    $formattedMatches = [];
    foreach ($individualMatches as $im) {
        // 技と勝者を配列で保持
        $techniques = [];
        if (!empty($im['first_technique'])) {
            $techniques[] = [
                'tech' => $im['first_technique'],
                'winner' => strtolower($im['first_winner'] ?? '')
            ];
        }
        if (!empty($im['second_technique'])) {
            $techniques[] = [
                'tech' => $im['second_technique'],
                'winner' => strtolower($im['second_winner'] ?? '')
            ];
        }
        if (!empty($im['third_technique'])) {
            $techniques[] = [
                'tech' => $im['third_technique'],
                'winner' => strtolower($im['third_winner'] ?? '')
            ];
        }

        $judgement = $im['judgement'] ?? '';
        $winner = strtolower($im['final_winner'] ?? '');

        // 赤と白の技を分離（それぞれの勝者情報も保持）
        $redTechs = [];
        $whiteTechs = [];
        
        foreach ($techniques as $t) {
            $techWinner = $t['winner'];
            if ($techWinner === 'red' || $techWinner === 'a') {
                $redTechs[] = $t['tech'];
            } else if ($techWinner === 'white' || $techWinner === 'b') {
                $whiteTechs[] = $t['tech'];
            }
        }

        // 「赤の技ー白の技ー決まり手」形式で結果を生成
        $resultText = '';
        $redPart = implode('', $redTechs);
        $whitePart = implode('', $whiteTechs);
        
        if (!empty($redPart) || !empty($whitePart) || !empty($judgement)) {
            $resultText = $redPart . 'ー' . $whitePart . 'ー' . $judgement;
            // 末尾の余分なーを削除
            $resultText = rtrim($resultText, 'ー');
        }

        $formattedMatches[] = [
            'matchId' => $im['match_id'],
            'individualMatchNum' => $im['individual_match_num'],
            'playerA' => $im['player_a_name'] ?? '未設定',
            'playerB' => $im['player_b_name'] ?? '未設定',
            'result' => $resultText,
            'winner' => $winner
        ];
    }

    $matchesData[] = [
        'matchId' => $team_match_id,
        'matchField' => $tm['match_field'],
        'departmentMatchNum' => $tm['match_number'],
        'teamRedName' => $tm['team_red_name'] ?? '未設定',
        'teamWhiteName' => $tm['team_white_name'] ?? '未設定',
        'redWins' => $redWins,
        'whiteWins' => $whiteWins,
        'individualMatches' => $formattedMatches
    ];
}

// JSON化
$matchesJson = json_encode($matchesData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>試合一覧 - <?= htmlspecialchars($dept_name) ?></title>
    <style>
        /* --- リセット & ベース --- */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            line-height: 1.5;
            min-height: 100vh;
        }

        /* --- レイアウト --- */
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* --- ヘッダー --- */
        .breadcrumb {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .breadcrumb a {
            color: #6b7280;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #1a1a1a;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            margin-top: 0.25rem;
            margin-bottom: 1.5rem;
        }

        /* --- 検索バー --- */
        .search-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #1a1a1a;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .btn-clear {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: #fff;
            font-size: 0.875rem;
            color: #1a1a1a;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .btn-clear:hover {
            background: #f3f4f6;
        }

        /* --- 全件一括保存ボタン --- */
        .btn-save-all {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1.25rem;
            border: none;
            border-radius: 0.375rem;
            background: #2563eb;
            color: #fff;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s, opacity 0.15s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .btn-save-all:hover {
            background: #1d4ed8;
        }

        .btn-save-all:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-save-all svg {
            width: 15px;
            height: 15px;
        }

        .top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        /* --- テーブル外枠 --- */
        .table-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .table-scroll {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* --- ヘッダー行 --- */
        thead th {
            padding: 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            white-space: nowrap;
        }

        thead th.center {
            text-align: center;
        }

        thead th.red {
            color: #ef4444;
        }

        thead th.blue {
            color: #3b82f6;
        }

        /* --- 本体行 --- */
        tbody tr.match-row {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.15s;
        }

        tbody tr.match-row:hover {
            background: #f9fafb;
        }

        tbody td {
            padding: 0.75rem;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .td-field {
            font-weight: 500;
        }

        .team-red {
            font-weight: 600;
            color: #dc2626;
        }

        .team-white {
            font-weight: 600;
            color: #2563eb;
        }

        .score {
            text-align: center;
            font-weight: 700;
        }

        .score-red {
            color: #dc2626;
        }

        .score-sep {
            color: #9ca3af;
            margin: 0 0.25rem;
        }

        .score-white {
            color: #2563eb;
        }

        .td-actions {
            text-align: center;
            white-space: nowrap;
        }

        /* --- 詳細ボタン --- */
        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: #fff;
            font-size: 0.75rem;
            font-weight: 500;
            color: #1a1a1a;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: background-color 0.15s;
        }

        .btn-detail:hover {
            background: #f3f4f6;
        }

        .btn-detail svg {
            width: 14px;
            height: 14px;
            transition: transform 0.2s;
        }

        .btn-detail.open svg {
            transform: rotate(180deg);
        }

        /* --- 再集計保存ボタン（行内） --- */
        .btn-recalc {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid #2563eb;
            border-radius: 0.375rem;
            background: #fff;
            font-size: 0.75rem;
            font-weight: 500;
            color: #2563eb;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: background-color 0.15s, color 0.15s;
            margin-left: 0.375rem;
        }

        .btn-recalc:hover {
            background: #eff6ff;
        }

        .btn-recalc:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-recalc svg {
            width: 13px;
            height: 13px;
        }

        /* 保存中スピナー */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: spin 0.7s linear infinite;
        }

        /* 保存済みバッジ */
        .saved-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-size: 0.7rem;
            color: #16a34a;
            margin-left: 0.375rem;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .saved-badge.show {
            opacity: 1;
        }

        .saved-badge svg {
            width: 12px;
            height: 12px;
        }

        /* --- 詳細展開行 --- */
        .detail-row {
            display: none;
            background: #f3f4f6;
        }

        .detail-row.open {
            display: table-row;
        }

        .detail-cell {
            padding: 0.75rem 1rem;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table thead th {
            padding: 0.5rem 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border-bottom: 1px solid #d1d5db;
        }

        .detail-table thead th.d-red {
            color: #ef4444;
            text-align: center;
        }

        .detail-table thead th.d-blue {
            color: #3b82f6;
            text-align: center;
        }

        .detail-table thead th.d-center {
            text-align: center;
        }

        .detail-table tbody td {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-table tbody tr:last-child td {
            border-bottom: none;
        }

        .detail-table .d-num {
            font-weight: 500;
        }

        .detail-table .d-player {
            text-align: center;
        }

        .detail-table .d-result {
            text-align: center;
            font-weight: 600;
        }

        /* --- 勝者表示 --- */
        .player-winner-red {
            color: #dc2626;
            font-weight: 600;
        }

        .player-winner-white {
            color: #2563eb;
            font-weight: 600;
        }

        /* --- 詳細行のクリック可能な行 --- */
        .detail-match-row {
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .detail-match-row:hover {
            background-color: #e5e7eb !important;
        }

        /* --- 空状態 --- */
        .empty-message {
            padding: 2rem 1rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        /* --- 戻るリンク --- */
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.15s;
        }

        .back-link:hover {
            color: #1a1a1a;
        }

        /* --- トースト通知 --- */
        #toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            padding: 0.625rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.25s, transform 0.25s;
            pointer-events: none;
            z-index: 100;
        }

        #toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        #toast.success { background: #16a34a; }
        #toast.error   { background: #dc2626; }
    </style>
</head>

<body>
    <div class="container">
        <!-- ヘッダー -->
        <p class="breadcrumb">
            <a href="../tournament_editor_menu.php?id=<?= htmlspecialchars($tournament_id) ?>">メニュー</a> &gt;
            <a href="match-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>">試合内容変更</a> &gt;
        </p>
        <h1 class="page-title"><?= htmlspecialchars($dept_name) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars($tournament_title) ?></p>

        <!-- 検索 & 一括保存ボタン -->
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="選手名・チーム名で検索" />
            <button class="btn-clear" onclick="clearSearch()">クリア</button>
        </div>

        <div class="top-bar">
            <button class="btn-save-all" id="btnSaveAll" onclick="saveAllTeamMatches()">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                全件一括保存
            </button>
        </div>

        <!-- テーブル -->
        <div class="table-wrapper">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>試合場</th>
                            <th>対戦番号</th>
                            <th class="red">赤</th>
                            <th class="center">結果</th>
                            <th class="blue">白</th>
                            <th class="center">操作</th>
                        </tr>
                    </thead>
                    <tbody id="matchBody"></tbody>
                </table>
            </div>
        </div>

        <a class="back-link" href="match-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>">&larr; 戻る</a>
    </div>

    <!-- トースト -->
    <div id="toast"></div>

    <script>
        // --- データ ---
        const teamMatches = <?= $matchesJson ?>;
        const tournamentId = <?= $tournament_id ?>;
        const deptId = <?= $dept_id ?>;

        // --- team_match_results を再集計して保存 ---
        async function saveTeamMatch(teamMatchId, btn, badge) {
            btn.disabled = true;
            const origSVG = btn.querySelector('svg').outerHTML;
            btn.querySelector('svg').outerHTML; // 参照保持
            btn.innerHTML = `
                <svg class="spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                保存中
            `;

            try {
                const res = await fetch('recalc-team-match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ team_match_id: teamMatchId })
                });
                const json = await res.json();

                if (json.status === 'ok') {
                    showToast('保存しました', 'success');
                    badge.classList.add('show');
                    setTimeout(() => badge.classList.remove('show'), 3000);
                } else {
                    showToast('保存に失敗しました: ' + (json.message ?? ''), 'error');
                }
            } catch (e) {
                showToast('通信エラーが発生しました', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    保存
                `;
            }
        }

        // --- 全件一括保存 ---
        async function saveAllTeamMatches() {
            const btn = document.getElementById('btnSaveAll');
            btn.disabled = true;
            btn.textContent = '保存中...';

            const ids = teamMatches.map(m => m.matchId);
            let successCount = 0;
            let failCount = 0;

            for (const id of ids) {
                try {
                    const res = await fetch('recalc-team-match.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ team_match_id: id })
                    });
                    const json = await res.json();
                    if (json.status === 'ok') successCount++;
                    else failCount++;
                } catch (e) {
                    failCount++;
                }
            }

            btn.disabled = false;
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                全件一括保存
            `;

            if (failCount === 0) {
                showToast(`全${successCount}件を保存しました`, 'success');
            } else {
                showToast(`${successCount}件成功、${failCount}件失敗`, 'error');
            }
        }

        // --- トースト ---
        let toastTimer = null;
        function showToast(msg, type = 'success') {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.className = `show ${type}`;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => {
                el.className = '';
            }, 3000);
        }

        // --- 描画 ---
        function renderMatches(data) {
            const tbody = document.getElementById("matchBody");
            tbody.innerHTML = "";

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-message">該当する試合がありません</td></tr>';
                return;
            }

            data.forEach((match) => {
                // メイン行
                const mainRow = document.createElement("tr");
                mainRow.className = "match-row";
                mainRow.innerHTML = `
                    <td class="td-field">${match.matchField}</td>
                    <td>${match.departmentMatchNum}</td>
                    <td class="team-red">${match.teamRedName}</td>
                    <td class="score">
                        <span class="score-red">${match.redWins}</span>
                        <span class="score-sep">-</span>
                        <span class="score-white">${match.whiteWins}</span>
                    </td>
                    <td class="team-white">${match.teamWhiteName}</td>
                    <td class="td-actions">
                        <button class="btn-detail" data-id="${match.matchId}">
                            詳細
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <button class="btn-recalc" data-match-id="${match.matchId}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            保存
                        </button>
                        <span class="saved-badge" data-badge="${match.matchId}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            保存済み
                        </span>
                    </td>
                `;
                tbody.appendChild(mainRow);

                // 再集計保存ボタンのイベント
                const recalcBtn = mainRow.querySelector('.btn-recalc');
                const badge = mainRow.querySelector(`.saved-badge[data-badge="${match.matchId}"]`);
                recalcBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    saveTeamMatch(match.matchId, recalcBtn, badge);
                });

                // 詳細行
                const detailRow = document.createElement("tr");
                detailRow.className = "detail-row";
                detailRow.id = `detail-${match.matchId}`;

                let detailHTML = `<td colspan="6" class="detail-cell">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>試合番号</th>
                                <th class="d-red">赤</th>
                                <th class="d-center">結果</th>
                                <th class="d-blue">白</th>
                            </tr>
                        </thead>
                        <tbody>`;

                match.individualMatches.forEach((im) => {
                    let playerAHTML = im.playerA;
                    let playerBHTML = im.playerB;

                    if (im.winner === 'red' || im.winner === 'a') {
                        playerAHTML = `<span class="player-winner-red">${im.playerA}</span>`;
                    } else if (im.winner === 'white' || im.winner === 'b') {
                        playerBHTML = `<span class="player-winner-white">${im.playerB}</span>`;
                    }

                    let resultHTML = '';
                    if (im.result) {
                        const parts = im.result.split('ー');
                        let coloredParts = [];
                        const isRedWinner = (im.winner === 'red' || im.winner === 'a');
                        const isWhiteWinner = (im.winner === 'white' || im.winner === 'b');

                        if (parts[0]) {
                            let redPart = '';
                            if (isRedWinner) {
                                for (let char of parts[0]) {
                                    redPart += `<span style="color: #dc2626; font-weight: 600;">${char}</span>`;
                                }
                            } else {
                                redPart = parts[0];
                            }
                            coloredParts.push(redPart);
                        } else {
                            coloredParts.push('');
                        }

                        if (parts.length > 1 && parts[1]) {
                            let whitePart = '';
                            if (isWhiteWinner) {
                                for (let char of parts[1]) {
                                    whitePart += `<span style="color: #2563eb; font-weight: 600;">${char}</span>`;
                                }
                            } else {
                                whitePart = parts[1];
                            }
                            coloredParts.push(whitePart);
                        } else if (parts.length > 1) {
                            coloredParts.push('');
                        }

                        if (parts.length > 2) {
                            coloredParts.push(parts[2]);
                        }

                        resultHTML = coloredParts.join('ー');
                    } else {
                        resultHTML = '<span style="color: #6b7280;">-</span>';
                    }

                    detailHTML += `
                        <tr class="detail-match-row" data-individual-match-id="${im.matchId}">
                            <td class="d-num">${im.individualMatchNum}</td>
                            <td class="d-player">${playerAHTML}</td>
                            <td class="d-result">${resultHTML}</td>
                            <td class="d-player">${playerBHTML}</td>
                        </tr>`;
                });

                detailHTML += `</tbody></table></td>`;
                detailRow.innerHTML = detailHTML;
                tbody.appendChild(detailRow);

                // 詳細ボタンイベント
                const btn = mainRow.querySelector(".btn-detail");
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const isOpen = detailRow.classList.toggle("open");
                    btn.classList.toggle("open", isOpen);
                });

                // 詳細行の個別試合クリック
                detailRow.querySelectorAll(".detail-match-row").forEach(row => {
                    row.addEventListener("click", () => {
                        const individualMatchNum = row.querySelector('.d-num').textContent;
                        window.location.href = `team-match-detail.php?match_id=${match.matchId}&id=${tournamentId}&dept=${deptId}&individual_match_num=${individualMatchNum}`;
                    });
                });
            });
        }

        // --- 検索 ---
        function filterMatches() {
            const q = document.getElementById("searchInput").value.toLowerCase();
            if (!q) {
                renderMatches(teamMatches);
                return;
            }
            const filtered = teamMatches.filter((m) => {
                return (
                    m.teamRedName.toLowerCase().includes(q) ||
                    m.teamWhiteName.toLowerCase().includes(q) ||
                    m.individualMatches.some(
                        (im) =>
                        im.playerA.toLowerCase().includes(q) ||
                        im.playerB.toLowerCase().includes(q)
                    )
                );
            });
            renderMatches(filtered);
        }

        function clearSearch() {
            document.getElementById("searchInput").value = "";
            renderMatches(teamMatches);
        }

        document.getElementById("searchInput").addEventListener("input", filterMatches);

        // 初期描画
        renderMatches(teamMatches);
    </script>
</body>

</html>