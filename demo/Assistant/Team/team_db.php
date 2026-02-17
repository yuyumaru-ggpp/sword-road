<?php
// 団体戦共通処理ファイル

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB接続
require_once '../../connect/db_connect.php';


/* ===============================
   セッション必須チェック（団体戦）
=============================== */
function checkTeamSession() {
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
}

/* ===============================
   セッション必須チェック（match_results必要）
=============================== */
function checkTeamSessionWithResults() {
    if (
        !isset(
            $_SESSION['tournament_id'],
            $_SESSION['division_id'],
            $_SESSION['match_number'],
            $_SESSION['team_red_id'],
            $_SESSION['team_white_id'],
            $_SESSION['match_results']
        )
    ) {
        header('Location: match_input.php');
        exit;
    }
}

/* ===============================
   団体戦変数取得
=============================== */
function getTeamVariables() {
    return [
        'tournament_id' => (int)($_SESSION['tournament_id'] ?? 0),
        'division_id'   => (int)($_SESSION['division_id'] ?? 0),
        'match_number'  => $_SESSION['match_number'] ?? '',
        'team_red_id'   => (int)($_SESSION['team_red_id'] ?? 0),
        'team_white_id' => (int)($_SESSION['team_white_id'] ?? 0)
    ];
}

/* ===============================
   大会・部門・チーム情報取得
=============================== */
function getTeamMatchInfo($pdo, $division_id, $team_red_id, $team_white_id) {
    // 大会・部門情報
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
    
    return [
        'tournament_name' => $info['tournament_name'],
        'division_name' => $info['division_name'],
        'team_red_name' => $team_red_name,
        'team_white_name' => $team_white_name
    ];
}

/* ===============================
   選手情報取得
=============================== */
function getPlayerInfo($pdo, $player_id) {
    $sql = "SELECT id, name, player_number FROM players WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $player_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===============================
   得点計算（団体戦）
=============================== */
function calcTeamPoints($scores, $selected) {
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

/* ===============================
   途中経過計算
=============================== */
function calculateMatchProgress($match_results) {
    $redWins = 0;
    $whiteWins = 0;
    $redPoints = 0;
    $whitePoints = 0;
    
    $positions = ['先鋒', '次鋒', '中堅', '副将', '大将'];
    
    foreach ($positions as $pos) {
        if (isset($match_results[$pos])) {
            $posData = $match_results[$pos];
            $scores = $posData['scores'] ?? [];
            $redSelected = $posData['red']['selected'] ?? [];
            $whiteSelected = $posData['white']['selected'] ?? [];
            
            $redPosPoints = 0;
            $whitePosPoints = 0;
            
            foreach ($scores as $i => $score) {
                if ($score !== '▼' && $score !== '▲' && $score !== '不' && $score !== '') {
                    if (is_array($redSelected) && in_array($i, $redSelected)) {
                        $redPosPoints++;
                    }
                    if (is_array($whiteSelected) && in_array($i, $whiteSelected)) {
                        $whitePosPoints++;
                    }
                }
            }
            
            // 一本勝の場合は1本に固定
            if (isset($posData['special']) && $posData['special'] === 'ippon') {
                if ($redPosPoints > $whitePosPoints) {
                    $redPosPoints = 1;
                    $whitePosPoints = 0;
                } else if ($whitePosPoints > $redPosPoints) {
                    $redPosPoints = 0;
                    $whitePosPoints = 1;
                }
            }
            
            $redPoints += $redPosPoints;
            $whitePoints += $whitePosPoints;
            
            // 勝者判定
            if ($redPosPoints > $whitePosPoints) {
                $redWins++;
            } else if ($whitePosPoints > $redPosPoints) {
                $whiteWins++;
            }
        }
    }
    
    return [
        'redWins' => $redWins,
        'whiteWins' => $whiteWins,
        'redPoints' => $redPoints,
        'whitePoints' => $whitePoints
    ];
}