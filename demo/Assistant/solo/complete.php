<?php
session_start();

/* ===============================
   POST & セッションチェック
=============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: match_input.php');
    exit;
}

$data = $_SESSION['match_input'];

/* ===============================
   DB接続
=============================== */
$dsn = "mysql:host=localhost;port=3308;dbname=kendo_support_system;charset=utf8mb4";
$pdo = new PDO($dsn, "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

/* ===============================
   試合結果を解析
=============================== */
// 技と勝者を抽出
$techniques = [];
$winners = [];

foreach ($data['upper']['scores'] as $i => $score) {
    if ($score !== '▼' && $score !== '▲' && $score !== '×') {
        $techniques[] = $score;
        $winners[] = ($data['upper']['selected'] == $i) ? 'A' : null;
    }
}

foreach ($data['lower']['scores'] as $i => $score) {
    if ($score !== '▼' && $score !== '▲' && $score !== '×') {
        if (!isset($techniques[count($techniques) - 1]) || $techniques[count($techniques) - 1] !== $score) {
            $techniques[] = $score;
        }
        if (($data['lower']['selected'] == $i) && !$winners[count($winners) - 1]) {
            $winners[count($winners) - 1] = 'B';
        }
    }
}

// 最終勝者を決定
$final_winner = null;
$judgement = null;

if ($data['upper']['decision']) {
    $final_winner = 'A';
    $judgement = '判定';
} else if ($data['lower']['decision']) {
    $final_winner = 'B';
    $judgement = '判定';
} else {
    // 勝ち本数で判定
    $a_count = count(array_filter($winners, fn($w) => $w === 'A'));
    $b_count = count(array_filter($winners, fn($w) => $w === 'B'));
    
    if ($a_count > $b_count) {
        $final_winner = 'A';
    } else if ($b_count > $a_count) {
        $final_winner = 'B';
    } else if ($data['special'] === 'draw') {
        $final_winner = null;
        $judgement = '引分け';
    }
}

/* ===============================
   試合結果 INSERT
=============================== */
$sql = "
INSERT INTO individual_matches (
    department_id,
    department,
    match_field,
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
    :department,
    1,
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

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':department_id'     => $_SESSION['division_id'],
    ':department'        => $_SESSION['match_number'],
    ':player_a_id'       => $_SESSION['player_a_id'],
    ':player_b_id'       => $_SESSION['player_b_id'],
    ':first_technique'   => $techniques[0] ?? null,
    ':first_winner'      => $winners[0] ?? null,
    ':second_technique'  => $techniques[1] ?? null,
    ':second_winner'     => $winners[1] ?? null,
    ':third_technique'   => $techniques[2] ?? null,
    ':third_winner'      => $winners[2] ?? null,
    ':judgement'         => $judgement,
    ':final_winner'      => $final_winner
]);

/* ===============================
   二重送信防止
=============================== */
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
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans',Meiryo,sans-serif;
    background:#f5f5f5;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem;
}
.container{max-width:1100px;width:100%;}
.success-box{
    background:#fff;
    border:4px solid #22c55e;
    border-radius:24px;
    padding:4rem 3rem;
    text-align:center;
    margin-bottom:3rem;
}
.success-icon{
    width:80px;
    height:80px;
    background:#22c55e;
    border-radius:16px;
    margin:0 auto 1.5rem;
    display:flex;
    align-items:center;
    justify-content:center;
}
.success-icon::before{
    content:'✓';
    color:#fff;
    font-size:3.5rem;
    font-weight:bold;
}
.success-message{
    font-size:2.5rem;
    font-weight:bold;
}
.button-container{
    display:flex;
    gap:2rem;
    justify-content:center;
    flex-wrap:wrap;
}
.action-button{
    padding:1.25rem 3.5rem;
    font-size:1.25rem;
    border:3px solid #000;
    border-radius:50px;
    background:#fff;
    cursor:pointer;
    transition:background-color 0.2s;
}
.action-button:hover{background:#f9fafb;}
</style>
</head>
<body>
<div class="container">
    <div class="success-box">
        <div class="success-icon"></div>
        <div class="success-message">送信しました</div>
    </div>

    <div class="button-container">
        <button class="action-button"
            onclick="location.href='match_input.php?division_id=<?= $_SESSION['division_id'] ?>'">
            連続で入力する
        </button>
        <button class="action-button"
            onclick="location.href='../index.php'">
            部門選択画面に戻る
        </button>
    </div>
</div>
</body>
</html>