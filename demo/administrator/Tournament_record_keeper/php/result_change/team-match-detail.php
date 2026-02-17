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

                // individual_match_numを計算(1-6)
                $individual_match_num = $index + 1;

                // 技のデータを取得
                $scores = $pos['upper']['scores'] ?? ['▼', '▼', '▼'];
                $first = $scores[0] ?? null;
                $second = $scores[1] ?? null;
                $third = $scores[2] ?? null;

                // 空文字や▼をNULLに変換
                if ($first === '▼' || $first === '×' || $first === '') $first = null;
                if ($second === '▼' || $second === '×' || $second === '') $second = null;
                if ($third === '▼' || $third === '×' || $third === '') $third = null;

                // 判定を取得
                $special = $pos['special'] ?? 'none';
                $judgement = null;
                if ($special === 'ippon') $judgement = '一本勝';
                else if ($special === 'nibon') $judgement = '二本勝';
                else if ($special === 'extend') $judgement = '延長戦';
                else if ($special === 'hantei') $judgement = '判定';
                else if ($special === 'draw') $judgement = '引き分け';

                // 勝者を判定
                $upperSelected = $pos['upper']['selected'] ?? -1;
                $lowerSelected = $pos['lower']['selected'] ?? -1;
                $finalWinner = null;

                if ($upperSelected >= 0) {
                    $finalWinner = 'red';
                } else if ($lowerSelected >= 0) {
                    $finalWinner = 'white';
                }

                // 更新(team_match_idとindividual_match_numで特定)
                $sql = "UPDATE individual_matches 
                        SET first_technique = :first,
                            second_technique = :second,
                            third_technique = :third,
                            judgement = :judgement,
                            final_winner = :winner
                        WHERE team_match_id = :team_match_id 
                        AND individual_match_num = :individual_match_num";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':first' => $first,
                    ':second' => $second,
                    ':third' => $third,
                    ':judgement' => $judgement,
                    ':winner' => $finalWinner,
                    ':team_match_id' => $team_match_id,
                    ':individual_match_num' => $individual_match_num
                ]);
            }

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
        // 技のデータ
        $scores = [
            $match['first_technique'] ?? '▼',
            $match['second_technique'] ?? '▼',
            $match['third_technique'] ?? '▼'
        ];

        // NULLの場合は▼に変換
        for ($i = 0; $i < 3; $i++) {
            if ($scores[$i] === null || $scores[$i] === '') {
                $scores[$i] = '▼';
            }
        }

        // 判定
        $judgement = $match['judgement'] ?? '';
        $special = 'none';
        if ($judgement === '一本勝') $special = 'ippon';
        else if ($judgement === '二本勝') $special = 'nibon';
        else if ($judgement === '延長戦') $special = 'extend';
        else if ($judgement === '判定') $special = 'hantei';
        else if ($judgement === '引き分け') $special = 'draw';

        // 勝者の判定ロジックを修正
        $winner = strtolower($match['final_winner'] ?? '');
        $upperSelected = -1;
        $lowerSelected = -1;

        if ($winner === 'red' || $winner === 'a') {
            // 赤が勝った場合
            if ($judgement === '二本勝') {
                // 二本勝の場合は2本目のラジオボタンを選択
                $upperSelected = 1;
            } else if ($judgement === '一本勝') {
                // 一本勝の場合は1本目のラジオボタンを選択
                $upperSelected = 0;
            } else {
                // その他の判定の場合は「メ」の数を数える
                $count = 0;
                foreach ($scores as $s) {
                    if ($s === 'メ' || $s === 'め') $count++;
                }
                if ($count > 0) $upperSelected = $count - 1;
            }
        } else if ($winner === 'white' || $winner === 'b') {
            // 白が勝った場合
            if ($judgement === '二本勝') {
                // 二本勝の場合は2本目のラジオボタンを選択
                $lowerSelected = 1;
            } else if ($judgement === '一本勝') {
                // 一本勝の場合は1本目のラジオボタンを選択
                $lowerSelected = 0;
            } else {
                // その他の判定の場合は「コ」の数を数える
                $count = 0;
                foreach ($scores as $s) {
                    if ($s === 'コ' || $s === 'こ') $count++;
                }
                if ($count > 0) $lowerSelected = $count - 1;
            }
        }

        $data['positions'][$posName] = [
            'upper' => [
                'team' => $teamMatch['team_red_name'] ?? '',
                'name' => $match['player_a_name'] ?? '',
                'scores' => $scores,
                'selected' => $upperSelected
            ],
            'lower' => [
                'team' => $teamMatch['team_white_name'] ?? '',
                'name' => $match['player_b_name'] ?? '',
                'scores' => $scores,
                'selected' => $lowerSelected
            ],
            'special' => $special
        ];
    } else {
        // データがない場合はデフォルト
        $data['positions'][$posName] = [
            'upper' => [
                'team' => $teamMatch['team_red_name'] ?? '',
                'name' => '',
                'scores' => ['▼', '▼', '▼'],
                'selected' => -1
            ],
            'lower' => [
                'team' => $teamMatch['team_white_name'] ?? '',
                'name' => '',
                'scores' => ['▲', '▲', '▲'],
                'selected' => -1
            ],
            'special' => 'none'
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
                                    <div class="dropdown-item" data-val="×">×</div>
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
        console.log('Initial data from PHP:', <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>);
    </script>
    <script>
        (function() {
            const positions = ['先鋒', '次鋒', '中堅', '副将', '大将', '代表決定戦'];
            let current = 0;
            const data = <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>;
            const tournamentId = <?= $tournament_id ?>;
            const teamMatchId = <?= $team_match_id ?>;

            // デバッグ用
            console.log('Initial data from PHP:', data);
            console.log('Positions:', positions);

            function load() {
                const key = positions[current];
                const p = data.positions[key];
                const isRepMatch = (key === '代表決定戦');

                console.log('Loading position:', key);
                console.log('Position data:', p);

                document.getElementById('positionHeader').textContent = key;

                document.getElementById('nextButton').style.display = (current < 4) ? 'block' : 'none';
                document.getElementById('prevButton').style.display = (current > 0) ? 'block' : 'none';
                document.getElementById('repButton').style.display = (current === 4) ? 'block' : 'none';

                document.querySelector('.upper-team').textContent = p.upper.team || '───';
                document.querySelector('.upper-name').textContent = p.upper.name || '───';
                document.querySelector('.lower-team').textContent = p.lower.team || '───';
                document.querySelector('.lower-name').textContent = p.lower.name || '───';

                const upperNumbers = document.querySelectorAll('.upper-numbers span');
                const lowerNumbers = document.querySelectorAll('.lower-numbers span');
                const redCircles = document.querySelectorAll('.red-circles .radio-circle');
                const whiteCircles = document.querySelectorAll('.white-circles .radio-circle');
                const upperDropdowns = document.querySelectorAll('.score-dropdowns .dropdown-container');

                if (isRepMatch) {
                    upperNumbers.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
                    lowerNumbers.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
                    redCircles.forEach((el, i) => el.style.display = i === 0 ? 'flex' : 'none');
                    whiteCircles.forEach((el, i) => el.style.display = i === 0 ? 'flex' : 'none');
                    upperDropdowns.forEach((el, i) => el.style.display = i === 0 ? 'block' : 'none');
                } else {
                    upperNumbers.forEach(el => el.style.display = 'block');
                    lowerNumbers.forEach(el => el.style.display = 'block');
                    redCircles.forEach(el => el.style.display = 'flex');
                    whiteCircles.forEach(el => el.style.display = 'flex');
                    upperDropdowns.forEach(el => el.style.display = 'block');
                }

                document.querySelectorAll('.score-dropdowns .score-dropdown').forEach((b, i) => {
                    b.textContent = (p.upper.scores && p.upper.scores[i]) || '▼';
                });

                document.querySelectorAll('.red-circles .radio-circle').forEach((c, i) => {
                    c.classList.toggle('selected', p.upper.selected === i);
                });

                document.querySelectorAll('.white-circles .radio-circle').forEach((c, i) => {
                    c.classList.toggle('selected', p.lower.selected === i);
                });

                const special = p.special || 'none';
                const text = special === 'ippon' ? '一本勝' :
                    special === 'nibon' ? '二本勝' :
                    special === 'extend' ? '延長戦' :
                    special === 'hantei' ? '判定' :
                    special === 'draw' ? '引き分け' : '-';
                document.getElementById('drawButton').textContent = text;
            }

            function saveLocal() {
                const key = positions[current];
                data.positions[key].upper.scores = Array.from(document.querySelectorAll('.score-dropdowns .score-dropdown')).map(b => b.textContent);
                const uSel = document.querySelector('.red-circles .radio-circle.selected');
                data.positions[key].upper.selected = uSel ? +uSel.dataset.index : -1;
                const lSel = document.querySelector('.white-circles .radio-circle.selected');
                data.positions[key].lower.selected = lSel ? +lSel.dataset.index : -1;

                const dt = document.getElementById('drawButton').textContent;
                data.positions[key].special = dt === '一本勝' ? 'ippon' :
                    dt === '二本勝' ? 'nibon' :
                    dt === '延長戦' ? 'extend' :
                    dt === '判定' ? 'hantei' :
                    dt === '引き分け' ? 'draw' : 'none';
            }

            for (let i = 0; i < 3; i++) {
                const red = document.querySelector(`.red-circles .radio-circle[data-index="${i}"]`);
                const white = document.querySelector(`.white-circles .radio-circle[data-index="${i}"]`);

                red.addEventListener('click', () => {
                    if (red.classList.contains('selected')) {
                        red.classList.remove('selected');
                    } else {
                        red.classList.add('selected');
                        white.classList.remove('selected');
                    }
                });

                white.addEventListener('click', () => {
                    if (white.classList.contains('selected')) {
                        white.classList.remove('selected');
                    } else {
                        white.classList.add('selected');
                        red.classList.remove('selected');
                    }
                });
            }

            document.querySelectorAll('.dropdown-container').forEach(container => {
                const btn = container.querySelector('.score-dropdown');
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

            document.addEventListener('click', () => document.querySelectorAll('.dropdown-menu,.draw-dropdown-menu').forEach(m => m.classList.remove('show')));

            document.getElementById('cancelButton').addEventListener('click', () => {
                if (confirm(positions[current] + ' をリセットしますか?')) {
                    data.positions[positions[current]] = {
                        upper: {
                            team: data.positions[positions[current]].upper.team,
                            name: data.positions[positions[current]].upper.name,
                            scores: ['▼', '▼', '▼'],
                            selected: -1
                        },
                        lower: {
                            team: data.positions[positions[current]].lower.team,
                            name: data.positions[positions[current]].lower.name,
                            scores: ['▼', '▼', '▼'],
                            selected: -1
                        },
                        special: 'none'
                    };
                    load();
                }
            });

            document.getElementById('nextButton').onclick = () => {
                saveLocal();
                if (current < 5) current++;
                load();
            };

            document.getElementById('prevButton').onclick = () => {
                saveLocal();
                if (current > 0) current--;
                load();
            };

            document.getElementById('repButton').onclick = () => {
                saveLocal();
                current = 5;
                load();
            };

            document.getElementById('submitButton').onclick = async () => {
                saveLocal();

                if (!confirm('以下の内容に変更しますか?')) {
                    return;
                }

                try {
                    const r = await fetch(location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
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
    <script>
        const rawData = <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>;
        console.log('Raw data structure:', rawData);
        console.log('Has positions?', 'positions' in rawData);
        console.log('Positions keys:', rawData.positions ? Object.keys(rawData.positions) : 'N/A');
    </script>
</body>

</html>