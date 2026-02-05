<?php
session_start();
require_once '../../../../connect/db_connect.php';

if (!isset($_SESSION['tournament_editor'])) {
    header('Location: ../../login.php');
    exit;
}

// パラメータ取得
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$dept_id = isset($_GET['dept']) ? (int)$_GET['dept'] : null;

if (!$match_id) {
    die("match_id が指定されていません");
}

// ヘルパー: DB の勝者表現を統一して 'red' / 'white' / '' にする
function normalizeWinner($v)
{
    if ($v === null) return '';
    $s = mb_strtolower(trim((string)$v));
    if ($s === '') return '';
    if (in_array($s, ['red', 'r', '赤', 'aka', 'a'], true)) return 'red';
    if (in_array($s, ['white', 'w', '白', 'shiro', 'b'], true)) return 'white';
    return '';
}

// 試合情報取得（players.player_number を取得する想定）
// 部門名が必要なら departments を JOIN して d.name を取得することを推奨
try {
    $sql = "SELECT im.match_id, im.department_id, im.team_match_id,
                   im.match_field, im.order_id, im.player_a_id, im.player_b_id,
                   im.started_at, im.ended_at,
                   im.first_technique, im.first_winner,
                   im.second_technique, im.second_winner,
                   im.third_technique, im.third_winner,
                   im.judgement, im.final_winner,
                   pa.name AS player_a_name, pa.player_number AS player_a_number,
                   pb.name AS player_b_name, pb.player_number AS player_b_number
            FROM individual_matches im
            LEFT JOIN players pa ON pa.id = im.player_a_id
            LEFT JOIN players pb ON pb.id = im.player_b_id
            WHERE im.match_id = :mid
            LIMIT 1";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mid', $match_id, PDO::PARAM_INT);
    $stmt->execute();
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("試合情報の取得に失敗しました");
}

if (!$match) {
    die("指定された試合が見つかりません");
}

// 正規化して JS に渡す winners 配列を作る
$uiWinners = [
    normalizeWinner($match['first_winner'] ?? null),
    normalizeWinner($match['second_winner'] ?? null),
    normalizeWinner($match['third_winner'] ?? null)
];
$uiFinalWinner = normalizeWinner($match['final_winner'] ?? null);

