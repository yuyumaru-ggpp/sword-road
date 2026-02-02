<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../../db_connect.php';

$team_id = $_GET['team'] ?? null;
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$team_id || !$tournament_id || !$department_id) {
    die("必要な情報が不足しています");
}

// players → orders → team の順に削除（ON DELETE CASCADE でもOK）

// orders 削除
$sql = "DELETE FROM orders WHERE team_id = :team";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':team', $team_id);
$stmt->execute();

// players 削除
$sql = "DELETE FROM players WHERE team_id = :team";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':team', $team_id);
$stmt->execute();

// team 削除
$sql = "DELETE FROM teams WHERE id = :team";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':team', $team_id);
$stmt->execute();

echo "<script>alert('チームを削除しました'); location.href='Admin_team_list.php?id={$tournament_id}&dept={$department_id}';</script>";
exit;