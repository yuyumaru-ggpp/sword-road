<?php
// 個人戦共通処理ファイル

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB接続
require_once '../../connect/db_connect.php';


/* ===============================
   セッション必須チェック（個人戦）
=============================== */
function checkSoloSession() {
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
}

/* ===============================
   大会・部門情報取得
=============================== */
function getTournamentInfo($pdo, $division_id) {
    $sql = "
        SELECT
            t.title AS tournament_name,
            d.name  AS division_name
        FROM departments d
        INNER JOIN tournaments t ON t.id = d.tournament_id
        WHERE d.id = :division_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':division_id' => $division_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        exit('部門情報が取得できません');
    }
    
    return $info;
}

/* ===============================
   選手一覧取得（個人戦）
   ※ substitute_flg = 1（棄権）の選手も含めて取得し、
     呼び出し側でUIに反映する
=============================== */
function getPlayers($pdo, $division_id) {
    $sql = "
        SELECT
            p.id,
            p.player_number,
            p.name,
            p.substitute_flg,
            t.name as team_name
        FROM players p
        INNER JOIN teams t ON p.team_id = t.id
        INNER JOIN departments d ON t.department_id = d.id
        WHERE d.id = :division_id
        ORDER BY p.player_number
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':division_id' => $division_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   得点計算
=============================== */
function calcPoints($scores, $selected) {
    $point = 0;
    
    if (!is_array($selected)) {
        $selected = [];
    }
    
    foreach ($scores as $i => $s) {
        if ($s !== '▼' && $s !== '▲' && $s !== '不' && $s !== '' && in_array($i, $selected)) {
            $point++;
        }
    }
    return $point;
}