// JS に渡すデータ整形
$uiData = [
    'match_id' => (int)$match['match_id'],
    'team_match_id' => isset($match['team_match_id']) ? (int)$match['team_match_id'] : null,
    'player_a' => [
        'id' => isset($match['player_a_id']) ? (int)$match['player_a_id'] : null,
        'name' => $match['player_a_name'] ?? '',
        'number' => $match['player_a_number'] ?? ''
    ],
    'player_b' => [
        'id' => isset($match['player_b_id']) ? (int)$match['player_b_id'] : null,
        'name' => $match['player_b_name'] ?? '',
        'number' => $match['player_b_number'] ?? ''
    ],
    'scores_a' => [
        $match['first_technique'] ?? '',
        $match['second_technique'] ?? '',
        $match['third_technique'] ?? ''
    ],
    'winners' => $uiWinners,
    'final_winner' => $uiFinalWinner,
    'judgement' => $match['judgement'] ?? '',
    'started_at' => $match['started_at'] ?? null,
    'ended_at' => $match['ended_at'] ?? null
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>試合詳細</title>
    <link rel="stylesheet" href="../../css/result_change/match-detail-style.css">
    <style>
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div><strong><?= !empty($uiData['team_match_id']) ? '団体内個人戦' : '個人戦' ?></strong></div>
            <div>大会ID: <?= htmlspecialchars($tournament_id ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <div>部門ID: <?= htmlspecialchars($dept_id ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <div class="content-wrapper">

            <!-- 上段選手 -->
            <div class="match-section upper-section">
                <div class="row">
                    <div class="label">名前</div>
                    <div class="value upper-name">───</div>
                </div>
                <div class="row">
                    <div class="label">選手番号</div>
                    <div class="value upper-number">───</div>
                </div>

                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers upper-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                        <div class="radio-circles upper-circles">
                            <div class="radio-item" data-index="0">
                                <div class="radio-circle" data-index="0"></div>
                                <div class="radio-label"></div>
                            </div>
                            <div class="radio-item" data-index="1">
                                <div class="radio-circle" data-index="1"></div>
                                <div class="radio-label"></div>
                            </div>
                            <div class="radio-item" data-index="2">
                                <div class="radio-circle" data-index="2"></div>
                                <div class="radio-label"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="decision-row" id="upperDecisionRow">
                    <button class="decision-button" id="upperDecisionBtn">判定勝ち</button>
                </div>
            </div>

            <!-- 技・判定 -->
            <div class="divider-section">
                <div class="middle-controls">
                    <div class="score-dropdowns upper-scores">
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <div class="dropdown-container" style="display:inline-block; position:relative; margin-right:6px;">
                                <button type="button" class="score-dropdown">▼</button>
                                <div class="dropdown-menu">
                                    <?php foreach (['▼', '×', 'メ', 'コ', 'ド', '反', 'ツ', '〇'] as $val): ?>
                                        <div class="dropdown-item" data-val="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="draw-container-wrapper" style="display:inline-block; margin-left:1rem; position:relative;">
                    <div class="draw-container">
                        <button type="button" class="draw-button" id="drawButton">-</button>
                        <div class="draw-dropdown-menu" id="drawMenu" style="position:absolute; left:0; top:100%;">
                            <div class="dropdown-item" data-val="-">-</div>
                            <div class="dropdown-item" data-val="引分け">引分け</div>
                            <div class="dropdown-item" data-val="一本勝">一本勝</div>
                            <div class="dropdown-item" data-val="延長">延長</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 下段選手 -->
            <div class="match-section lower-section">
                <div class="decision-row" id="lowerDecisionRow">
                    <button class="decision-button" id="lowerDecisionBtn">判定勝ち</button>
                </div>

                <div class="row">
                    <div class="label">名前</div>
                    <div class="value lower-name">───</div>
                </div>
                <div class="row">
                    <div class="label">選手番号</div>
                    <div class="value lower-number">───</div>
                </div>

                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers lower-numbers">
                            <span>1</span><span>2</span><span>3</span>
                        </div>
                        <div class="radio-circles lower-circles">
                            <div class="radio-item" data-index="0">
                                <div class="radio-circle" data-index="0"></div>
                                <div class="radio-label"></div>
                            </div>
                            <div class="radio-item" data-index="1">
                                <div class="radio-circle" data-index="1"></div>
                                <div class="radio-label"></div>
                            </div>
                            <div class="radio-item" data-index="2">
                                <div class="radio-circle" data-index="2"></div>
                                <div class="radio-label"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="bottom-area">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div><button class="bottom-button" id="cancelButton">取り消し</button></div>
                    <div class="bottom-buttons">
                        <button type="button" class="bottom-button back-button" onclick="history.back()">キャンセル</button>
                        <button type="button" class="bottom-button submit-button" id="submitButton">変更</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 安全に JS に埋める
        const UI_DATA = <?= json_encode($uiData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <script>
        (function() {
            // data を let で初期化。UI_DATA が falsy の場合はデフォルトを使う
            let data = (typeof UI_DATA !== 'undefined' && UI_DATA) ? UI_DATA : {
                match_id: null,
                team_match_id: null,
                player_a: {
                    id: null,
                    name: '',
                    number: ''
                },
                player_b: {
                    id: null,
                    name: '',
                    number: ''
                },
                scores_a: ['▼', '▼', '▼'],
                winners: ['', '', ''],
                final_winner: '',
                judgement: '',
                started_at: null,
                ended_at: null
            };

            // DOM 要素取得（存在チェックを厳密に）
            const upperNameEl = document.querySelector('.upper-name');
            const upperNumberEl = document.querySelector('.upper-number');
            const lowerNameEl = document.querySelector('.lower-name');
            const lowerNumberEl = document.querySelector('.lower-number');
            const upperScoreDropdowns = document.querySelectorAll('.upper-scores .score-dropdown');
            const upperRadioItems = document.querySelectorAll('.upper-circles .radio-item');
            const lowerRadioItems = document.querySelectorAll('.lower-circles .radio-item');
            const upperDecisionBtn = document.getElementById('upperDecisionBtn');
            const lowerDecisionBtn = document.getElementById('lowerDecisionBtn');
            const drawButton = document.getElementById('drawButton');
            const drawMenu = document.getElementById('drawMenu');
            const submitButton = document.getElementById('submitButton');
            const cancelButton = document.getElementById('cancelButton');

            function safeText(v) {
                return (v === null || typeof v === 'undefined') ? '' : String(v);
            }

            function closeAllMenus() {
                document.querySelectorAll('.dropdown-menu, .draw-dropdown-menu').forEach(m => m.classList.remove('show'));
            }

            function updateDecisionButtonsVisibility() {
                const drawText = drawButton ? drawButton.textContent.trim() : '';
                const show = drawText === '延長';
                const upperRow = document.getElementById('upperDecisionRow');
                const lowerRow = document.getElementById('lowerDecisionRow');
                if (upperRow) upperRow.classList.toggle('show', show);
                if (lowerRow) lowerRow.classList.toggle('show', show);
                if (!show) {
                    if (upperDecisionBtn) upperDecisionBtn.classList.remove('active');
                    if (lowerDecisionBtn) lowerDecisionBtn.classList.remove('active');
                    data.final_winner = '';
                }
            }

            function reflectWinnersToUI() {
                const winners = Array.isArray(data.winners) ? data.winners.slice(0, 3) : ['', '', ''];

                upperRadioItems.forEach((item, i) => {
                    const circle = item.querySelector('.radio-circle');
                    if (!circle) return;
                    circle.classList.toggle('selected', winners[i] === 'red');
                    const label = item.querySelector('.radio-label');
                    if (label) label.textContent = safeText(data.player_a.name);
                    item.dataset.index = i;
                });

                lowerRadioItems.forEach((item, i) => {
                    const circle = item.querySelector('.radio-circle');
                    if (!circle) return;
                    circle.classList.toggle('selected', winners[i] === 'white');
                    const label = item.querySelector('.radio-label');
                    if (label) label.textContent = safeText(data.player_b.name);
                    item.dataset.index = i;
                });

                if (upperDecisionBtn) upperDecisionBtn.classList.toggle('active', data.final_winner === 'red');
                if (lowerDecisionBtn) lowerDecisionBtn.classList.toggle('active', data.final_winner === 'white');

                if (drawButton && data.judgement) {
                    if (data.judgement.indexOf('引分') !== -1) drawButton.textContent = '引分け';
                    else if (data.judgement.indexOf('一本') !== -1) drawButton.textContent = '一本勝';
                    else if (data.judgement.indexOf('延長') !== -1) drawButton.textContent = '延長';
                }
                updateDecisionButtonsVisibility();
            }

            function load() {
                if (upperNameEl) upperNameEl.textContent = safeText(data.player_a.name) || '───';
                if (upperNumberEl) upperNumberEl.textContent = safeText(data.player_a.number) || '───';
                if (lowerNameEl) lowerNameEl.textContent = safeText(data.player_b.name) || '───';
                if (lowerNumberEl) lowerNumberEl.textContent = safeText(data.player_b.number) || '───';

                upperScoreDropdowns.forEach((btn, i) => {
                    if (btn) btn.textContent = (data.scores_a && data.scores_a[i]) ? data.scores_a[i] : '▼';
                });

                reflectWinnersToUI();
            }

            function saveLocal() {
                data.scores_a = Array.from(upperScoreDropdowns).map(b => b ? b.textContent.trim() : '▼');
                const winners = ['', '', ''];
                upperRadioItems.forEach((item, i) => {
                    const c = item.querySelector('.radio-circle');
                    if (c && c.classList.contains('selected')) winners[i] = 'red';
                });
                lowerRadioItems.forEach((item, i) => {
                    const c = item.querySelector('.radio-circle');
                    if (c && c.classList.contains('selected')) winners[i] = 'white';
                });
                data.winners = winners;
                data.final_winner = (upperDecisionBtn && upperDecisionBtn.classList.contains('active')) ? 'red' : (lowerDecisionBtn && lowerDecisionBtn.classList.contains('active')) ? 'white' : '';
                const dt = drawButton ? drawButton.textContent.trim() : '-';
                data.judgement = dt === '一本勝' ? '一本勝' : dt === '延長' ? '延長' : dt === '引分け' ? '引分け' : '';
            }

            // 判定ボタン（排他）
            if (upperDecisionBtn && lowerDecisionBtn) {
                upperDecisionBtn.addEventListener('click', () => {
                    if (upperDecisionBtn.classList.contains('active')) {
                        upperDecisionBtn.classList.remove('active');
                        data.final_winner = '';
                    } else {
                        upperDecisionBtn.classList.add('active');
                        lowerDecisionBtn.classList.remove('active');
                        data.final_winner = 'red';
                    }
                    saveLocal();
                });
                lowerDecisionBtn.addEventListener('click', () => {
                    if (lowerDecisionBtn.classList.contains('active')) {
                        lowerDecisionBtn.classList.remove('active');
                        data.final_winner = '';
                    } else {
                        lowerDecisionBtn.classList.add('active');
                        upperDecisionBtn.classList.remove('active');
                        data.final_winner = 'white';
                    }
                    saveLocal();
                });
            }

            // 勝者サークル（上/下）クリック処理（排他）
            upperRadioItems.forEach((item, i) => {
                const circle = item.querySelector('.radio-circle');
                if (!circle) return;
                circle.addEventListener('click', () => {
                    const was = circle.classList.contains('selected');
                    if (was) {
                        circle.classList.remove('selected');
                        data.winners[i] = '';
                    } else {
                        circle.classList.add('selected');
                        const lowerItem = document.querySelector(`.lower-circles .radio-item[data-index="${i}"]`);
                        if (lowerItem) {
                            const lc = lowerItem.querySelector('.radio-circle');
                            if (lc) lc.classList.remove('selected');
                        }
                        data.winners[i] = 'red';
                    }
                    saveLocal();
                });
            });
            lowerRadioItems.forEach((item, i) => {
                const circle = item.querySelector('.radio-circle');
                if (!circle) return;
                circle.addEventListener('click', () => {
                    const was = circle.classList.contains('selected');
                    if (was) {
                        circle.classList.remove('selected');
                        data.winners[i] = '';
                    } else {
                        circle.classList.add('selected');
                        const upperItem = document.querySelector(`.upper-circles .radio-item[data-index="${i}"]`);
                        if (upperItem) {
                            const uc = upperItem.querySelector('.radio-circle');
                            if (uc) uc.classList.remove('selected');
                        }
                        data.winners[i] = 'white';
                    }
                    saveLocal();
                });
            });

            // ドロップダウン（技選択）
            document.querySelectorAll('.dropdown-container').forEach(container => {
                const btn = container.querySelector('.score-dropdown');
                const menu = container.querySelector('.dropdown-menu');
                if (!btn || !menu) return;
                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    closeAllMenus();
                    menu.classList.toggle('show');
                });
                menu.querySelectorAll('.dropdown-item').forEach(item => {
                    item.addEventListener('click', () => {
                        btn.textContent = item.dataset.val || item.textContent;
                        menu.classList.remove('show');
                        saveLocal();
                    });
                });
            });

            // 引き分け/延長メニュー
            if (drawButton && drawMenu) {
                drawButton.addEventListener('click', e => {
                    e.stopPropagation();
                    closeAllMenus();
                    drawMenu.classList.toggle('show');
                });
                drawMenu.querySelectorAll('.dropdown-item').forEach(item => {
                    item.addEventListener('click', () => {
                        drawButton.textContent = item.textContent;
                        drawMenu.classList.remove('show');
                        saveLocal();
                        updateDecisionButtonsVisibility();
                    });
                });
            }

            document.addEventListener('click', () => closeAllMenus());

            // 取り消し（リセット）
            if (cancelButton) {
                cancelButton.addEventListener('click', () => {
                    if (!confirm('試合内容をリセットしますか？')) return;
                    data.scores_a = ['▼', '▼', '▼'];
                    data.winners = ['', '', ''];
                    data.final_winner = '';
                    data.judgement = '';
                    load();
                });
            }

            // 送信処理
            if (submitButton) {
                submitButton.addEventListener('click', async () => {
                    saveLocal();
                    const winners = (data.winners || ['', '', '']).map(v => v || '');
                    const payload = {
                        match_id: data.match_id,
                        first_technique: (data.scores_a && data.scores_a[0]) ? data.scores_a[0] : '',
                        second_technique: (data.scores_a && data.scores_a[1]) ? data.scores_a[1] : '',
                        third_technique: (data.scores_a && data.scores_a[2]) ? data.scores_a[2] : '',
                        first_winner: winners[0] || '',
                        second_winner: winners[1] || '',
                        third_winner: winners[2] || '',
                        final_winner: data.final_winner || '',
                        judgement: data.judgement || '',
                        started_at: data.started_at || null,
                        ended_at: data.ended_at || null
                    };

                    if (!payload.match_id) {
                        alert('match_id が設定されていません。ページを再読み込みしてください。');
                        return;
                    }

                    const btn = submitButton;
                    btn.disabled = true;
                    const originalText = btn.textContent;
                    btn.textContent = '保存中...';

                    try {
                        const res = await fetch('match-update.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        const json = await res.json();
                        if (json.status === 'ok') {
                            alert('保存しました');
                            location.reload();
                        } else {
                            alert('保存に失敗しました: ' + (json.message || 'サーバーエラー'));
                        }
                    } catch (err) {
                        console.error(err);
                        alert('通信エラーが発生しました');
                    } finally {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                });
            }

            // 初期化
            load();
        })();
    </script>
</body>

</html>