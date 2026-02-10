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
$division_id   = $_SESSION['division_id'];
$match_number  = $_SESSION['match_number'];
$team_red_id   = $_SESSION['team_red_id'];
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

// チーム名取得
$sql = "SELECT name FROM teams WHERE id = :team_id";
$stmt = $pdo->prepare($sql);

$stmt->execute([':team_id' => $team_red_id]);
$team_red_name = $stmt->fetchColumn();

$stmt->execute([':team_id' => $team_white_id]);
$team_white_name = $stmt->fetchColumn();

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
foreach ($red_order_from_db as $order) {
    $red_initial_order[$order['order_detail']] = $order['player_id'];
}

// 白チームのオーダー
$stmt->execute([':team_id' => $team_white_id]);
$white_order_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$white_initial_order = [];
foreach ($white_order_from_db as $order) {
    $white_initial_order[$order['order_detail']] = $order['player_id'];
}

/* ===============================
   POST処理
=============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // オーダー情報をセッションに保存
    $_SESSION['team_red_order'] = [
        '先鋒' => $_POST['red_senpo'] ?? null,
        '次鋒' => $_POST['red_jiho'] ?? null,
        '中堅' => $_POST['red_chuken'] ?? null,
        '副将' => $_POST['red_fukusho'] ?? null,
        '大将' => $_POST['red_taisho'] ?? null
    ];
    
    $_SESSION['team_white_order'] = [
        '先鋒' => $_POST['white_senpo'] ?? null,
        '次鋒' => $_POST['white_jiho'] ?? null,
        '中堅' => $_POST['white_chuken'] ?? null,
        '副将' => $_POST['white_fukusho'] ?? null,
        '大将' => $_POST['white_taisho'] ?? null
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

html, body {
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

.container {
    width: 100%;
    max-width: 1100px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    margin: auto;
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
    border-radius: 20px 20px 0 0;
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
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.match-info {
    background: #f7fafc;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
}

.match-info-item {
    font-size: 14px;
    color: #4a5568;
}

.match-info-item strong {
    font-weight: 700;
    color: #2d3748;
}

.note {
    text-align: center;
    font-size: 12px;
    color: #744210;
    background: #fef3c7;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    line-height: 1.5;
    flex-shrink: 0;
}

.teams-wrapper {
    margin-bottom: 15px;
}

.teams-container {
    display: flex;
    gap: 20px;
}

.team-section {
    flex: 1;
    min-width: 0;
}

.team-header {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 12px;
    padding: 10px;
    text-align: center;
    border-radius: 10px;
}

.team-header.red {
    background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
    color: white;
}

.team-header.white {
    background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
    color: white;
}

.position-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.position-label {
    font-size: 14px;
    font-weight: bold;
    min-width: 50px;
    color: #4a5568;
    flex-shrink: 0;
}

.player-display {
    flex: 1;
    padding: 10px 14px;
    font-size: 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    text-align: center;
    background: #f7fafc;
    color: #2d3748;
    font-weight: 600;
    word-break: break-all;
}

.buttons {
    display: flex;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
    margin-top: 10px;
}

.btn {
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

.btn-back {
    background-color: #e2e8f0;
    color: #4a5568;
}

.btn-back:hover {
    background-color: #cbd5e0;
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

/* タブレット縦向き・横向き */
@media (max-width: 900px) {
    .teams-container {
        flex-direction: column;
    }

    .team-section {
        width: 100%;
        max-width: none;
    }
}

/* スマートフォン縦向き */
@media (max-width: 600px) {
    body {
        padding: 8px;
    }

    .container {
        border-radius: 12px;
    }

    .header {
        padding: 10px 12px;
        gap: 6px;
        border-radius: 12px 12px 0 0;
    }

    .header-badge {
        font-size: 11px;
        padding: 5px 10px;
    }

    .main-content {
        padding: 15px;
    }

    .match-info {
        padding: 10px 12px;
        gap: 8px;
        margin-bottom: 12px;
        flex-direction: column;
    }

    .match-info-item {
        font-size: 12px;
    }

    .note {
        font-size: 11px;
        padding: 8px;
        margin-bottom: 12px;
        line-height: 1.4;
    }

    .teams-container {
        gap: 15px;
    }

    .team-header {
        font-size: 14px;
        padding: 8px;
        margin-bottom: 10px;
    }

    .position-row {
        margin-bottom: 8px;
        gap: 8px;
    }

    .position-label {
        font-size: 12px;
        min-width: 45px;
    }

    .player-display {
        padding: 8px 10px;
        font-size: 12px;
    }

    .btn {
        padding: 12px 16px;
        font-size: 14px;
    }

    .buttons {
        padding-top: 12px;
        gap: 10px;
    }

    .teams-wrapper {
        margin-bottom: 12px;
    }
}

