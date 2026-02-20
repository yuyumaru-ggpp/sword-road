<?php
session_start();
/* ===============================
   DB接続
=============================== */
require_once '../../connect/db_connect.php';

/* ===============================
   POST & セッションチェック
=============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: match_input.php');
    exit;
}

if (!isset($_SESSION['match_input']) || !is_array($_SESSION['match_input'])) {
    header('Location: match_input.php');
    exit;
}

$data = $_SESSION['match_input'];



/* ===============================
   試合結果を解析
=============================== */
// 技データを取得
$allScores = $data['scores'] ?? [];
$upperSelected = $data['upper']['selected'] ?? [];
$lowerSelected = $data['lower']['selected'] ?? [];

$techniques = [];
$winners = [];
$invalidMarks = ['▼', '▲', '不'];

// 上段選手（red）の技を抽出
foreach ($upperSelected as $index) {
    if (isset($allScores[$index])) {
        $score = $allScores[$index];
        if (!in_array($score, $invalidMarks, true) && $score !== '') {
            $techniques[] = $score;
            $winners[] = 'red';
        }
    }
}

// 下段選手（white）の技を抽出
foreach ($lowerSelected as $index) {
    if (isset($allScores[$index])) {
        $score = $allScores[$index];
        if (!in_array($score, $invalidMarks, true) && $score !== '') {
            $techniques[] = $score;
            $winners[] = 'white';
        }
    }
}

// 技と勝者を3つまでに制限
$firstTechnique = $techniques[0] ?? null;
$firstWinner = $winners[0] ?? null;
$secondTechnique = $techniques[1] ?? null;
$secondWinner = $winners[1] ?? null;
$thirdTechnique = $techniques[2] ?? null;
$thirdWinner = $winners[2] ?? null;

// 判定
$judgement = null;
if (isset($data['special'])) {
    switch ($data['special']) {
        case 'nihon':
            $judgement = '二本勝';
            break;
        case 'ippon':
            $judgement = '一本勝';
            break;
        case 'extend':
            $judgement = '延長';
            break;
        case 'hantei':
            $judgement = '判定';
            break;
        case 'draw':
            $judgement = '引分け';
            break;
    }
}

// 最終勝者を計算
$upperPoints = count($upperSelected);
$lowerPoints = count($lowerSelected);

if ($upperPoints > $lowerPoints) {
    $finalWinner = 'red';
} elseif ($lowerPoints > $upperPoints) {
    $finalWinner = 'white';
} else {
    // 同点の場合
    $finalWinner = null;

    // 判定勝ちの確認
    if (isset($data['upper']['decision']) && $data['upper']['decision'] === true) {
        $finalWinner = 'red';
    } elseif (isset($data['lower']['decision']) && $data['lower']['decision'] === true) {
        $finalWinner = 'white';
    }
}

/* ===============================
   individual_match_numを決定
   セッションの試合番号を優先使用し、
   ない場合は match_field 単位で自動採番
=============================== */
if (isset($_SESSION['match_number']) && $_SESSION['match_number'] !== '') {
    // match_input.phpでユーザーが入力した試合番号をそのまま使用
    $nextNum = $_SESSION['match_number'];
} else {
    // フォールバック：match_field単位でMAX+1を採番
    try {
        $countSql = "
            SELECT COALESCE(MAX(individual_match_num), 0) + 1 as next_num
            FROM individual_matches
            WHERE department_id = :department_id
            AND match_field = :match_field
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            ':department_id' => $_SESSION['division_id'] ?? null,
            ':match_field'   => $_SESSION['match_field'] ?? 1,
        ]);
        $nextNum = $countStmt->fetchColumn();
        if ($nextNum === false) {
            $nextNum = 1;
        }
    } catch (PDOException $e) {
        error_log('Failed to get next individual_match_num: ' . $e->getMessage());
        http_response_code(500);
        echo '試合番号の取得に失敗しました。';
        exit;
    }
}

