<?php
/* ---------- モックデータ（DB代用） ---------- */

// 大会・部門情報のモック
$mock_divisions = [
    1 => [
        'tournament_name' => 'テスト大会2025',
        'division_name'   => 'A部門',
        'distinction'     => 2,
    ],
    2 => [
        'tournament_name' => 'テスト大会2025',
        'division_name'   => 'B部門',
        'distinction'     => 1,
    ],
];

// 選手一覧のモック
$mock_players = [
    1 => [
        ['id' => '101', 'name' => '田中太郎',   'player_number' => '1',  'team_name' => 'Aチーム'],
        ['id' => '102', 'name' => '鈴木花子',   'player_number' => '2',  'team_name' => 'Aチーム'],
        ['id' => '103', 'name' => '佐藤次郎',   'player_number' => '3',  'team_name' => 'Bチーム'],
        ['id' => '104', 'name' => '高橋美子',   'player_number' => '4',  'team_name' => 'Bチーム'],
        ['id' => '105', 'name' => '伊藤健一',   'player_number' => '5',  'team_name' => 'Cチーム'],
        ['id' => '106', 'name' => '渡辺恵子',   'player_number' => '6',  'team_name' => 'Cチーム'],
    ],
    2 => [
        ['id' => '201', 'name' => '山田一郎',   'player_number' => '1',  'team_name' => 'Dチーム'],
        ['id' => '202', 'name' => '中村さくら',  'player_number' => '2',  'team_name' => 'Dチーム'],
        ['id' => '203', 'name' => '小林雄太',   'player_number' => '3',  'team_name' => 'Eチーム'],
    ],
];


