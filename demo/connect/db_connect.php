<?php
$user = "root";
$pass = "";
$database = "kendo_support_system";
$server = "localhost";
$port = "3307";

$dsn = "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "データベースに接続完了"; // ← 必要なら残す
} catch (Exception $e) {
    echo "データベースに接続できませんでした<br>";
    echo $e->getMessage();
}