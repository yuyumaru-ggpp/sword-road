<?php
session_start();
require_once '../../connect/db_connect.php';

/* セッションチェック */
if (!isset($_SESSION['tournament_id'], $_SESSION['division_id'], $_SESSION['match_number'])) {
    header('Location: match_input.php');
    exit;
}

$tournament_id = (int) $_SESSION['tournament_id'];
$division_id = (int) $_SESSION['division_id'];
$match_number = $_SESSION['match_number'];


/* ===============================
   大会・部門・選手情報取得
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

// セッションから選手情報を取得
$upper_name = $_SESSION['player_a_name'] ?? '';
$upper_no = $_SESSION['player_a_number'] ?? '';
$lower_name = $_SESSION['player_b_name'] ?? '';
$lower_no = $_SESSION['player_b_number'] ?? '';
$upper_team = $_SESSION['player_a_team'] ?? '';
$lower_team = $_SESSION['player_b_team'] ?? '';
$oldInput = $_SESSION['match_input'] ?? null;

/* ===============================
   POST（試合結果保存）
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['status' => 'ng', 'message' => 'Invalid input']);
        exit;
    }

    // セッションに保存して確認画面へ
    $_SESSION['match_input'] = $input;

    echo json_encode(['status' => 'ok']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>個人戦試合詳細</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="individual-match-detail.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <span>個人戦</span>
            <span><?= htmlspecialchars($info['tournament_name']) ?></span>
            <span><?= htmlspecialchars($info['division_name']) ?></span>
        </div>

        <div class="content-wrapper">
            <div class="match-section upper-section">
                <div class="row">
                    <div class="label">選手番号</div>
                    <div class="value upper-number">
                        <?= htmlspecialchars($upper_no) ?>
                    </div>
                </div>

                <div class="row">
                    <div class="label">選手名</div>
                    <div class="value upper-name">
                        <?= htmlspecialchars($upper_name) ?>
                    </div>
                </div>

                <div class="row">
                    <div class="label">チーム名</div>
                    <div class="value upper-team"><?= htmlspecialchars($upper_team) ?></div>
                </div>

                <div class="score-display">
                    <div class="score-group">
                        <div class="score-numbers upper-scores">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                        </div>
                        <div class="radio-circles upper-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                    </div>
                </div>

                <div class="decision-row" id="upperDecisionRow">
                    <button type="button" class="decision-button" id="upperDecisionBtn">判定勝ち</button>
                </div>
            </div>

            <div class="divider-section">

                <div class="middle-controls">
                    <div class="score-dropdowns">
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
                    </div>
                </div>

                <div class="draw-container-wrapper">
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
            </div>

            <div class="match-section lower-section">
                <div class="decision-row" id="lowerDecisionRow">
                    <button type="button" class="decision-button" id="lowerDecisionBtn">判定勝ち</button>
                </div>

                <div class="score-display">
                    <div class="score-group">
                        <div class="radio-circles lower-circles">
                            <div class="radio-circle" data-index="0"></div>
                            <div class="radio-circle" data-index="1"></div>
                            <div class="radio-circle" data-index="2"></div>
                        </div>
                        <div class="score-numbers lower-scores">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="label">選手番号</div>
                    <div class="value lower-number">
                        <?= htmlspecialchars($lower_no) ?>
                    </div>
                </div>

                <div class="row">
                    <div class="label">選手名</div>
                    <div class="value lower-name">
                        <?= htmlspecialchars($lower_name) ?>
                    </div>
                </div>

                <div class="row">
                    <div class="label">チーム名</div>
                    <div class="value lower-team"><?= htmlspecialchars($lower_team) ?></div>
                </div>
            </div>
        </div>

        <div class="bottom-area">
            <div class="bottom-right-button">
                <button type="button" class="cancel-button" id="cancelButton">入力内容をリセット</button>
            </div>

            <div class="bottom-buttons">
                <button type="button" class="bottom-button back-button" onclick="location.href='solo-forfeit.php'">戻る</button>
                <button type="button" class="bottom-button submit-button" id="submitButton">決定</button>
            </div>
        </div>
    </div>
    <script>
        const oldInput = <?= $oldInput ? json_encode($oldInput, JSON_UNESCAPED_UNICODE) : 'null' ?>;
    </script>
    <script src="individual-match-detail.js"></script>
</body>

</html>