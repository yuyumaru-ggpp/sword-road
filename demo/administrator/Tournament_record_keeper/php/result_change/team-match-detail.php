<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$team_match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;
$individual_match_num = isset($_GET['individual_match_num']) ? (int)$_GET['individual_match_num'] : null;

if (!$tournament_id || !$team_match_id) {
    die("大会ID または 試合ID が指定されていません");
}

// POSTリクエスト処理(保存)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input && isset($input['positions'])) {
        try {
            $pdo->beginTransaction();

            $positions = ['先鋒', '次鋒', '中堅', '副将', '大将', '代表決定戦'];

            foreach ($positions as $index => $posName) {
                if (!isset($input['positions'][$posName])) continue;

                $pos = $input['positions'][$posName];
                $individual_match_num_loop = $index + 1;

                // ── 技（スコア）は upper.scores から取得 ──
                $scores = $pos['upper']['scores'] ?? ['▼', '▼', '▼'];
                $first  = $scores[0] ?? null;
                $second = $scores[1] ?? null;
                $third  = $scores[2] ?? null;

                if ($first  === '▼' || $first  === '×' || $first  === '') $first  = null;
                if ($second === '▼' || $second === '×' || $second === '') $second = null;
                if ($third  === '▼' || $third  === '×' || $third  === '') $third  = null;

                // ── 列ごとの勝者から first/second/third_winner を決定 ──
                // winners[0] = 列1の勝者, winners[1] = 列2, winners[2] = 列3
                $winners = $pos['winners'] ?? [null, null, null];

                $toWinner = function($v) {
                    if ($v === 'red')   return 'red';
                    if ($v === 'white') return 'white';
                    return null;
                };

                $first_winner  = $toWinner($winners[0] ?? null);
                $second_winner = $toWinner($winners[1] ?? null);
                $third_winner  = $toWinner($winners[2] ?? null);

                // ── 最終勝者を決定（赤白どちらが多く取ったか） ──
                $redWins   = count(array_filter($winners, fn($v) => $v === 'red'));
                $whiteWins = count(array_filter($winners, fn($v) => $v === 'white'));

                $finalWinner = null;
                if ($redWins > $whiteWins)        $finalWinner = 'red';
                elseif ($whiteWins > $redWins)    $finalWinner = 'white';

                // ── 判定（judgement）を決定 ──
                $special   = $pos['special'] ?? 'none';
                $judgement = null;
                if ($special === 'ippon')  $judgement = '一本勝';
                if ($special === 'nibon')  $judgement = '二本勝';
                if ($special === 'extend') $judgement = '延長戦';
                if ($special === 'hantei') $judgement = '判定';
                if ($special === 'draw')   $judgement = '引き分け';

                // ── DB更新 ──
                $sql = "UPDATE individual_matches
                        SET first_technique   = :first,
                            second_technique  = :second,
                            third_technique   = :third,
                            first_winner      = :first_winner,
                            second_winner     = :second_winner,
                            third_winner      = :third_winner,
                            judgement         = :judgement,
                            final_winner      = :winner
                        WHERE team_match_id         = :team_match_id
                          AND individual_match_num  = :individual_match_num";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':first',                $first,                     $first        === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':second',               $second,                    $second       === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':third',                $third,                     $third        === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':first_winner',         $first_winner,              $first_winner === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':second_winner',        $second_winner,             $second_winner=== null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':third_winner',         $third_winner,              $third_winner === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':judgement',            $judgement,                 $judgement    === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':winner',               $finalWinner,               $finalWinner  === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':team_match_id',        $team_match_id,             PDO::PARAM_INT);
                $stmt->bindValue(':individual_match_num', $individual_match_num_loop, PDO::PARAM_INT);
                $stmt->execute();
            }

            // ── team_match_results を再集計して更新 ──
            $score_techs = ['メ', 'コ', 'ド', 'ツ', '反', '判'];

            $sql_all = "
                SELECT final_winner,
                       first_technique,  first_winner,
                       second_technique, second_winner,
                       third_technique,  third_winner,
                       judgement
                FROM individual_matches
                WHERE team_match_id = :team_match_id
            ";
            $stmt_all = $pdo->prepare($sql_all);
            $stmt_all->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
            $stmt_all->execute();
            $all_matches = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

            $red_wins   = 0; $white_wins  = 0;
            $red_score  = 0; $white_score = 0;

            foreach ($all_matches as $im) {
                $fw       = strtolower($im['final_winner'] ?? '');
                $is_red   = ($fw === 'red' || $fw === 'a');
                $is_white = ($fw === 'white' || $fw === 'b');

                if ($is_red)   $red_wins++;
                if ($is_white) $white_wins++;

                $tech_slots = [
                    ['tech' => $im['first_technique'],  'winner' => strtolower($im['first_winner']  ?? '')],
                    ['tech' => $im['second_technique'], 'winner' => strtolower($im['second_winner'] ?? '')],
                    ['tech' => $im['third_technique'],  'winner' => strtolower($im['third_winner']  ?? '')],
                ];

                foreach ($tech_slots as $slot) {
                    $tech        = $slot['tech']   ?? '';
                    $tech_winner = $slot['winner'];
                    if (empty($tech) || !in_array($tech, $score_techs)) continue;

                    $points = ($tech === 'メ') ? 2 : 1;

                    if ($tech === '判') {
                        if ($is_red)   $red_score   += $points;
                        if ($is_white) $white_score += $points;
                    } else {
                        if ($tech_winner === 'red'   || $tech_winner === 'a') $red_score   += $points;
                        if ($tech_winner === 'white' || $tech_winner === 'b') $white_score += $points;
                    }
                }
            }

            $team_winner = null;
            if ($red_wins > $white_wins)        $team_winner = 'red';
            elseif ($white_wins > $red_wins)    $team_winner = 'white';
            elseif ($red_score > $white_score)  $team_winner = 'red';
            elseif ($white_score > $red_score)  $team_winner = 'white';

            $sql_update = "
                UPDATE team_match_results
                SET red_win_count   = :red_wins,
                    white_win_count = :white_wins,
                    red_score       = :red_score,
                    white_score     = :white_score,
                    winner          = :winner
                WHERE id = :team_match_id
            ";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindValue(':red_wins',      $red_wins,    PDO::PARAM_INT);
            $stmt_update->bindValue(':white_wins',    $white_wins,  PDO::PARAM_INT);
            $stmt_update->bindValue(':red_score',     $red_score,   PDO::PARAM_INT);
            $stmt_update->bindValue(':white_score',   $white_score, PDO::PARAM_INT);
            $stmt_update->bindValue(':winner',        $team_winner, $team_winner === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_update->bindValue(':team_match_id', $team_match_id, PDO::PARAM_INT);
            $stmt_update->execute();

            $pdo->commit();
            echo json_encode(['status' => 'ok']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// 団体戦情報を取得
$sql = "SELECT tmr.*, tr.name AS team_red_name, tw.name AS team_white_name,
               d.name AS dept_name, t.title AS tournament_title
        FROM team_match_results tmr
        LEFT JOIN teams tr ON tr.id = tmr.team_red_id
        LEFT JOIN teams tw ON tw.id = tmr.team_white_id
        LEFT JOIN departments d ON d.id = tmr.department_id
        LEFT JOIN tournaments t ON t.id = d.tournament_id
        WHERE tmr.id = :tmid
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tmid' => $team_match_id]);
$teamMatch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teamMatch) {
    die("試合が見つかりません");
}

// 個別試合を取得(1-6)
$sql = "SELECT im.*,
               pa.name AS player_a_name,
               pb.name AS player_b_name
        FROM individual_matches im
        LEFT JOIN players pa ON pa.id = im.player_a_id
        LEFT JOIN players pb ON pb.id = im.player_b_id
        WHERE im.team_match_id = :tmid
        ORDER BY im.individual_match_num ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tmid' => $team_match_id]);
$individualMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// データを整形
$positions = ['先鋒', '次鋒', '中堅', '副将', '大将', '代表決定戦'];
$data = ['positions' => []];

foreach ($positions as $index => $posName) {
    $matchNum = $index + 1;
    $match = null;

    foreach ($individualMatches as $im) {
        if ($im['individual_match_num'] == $matchNum) {
            $match = $im;
            break;
        }
    }

    if ($match) {
        // 技データ（NULL → '▼'）
        $scores = [
            ($match['first_technique']  !== null && $match['first_technique']  !== '') ? $match['first_technique']  : '▼',
            ($match['second_technique'] !== null && $match['second_technique'] !== '') ? $match['second_technique'] : '▼',
            ($match['third_technique']  !== null && $match['third_technique']  !== '') ? $match['third_technique']  : '▼',
        ];

        // 判定
        $judgement = $match['judgement'] ?? '';
        $special = 'none';
        if ($judgement === '一本勝')   $special = 'ippon';
        if ($judgement === '二本勝')   $special = 'nibon';
        if ($judgement === '延長戦')   $special = 'extend';
        if ($judgement === '判定')     $special = 'hantei';
        if ($judgement === '引き分け') $special = 'draw';

        // first/second/third_winner から列ごとの winners を復元
        $winnerMap = [
            strtolower($match['first_winner']  ?? ''),
            strtolower($match['second_winner'] ?? ''),
            strtolower($match['third_winner']  ?? ''),
        ];
        $winners = [];
        foreach ($winnerMap as $w) {
            if ($w === 'red'   || $w === 'a') $winners[] = 'red';
            elseif ($w === 'white' || $w === 'b') $winners[] = 'white';
            else $winners[] = null;
        }

        // 旧データ互換（first/second/third_winner 未設定の場合 final_winner から推定）
        $fw = strtolower($match['final_winner'] ?? '');
        if ($winners === [null, null, null] && $fw !== '') {
            $side = ($fw === 'red' || $fw === 'a') ? 'red' : 'white';
            if ($special === 'nibon') {
                $winners[0] = $side;
                $winners[1] = $side;
            } else {
                $winners[0] = $side;
            }
        }

        $data['positions'][$posName] = [
            'upper' => [
                'team'   => $teamMatch['team_red_name'] ?? '',
                'name'   => $match['player_a_name'] ?? '',
                'scores' => $scores,
            ],
            'lower' => [
                'team'   => $teamMatch['team_white_name'] ?? '',
                'name'   => $match['player_b_name'] ?? '',
                'scores' => $scores,
            ],
            'winners' => $winners,   // [列0:'red'/'white'/null, 列1, 列2]
            'special' => $special,
        ];
    } else {
        // データなし → デフォルト
        $data['positions'][$posName] = [
            'upper' => [
                'team'   => $teamMatch['team_red_name'] ?? '',
                'name'   => '',
                'scores' => ['▼', '▼', '▼'],
            ],
            'lower' => [
                'team'   => $teamMatch['team_white_name'] ?? '',
                'name'   => '',
                'scores' => ['▼', '▼', '▼'],
            ],
            'winners' => [null, null, null],
            'special' => 'none',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦試合詳細 - <?= htmlspecialchars($teamMatch['dept_name']) ?></title>
    <link rel="stylesheet" href="../../css/result_change/match-detail-style.css">
</head>

<body>
    <div class="container">
        <div class="position-header" id="positionHeader">先鋒</div>

        <div class="header">
            <div class="header-badge">団体戦</div>
            <div class="header-badge"><?= htmlspecialchars($teamMatch['tournament_title']) ?></div>
            <div class="header-badge"><?= htmlspecialchars($teamMatch['dept_name']) ?></div>
        </div>

        <div class="top-right-controls">
            <div class="nav-buttons">
                <button class="nav-button" id="nextButton">次へ</button>
                <button class="nav-button" id="prevButton">戻る</button>
                <button class="nav-button" id="repButton" style="display:none;">代表決定戦</button>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="match-section upper-section">
                <div class="player-info" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value upper-team">───</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">名前</div>
                        <div class="info-value upper-name">───</div>
                    </div>
                </div>

                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers upper-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                        <div class="radio-circles red-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider-section">
                <hr class="divider-line">
                <div class="middle-controls">
                    <div class="score-dropdowns">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="dropdown-container">
                                <div class="score-dropdown">▼</div>
                                <div class="dropdown-menu">
                                    <div class="dropdown-item" data-val="▼">▼</div>
                                    <div class="dropdown-item" data-val="メ">メ</div>
                                    <div class="dropdown-item" data-val="コ">コ</div>
                                    <div class="dropdown-item" data-val="ド">ド</div>
                                    <div class="dropdown-item" data-val="ツ">ツ</div>
                                    <div class="dropdown-item" data-val="反">反</div>
                                    <div class="dropdown-item" data-val="判">判</div>
                                    <div class="dropdown-item" data-val="不">不</div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="draw-container">
                        <button type="button" class="draw-button" id="drawButton">-</button>
                        <div class="draw-dropdown-menu" id="drawMenu">
                            <div class="dropdown-item">二本勝</div>
                            <div class="dropdown-item">一本勝</div>
                            <div class="dropdown-item">延長戦</div>
                            <div class="dropdown-item">判定</div>
                            <div class="dropdown-item">引き分け</div>
                            <div class="dropdown-item">-</div>
                        </div>
                    </div>
                </div>
                <hr class="divider-line">
            </div>

            <div class="match-section lower-section">
                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles white-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                        <div class="score-numbers lower-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                    </div>
                </div>

                <div class="player-info" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
                    <div class="info-row">
                        <div class="info-label">名前</div>
                        <div class="info-value lower-name">───</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">チーム名</div>
                        <div class="info-value lower-team">───</div>
                    </div>
                </div>
            </div>

            <div class="bottom-area">
                <div class="bottom-right-button">
                    <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
                </div>

                <div class="bottom-buttons">
                    <button type="button" class="bottom-button back-button" onclick="history.back()">戻る</button>
                    <button type="button" class="bottom-button submit-button" id="submitButton">変更</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const positions = ['先鋒', '次鋒', '中堅', '副将', '大将', '代表決定戦'];
        const urlParams = new URLSearchParams(window.location.search);
        const individualMatchNumParam = urlParams.get('individual_match_num');

        let current = individualMatchNumParam ? (parseInt(individualMatchNumParam) - 1) : 0;
        const singleMatchMode = !!individualMatchNumParam;

        const data = <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>;
        const tournamentId = <?= $tournament_id ?>;
        const teamMatchId  = <?= $team_match_id ?>;

        // ── 画面に現在のポジションデータをロード ──
        function load() {
            const key = positions[current];
            const p   = data.positions[key];
            const isRepMatch = (key === '代表決定戦');

            document.getElementById('positionHeader').textContent = key;

            if (singleMatchMode) {
                document.getElementById('nextButton').style.display = 'none';
                document.getElementById('prevButton').style.display = 'none';
                document.getElementById('repButton').style.display  = 'none';
            } else {
                document.getElementById('nextButton').style.display = (current < 4) ? 'block' : 'none';
                document.getElementById('prevButton').style.display = (current > 0) ? 'block' : 'none';
                document.getElementById('repButton').style.display  = (current === 4) ? 'block' : 'none';
            }

            document.querySelector('.upper-team').textContent = p.upper.team || '───';
            document.querySelector('.upper-name').textContent = p.upper.name || '───';
            document.querySelector('.lower-team').textContent = p.lower.team || '───';
            document.querySelector('.lower-name').textContent = p.lower.name || '───';

            const upperNumbers   = document.querySelectorAll('.upper-numbers span');
            const lowerNumbers   = document.querySelectorAll('.lower-numbers span');
            const redCircles     = document.querySelectorAll('.red-circles .radio-circle');
            const whiteCircles   = document.querySelectorAll('.white-circles .radio-circle');
            const upperDropdowns = document.querySelectorAll('.score-dropdowns .dropdown-container');

            if (isRepMatch) {
                upperNumbers.forEach((el, i)   => el.style.display = i === 0 ? 'block' : 'none');
                lowerNumbers.forEach((el, i)   => el.style.display = i === 0 ? 'block' : 'none');
                redCircles.forEach((el, i)     => el.style.display = i === 0 ? 'flex'  : 'none');
                whiteCircles.forEach((el, i)   => el.style.display = i === 0 ? 'flex'  : 'none');
                upperDropdowns.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
            } else {
                upperNumbers.forEach(el   => el.style.display = 'block');
                lowerNumbers.forEach(el   => el.style.display = 'block');
                redCircles.forEach(el     => el.style.display = 'flex');
                whiteCircles.forEach(el   => el.style.display = 'flex');
                upperDropdowns.forEach(el => el.style.display = 'block');
            }

            // ドロップダウン（技）を復元
            document.querySelectorAll('.score-dropdowns .score-dropdown').forEach((btn, i) => {
                btn.textContent = (p.upper.scores && p.upper.scores[i]) ? p.upper.scores[i] : '▼';
            });

            // ラジオ（勝者）を復元 — winners[i] = 'red'/'white'/null
            const winners = p.winners || [null, null, null];
            for (let i = 0; i < 3; i++) {
                const red   = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
                const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);
                red.classList.toggle('selected',   winners[i] === 'red');
                white.classList.toggle('selected', winners[i] === 'white');
            }

            // 判定ボタンを復元
            const special = p.special || 'none';
            document.getElementById('drawButton').textContent =
                special === 'ippon'  ? '一本勝' :
                special === 'nibon'  ? '二本勝' :
                special === 'extend' ? '延長戦' :
                special === 'hantei' ? '判定' :
                special === 'draw'   ? '引き分け' : '-';
        }

        // ── 現在の画面入力を data に保存 ──
        function saveLocal() {
            const key = positions[current];

            // 技（upper.scores / lower.scores 両方を同一配列で更新）
            const scores = Array.from(
                document.querySelectorAll('.score-dropdowns .score-dropdown')
            ).map(b => b.textContent);

            data.positions[key].upper.scores = scores;
            data.positions[key].lower.scores = scores;

            // 列ごとの赤白選択状態を保存
            // winners[i] = 'red' / 'white' / null（列0,1,2に対応）
            const winners = [null, null, null];
            for (let i = 0; i < 3; i++) {
                const red   = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
                const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);
                if (red.classList.contains('selected'))        winners[i] = 'red';
                else if (white.classList.contains('selected')) winners[i] = 'white';
            }
            data.positions[key].winners = winners;

            // 判定
            const dt = document.getElementById('drawButton').textContent;
            data.positions[key].special =
                dt === '一本勝' ? 'ippon'  :
                dt === '二本勝' ? 'nibon'  :
                dt === '延長戦' ? 'extend' :
                dt === '判定'   ? 'hantei' :
                dt === '引き分け' ? 'draw' : 'none';
        }

        // ── ラジオサークルのイベント ──
        // 仕様：列（1/2/3）ごとに赤か白かを選ぶ
        //       同じ列で赤を選んだら白は解除、白を選んだら赤は解除
        //       他の列の選択状態には影響しない
        for (let i = 0; i < 3; i++) {
            const red   = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
            const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);

            red.addEventListener('click', () => {
                if (red.classList.contains('selected')) {
                    red.classList.remove('selected');
                } else {
                    white.classList.remove('selected');
                    red.classList.add('selected');
                }
            });

            white.addEventListener('click', () => {
                if (white.classList.contains('selected')) {
                    white.classList.remove('selected');
                } else {
                    red.classList.remove('selected');
                    white.classList.add('selected');
                }
            });
        }

        // ── ドロップダウン（技）イベント ──
        document.querySelectorAll('.dropdown-container').forEach(container => {
            const btn  = container.querySelector('.score-dropdown');
            const menu = container.querySelector('.dropdown-menu');

            btn.addEventListener('click', e => {
                e.stopPropagation();
                document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m => m.classList.remove('show'));
                menu.classList.toggle('show');
            });

            menu.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', () => {
                    btn.textContent = item.dataset.val || item.textContent;
                    menu.classList.remove('show');
                });
            });
        });

        // ── 判定ドロップダウンイベント ──
        document.getElementById('drawButton').addEventListener('click', e => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
            document.getElementById('drawMenu').classList.toggle('show');
        });

        document.getElementById('drawMenu').querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => {
                document.getElementById('drawButton').textContent = item.textContent;
                document.getElementById('drawMenu').classList.remove('show');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m => m.classList.remove('show'));
        });

        // ── リセットボタン ──
        document.getElementById('cancelButton').addEventListener('click', () => {
            if (confirm(positions[current] + ' をリセットしますか?')) {
                const key = positions[current];
                data.positions[key] = {
                    upper:   { team: data.positions[key].upper.team, name: data.positions[key].upper.name, scores: ['▼','▼','▼'], selected: -1 },
                    lower:   { team: data.positions[key].lower.team, name: data.positions[key].lower.name, scores: ['▼','▼','▼'], selected: -1 },
                    special: 'none',
                };
                load();
            }
        });

        // ── ナビゲーション ──
        document.getElementById('nextButton').onclick = () => { saveLocal(); if (current < 5) current++; load(); };
        document.getElementById('prevButton').onclick = () => { saveLocal(); if (current > 0) current--; load(); };
        document.getElementById('repButton').onclick  = () => { saveLocal(); current = 5; load(); };

        // ── 保存ボタン ──
        document.getElementById('submitButton').onclick = async () => {
            saveLocal();

            if (!confirm('以下の内容に変更しますか?')) return;

            try {
                const r = await fetch(location.href, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(data),
                });
                const j = await r.json();
                if (j.status === 'ok') {
                    alert('保存しました');
                    history.back();
                } else {
                    alert('保存失敗: ' + (j.message || ''));
                }
            } catch (e) {
                alert('エラー発生');
                console.error(e);
            }
        };

        load();
    })();
    </script>
</body>
</html>