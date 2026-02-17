<?php
require_once 'team_db.php';

/* ===============================
   セッションチェック
=============================== */
if (
    !isset(
    $_SESSION['tournament_id'],
    $_SESSION['division_id'],
    $_SESSION['match_number'],
    $_SESSION['team_red_id'],
    $_SESSION['team_white_id']
)
) {
    header('Location: match_input.php');
    exit;
}

$tournament_id = $_SESSION['tournament_id'];
$division_id = $_SESSION['division_id'];
$match_number = $_SESSION['match_number'];
$team_red_id = $_SESSION['team_red_id'];
$team_white_id = $_SESSION['team_white_id'];


/* ===============================
   大会・部門・チーム情報取得
=============================== */
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

if (!$info) {
    exit('試合情報が取得できません');
}

// チーム番号とチーム名取得
$sql = "SELECT team_number, name FROM teams WHERE id = :team_id";
$stmt = $pdo->prepare($sql);

$stmt->execute([':team_id' => $team_red_id]);
$team_red = $stmt->fetch(PDO::FETCH_ASSOC);
$team_red_number = $team_red['team_number'];
$team_red_name = $team_red['name'];

$stmt->execute([':team_id' => $team_white_id]);
$team_white = $stmt->fetch(PDO::FETCH_ASSOC);
$team_white_number = $team_white['team_number'];
$team_white_name = $team_white['name'];

// 各チームの選手一覧を取得
$sql = "
    SELECT
        p.id,
        p.name,
        p.player_number
    FROM players p
    WHERE p.team_id = :team_id
      AND p.substitute_flg = 0
    ORDER BY p.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':team_id' => $team_red_id]);
$red_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute([':team_id' => $team_white_id]);
$white_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ordersテーブルから既存のオーダーを取得
$sql = "
    SELECT
        o.player_id,
        o.order_detail,
        p.name,
        p.player_number
    FROM orders o
    INNER JOIN players p ON o.player_id = p.id
    WHERE o.team_id = :team_id
      AND o.order_detail BETWEEN 1 AND 5
    ORDER BY o.order_detail
";

$stmt = $pdo->prepare($sql);

// 赤チームのオーダー
$stmt->execute([':team_id' => $team_red_id]);
$red_order_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$red_initial_order = [];
$red_initial_order_info = []; // 名前と番号を保持
foreach ($red_order_from_db as $order) {
    $red_initial_order[$order['order_detail']] = $order['player_id'];
    $red_initial_order_info[$order['order_detail']] = [
        'name' => $order['name'],
        'number' => $order['player_number']
    ];
}

