<?php
require_once 'team_db.php';

// セッションチェック（team_red_id, team_white_idは不要）
if (
    !isset(
    $_SESSION['tournament_id'],
    $_SESSION['division_id'],
    $_SESSION['match_number']
)
) {
    header('Location: match_input.php');
    exit;
}

// 変数取得
$tournament_id = (int) $_SESSION['tournament_id'];
$division_id = (int) $_SESSION['division_id'];
$match_number = $_SESSION['match_number'];

// デバッグ用ログ
error_log('=== team-forfeit.php SESSION CHECK ===');
error_log('division_id: ' . ($_SESSION['division_id'] ?? 'NOT SET'));
error_log('match_number: ' . ($_SESSION['match_number'] ?? 'NOT SET'));
error_log('match_field: ' . ($_SESSION['match_field'] ?? 'NOT SET'));
error_log('tournament_id: ' . ($_SESSION['tournament_id'] ?? 'NOT SET'));

// 大会・部門情報取得
$sql = "
    SELECT
        t.title AS tournament_name,
        d.name  AS division_name
    FROM tournaments t
    JOIN departments d ON d.tournament_id = t.id
    WHERE d.id = :division_id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $division_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// 部門に属するチーム一覧を取得（withdraw_flg も含める）
$sql = "
    SELECT
        t.id,
        t.team_number,
        t.name,
        t.withdraw_flg
    FROM teams t
    WHERE t.department_id = :division_id
    ORDER BY t.team_number
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':division_id' => $division_id]);
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// withdraw_flg=1 のチームIDセットを作成（JS用）
$withdraw_ids = [];
foreach ($teams as $team) {
    if ((int)$team['withdraw_flg'] === 1) {
        $withdraw_ids[] = (int)$team['id'];
    }
}

