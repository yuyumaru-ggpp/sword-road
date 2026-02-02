<?php
// match-update.php
// 前提: db_connect.php で $pdo (PDO) を作成していること
header('Content-Type: application/json; charset=utf-8');
session_start();

// db_connect のパスを環境に合わせて調整してください
$dbPath = dirname(__DIR__, 3) . '/db_connect.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'サーバー設定エラー: DB接続ファイルが見つかりません']);
    exit;
}
require_once $dbPath;

// 簡易的な認可チェック（必要に応じて強化）
if (!isset($_SESSION['admin_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '認可エラー']);
    exit;
}

// 入力を取得
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '不正なリクエスト']);
    exit;
}

// 必須: match_id
$match_id = isset($input['match_id']) ? (int)$input['match_id'] : 0;
if ($match_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'match_id が指定されていません']);
    exit;
}

// DB から該当試合と選手情報を取得（player_a / player_b の名前と id を使って正規化）
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT im.match_id, im.player_a_id, im.player_b_id, pa.name AS player_a_name, pb.name AS player_b_name
                           FROM individual_matches im
                           LEFT JOIN players pa ON pa.id = im.player_a_id
                           LEFT JOIN players pb ON pb.id = im.player_b_id
                           WHERE im.match_id = :mid
                           LIMIT 1");
    $stmt->bindValue(':mid', $match_id, PDO::PARAM_INT);
    $stmt->execute();
    $matchRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$matchRow) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => '指定された試合が見つかりません']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'DBエラー']);
    exit;
}

// 正規化関数: 入力値 -> 'red' / 'white' / ''
function normalize_incoming_winner($val, $playerAName, $playerBName, $playerAId = null, $playerBId = null) {
    if ($val === null) return '';
    $v = trim((string)$val);
    if ($v === '') return '';

    // 小文字化（英字）と全角半角トリム
    $low = mb_strtolower($v);

    // 色・コードを受け入れる
    $reds = ['red','r','赤','aka','a'];
    $whites = ['white','w','白','shiro','b'];

    if (in_array($low, $reds, true)) return 'red';
    if (in_array($low, $whites, true)) return 'white';

    // 単一文字 'A'/'B' の場合
    if ($low === 'a') return 'red';
    if ($low === 'b') return 'white';

    // 数値（選手ID）が来たら判定
    if (ctype_digit($v)) {
        $id = (int)$v;
        if ($playerAId !== null && $id === (int)$playerAId) return 'red';
        if ($playerBId !== null && $id === (int)$playerBId) return 'white';
    }

    // 名前が来たら完全一致で判定（日本語は大文字小文字の差がない）
    if ($playerAName !== null && $v === $playerAName) return 'red';
    if ($playerBName !== null && $v === $playerBName) return 'white';

    // 部分一致や別表記を許す場合はここで拡張可能（例: 漢字/カナの正規化）
    return ''; // 不明な値は空にする（保存前に必要ならエラーにする）
}

// 正規化して保存用の値を作る
$playerAName = $matchRow['player_a_name'] ?? '';
$playerBName = $matchRow['player_b_name'] ?? '';
$playerAId = $matchRow['player_a_id'] ?? null;
$playerBId = $matchRow['player_b_id'] ?? null;

$first_winner_in = $input['first_winner'] ?? null;
$second_winner_in = $input['second_winner'] ?? null;
$third_winner_in = $input['third_winner'] ?? null;
$final_winner_in = $input['final_winner'] ?? null;

$first_winner = normalize_incoming_winner($first_winner_in, $playerAName, $playerBName, $playerAId, $playerBId);
$second_winner = normalize_incoming_winner($second_winner_in, $playerAName, $playerBName, $playerAId, $playerBId);
$third_winner = normalize_incoming_winner($third_winner_in, $playerAName, $playerBName, $playerAId, $playerBId);
$final_winner = normalize_incoming_winner($final_winner_in, $playerAName, $playerBName, $playerAId, $playerBId);

// 必要に応じてバリデーション（例: 不正な値はエラーにする）
if ($first_winner_in !== null && $first_winner === '') $first_winner = '';
if ($second_winner_in !== null && $second_winner === '') $second_winner = '';
if ($third_winner_in !== null && $third_winner === '') $third_winner = '';
if ($final_winner_in !== null && $final_winner === '') $final_winner = '';

// started_at / ended_at の正規化（空文字 -> NULL）
function normalize_datetime($v) {
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === '') return null;
    // 簡易チェック: YYYY-MM-DD HH:MM:SS 形式を期待（必要なら厳密に検証）
    return $s;
}

$first_technique = isset($input['first_technique']) ? trim((string)$input['first_technique']) : '';
$second_technique = isset($input['second_technique']) ? trim((string)$input['second_technique']) : '';
$third_technique = isset($input['third_technique']) ? trim((string)$input['third_technique']) : '';
$judgement = isset($input['judgement']) ? trim((string)$input['judgement']) : '';
$started_at = normalize_datetime($input['started_at'] ?? null);
$ended_at = normalize_datetime($input['ended_at'] ?? null);

// DB 更新
try {
    $sql = "UPDATE individual_matches SET
                first_technique = :first_technique,
                first_winner = :first_winner,
                second_technique = :second_technique,
                second_winner = :second_winner,
                third_technique = :third_technique,
                third_winner = :third_winner,
                final_winner = :final_winner,
                judgement = :judgement,
                started_at = :started_at,
                ended_at = :ended_at
            WHERE match_id = :match_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':first_technique', $first_technique ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':first_winner', $first_winner ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':second_technique', $second_technique ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':second_winner', $second_winner ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':third_technique', $third_technique ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':third_winner', $third_winner ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':final_winner', $final_winner ?: null, PDO::PARAM_STR);
    $stmt->bindValue(':judgement', $judgement ?: null, PDO::PARAM_STR);
    if ($started_at === null) $stmt->bindValue(':started_at', null, PDO::PARAM_NULL);
    else $stmt->bindValue(':started_at', $started_at, PDO::PARAM_STR);
    if ($ended_at === null) $stmt->bindValue(':ended_at', null, PDO::PARAM_NULL);
    else $stmt->bindValue(':ended_at', $ended_at, PDO::PARAM_STR);
    $stmt->bindValue(':match_id', $match_id, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['status' => 'ok', 'message' => '保存しました']);
    exit;
}catch (PDOException $e) {
    // 開発用：詳細を返す（本番では消す）
    http_response_code(500);
    $msg = $e->getMessage();
    error_log("MATCH-UPDATE ERROR: " . $msg);
    echo json_encode([
        'status' => 'error',
        'message' => 'DBエラーが発生しました',
        'debug' => $msg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