// 白チームのオーダー
$stmt->execute([':team_id' => $team_white_id]);
$white_order_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$white_initial_order = [];
$white_initial_order_info = []; // 名前と番号を保持
foreach ($white_order_from_db as $order) {
    $white_initial_order[$order['order_detail']] = $order['player_id'];
    $white_initial_order_info[$order['order_detail']] = [
        'name' => $order['name'],
        'number' => $order['player_number']
    ];
}

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // オーダー情報をセッションに保存（空文字をnullに変換）
    $_SESSION['team_red_order'] = [
        '先鋒' => !empty($_POST['red_senpo']) ? (int) $_POST['red_senpo'] : null,
        '次鋒' => !empty($_POST['red_jiho']) ? (int) $_POST['red_jiho'] : null,
        '中堅' => !empty($_POST['red_chuken']) ? (int) $_POST['red_chuken'] : null,
        '副将' => !empty($_POST['red_fukusho']) ? (int) $_POST['red_fukusho'] : null,
        '大将' => !empty($_POST['red_taisho']) ? (int) $_POST['red_taisho'] : null
    ];

    $_SESSION['team_white_order'] = [
        '先鋒' => !empty($_POST['white_senpo']) ? (int) $_POST['white_senpo'] : null,
        '次鋒' => !empty($_POST['white_jiho']) ? (int) $_POST['white_jiho'] : null,
        '中堅' => !empty($_POST['white_chuken']) ? (int) $_POST['white_chuken'] : null,
        '副将' => !empty($_POST['white_fukusho']) ? (int) $_POST['white_fukusho'] : null,
        '大将' => !empty($_POST['white_taisho']) ? (int) $_POST['white_taisho'] : null
    ];

    // チーム名をセッションに保存
    $_SESSION['team_red_name'] = $team_red_name;
    $_SESSION['team_white_name'] = $team_white_name;

    // match_resultsを初期化（重要！）
    $_SESSION['match_results'] = [];

    header('Location: team-match-senpo.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手登録・団体戦</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            min-height: 100vh;
        }

        .outer-container {
            width: 100%;
            max-width: 1100px;
            height: calc(100vh - 16px);
            max-height: 900px;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
            height: 100%;
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
            padding: 15px 20px;
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
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .note {
            text-align: center;
            padding: 12px 20px;
            background: #fff3cd;
            color: #856404;
            font-size: 13px;
            border-bottom: 1px solid #ffeaa7;
            flex-shrink: 0;
        }

        .content-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .teams-wrapper {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .team-section {
            flex: 1;
            max-width: 450px;
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .team-header {
            padding: 12px;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            color: white;
        }

        .team-header.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .team-header.white {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        .position-row {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        .position-row:last-child {
            border-bottom: none;
        }

        .position-label {
            min-width: 60px;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
        }

        .player-display {
            flex: 1;
            font-size: 14px;
            color: #1f2937;
        }

        .player-number {
            color: #6b7280;
            font-size: 13px;
        }

        .buttons {
            padding: 20px;
            background: #f9fafb;
            display: flex;
            gap: 15px;
            justify-content: center;
            border-top: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .btn {
            padding: 12px 40px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-back:hover {
            background: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .team-info {
            padding: 12px 16px;
            background: white;
            border-bottom: 2px solid #e5e7eb;
            text-align: center;
            font-weight: 600;
            font-size: 15px;
            color: #1f2937;
        }

        /* レスポンシブ対応 */
        @media (max-width: 900px) {
            .teams-wrapper {
                flex-direction: column;
                gap: 15px;
            }

            .team-section {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 0;
            }

            .outer-container {
                height: 100vh;
                max-height: none;
            }

            .container {
                border-radius: 0;
            }

            .header {
                padding: 12px 15px;
            }

            .header-badge {
                font-size: 12px;
                padding: 5px 12px;
            }

            .content-wrapper {
                padding: 15px;
            }

            .position-row {
                padding: 10px 12px;
            }

            .position-label {
                min-width: 50px;
                font-size: 13px;
            }

            .player-display {
                font-size: 13px;
            }

            .buttons {
                padding: 15px;
                gap: 10px;
            }

            .btn {
                padding: 10px 30px;
                font-size: 14px;
            }
        }

        @media (max-height: 700px) {
            .outer-container {
                max-height: 100vh;
            }

            .header {
                padding: 10px 15px;
            }

            .note {
                padding: 8px 15px;
                font-size: 12px;
            }

            .content-wrapper {
                padding: 15px;
            }

            .position-row {
                padding: 8px 12px;
            }
        }
    </style>
</head>

<body>
    <div class="outer-container">
        <div class="container">
            <div class="header">
                <div class="header-badge">団体戦</div>
                <div class="header-badge"><?php echo htmlspecialchars($info['tournament_name']); ?></div>
                <div class="header-badge"><?php echo htmlspecialchars($info['division_name']); ?></div>
                <div class="header-badge">試合番号: <?php echo htmlspecialchars($match_number); ?></div>
            </div>

            <div class="main-content">
                <div class="note">
                    ※選手変更は必ず本部に届けてから変更してください
                </div>

                <form method="POST" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                    <div class="content-wrapper">
                        <div class="teams-wrapper">
                            <!-- 赤チーム -->
                            <div class="team-section">
                                <div class="team-header red">赤チーム</div>
                                <div class="team-info">
                                    ID:<?php echo htmlspecialchars($team_red_number . ' ' . $team_red_name); ?>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">先鋒</div>
                                    <input type="hidden" name="red_senpo"
                                        value="<?= isset($red_initial_order[1]) ? $red_initial_order[1] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($red_initial_order_info[1])) {
                                        echo htmlspecialchars($red_initial_order_info[1]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($red_initial_order_info[1]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">次鋒</div>
                                    <input type="hidden" name="red_jiho"
                                        value="<?= isset($red_initial_order[2]) ? $red_initial_order[2] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($red_initial_order_info[2])) {
                                        echo htmlspecialchars($red_initial_order_info[2]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($red_initial_order_info[2]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">中堅</div>
                                    <input type="hidden" name="red_chuken"
                                        value="<?= isset($red_initial_order[3]) ? $red_initial_order[3] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($red_initial_order_info[3])) {
                                        echo htmlspecialchars($red_initial_order_info[3]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($red_initial_order_info[3]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">副将</div>
                                    <input type="hidden" name="red_fukusho"
                                        value="<?= isset($red_initial_order[4]) ? $red_initial_order[4] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($red_initial_order_info[4])) {
                                        echo htmlspecialchars($red_initial_order_info[4]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($red_initial_order_info[4]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">大将</div>
                                    <input type="hidden" name="red_taisho"
                                        value="<?= isset($red_initial_order[5]) ? $red_initial_order[5] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($red_initial_order_info[5])) {
                                        echo htmlspecialchars($red_initial_order_info[5]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($red_initial_order_info[5]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>
                            </div>

                            <!-- 白チーム -->
                            <div class="team-section">
                                <div class="team-header white">白チーム</div>
                                <div class="team-info">
                                    ID:<?php echo htmlspecialchars($team_white_number . ' ' . $team_white_name); ?>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">先鋒</div>
                                    <input type="hidden" name="white_senpo"
                                        value="<?= isset($white_initial_order[1]) ? $white_initial_order[1] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($white_initial_order_info[1])) {
                                        echo htmlspecialchars($white_initial_order_info[1]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($white_initial_order_info[1]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">次鋒</div>
                                    <input type="hidden" name="white_jiho"
                                        value="<?= isset($white_initial_order[2]) ? $white_initial_order[2] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($white_initial_order_info[2])) {
                                        echo htmlspecialchars($white_initial_order_info[2]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($white_initial_order_info[2]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">中堅</div>
                                    <input type="hidden" name="white_chuken"
                                        value="<?= isset($white_initial_order[3]) ? $white_initial_order[3] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($white_initial_order_info[3])) {
                                        echo htmlspecialchars($white_initial_order_info[3]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($white_initial_order_info[3]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">副将</div>
                                    <input type="hidden" name="white_fukusho"
                                        value="<?= isset($white_initial_order[4]) ? $white_initial_order[4] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($white_initial_order_info[4])) {
                                        echo htmlspecialchars($white_initial_order_info[4]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($white_initial_order_info[4]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>

                                <div class="position-row">
                                    <div class="position-label">大将</div>
                                    <input type="hidden" name="white_taisho"
                                        value="<?= isset($white_initial_order[5]) ? $white_initial_order[5] : '' ?>">
                                    <div class="player-display"><?php
                                    if (isset($white_initial_order_info[5])) {
                                        echo htmlspecialchars($white_initial_order_info[5]['name']);
                                        echo ' <span class="player-number">(' . htmlspecialchars($white_initial_order_info[5]['number']) . ')</span>';
                                    } else {
                                        echo '（未登録）';
                                    }
                                    ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="buttons">
                        <button type="button" class="btn btn-back"
                            onclick="location.href='team-forfeit.php'">戻る</button>
                        <button type="submit" class="btn btn-submit">決定</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>