/* ===============================
   試合結果 INSERT
=============================== */
$sql = "
INSERT INTO individual_matches (
    department_id,
    match_field,
    individual_match_num,
    player_a_id,
    player_b_id,
    started_at,
    ended_at,
    first_technique,
    first_winner,
    second_technique,
    second_winner,
    third_technique,
    third_winner,
    judgement,
    final_winner
) VALUES (
    :department_id,
    :match_field,
    :individual_match_num,
    :player_a_id,
    :player_b_id,
    NOW(),
    NOW(),
    :first_technique,
    :first_winner,
    :second_technique,
    :second_winner,
    :third_technique,
    :third_winner,
    :judgement,
    :final_winner
)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':department_id' => $_SESSION['division_id'] ?? null,
        ':match_field' => $_SESSION['match_field'] ?? 1,
        ':individual_match_num' => $nextNum,
        ':player_a_id' => $_SESSION['player_a_id'] ?? null,
        ':player_b_id' => $_SESSION['player_b_id'] ?? null,
        ':first_technique' => $firstTechnique,
        ':first_winner' => $firstWinner,
        ':second_technique' => $secondTechnique,
        ':second_winner' => $secondWinner,
        ':third_technique' => $thirdTechnique,
        ':third_winner' => $thirdWinner,
        ':judgement' => $judgement,
        ':final_winner' => $finalWinner
    ]);
} catch (PDOException $e) {
    error_log('Insert failed: ' . $e->getMessage());
    error_log('SQL State: ' . $e->getCode());
    http_response_code(500);
    echo '試合結果の登録に失敗しました。<br>';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}

/* ===============================
   セッションクリア
=============================== */
$division_id = $_SESSION['division_id'] ?? null;

unset($_SESSION['match_input']);
unset($_SESSION['match_number']);
unset($_SESSION['player_a_id']);
unset($_SESSION['player_b_id']);
unset($_SESSION['player_a_name']);
unset($_SESSION['player_b_name']);
unset($_SESSION['player_a_number']);
unset($_SESSION['player_b_number']);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
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
            max-width: 900px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .success-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 4rem 3rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.5) inset;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 10px 30px rgba(16, 185, 129, 0.4),
                0 0 0 10px rgba(16, 185, 129, 0.1);
            animation: checkmark 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }

            50% {
                transform: scale(1.2) rotate(5deg);
            }

            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .success-icon::before {
            content: '✓';
            color: #fff;
            font-size: 4rem;
            font-weight: bold;
            line-height: 1;
        }

        .success-message {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: 0.02em;
        }

        .success-subtitle {
            font-size: 1.125rem;
            color: #6b7280;
            margin-top: 0.75rem;
        }

        .button-container {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 1.25rem 3rem;
            font-size: 1.125rem;
            font-weight: 600;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
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

        .action-button:first-child {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .action-button:first-child:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .action-button:last-child {
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }

        .action-button:last-child:hover {
            background: #fff;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .action-button span {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .success-box {
                padding: 3rem 2rem;
                border-radius: 24px;
            }

            .success-icon {
                width: 80px;
                height: 80px;
            }

            .success-icon::before {
                font-size: 3rem;
            }

            .success-message {
                font-size: 2rem;
            }

            .button-container {
                flex-direction: column;
                gap: 1rem;
            }

            .action-button {
                width: 100%;
                padding: 1rem 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="success-box">
            <div class="success-icon"></div>
            <div class="success-message">送信完了</div>
            <div class="success-subtitle">試合結果が正常に記録されました</div>
        </div>

        <div class="button-container">
            <button class="action-button"
                onclick="location.href='match_input.php?division_id=<?= $_SESSION['division_id'] ?>'">
                <span>連続で入力する</span>
            </button>
            <button class="action-button" onclick="location.href='../index.php'">
                <span>部門選択画面に戻る</span>
            </button>
        </div>
    </div>
</body>

</html>