$error = '';

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $red_team_id = trim($_POST['red_team'] ?? '');
    $white_team_id = trim($_POST['white_team'] ?? '');
    $forfeit = $_POST['forfeit'] ?? '';

    if ($red_team_id === '' || $white_team_id === '') {
        $error = 'チームを選択してください';
    } else if ($red_team_id === $white_team_id) {
        $error = '同じチームは選択できません';
    } else {

        // チームIDの存在チェック
        $sql = "
            SELECT t.id, t.name, t.team_number, t.withdraw_flg
            FROM teams t
            WHERE t.department_id = :division_id
              AND t.id IN (:red_id, :white_id)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':division_id' => $division_id,
            ':red_id' => $red_team_id,
            ':white_id' => $white_team_id
        ]);

        $found_teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($found_teams) !== 2) {
            $error = '選択されたチームが見つかりません';
        } else {

            // チーム情報を取得
            $team_info = [];
            foreach ($found_teams as $t) {
                $team_info[$t['id']] = [
                    'name'         => $t['name'],
                    'number'       => $t['team_number'],
                    'withdraw_flg' => (int)$t['withdraw_flg']
                ];
            }

            // ★ 棄権チームが含まれているのに不戦勝ボタンが押されていない場合はブロック
            $red_withdrawn   = $team_info[$red_team_id]['withdraw_flg'] === 1;
            $white_withdrawn = $team_info[$white_team_id]['withdraw_flg'] === 1;

            if (($red_withdrawn || $white_withdrawn) && $forfeit === '') {
                if ($red_withdrawn && $white_withdrawn) {
                    $error = '両チームとも棄権しています。不戦勝ボタンを押して処理を進めてください。';
                } elseif ($red_withdrawn) {
                    $error = '赤チーム「' . htmlspecialchars($team_info[$red_team_id]['name']) . '」は棄権しています。白チームの「不戦勝」ボタンを押してください。';
                } else {
                    $error = '白チーム「' . htmlspecialchars($team_info[$white_team_id]['name']) . '」は棄権しています。赤チームの「不戦勝」ボタンを押してください。';
                }
            } else {

                /* ===============================
                   不戦勝（ボタンを押した側が勝ち）
                =============================== */
                if ($forfeit === 'red' || $forfeit === 'white') {

                    // 不戦勝ボタンを押した側が勝ち
                    // red ボタン押下 = 赤チームが勝ち、白チームが負け
                    // white ボタン押下 = 白チームが勝ち、赤チームが負け

                    $_SESSION['team_forfeit_data'] = [
                        'red_team_id'       => $red_team_id,
                        'white_team_id'     => $white_team_id,
                        'red_team_name'     => $team_info[$red_team_id]['name'],
                        'white_team_name'   => $team_info[$white_team_id]['name'],
                        'red_team_number'   => $team_info[$red_team_id]['number'],
                        'white_team_number' => $team_info[$white_team_id]['number'],
                        'winner'            => ($forfeit === 'red') ? 'red' : 'white'
                    ];

                    header('Location: team-forfeit-confirm.php');
                    exit;
                }

                /* ===============================
                   通常試合 → オーダー登録へ
                =============================== */
                $_SESSION['team_red_id']   = $red_team_id;
                $_SESSION['team_white_id'] = $white_team_id;

                header('Location: team-order-registration.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦チーム選択</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            height: 100vh;
            max-height: 900px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .main-content {
            flex: 1;
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .notice {
            text-align: center;
            font-size: 14px;
            color: #718096;
            margin-bottom: 20px;
            padding: 10px;
            background: #f7fafc;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .error {
            background-color: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
            border-left: 4px solid #c53030;
            animation: shake 0.4s;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }

        /* ========================================
           ★ 追加CSS（新規クラスのみ・既存を一切変更しない）
        ======================================== */

        /* 棄権警告バナー */
        .withdraw-warning {
            display: none;
            align-items: center;
            gap: 10px;
            background: #fffbeb;
            border: 2px solid #f6ad55;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #744210;
            flex-shrink: 0;
        }

        .withdraw-warning.active {
            display: flex;
        }

        .withdraw-warning .warn-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        /* 棄権チーム選択時のセレクト強調（border と background のみ上書き） */
        .team-select.withdrawn-selected {
            border-color: #f6ad55;
            background-color: #fffbeb;
        }

        /* 不戦勝ボタン推奨状態（既存の .forfeit-button はそのまま） */
        .forfeit-button.recommended {
            border-color: #f6ad55;
            color: #744210;
            background: #fefcbf;
            animation: pulse-orange 1.5s infinite;
        }

        @keyframes pulse-orange {
            0%, 100% { box-shadow: 0 0 0 0 rgba(246, 173, 85, 0.5); }
            50%       { box-shadow: 0 0 0 6px rgba(246, 173, 85, 0); }
        }

        /* ======================================== */

        .match-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .match-row {
            display: flex;
            gap: 20px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .team-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .team-label {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            padding: 8px 20px;
            border-radius: 8px;
        }

        .team-label.red {
            background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
            color: white;
        }

        .team-label.white {
            background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
            color: white;
        }

        .input-label-small {
            font-size: 12px;
            color: #718096;
            font-weight: 600;
        }

        .team-number-input {
            width: 100%;
            max-width: 200px;
            padding: 10px 16px;
            font-size: 16px;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            background-color: #f7fafc;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .team-number-input:focus {
            border-color: #667eea;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .team-select {
            width: 100%;
            max-width: 280px;
            padding: 10px 16px;
            font-size: 14px;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            appearance: none;
            background-color: #f7fafc;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16' fill='%234a5568'%3E%3Cpath d='W8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .team-select:focus {
            outline: none;
            border-color: #667eea;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .forfeit-button {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .forfeit-button:hover {
            border-color: #cbd5e0;
            background: #f7fafc;
        }

        .forfeit-button.selected {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .vs-text {
            font-size: 32px;
            font-weight: bold;
            color: #4a5568;
            flex-shrink: 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-shrink: 0;
        }

        .action-button {
            flex: 1;
            padding: 14px 20px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .confirm-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .confirm-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .back-button {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .back-button:hover {
            background-color: #cbd5e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .action-button:active {
            transform: translateY(0);
        }

        /* 小さい画面での調整 */
        @media (max-height: 700px) {
            .container {
                max-height: 100vh;
            }

            .header {
                padding: 15px;
            }

            .main-content {
                padding: 20px 15px;
            }

            .notice {
                font-size: 13px;
                padding: 8px;
                margin-bottom: 15px;
            }

            .team-label {
                font-size: 20px;
                padding: 6px 16px;
            }

            .vs-text {
                font-size: 24px;
            }

            .team-number-input,
            .team-select {
                padding: 8px 14px;
                font-size: 14px;
            }

            .forfeit-button {
                padding: 8px 20px;
                font-size: 13px;
            }

            .action-button {
                padding: 12px 18px;
                font-size: 15px;
            }

            .match-row {
                gap: 15px;
                margin-bottom: 15px;
            }

            .team-section {
                gap: 8px;
            }
        }

        /* タブレット縦向き・横向き */
        @media (max-width: 900px) {
            .match-row {
                flex-direction: column;
                gap: 20px;
            }

            .vs-text {
                transform: rotate(90deg);
            }

            .team-section {
                width: 100%;
                max-width: 400px;
            }

            .team-select,
            .team-number-input {
                max-width: 100%;
            }
        }

        /* スマートフォン横向き */
        @media (max-width: 900px) and (max-height: 500px) {
            .container {
                max-width: 95%;
                max-height: 95vh;
            }

            .header {
                padding: 10px;
            }

            .header-badge {
                font-size: 12px;
                padding: 5px 12px;
            }

            .main-content {
                padding: 15px;
            }

            .notice {
                font-size: 12px;
                padding: 6px;
                margin-bottom: 10px;
            }

            .match-row {
                flex-direction: row;
                gap: 10px;
                margin-bottom: 10px;
            }

            .team-section {
                gap: 6px;
            }

            .team-label {
                font-size: 16px;
                padding: 5px 12px;
            }

            .vs-text {
                font-size: 20px;
                transform: none;
            }

            .input-label-small {
                font-size: 11px;
            }

            .team-number-input,
            .team-select {
                padding: 6px 12px;
                font-size: 13px;
            }

            .forfeit-button {
                padding: 6px 16px;
                font-size: 12px;
            }

            .action-button {
                padding: 10px 16px;
                font-size: 14px;
            }

            .action-buttons {
                margin-top: 10px;
            }
        }

        /* 小さいスマートフォン */
        @media (max-width: 400px) {
            .header-badge {
                font-size: 12px;
                padding: 6px 12px;
            }

            .team-label {
                font-size: 18px;
            }

            .team-select,
            .team-number-input {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div class="header-badge">団体戦</div>
            <div class="header-badge"><?= htmlspecialchars($info['tournament_name']) ?></div>
            <div class="header-badge"><?= htmlspecialchars($info['division_name']) ?></div>
        </div>

        <div class="main-content">
            <div class="notice">
                ※ 不戦勝の場合は勝者側の「不戦勝」ボタンを押してください
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ★ 棄権警告バナー（JS で動的表示） -->
            <div class="withdraw-warning" id="withdrawWarning">
                <span class="warn-icon">⚠️</span>
                <span id="withdrawWarningText"></span>
            </div>

            <form method="POST" id="mainForm">
                <input type="hidden" name="forfeit" id="forfeitInput">

                <div class="match-container">
                    <div class="match-row">
                        <div class="team-section">
                            <div class="team-label red">赤</div>
                            <div class="input-label-small">チーム番号</div>
                            <input type="text" class="team-number-input" id="redTeamNumber" placeholder="番号入力">
                            <div class="input-label-small">またはチームを選択</div>
                            <select name="red_team" class="team-select" id="redTeam" required>
                                <option value="">選択してください</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>"
                                        data-number="<?= htmlspecialchars($team['team_number']) ?>"
                                        data-withdraw="<?= (int)$team['withdraw_flg'] ?>">
                                        <?= htmlspecialchars($team['name']) ?>
                                        (<?= htmlspecialchars($team['team_number']) ?>)
                                        <?php if ((int)$team['withdraw_flg'] === 1): ?>【棄権】<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="forfeit-button" id="redForfeit">不戦勝</button>
                        </div>

                        <div class="vs-text">対</div>

                        <div class="team-section">
                            <div class="team-label white">白</div>
                            <div class="input-label-small">チーム番号</div>
                            <input type="text" class="team-number-input" id="whiteTeamNumber" placeholder="番号入力">
                            <div class="input-label-small">またはチームを選択</div>
                            <select name="white_team" class="team-select" id="whiteTeam" required>
                                <option value="">選択してください</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>"
                                        data-number="<?= htmlspecialchars($team['team_number']) ?>"
                                        data-withdraw="<?= (int)$team['withdraw_flg'] ?>">
                                        <?= htmlspecialchars($team['name']) ?>
                                        (<?= htmlspecialchars($team['team_number']) ?>)
                                        <?php if ((int)$team['withdraw_flg'] === 1): ?>【棄権】<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="forfeit-button" id="whiteForfeit">不戦勝</button>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="action-button back-button"
                            onclick="location.href='match_input.php?division_id=<?= $division_id ?>'">戻る</button>
                        <button type="submit" class="action-button confirm-button" id="confirmButton">決定</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const withdrawIds = new Set(<?= json_encode($withdraw_ids) ?>);

        const redBtn        = document.getElementById('redForfeit');
        const whiteBtn      = document.getElementById('whiteForfeit');
        const forfeitInput  = document.getElementById('forfeitInput');
        const redSelect     = document.getElementById('redTeam');
        const whiteSelect   = document.getElementById('whiteTeam');
        const warning       = document.getElementById('withdrawWarning');
        const warningText   = document.getElementById('withdrawWarningText');

        // ────────────────────────────────────────────────
        //  棄権状態の判定と UI 更新
        // ────────────────────────────────────────────────
        function isWithdrawn(select) {
            const opt = select.options[select.selectedIndex];
            return opt && opt.dataset.withdraw === '1';
        }

        function getTeamName(select) {
            const opt = select.options[select.selectedIndex];
            return opt ? opt.textContent.replace('【棄権】', '').trim() : '';
        }

        function updateWithdrawUI() {
            const redW   = isWithdrawn(redSelect);
            const whiteW = isWithdrawn(whiteSelect);

            // セレクトのボーダー強調
            redSelect.classList.toggle('withdrawn-selected', redW);
            whiteSelect.classList.toggle('withdrawn-selected', whiteW);

            // 不戦勝ボタン推奨ハイライト（手動選択中は上書きしない）
            if (!redBtn.classList.contains('selected') && !whiteBtn.classList.contains('selected')) {
                whiteBtn.classList.toggle('recommended', redW && !whiteW);
                redBtn.classList.toggle('recommended', whiteW && !redW);
            }

            // 警告バナー
            if (redW || whiteW) {
                warning.classList.add('active');
                warning.style.border = '';
                warning.style.background = '';
                warning.style.color = '';
                if (redW && whiteW) {
                    warningText.textContent = '両チームとも棄権しています。不戦勝ボタンを押して処理してください。';
                } else if (redW) {
                    warningText.textContent = '赤チーム「' + getTeamName(redSelect) + '」は棄権しています。白チームの「不戦勝」ボタンを押してください。';
                } else {
                    warningText.textContent = '白チーム「' + getTeamName(whiteSelect) + '」は棄権しています。赤チームの「不戦勝」ボタンを押してください。';
                }
            } else {
                warning.classList.remove('active');
                redBtn.classList.remove('recommended');
                whiteBtn.classList.remove('recommended');
            }
        }

        // ────────────────────────────────────────────────
        //  不戦勝ボタン（元の動作を完全維持）
        // ────────────────────────────────────────────────
        redBtn.onclick = () => {
            if (redBtn.classList.contains('selected')) {
                redBtn.classList.remove('selected');
            } else {
                redBtn.classList.add('selected');
                whiteBtn.classList.remove('selected');
            }
            redBtn.classList.remove('recommended');
            whiteBtn.classList.remove('recommended');
        };

        whiteBtn.onclick = () => {
            if (whiteBtn.classList.contains('selected')) {
                whiteBtn.classList.remove('selected');
            } else {
                whiteBtn.classList.add('selected');
                redBtn.classList.remove('selected');
            }
            redBtn.classList.remove('recommended');
            whiteBtn.classList.remove('recommended');
        };

        // ────────────────────────────────────────────────
        //  フォーム送信（元の動作 + 棄権チェックを追加）
        // ────────────────────────────────────────────────
        document.querySelector('form').onsubmit = (e) => {
            if (redBtn.classList.contains('selected')) {
                forfeitInput.value = 'red';
            } else if (whiteBtn.classList.contains('selected')) {
                forfeitInput.value = 'white';
            } else {
                forfeitInput.value = '';
            }

            // ★ 棄権チームが含まれているのに不戦勝ボタン未押下 → ブロック
            const redW   = isWithdrawn(redSelect);
            const whiteW = isWithdrawn(whiteSelect);

            if ((redW || whiteW) && forfeitInput.value === '') {
                e.preventDefault();
                warning.classList.add('active');
                warning.style.border = '2px solid #c53030';
                warning.style.background = '#fff5f5';
                warning.style.color = '#742a2a';
                if (redW && whiteW) {
                    warningText.textContent = '⛔ 両チームとも棄権しています。不戦勝ボタンを押してから決定してください。';
                } else if (redW) {
                    warningText.textContent = '⛔ 赤チームは棄権しています。白チームの「不戦勝」ボタンを押してから決定してください。';
                } else {
                    warningText.textContent = '⛔ 白チームは棄権しています。赤チームの「不戦勝」ボタンを押してから決定してください。';
                }
                return false;
            }
        };

        // ────────────────────────────────────────────────
        //  チーム番号入力時の自動選択機能（元の動作を維持）
        // ────────────────────────────────────────────────
        document.getElementById('redTeamNumber').addEventListener('input', function(e) {
            const number = e.target.value.trim();
            const select = document.getElementById('redTeam');

            if (number === '') {
                return;
            }

            for (let option of select.options) {
                if (option.dataset.number && option.dataset.number === number) {
                    select.value = option.value;
                    updateWithdrawUI();
                    return;
                }
            }
        });

        document.getElementById('whiteTeamNumber').addEventListener('input', function(e) {
            const number = e.target.value.trim();
            const select = document.getElementById('whiteTeam');

            if (number === '') {
                return;
            }

            for (let option of select.options) {
                if (option.dataset.number && option.dataset.number === number) {
                    select.value = option.value;
                    updateWithdrawUI();
                    return;
                }
            }
        });

        // ────────────────────────────────────────────────
        //  プルダウン選択時にチーム番号欄に反映（元の動作を維持）
        // ────────────────────────────────────────────────
        document.getElementById('redTeam').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const numberInput = document.getElementById('redTeamNumber');

            if (selectedOption.dataset.number) {
                numberInput.value = selectedOption.dataset.number;
            } else {
                numberInput.value = '';
            }

            updateWithdrawUI();
        });

        document.getElementById('whiteTeam').addEventListener('change', function(e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const numberInput = document.getElementById('whiteTeamNumber');

            if (selectedOption.dataset.number) {
                numberInput.value = selectedOption.dataset.number;
            } else {
                numberInput.value = '';
            }

            updateWithdrawUI();
        });
    </script>

</body>

</html>