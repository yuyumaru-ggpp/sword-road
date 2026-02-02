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
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans','Meiryo',sans-serif;
    background:#f5f5f5;
    padding:2rem;
    min-height:100vh;
}
.container {
    max-width:1200px;
    margin:0 auto;
    background:white;
    padding:3rem;
    border-radius:8px;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}
.header {
    display:flex;
    flex-wrap:wrap;
    gap:2rem;
    font-size:1.5rem;
    font-weight:bold;
    margin-bottom:1rem;
    align-items:center;
}
.match-info {
    background:#f9fafb;
    padding:1rem 2rem;
    border-radius:8px;
    margin-bottom:2rem;
    display:flex;
    gap:2rem;
    align-items:center;
}
.match-info-item {
    font-size:1.1rem;
}
.note {
    text-align:center;
    font-size:1rem;
    color:#666;
    margin-bottom:3rem;
    padding:1rem;
    background:#fef3c7;
    border-radius:4px;
}
.teams-container {
    display:flex;
    gap:3rem;
    margin-bottom:3rem;
}
.team-section {
    flex:1;
}
.team-header {
    font-size:1.5rem;
    font-weight:bold;
    margin-bottom:2rem;
    padding:1rem;
    text-align:center;
    border-radius:8px;
}
.team-header.red {
    background:#fee2e2;
    color:#dc2626;
}
.team-header.white {
    background:#f3f4f6;
    color:#374151;
}
.position-row {
    display:flex;
    align-items:center;
    gap:1rem;
    margin-bottom:1.5rem;
}
.position-label {
    font-size:1.3rem;
    font-weight:bold;
    min-width:80px;
}
.player-input {
    flex:1;
    padding:0.75rem 1rem;
    font-size:1.1rem;
    border:2px solid #d1d5db;
    border-radius:8px;
    text-align:center;
}
.player-input:focus {
    outline:none;
    border-color:#3b82f6;
}
.player-display {
    flex:1;
    padding:0.75rem 1rem;
    font-size:1.1rem;
    border:2px solid #d1d5db;
    border-radius:8px;
    text-align:center;
    background:#f9fafb;
    color:#374151;
    font-weight:bold;
}
.player-select {
    flex:1;
    padding:0.75rem 1rem;
    font-size:1.1rem;
    border:2px solid #d1d5db;
    border-radius:8px;
    cursor:pointer;
}
.player-select:focus {
    outline:none;
    border-color:#3b82f6;
}
.buttons {
    display:flex;
    gap:2rem;
    justify-content:center;
    margin-top:3rem;
}
.btn {
    padding:1rem 3rem;
    font-size:1.3rem;
    font-weight:bold;
    border-radius:50px;
    cursor:pointer;
    transition:all 0.2s;
}
.btn-back {
    background:white;
    border:3px solid #000;
}
.btn-back:hover {
    background:#f9fafb;
}
.btn-submit {
    background:#3b82f6;
    color:white;
    border:3px solid #3b82f6;
}
.btn-submit:hover {
    background:#2563eb;
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <span>選手登録・団体戦</span>
        <span><?= htmlspecialchars($info['tournament_name']) ?></span>
        <span><?= htmlspecialchars($info['division_name']) ?></span>
    </div>
    
    <div class="match-info">
        <div class="match-info-item">試合番号: <strong><?= htmlspecialchars($match_number) ?></strong></div>
        <div class="match-info-item" style="color:#dc2626;">赤: <strong><?= htmlspecialchars($team_red_name) ?></strong></div>
        <div class="match-info-item">白: <strong><?= htmlspecialchars($team_white_name) ?></strong></div>
    </div>
    
    <div class="note">
        ※選手名はordersテーブルの登録内容が表示されます<br>
        ※選手変更は必ず本部に届けてから変更してください
    </div>
    
    <form method="POST">
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
        
        <div class="buttons">
            <button type="button" class="btn btn-back" onclick="history.back()">戻る</button>
            <button type="submit" class="btn btn-submit">決定</button>
        </div>
    </form>
</div>

</body>
</html>