/* スマートフォン横向き */
@media (max-width: 900px) and (max-height: 500px) {
    body {
        padding: 6px;
    }

    .container {
        border-radius: 10px;
    }

    .header {
        padding: 6px 10px;
        border-radius: 10px 10px 0 0;
    }

    .header-badge {
        font-size: 10px;
        padding: 4px 8px;
    }

    .main-content {
        padding: 10px;
    }

    .match-info {
        padding: 6px 10px;
        gap: 6px;
        margin-bottom: 8px;
    }

    .match-info-item {
        font-size: 11px;
    }

    .note {
        font-size: 10px;
        padding: 6px;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .teams-container {
        flex-direction: row;
        gap: 10px;
    }

    .team-header {
        font-size: 12px;
        padding: 6px;
        margin-bottom: 8px;
    }

    .position-row {
        margin-bottom: 6px;
        gap: 6px;
    }

    .position-label {
        font-size: 11px;
        min-width: 35px;
    }

    .player-display {
        padding: 6px 8px;
        font-size: 11px;
        border-width: 1px;
    }

    .btn {
        padding: 8px 12px;
        font-size: 13px;
    }

    .buttons {
        padding-top: 8px;
        gap: 8px;
    }

    .teams-wrapper {
        margin-bottom: 8px;
    }
}

/* 小さいスマートフォン */
@media (max-width: 400px) {
    .header-badge {
        font-size: 10px;
        padding: 4px 8px;
    }

    .match-info-item {
        font-size: 11px;
    }

    .position-label {
        min-width: 40px;
        font-size: 11px;
    }

    .player-display {
        font-size: 11px;
        padding: 6px 8px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-badge">選手登録・団体戦</div>
        <div class="header-badge"><?= htmlspecialchars($info['tournament_name']) ?></div>
        <div class="header-badge"><?= htmlspecialchars($info['division_name']) ?></div>
    </div>
    
    <div class="main-content">
        <div class="match-info">
            <div class="match-info-item">試合番号: <strong><?= htmlspecialchars($match_number) ?></strong></div>
            <div class="match-info-item" style="color:#dc2626;">赤: <strong><?= htmlspecialchars($team_red_name) ?></strong></div>
            <div class="match-info-item">白: <strong><?= htmlspecialchars($team_white_name) ?></strong></div>
        </div>
        
        <div class="note">
            ※選手名はordersテーブルの登録内容が表示されます<br>
            ※選手変更は必ず本部に届けてから変更してください
        </div>
        
        <form method="POST" style="flex: 1; display: flex; flex-direction: column;">
            <div class="teams-wrapper">
                <div class="teams-container">
                    <!-- 赤チーム -->
                    <div class="team-section">
                        <div class="team-header red">赤チーム</div>
                        
                        <div class="position-row">
                            <div class="position-label">先鋒</div>
                            <input type="hidden" name="red_senpo" value="<?= isset($red_initial_order[1]) ? $red_initial_order[1] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($red_initial_order[1])) {
                                    foreach ($red_players as $player) {
                                        if ($player['id'] == $red_initial_order[1]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">次鋒</div>
                            <input type="hidden" name="red_jiho" value="<?= isset($red_initial_order[2]) ? $red_initial_order[2] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($red_initial_order[2])) {
                                    foreach ($red_players as $player) {
                                        if ($player['id'] == $red_initial_order[2]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">中堅</div>
                            <input type="hidden" name="red_chuken" value="<?= isset($red_initial_order[3]) ? $red_initial_order[3] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($red_initial_order[3])) {
                                    foreach ($red_players as $player) {
                                        if ($player['id'] == $red_initial_order[3]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">副将</div>
                            <input type="hidden" name="red_fukusho" value="<?= isset($red_initial_order[4]) ? $red_initial_order[4] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($red_initial_order[4])) {
                                    foreach ($red_players as $player) {
                                        if ($player['id'] == $red_initial_order[4]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">大将</div>
                            <input type="hidden" name="red_taisho" value="<?= isset($red_initial_order[5]) ? $red_initial_order[5] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($red_initial_order[5])) {
                                    foreach ($red_players as $player) {
                                        if ($player['id'] == $red_initial_order[5]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                    </div>
                    
                    <!-- 白チーム -->
                    <div class="team-section">
                        <div class="team-header white">白チーム</div>
                        
                        <div class="position-row">
                            <div class="position-label">先鋒</div>
                            <input type="hidden" name="white_senpo" value="<?= isset($white_initial_order[1]) ? $white_initial_order[1] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($white_initial_order[1])) {
                                    foreach ($white_players as $player) {
                                        if ($player['id'] == $white_initial_order[1]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">次鋒</div>
                            <input type="hidden" name="white_jiho" value="<?= isset($white_initial_order[2]) ? $white_initial_order[2] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($white_initial_order[2])) {
                                    foreach ($white_players as $player) {
                                        if ($player['id'] == $white_initial_order[2]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">中堅</div>
                            <input type="hidden" name="white_chuken" value="<?= isset($white_initial_order[3]) ? $white_initial_order[3] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($white_initial_order[3])) {
                                    foreach ($white_players as $player) {
                                        if ($player['id'] == $white_initial_order[3]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">副将</div>
                            <input type="hidden" name="white_fukusho" value="<?= isset($white_initial_order[4]) ? $white_initial_order[4] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($white_initial_order[4])) {
                                    foreach ($white_players as $player) {
                                        if ($player['id'] == $white_initial_order[4]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                        
                        <div class="position-row">
                            <div class="position-label">大将</div>
                            <input type="hidden" name="white_taisho" value="<?= isset($white_initial_order[5]) ? $white_initial_order[5] : '' ?>">
                            <div class="player-display"><?php 
                                if (isset($white_initial_order[5])) {
                                    foreach ($white_players as $player) {
                                        if ($player['id'] == $white_initial_order[5]) {
                                            echo htmlspecialchars($player['name']);
                                            break;
                                        }
                                    }
                                } else {
                                    echo '（未登録）';
                                }
                            ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="buttons">
                <button type="button" class="btn btn-back" onclick="location.href='team-forfeit.php'">戻る</button>
                <button type="submit" class="btn btn-submit">決定</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>