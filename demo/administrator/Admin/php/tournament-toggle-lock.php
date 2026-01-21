<?php
session_start();
header('Content-Type: application/json');
require_once '../../db_connect.php';

if (!isset($_SESSION['admin_user'])) {
    echo json_encode(['success' => false, 'error' => '権限がありません']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($input === null) {
    echo json_encode(['success' => false, 'error' => 'JSONパースエラー', 'raw' => $raw]);
    exit;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
$set_locked = array_key_exists('set_locked', $input) ? (int)$input['set_locked'] : null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'パラメータ不正: id が必要です']);
    exit;
}

// 現在の状態を取得
$stmt = $pdo->prepare("SELECT is_locked FROM tournaments WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$current = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$current) {
    echo json_encode(['success' => false, 'error' => '大会が見つかりません']);
    exit;
}

// set_locked が指定されていなければトグル、指定されていればその値を使う
if ($set_locked === null) {
    $newState = ((int)$current['is_locked'] === 1) ? 0 : 1;
} else {
    $newState = ($set_locked === 1) ? 1 : 0;
}

$upd = $pdo->prepare("UPDATE tournaments SET is_locked = :newState, update_at = NOW() WHERE id = :id");
$upd->bindValue(':newState', $newState, PDO::PARAM_INT);
$upd->bindValue(':id', $id, PDO::PARAM_INT);

if ($upd->execute()) {
    echo json_encode(['success' => true, 'is_locked' => $newState]);
} else {
    $err = $pdo->errorInfo();
    echo json_encode(['success' => false, 'error' => 'DB更新に失敗しました', 'db_error' => $err]);
}