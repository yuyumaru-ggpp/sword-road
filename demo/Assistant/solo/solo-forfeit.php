<?php
require_once 'solo_db.php';

// セッションチェック
checkSoloSession();

// 変数取得
$tournament_id = (int) $_SESSION['tournament_id'];
$division_id = (int) $_SESSION['division_id'];
$match_number = $_SESSION['match_number'];

// 大会・部門情報取得
$info = getTournamentInfo($pdo, $division_id);

// 選手一覧取得
$players = getPlayers($pdo, $division_id);

$error = '';

// セッションから選手IDを取得（戻るボタンで戻ってきた場合）
$selected_upper_id = $_SESSION['player_a_id'] ?? '';
$selected_lower_id = $_SESSION['player_b_id'] ?? '';

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $upper_id = trim($_POST['upper_player'] ?? '');
    $lower_id = trim($_POST['lower_player'] ?? '');
    $forfeit = $_POST['forfeit'] ?? '';

    if ($upper_id === '' || $lower_id === '') {
        $error = '選手を選択してください';
    } else {

        // 選手IDの存在チェック
        $sql = "
            SELECT p.id, p.name, p.player_number, t.name AS team_name
            FROM players p
            INNER JOIN teams t ON p.team_id = t.id
            INNER JOIN departments d ON t.department_id = d.id
            WHERE d.id = :division_id
              AND p.substitute_flg = 0
              AND p.id IN (:upper_id, :lower_id)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':division_id' => $division_id,
            ':upper_id' => $upper_id,
            ':lower_id' => $lower_id
        ]);

        $found_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($found_players) !== 2) {
            $error = '選択された選手が見つかりません';
        } else {

            // 選手情報を取得
            $player_info = [];
            foreach ($found_players as $p) {
                $player_info[$p['id']] = [
                    'name' => $p['name'],
                    'number' => $p['player_number'],
                    'team' => $p['team_name']
                ];
            }

            /* ===============================
               不戦勝(ボタンを押した側が勝ち)
            =============================== */
            if ($forfeit === 'upper' || $forfeit === 'lower') {

                $_SESSION['forfeit_data'] = [
                    'upper_id' => $upper_id,
                    'lower_id' => $lower_id,
                    'upper_name' => $player_info[$upper_id]['name'],
                    'lower_name' => $player_info[$lower_id]['name'],
                    'upper_number' => $player_info[$upper_id]['number'],
                    'lower_number' => $player_info[$lower_id]['number'],
                    'upper_team' => $player_info[$upper_id]['team'],
                    'lower_team' => $player_info[$lower_id]['team'],
                    'winner' => ($forfeit === 'upper') ? 'A' : 'B',
                    'upper_score' => ($forfeit === 'upper') ? 2 : 0,
                    'lower_score' => ($forfeit === 'lower') ? 2 : 0
                ];

                header('Location: solo-forfeit-confirm.php');
                exit;
            }

            /* ===============================
               通常試合 → 詳細入力へ
            =============================== */
            $_SESSION['player_a_id'] = $upper_id;
            $_SESSION['player_b_id'] = $lower_id;
            $_SESSION['player_a_name'] = $player_info[$upper_id]['name'];
            $_SESSION['player_b_name'] = $player_info[$lower_id]['name'];
            $_SESSION['player_a_number'] = $player_info[$upper_id]['number'];
            $_SESSION['player_b_number'] = $player_info[$lower_id]['number'];
            $_SESSION['player_a_team'] = $player_info[$upper_id]['team'];
            $_SESSION['player_b_team'] = $player_info[$lower_id]['team'];

            header('Location: individual-match-detail.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人戦選手選択</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: auto;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: backgroundMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes backgroundMove {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(50px, 50px);
            }
        }

        .container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: min(2vh, 20px);
            position: relative;
            z-index: 1;
            gap: min(2vh, 15px);
        }

        /* ヘッダー */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: min(3vw, 16px);
            padding: min(2.5vh, 20px) min(3vw, 25px);
            box-shadow:
                0 10px 40px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            animation: slideDown 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-title {
            display: inline-block;
            padding: min(1vh, 8px) min(2.5vw, 18px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: min(2vw, 10px);
            font-size: clamp(12px, 2vh, 16px);
            font-weight: 700;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            margin-bottom: min(1.5vh, 10px);
        }

        .header-main {
            font-size: clamp(14px, 2.5vh, 20px);
            font-weight: 700;
            color: #1f2937;
            line-height: 1.4;
        }

        /* 通知バー */
        .notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            padding: min(1.5vh, 12px) min(3vw, 20px);
            border-radius: min(2.5vw, 12px);
            font-size: clamp(12px, 2vh, 15px);
            font-weight: 600;
            text-align: center;
            border: 2px solid rgba(251, 191, 36, 0.3);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* エラー */
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            padding: min(1.5vh, 12px) min(3vw, 20px);
            border-radius: min(2.5vw, 12px);
            font-size: clamp(12px, 2vh, 15px);
            font-weight: 600;
            text-align: center;
            border: 2px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            animation: shake 0.5s;
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

        /* フォーム */
        form {
            display: flex;
            flex-direction: column;
            gap: min(2vh, 15px);
            flex: 1;
        }

        /* 対戦カード全体 */
        .match-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: min(4vw, 24px);
            padding: min(3vh, 25px) min(3vw, 20px);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: min(2vw, 15px);
            align-items: start;
            animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* 選手カード */
        .player-card {
            display: flex;
            flex-direction: column;
            gap: min(1.5vh, 12px);
        }

        /* 選手ラベル(赤/白) */
        .player-label {
            font-size: clamp(16px, 2.8vh, 22px);
            font-weight: 800;
            text-align: center;
            padding: min(1.2vh, 10px);
            border-radius: min(2vw, 12px);
            letter-spacing: 0.1em;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .player-card.left .player-label {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .player-card.right .player-label {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #1f2937;
        }

        /* 入力ラベル */
        .input-label-small {
            font-size: clamp(11px, 1.8vh, 14px);
            color: #6b7280;
            font-weight: 600;
            text-align: center;
        }

        /* 選手番号入力 */
        .player-number-input {
            width: 100%;
            padding: min(1.8vh, 14px) min(2vw, 16px);
            font-size: clamp(14px, 2.5vh, 18px);
            font-weight: 600;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: min(2vw, 12px);
            background: white;
            color: #1f2937;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .player-number-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow:
                0 0 0 4px rgba(102, 126, 234, 0.15),
                0 4px 12px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .player-number-input::placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        /* 選手選択プルダウン */
        .player-select {
            width: 100%;
            padding: min(1.8vh, 14px) min(2vw, 16px);
            font-size: clamp(13px, 2.2vh, 16px);
            font-weight: 600;
            text-align: center;
            border: 2px solid #e5e7eb;
            border-radius: min(2vw, 12px);
            background: white;
            color: #1f2937;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right min(2vw, 16px) center;
            padding-right: min(6vw, 45px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .player-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow:
                0 0 0 4px rgba(102, 126, 234, 0.15),
                0 4px 12px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        /* 不戦勝ボタン */
        .forfeit-button {
            width: 100%;
            padding: min(1.8vh, 14px);
            font-size: clamp(13px, 2.2vh, 16px);
            font-weight: 700;
            border: 2px solid #e5e7eb;
            border-radius: min(2vw, 12px);
            background: white;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            letter-spacing: 0.05em;
        }

        .forfeit-button:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .forfeit-button.selected {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            border-color: #f59e0b;
            box-shadow:
                0 4px 12px rgba(245, 158, 11, 0.4),
                0 0 0 4px rgba(251, 191, 36, 0.2);
        }

        .forfeit-button:active {
            transform: scale(0.98);
        }

        /* VS区切り */
        .vs-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: min(2vh, 15px) 0;
        }

        .vs-text {
            font-size: clamp(18px, 3.5vh, 28px);
            font-weight: 900;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.1em;
            text-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
        }

        /* アクションボタン */
        .action-buttons {
            display: flex;
            gap: min(2vw, 15px);
            padding: 0 min(2vw, 15px);
        }

        .action-button {
            flex: 1;
            padding: min(2vh, 16px);
            font-size: clamp(15px, 2.5vh, 19px);
            font-weight: 700;
            border: none;
            border-radius: min(2.5vw, 14px);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.05em;
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .action-button:active {
            transform: scale(0.95);
        }

        .confirm-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .confirm-button:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .back-button {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        .back-button:hover {
            background: white;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        /* レスポンシブ: スマホ縦向き */
        @media (max-width: 768px) {
            .match-container {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
                gap: min(2vh, 20px);
            }

            .vs-divider {
                order: 2;
                padding: min(1vh, 10px) 0;
            }

            .player-card.left {
                order: 1;
            }

            .player-card.right {
                order: 3;
            }

            .vs-text {
                font-size: clamp(20px, 4vh, 32px);
            }
        }

        /* 小さい画面の高さ対応 */
        @media (max-height: 700px) {
            .container {
                padding: 1.5vh 2vw;
                gap: 1.5vh;
            }

            .header {
                padding: 1.5vh 3vw;
            }

            .match-container {
                padding: 2vh 2.5vw;
            }

            .player-card {
                gap: 1vh;
            }

            .notice,
            .error {
                padding: 1vh 2.5vw;
            }
        }

        /* 極端に縦長の画面 */
        @media (max-height: 600px) {
            .header-title {
                font-size: clamp(11px, 1.8vh, 14px);
                padding: 6px 14px;
            }

            .header-main {
                font-size: clamp(12px, 2vh, 16px);
            }

            .notice,
            .error {
                font-size: clamp(11px, 1.8vh, 13px);
            }
        }

        /* 小型スマホ */
        @media (max-width: 360px) {
            .action-buttons {
                flex-direction: column;
                gap: 12px;
            }

            .action-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div class="header-title">個人戦</div>
            <div class="header-main">
                <?= htmlspecialchars($info['tournament_name']) ?><br>
                <?= htmlspecialchars($info['division_name']) ?>
            </div>
        </div>

        <div class="notice">
            💡 不戦勝の場合は勝者側の「不戦勝」ボタンを押してください
        </div>

        <?php if ($error): ?>
            <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="forfeit" id="forfeitInput">

            <div class="match-container">

                <!-- 赤側 -->
                <div class="player-card left">
                    <div class="player-label">赤</div>
                    <div class="input-label-small">選手番号</div>
                    <input type="number" class="player-number-input" id="upperPlayerNumber" placeholder="番号を入力" min="1">
                    <div class="input-label-small">または選手を選択</div>
                    <select name="upper_player" class="player-select" id="upperPlayer">
                        <option value="">選手を選択してください</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?= $player['id'] ?>" data-number="<?= (int) $player['player_number'] ?>"
                                <?= (isset($_POST['upper_player']) && $_POST['upper_player'] == $player['id']) || 
                                    (!isset($_POST['upper_player']) && $selected_upper_id == $player['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="forfeit-button" id="upperForfeit">不戦勝</button>
                </div>

                <!-- VS区切り -->
                <div class="vs-divider">
                    <span class="vs-text">VS</span>
                </div>

                <!-- 白側 -->
                <div class="player-card right">
                    <div class="player-label">白</div>
                    <div class="input-label-small">選手番号</div>
                    <input type="number" class="player-number-input" id="lowerPlayerNumber" placeholder="番号を入力" min="1">
                    <div class="input-label-small">または選手を選択</div>
                    <select name="lower_player" class="player-select" id="lowerPlayer">
                        <option value="">選手を選択してください</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?= $player['id'] ?>" data-number="<?= (int) $player['player_number'] ?>"
                                <?= (isset($_POST['lower_player']) && $_POST['lower_player'] == $player['id']) || 
                                    (!isset($_POST['lower_player']) && $selected_lower_id == $player['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="forfeit-button" id="lowerForfeit">不戦勝</button>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="action-button confirm-button">決定</button>
                <button type="button" class="action-button back-button" onclick="location.href='match_input.php?division_id=<?= $division_id ?>'">戻る</button>
            </div>
        </form>
    </div>

    <script>
        // 不戦勝ボタンの処理
        const upperBtn = document.getElementById('upperForfeit');
        const lowerBtn = document.getElementById('lowerForfeit');
        const forfeitInput = document.getElementById('forfeitInput');

        upperBtn.onclick = () => {
            if (upperBtn.classList.contains('selected')) {
                upperBtn.classList.remove('selected');
                forfeitInput.value = '';
            } else {
                upperBtn.classList.add('selected');
                lowerBtn.classList.remove('selected');
                forfeitInput.value = 'upper';
            }
        };

        lowerBtn.onclick = () => {
            if (lowerBtn.classList.contains('selected')) {
                lowerBtn.classList.remove('selected');
                forfeitInput.value = '';
            } else {
                lowerBtn.classList.add('selected');
                upperBtn.classList.remove('selected');
                forfeitInput.value = 'lower';
            }
        };

        // フォーム送信時の処理
        document.querySelector('form').onsubmit = (e) => {
            // 選手が選択されているかチェック
            const upperSelect = document.getElementById('upperPlayer');
            const lowerSelect = document.getElementById('lowerPlayer');

            if (!upperSelect.value || !lowerSelect.value) {
                e.preventDefault();
                alert('両方の選手を選択してください');
                return false;
            }
        };

        // 選手番号入力時の自動選択機能(赤側)
        document.getElementById('upperPlayerNumber').addEventListener('input', function (e) {
            const number = parseInt(e.target.value, 10);
            const select = document.getElementById('upperPlayer');

            if (isNaN(number) || e.target.value === '') {
                select.value = '';
                return;
            }

            let found = false;
            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                const optionNumber = parseInt(option.dataset.number, 10);

                if (!isNaN(optionNumber) && optionNumber === number) {
                    select.value = option.value;
                    found = true;
                    break;
                }
            }

            if (!found) {
                select.value = '';
            }
        });

        // 選手番号入力時の自動選択機能(白側)
        document.getElementById('lowerPlayerNumber').addEventListener('input', function (e) {
            const number = parseInt(e.target.value, 10);
            const select = document.getElementById('lowerPlayer');

            if (isNaN(number) || e.target.value === '') {
                select.value = '';
                return;
            }

            let found = false;
            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                const optionNumber = parseInt(option.dataset.number, 10);

                if (!isNaN(optionNumber) && optionNumber === number) {
                    select.value = option.value;
                    found = true;
                    break;
                }
            }

            if (!found) {
                select.value = '';
            }
        });

        // プルダウン選択時に選手番号欄に反映(赤側)
        document.getElementById('upperPlayer').addEventListener('change', function (e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const numberInput = document.getElementById('upperPlayerNumber');
            if (selectedOption.value === '') {
                numberInput.value = '';
            } else {
                const num = selectedOption.dataset.number;
                numberInput.value = num ? num : '';
            }
        });

        // プルダウン選択時に選手番号欄に反映(白側)
        document.getElementById('lowerPlayer').addEventListener('change', function (e) {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const numberInput = document.getElementById('lowerPlayerNumber');
            if (selectedOption.value === '') {
                numberInput.value = '';
            } else {
                const num = selectedOption.dataset.number;
                numberInput.value = num ? num : '';
            }
        });

        // ページ読み込み時に選手番号欄を初期化（セッションから復元された選手がいる場合）
        document.addEventListener('DOMContentLoaded', function() {
            // 赤側の初期化
            const upperSelect = document.getElementById('upperPlayer');
            const upperNumberInput = document.getElementById('upperPlayerNumber');
            if (upperSelect.value) {
                const selectedOption = upperSelect.options[upperSelect.selectedIndex];
                const num = selectedOption.dataset.number;
                upperNumberInput.value = num ? num : '';
            }

            // 白側の初期化
            const lowerSelect = document.getElementById('lowerPlayer');
            const lowerNumberInput = document.getElementById('lowerPlayerNumber');
            if (lowerSelect.value) {
                const selectedOption = lowerSelect.options[lowerSelect.selectedIndex];
                const num = selectedOption.dataset.number;
                lowerNumberInput.value = num ? num : '';
            }
        });
    </script>

</body>

</html>