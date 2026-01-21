<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../../db_connect.php';

$delete_ids = $_POST['delete_ids'] ?? [];

if (empty($delete_ids)) {
    echo "<script>alert('削除する選手が選択されていません'); history.back();</script>";
    exit;
}

// 配列を IN句に変換
$placeholders = implode(',', array_fill(0, count($delete_ids), '?'));

$sql = "DELETE FROM players WHERE id IN ($placeholders)";
$stmt = $pdo->prepare($sql);
$stmt->execute($delete_ids);

echo "<script>alert('選択した選手を削除しました'); history.back();</script>";
exit;
?>