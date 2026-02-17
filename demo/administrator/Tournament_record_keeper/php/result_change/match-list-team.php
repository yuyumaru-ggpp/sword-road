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
            im.first_technique, im.second_technique, im.third_technique,
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
        // 技を収集（メ、コ、×など）
        $techs = [];
        if (!empty($im['first_technique'])) $techs[] = $im['first_technique'];
        if (!empty($im['second_technique'])) $techs[] = $im['second_technique'];
        if (!empty($im['third_technique'])) $techs[] = $im['third_technique'];

        $judgement = $im['judgement'] ?? '';
        $winner = strtolower($im['final_winner'] ?? '');

        // メーココ形式で結果を生成
        $resultText = '';
        if (!empty($techs)) {
            // すべての技を連結
            $allTechs = implode('', $techs);

            // 判定がある場合は追加
            if (!empty($judgement)) {
                $resultText = $allTechs . 'ー' . $judgement;
            } else {
                $resultText = $allTechs;
            }
        } else if (!empty($judgement)) {
            // 技がなくて判定だけある場合
            $resultText = 'ー' . $judgement;
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

        .td-detail {
            text-align: center;
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

        <!-- 検索 -->
        <div class="search-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="選手名・チーム名で検索" />
            <button class="btn-clear" onclick="clearSearch()">クリア</button>
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
                            <th class="center">詳細</th>
                        </tr>
                    </thead>
                    <tbody id="matchBody"></tbody>
                </table>
            </div>
        </div>

        <a class="back-link" href="match-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>">&larr; 戻る</a>
    </div>

    <script>
        // --- データ ---
        const teamMatches = <?= $matchesJson ?>;
        const tournamentId = <?= $tournament_id ?>;
        const deptId = <?= $dept_id ?>;

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
          <td class="td-detail">
            <button class="btn-detail" data-id="${match.matchId}">
              詳細
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
          </td>
        `;
                tbody.appendChild(mainRow);

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
                    // 選手名に勝者の色をつける
                    let playerAHTML = im.playerA;
                    let playerBHTML = im.playerB;

                    if (im.winner === 'red' || im.winner === 'a') {
                        playerAHTML = `<span class="player-winner-red">${im.playerA}</span>`;
                    } else if (im.winner === 'white' || im.winner === 'b') {
                        playerBHTML = `<span class="player-winner-white">${im.playerB}</span>`;
                    }

                    // 結果表示を整形（文字ごとに色分け）
                    let resultHTML = '';
                    if (im.result) {
                        let coloredResult = '';
                        for (let char of im.result) {
                            if (char === 'メ' || char === 'め') {
                                coloredResult += `<span style="color: #dc2626; font-weight: 600;">${char}</span>`;
                            } else if (char === 'コ' || char === 'こ') {
                                coloredResult += `<span style="color: #2563eb; font-weight: 600;">${char}</span>`;
                            } else {
                                coloredResult += char;
                            }
                        }
                        resultHTML = coloredResult;
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

                // ボタンイベント
                const btn = mainRow.querySelector(".btn-detail");
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    const isOpen = detailRow.classList.toggle("open");
                    btn.classList.toggle("open", isOpen);
                });

                // 詳細行の各試合をクリックしたときのイベント
                detailRow.querySelectorAll(".detail-match-row").forEach(row => {
                    row.addEventListener("click", () => {
                        const matchId = row.dataset.individualMatchId;
                        window.location.href = `match-detail.php?match_id=${matchId}&id=${tournamentId}&dept=${deptId}`;
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