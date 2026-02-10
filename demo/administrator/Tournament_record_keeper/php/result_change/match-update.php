<?php
session_start();
require_once '../../../../connect/db_connect.php';

// セッションチェック
if (!isset($_SESSION['tournament_editor'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// JSON データ取得
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['match_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // UPDATE クエリ
    $sql = "UPDATE individual_matches 
            SET first_technique = :first_technique,
                first_winner = :first_winner,
                second_technique = :second_technique,
                second_winner = :second_winner,
                third_technique = :third_technique,
                third_winner = :third_winner,
                judgement = :judgement,
                final_winner = :final_winner,
                started_at = :started_at,
                ended_at = :ended_at
            WHERE match_id = :match_id";
    
    $stmt = $pdo->prepare($sql);
    
    // パラメータバインド
    $stmt->bindValue(':match_id', $data['match_id'], PDO::PARAM_INT);
    $stmt->bindValue(':first_technique', $data['first_technique'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':first_winner', $data['first_winner'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':second_technique', $data['second_technique'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':second_winner', $data['second_winner'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':third_technique', $data['third_technique'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':third_winner', $data['third_winner'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':judgement', $data['judgement'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':final_winner', $data['final_winner'] ?? '', PDO::PARAM_STR);
    
    // 日時の処理
    $started_at = !empty($data['started_at']) ? $data['started_at'] : null;
    $ended_at = !empty($data['ended_at']) ? $data['ended_at'] : null;
    
    if ($started_at === null) {
        $stmt->bindValue(':started_at', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':started_at', $started_at, PDO::PARAM_STR);
    }
    
    if ($ended_at === null) {
        $stmt->bindValue(':ended_at', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':ended_at', $ended_at, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    // 成功レスポンス
    echo json_encode([
        'status' => 'ok',
        'message' => 'Match updated successfully',
        'updated_rows' => $stmt->rowCount()
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>