/* ---------- セッション & 変数の準備 ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 基本セッション変数の初期化
if (!isset($_SESSION['tournament_id'])) {
    $_SESSION['tournament_id'] = 1;
}
if (!isset($_SESSION['division_id'])) {
    $_SESSION['division_id'] = 1;
}
if (!isset($_SESSION['match_number'])) {
    $_SESSION['match_number'] = '10';
}

$tournament_id = (int)$_SESSION['tournament_id'];
$division_id   = (int)$_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];

// 大会・部門情報取得（モック）
$info = $mock_divisions[$division_id] ?? null;
if (!$info) {
    exit('部門情報が取得できません');
}

// 選手一覧取得（モック）
$players = $mock_players[$division_id] ?? [];

$error = '';
$show_completion = false;
$forfeit_winner_name = '';

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    // 完了画面フラグをクリア
    unset($_SESSION['show_forfeit_completion']);
    unset($_SESSION['forfeit_winner_name']);
    unset($_SESSION['forfeit_data']);
    
    // 明示的に通常画面へリダイレクト（GETパラメータなし）
    header('Location: ' . strtok($_SERVER['PHP_SELF'], '?'));
    exit;
}

/* ===============================
   完了画面の表示判定
   
   重要: GETパラメータで明示的に完了画面を表示する指示がある場合のみ表示
   これにより、初回アクセス時やブラウザバック時に完了画面が表示されることを防ぐ
=============================== */
if (isset($_GET['completed']) && $_GET['completed'] === '1' 
    && isset($_SESSION['show_forfeit_completion']) 
    && $_SESSION['show_forfeit_completion'] === true) {
    $show_completion = true;
    $forfeit_winner_name = $_SESSION['forfeit_winner_name'] ?? '';
}

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $upper_id = trim($_POST['upper_player'] ?? '');
    $lower_id = trim($_POST['lower_player'] ?? '');
    $forfeit  = $_POST['forfeit'] ?? '';

    if ($upper_id === '' || $lower_id === '') {
        $error = '選手を選択してください';
    } else {

        // 選手IDの存在チェック（モック配列で検索）
        $found_players = [];
        foreach ($players as $p) {
            if ($p['id'] === $upper_id || $p['id'] === $lower_id) {
                $found_players[] = [
                    'id'            => $p['id'],
                    'name'          => $p['name'],
                    'player_number' => $p['player_number'],
                ];
            }
        }

        if (count($found_players) !== 2) {
            $error = '選択された選手が見つかりません';
        } else {

            $player_info = [];
            foreach ($found_players as $p) {
                $player_info[$p['id']] = [
                    'name'   => $p['name'],
                    'number' => $p['player_number'],
                ];
            }

            /* ===============================
               不戦勝（ボタンを押した側が勝ち）
            =============================== */
            if ($forfeit === 'upper' || $forfeit === 'lower') {

                // 不戦勝データを保存（実際のシステムではDBに保存）
                $_SESSION['forfeit_data'] = [
                    'upper_id'     => $upper_id,
                    'lower_id'     => $lower_id,
                    'upper_name'   => $player_info[$upper_id]['name'],
                    'lower_name'   => $player_info[$lower_id]['name'],
                    'upper_number' => $player_info[$upper_id]['number'],
                    'lower_number' => $player_info[$lower_id]['number'],
                    'winner'       => ($forfeit === 'upper') ? 'A' : 'B',
                    'upper_score'  => ($forfeit === 'upper') ? 2 : 0,
                    'lower_score'  => ($forfeit === 'lower') ? 2 : 0,
                ];

                // 完了画面フラグを設定
                $_SESSION['show_forfeit_completion'] = true;
                $_SESSION['forfeit_winner_name'] = ($forfeit === 'upper') 
                    ? $player_info[$upper_id]['name'] 
                    : $player_info[$lower_id]['name'];

                // リダイレクトして完了画面を表示（GETパラメータで明示的に指定）
                header('Location: ' . $_SERVER['PHP_SELF'] . '?completed=1');
                exit;
            }
            /* ===============================
               通常試合 → 詳細入力へ
            =============================== */
            else {
                $_SESSION['player_a_id']     = $upper_id;
                $_SESSION['player_b_id']     = $lower_id;
                $_SESSION['player_a_name']   = $player_info[$upper_id]['name'];
                $_SESSION['player_b_name']   = $player_info[$lower_id]['name'];
                $_SESSION['player_a_number'] = $player_info[$upper_id]['number'];
                $_SESSION['player_b_number'] = $player_info[$lower_id]['number'];

                header('Location: individual-match-detail.php');
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
<title>個人戦選手選択</title>
<style>
    /* ===== リセット & ベース ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        height: 100%;
        overflow-x: hidden;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100%;
        position: relative;
    }

    /* ===== 背景ドットパターン ===== */
    body::before {
        content: '';
        position: fixed;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 50px 50px;
        animation: backgroundMove 20s linear infinite;
        pointer-events: none;
        z-index: 0;
    }

    @keyframes backgroundMove {
        0%   { transform: translate(0, 0); }
        100% { transform: translate(50px, 50px); }
    }

    /* ===== コンテナ ===== */
    .container {
        position: relative;
        z-index: 1;
        width: 100%;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: min(3vh, 20px);
    }

    /* ===== ヘッダー ===== */
    .header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: min(3vw, 16px);
        padding: min(2.5vh, 18px) min(3vw, 25px);
        box-shadow:
            0 10px 40px rgba(0, 0, 0, 0.15),
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        margin-bottom: min(2vh, 15px);
        animation: slideDown 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .header-content {
        display: flex;
        flex-wrap: wrap;
        gap: min(2vw, 12px);
        align-items: center;
        font-size: clamp(14px, 2.5vh, 20px);
        font-weight: 700;
        line-height: 1.3;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: min(1.2vh, 8px) min(2.5vw, 18px);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: min(2vw, 10px);
        font-size: clamp(12px, 2vh, 16px);
        font-weight: 700;
        letter-spacing: 0.05em;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        white-space: nowrap;
    }

    .header-text {
        color: #1f2937;
        white-space: nowrap;
    }

    /* ===== メインコンテンツ ===== */
    .main-content {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        border-radius: min(4vw, 24px);
        padding: min(5vh, 40px) min(5vw, 35px) min(4vh, 30px);
        box-shadow:
            0 20px 60px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        animation: fadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to   { opacity: 1; transform: scale(1); }
    }

    /* ===== 見出し ===== */
    h2 {
        font-size: clamp(20px, 4vh, 28px);
        font-weight: 800;
        margin-bottom: min(3vh, 24px);
        text-align: center;
        line-height: 1.3;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: 0.02em;
    }

    /* ===== アドバイス通知 ===== */
    .notice {
        text-align: center;
        font-size: clamp(13px, 2vh, 15px);
        color: #6b7280;
        margin-bottom: min(3vh, 24px);
        background: rgba(102, 126, 234, 0.08);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: min(2vw, 10px);
        padding: min(1.5vh, 10px) min(3vw, 18px);
        width: 100%;
        max-width: 700px;
    }

    /* ===== フォーム ===== */
    form {
        width: 100%;
        max-width: 700px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* ===== エラー ===== */
    .error {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        padding: min(1.5vh, 10px) min(2.5vw, 16px);
        border-radius: min(2vw, 10px);
        margin-bottom: min(2vh, 16px);
        font-size: clamp(13px, 2vh, 15px);
        font-weight: 600;
        text-align: center;
        border: 2px solid rgba(239, 68, 68, 0.3);
        animation: shake 0.5s;
        width: 100%;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25%      { transform: translateX(-10px); }
        75%      { transform: translateX(10px); }
    }

    /* ===== 対戦レイアウト ===== */
    .match-row {
        display: flex;
        gap: min(3vw, 24px);
        justify-content: center;
        align-items: flex-start;
        width: 100%;
        margin-bottom: min(4vh, 32px);
    }

    .player-section {
        flex: 1;
        max-width: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: min(1.5vh, 10px);
    }

    /* ===== 赤・白ラベル ===== */
    .player-label {
        font-size: clamp(18px, 3vh, 24px);
        font-weight: 800;
        padding: min(0.8vh, 6px) min(3vw, 22px);
        border-radius: min(2vw, 10px);
        color: white;
        letter-spacing: 0.1em;
    }

    .player-label.red {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    }

    .player-label.white {
        background: linear-gradient(135deg, #e5e7eb, #d1d5db);
        color: #374151;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    /* ===== 入力ラベル ===== */
    .input-label-small {
        font-size: clamp(12px, 1.8vh, 14px);
        color: #6b7280;
        font-weight: 600;
        margin-top: min(1vh, 6px);
        margin-bottom: 2px;
    }

    /* ===== テキスト入力・セレクト ===== */
    input[type="text"],
    select {
        width: 100%;
        padding: min(2vh, 14px) min(3vw, 18px);
        font-size: clamp(15px, 2.5vh, 18px);
        font-weight: 600;
        text-align: center;
        border: 3px solid transparent;
        border-radius: min(3vw, 14px);
        outline: none;
        background: linear-gradient(white, white) padding-box,
                    linear-gradient(135deg, #667eea, #764ba2) border-box;
        box-shadow:
            0 6px 20px rgba(102, 126, 234, 0.12),
            0 0 0 1px rgba(255, 255, 255, 0.8) inset;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        color: #1f2937;
    }

    input[type="text"]:focus,
    select:focus {
        box-shadow:
            0 8px 28px rgba(102, 126, 234, 0.25),
            0 0 0 1px rgba(255, 255, 255, 0.9) inset;
        transform: translateY(-2px);
    }

    input[type="text"]::placeholder {
        color: #9ca3af;
        font-weight: 500;
    }

    select {
        cursor: pointer;
        appearance: none;
        background-image:
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E"),
            linear-gradient(135deg, #667eea, #764ba2);
        background-repeat: no-repeat, no-repeat;
        background-position: right 16px center, padding-box;
        background-origin: content-box, border-box;
        background-clip: content-box, border-box;
        padding-right: 42px;
    }

    select option {
        text-align: left;
    }

    /* ===== VS ===== */
    .vs-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        align-self: center;
        flex-shrink: 0;
        margin-top: min(4vh, 38px);
    }

    .vs-text {
        font-size: clamp(22px, 4vh, 32px);
        font-weight: 900;
        color: white;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        letter-spacing: 0.08em;
    }

    /* ===== 不戦勝ボタン ===== */
    .forfeit-button {
        margin-top: min(1.5vh, 10px);
        padding: min(1.2vh, 10px) min(4vw, 24px);
        font-size: clamp(14px, 2.2vh, 16px);
        font-weight: 700;
        background: rgba(255, 255, 255, 0.95);
        color: #667eea;
        border: 2px solid rgba(102, 126, 234, 0.35);
        border-radius: min(2vw, 10px);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        letter-spacing: 0.05em;
        position: relative;
        overflow: hidden;
    }

    .forfeit-button::before {
        content: '';
        position: absolute;
        top: 50%; left: 50%;
        width: 0; height: 0;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.15);
        transform: translate(-50%, -50%);
        transition: width 0.5s, height 0.5s;
    }

    .forfeit-button:hover::before {
        width: 200px;
        height: 200px;
    }

    .forfeit-button:hover {
        border-color: #667eea;
        box-shadow: 0 4px 14px rgba(102, 126, 234, 0.25);
        transform: translateY(-2px);
    }

    .forfeit-button.selected {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
    }

    .forfeit-button.selected::before {
        background: rgba(255, 255, 255, 0.2);
    }

    /* ===== アクションボタン ===== */
    .action-buttons {
        display: flex;
        gap: min(3vw, 20px);
        justify-content: center;
        width: 100%;
        margin-top: min(2vh, 12px);
    }

    .action-button {
        flex: 1;
        max-width: 180px;
        padding: min(2vh, 14px) min(4vw, 28px);
        font-size: clamp(16px, 2.5vh, 18px);
        font-weight: 700;
        border: none;
        border-radius: min(2.5vw, 14px);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .action-button::before {
        content: '';
        position: absolute;
        top: 50%; left: 50%;
        width: 0; height: 0;
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

    /* 決定ボタン */
    .confirm-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
    }

    .confirm-button:hover {
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }

    /* 戻る ボタン */
    .back-button {
        background: rgba(255, 255, 255, 0.95);
        color: #667eea;
        border: 2px solid rgba(102, 126, 234, 0.3);
    }

    .back-button:hover {
        background: #fff;
        border-color: #667eea;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        transform: translateY(-2px);
    }

    /* ===== 完了画面 ===== */
    .completion-screen {
        width: 100%;
        max-width: 600px;
        text-align: center;
        animation: fadeIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .completion-icon {
        font-size: clamp(60px, 10vh, 100px);
        margin-bottom: min(3vh, 24px);
        animation: bounce 1s ease-in-out;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .completion-title {
        font-size: clamp(24px, 4.5vh, 32px);
        font-weight: 800;
        color: #10b981;
        margin-bottom: min(2vh, 16px);
    }

    .completion-message {
        font-size: clamp(16px, 2.5vh, 20px);
        color: #374151;
        margin-bottom: min(1vh, 8px);
        line-height: 1.6;
    }

    .completion-detail {
        font-size: clamp(14px, 2.2vh, 18px);
        color: #6b7280;
        margin-bottom: min(4vh, 32px);
    }

    .completion-winner {
        font-weight: 700;
        color: #667eea;
    }

    .completion-buttons {
        display: flex;
        flex-direction: column;
        gap: min(2vh, 16px);
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }

    .completion-button {
        width: 100%;
        padding: min(2.5vh, 16px) min(4vw, 28px);
        font-size: clamp(16px, 2.5vh, 18px);
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

    .completion-button::before {
        content: '';
        position: absolute;
        top: 50%; left: 50%;
        width: 0; height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .completion-button:hover::before {
        width: 400px;
        height: 400px;
    }

    .completion-button.continue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .completion-button.continue:hover {
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        transform: translateY(-2px);
    }

    .completion-button.back {
        background: rgba(255, 255, 255, 0.95);
        color: #667eea;
        border: 2px solid rgba(102, 126, 234, 0.3);
    }

    .completion-button.back:hover {
        background: #fff;
        border-color: #667eea;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        transform: translateY(-2px);
    }

    /* ===== レスポンシブ: スマホ縦（768px以下） ===== */
    @media (max-width: 768px) {
        .match-row {
            flex-direction: column;
            align-items: center;
            gap: min(2vh, 14px);
        }

        .vs-divider {
            margin-top: 0;
        }

        .player-section {
            max-width: 100%;
            width: 100%;
        }
    }

    /* ===== レスポンシブ: 縦長画面 ===== */
    @media (max-height: 700px) {
        .container {
            padding: 2vh 2vw;
        }

        .header {
            padding: 1.5vh 3vw;
            margin-bottom: 1.5vh;
        }

        .main-content {
            padding: 3vh 4vw 2.5vh;
        }

        h2 {
            font-size: clamp(18px, 3.5vh, 22px);
            margin-bottom: 2vh;
        }

        .notice {
            margin-bottom: 2vh;
            padding: 1vh 2vw;
        }

        .match-row {
            margin-bottom: 2vh;
        }

        input[type="text"],
        select {
            padding: 1.5vh 3vw;
            font-size: clamp(14px, 2.2vh, 16px);
        }

        .forfeit-button {
            padding: 1vh 3vw;
            font-size: clamp(13px, 2vh, 15px);
        }

        .action-button {
            padding: 1.5vh 3vw;
            font-size: clamp(14px, 2.2vh, 16px);
        }

        .completion-icon {
            font-size: clamp(50px, 8vh, 80px);
            margin-bottom: 2vh;
        }

        .completion-title {
            font-size: clamp(20px, 3.5vh, 26px);
            margin-bottom: 1.5vh;
        }
    }

    /* ===== レスポンシブ: 極端に縦長 ===== */
    @media (max-height: 600px) {
        h2 {
            font-size: clamp(16px, 3vh, 20px);
            margin-bottom: 1.5vh;
        }

        .notice {
            font-size: clamp(11px, 1.8vh, 13px);
            margin-bottom: 1.5vh;
        }

        .error {
            margin-bottom: 1vh;
            font-size: clamp(11px, 1.8vh, 13px);
        }
    }

    /* ===== レスポンシブ: 横長画面（タブレット横向き） ===== */
    @media (min-aspect-ratio: 4/3) and (max-height: 800px) {
        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header-content {
            font-size: clamp(14px, 2.2vh, 18px);
        }

        h2 {
            font-size: clamp(20px, 3.5vh, 26px);
        }
    }

    /* ===== レスポンシブ: 小型スマホ ===== */
    @media (max-width: 360px) {
        .header-content {
            gap: 8px;
        }

        .badge {
            font-size: clamp(11px, 1.8vh, 14px);
            padding: 6px 12px;
        }

        .header-text {
            font-size: clamp(12px, 2.2vh, 16px);
        }

        .action-buttons {
            gap: 12px;
        }
    }
</style>
</head>

<body>
<div class="container">

    <!-- ヘッダー -->
    <div class="header">
        <div class="header-content">
            <span class="badge"><?php echo ((int)$info['distinction'] === 2) ? '個人戦' : '団体戦'; ?></span>
            <span class="header-text"><?php echo htmlspecialchars($info['tournament_name']); ?></span>
            <span class="header-text"><?php echo htmlspecialchars($info['division_name']); ?></span>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <?php if ($show_completion): ?>
            <!-- 完了画面 -->
            <div class="completion-screen">
                <div class="completion-icon">✅</div>
                <div class="completion-title">不戦勝の操作は終わりです</div>
                <div class="completion-message">
                    勝者：<span class="completion-winner"><?php echo htmlspecialchars($forfeit_winner_name); ?></span>
                </div>
                <div class="completion-detail">
                    試合結果が記録されました
                </div>
                <div class="completion-buttons">
                    <button type="button" class="completion-button continue" onclick="location.href='?reset=1'">
                        続けて入力する
                    </button>
                    <button type="button" class="completion-button back" onclick="location.href='../../index.php'">
                        最初に戻る
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- 選手選択画面 -->
            <h2>選手を選択してください</h2>

            <div class="notice">
                💡 不戦勝の場合は勝者側の「不戦勝」ボタンを押してください
            </div>

            <?php if ($error): ?>
                <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="forfeit" id="forfeitInput">

                <div class="match-row">
                    <!-- 赤側 -->
                    <div class="player-section">
                        <div class="player-label red">赤</div>

                        <div class="input-label-small">選手番号</div>
                        <input type="text" id="upperPlayerNumber" placeholder="番号を入力">

                        <div class="input-label-small">または選手を選択</div>
                        <select name="upper_player" id="upperPlayer" required>
                            <option value="">選手を選択してください</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>" data-number="<?= htmlspecialchars($player['player_number']) ?>" <?= (isset($_POST['upper_player']) && $_POST['upper_player'] == $player['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="button" class="forfeit-button" id="upperForfeit">不戦勝</button>
                    </div>

                    <!-- VS -->
                    <div class="vs-divider">
                        <span class="vs-text">VS</span>
                    </div>

                    <!-- 白側 -->
                    <div class="player-section">
                        <div class="player-label white">白</div>

                        <div class="input-label-small">選手番号</div>
                        <input type="text" id="lowerPlayerNumber" placeholder="番号を入力">

                        <div class="input-label-small">または選手を選択</div>
                        <select name="lower_player" id="lowerPlayer" required>
                            <option value="">選手を選択してください</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>" data-number="<?= htmlspecialchars($player['player_number']) ?>" <?= (isset($_POST['lower_player']) && $_POST['lower_player'] == $player['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($player['name']) ?> (<?= htmlspecialchars($player['team_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="button" class="forfeit-button" id="lowerForfeit">不戦勝</button>
                    </div>
                </div>

                <!-- アクションボタン -->
                <div class="action-buttons">
                    <button type="button" class="action-button back-button" onclick="goBack()">戻る</button>
                    <button type="submit" class="action-button confirm-button">決定</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

</div>

<script>
<?php if (!$show_completion): ?>
// 戻るボタンの処理
function goBack() {
    location.href = 'match_input.php';
}

const upperBtn = document.getElementById('upperForfeit');
const lowerBtn = document.getElementById('lowerForfeit');
const forfeitInput = document.getElementById('forfeitInput');

upperBtn.onclick = () => {
    if (upperBtn.classList.contains('selected')) {
        upperBtn.classList.remove('selected');
    } else {
        upperBtn.classList.add('selected');
        lowerBtn.classList.remove('selected');
    }
};

lowerBtn.onclick = () => {
    if (lowerBtn.classList.contains('selected')) {
        lowerBtn.classList.remove('selected');
    } else {
        lowerBtn.classList.add('selected');
        upperBtn.classList.remove('selected');
    }
};

document.querySelector('form').onsubmit = (e) => {
    if (upperBtn.classList.contains('selected')) {
        forfeitInput.value = 'upper';
    } else if (lowerBtn.classList.contains('selected')) {
        forfeitInput.value = 'lower';
    } else {
        forfeitInput.value = '';
    }
};

// 選手番号入力時の自動選択
document.getElementById('upperPlayerNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('upperPlayer');
    if (number === '') return;
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

document.getElementById('lowerPlayerNumber').addEventListener('input', function(e) {
    const number = e.target.value.trim();
    const select = document.getElementById('lowerPlayer');
    if (number === '') return;
    for (let option of select.options) {
        if (option.dataset.number && option.dataset.number === number) {
            select.value = option.value;
            return;
        }
    }
});

// プルダウン選択時に番号欄に反映
document.getElementById('upperPlayer').addEventListener('change', function(e) {
    const sel = e.target.options[e.target.selectedIndex];
    document.getElementById('upperPlayerNumber').value = sel.dataset.number || '';
});

document.getElementById('lowerPlayer').addEventListener('change', function(e) {
    const sel = e.target.options[e.target.selectedIndex];
    document.getElementById('lowerPlayerNumber').value = sel.dataset.number || '';
});
<?php endif; ?>
</script>

</body>
</html>