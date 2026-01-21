<?php
session_start();

if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../../db_connect.php';

$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
    die("大会ID または 部門ID が指定されていません");
}

// distinction を取得（1=団体戦, 2=個人戦）
$sql = "SELECT distinction FROM departments WHERE id = :dept";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id);
$stmt->execute();
$department = $stmt->fetch();

if (!$department) {
    die("部門が存在しません");
}

$distinction = (int)$department['distinction'];

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    die("CSVファイルがアップロードされていません");
}

$csv = fopen($_FILES['csv_file']['tmp_name'], 'r');
if (!$csv) {
    die("CSVファイルを開けませんでした");
}

function convert_encoding($str) {
    return mb_convert_encoding($str, 'UTF-8', 'SJIS-win, SJIS, UTF-8');
}

if ($distinction === 2) {
    // -------------------------------
    // 個人戦 CSV 登録処理
    // -------------------------------
    $header = fgetcsv($csv);

    while (($row = fgetcsv($csv)) !== false) {
        if (count($row) < 3 || trim($row[0]) === '') continue;

        $player_name     = convert_encoding(trim($row[0]));
        $player_furigana = convert_encoding(trim($row[1]));
        $team_name       = convert_encoding(trim($row[2]));

        if ($player_name === '' || $team_name === '') continue;

        // チーム取得 or 作成
        $sql = "SELECT id FROM teams WHERE name = :name LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $team_name);
        $stmt->execute();
        $team = $stmt->fetch();

        if ($team) {
            $team_id = $team['id'];
        } else {
            $sql = "INSERT INTO teams (name, abbreviation, department_id, withdraw_flg)
                    VALUES (:name, '', :dept, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':name', $team_name);
            $stmt->bindValue(':dept', $department_id);
            $stmt->execute();
            $team_id = $pdo->lastInsertId();
        }

        // 選手番号採番
        $sql = "SELECT COALESCE(MAX(p.player_number), 0) AS max_no
                FROM players p
                JOIN teams t ON p.team_id = t.id
                WHERE t.department_id = :dept";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':dept', $department_id);
        $stmt->execute();
        $row_no = $stmt->fetch();
        $next_number = $row_no['max_no'] + 1;

        // players 登録
        $sql = "INSERT INTO players (name, furigana, player_number, team_id, substitute_flg)
                VALUES (:name, :furigana, :pnum, :team_id, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $player_name);
        $stmt->bindValue(':furigana', $player_furigana);
        $stmt->bindValue(':pnum', $next_number);
        $stmt->bindValue(':team_id', $team_id);
        $stmt->execute();
    }

} else {
    // -------------------------------
    // 団体戦 CSV 登録処理（横流れ）
    // -------------------------------
    while (($row = fgetcsv($csv)) !== false) {
        if (count($row) < 4 || trim($row[0]) === '') continue;

        $team_name = convert_encoding(trim($row[0]));
        $abbreviation = convert_encoding(trim($row[1]));

        // チーム番号採番
        $sql = "SELECT COALESCE(MAX(team_number), 0) AS max_no FROM teams WHERE department_id = :dept";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':dept', $department_id);
        $stmt->execute();
        $row_no = $stmt->fetch();
        $next_team_number = $row_no['max_no'] + 1;

        // teams 登録
        $sql = "INSERT INTO teams (name, abbreviation, department_id, team_number, withdraw_flg)
                VALUES (:name, :abbr, :dept, :tnum, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $team_name);
        $stmt->bindValue(':abbr', $abbreviation);
        $stmt->bindValue(':dept', $department_id);
        $stmt->bindValue(':tnum', $next_team_number);
        $stmt->execute();
        $team_id = $pdo->lastInsertId();

        // 選手登録（先鋒〜補員）
        $order_detail = 1;
        for ($i = 2; $i < count($row); $i += 2) {
            $player_name = convert_encoding(trim($row[$i] ?? ''));
            $furigana = convert_encoding(trim($row[$i + 1] ?? ''));

            if ($player_name === '') continue;

            // players 登録
            $sql = "INSERT INTO players (name, furigana, team_id, substitute_flg)
                    VALUES (:name, :furigana, :team_id, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':name', $player_name);
            $stmt->bindValue(':furigana', $furigana);
            $stmt->bindValue(':team_id', $team_id);
            $stmt->execute();
            $player_id = $pdo->lastInsertId();

            // orders 登録
            $sql = "INSERT INTO orders (team_id, player_id, order_detail)
                    VALUES (:team_id, :player_id, :od)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':team_id', $team_id);
            $stmt->bindValue(':player_id', $player_id);
            $stmt->bindValue(':od', $order_detail);
            $stmt->execute();

            $order_detail++;
        }
    }
}

fclose($csv);

echo "<script>alert('CSVの登録が完了しました'); history.back();</script>";
exit;