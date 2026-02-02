<?php
// import_players_csv.php
// CSVアップロードから個人戦／団体戦の登録を行うスクリプト（改良版）
// - 団体戦で player_number が未設定になる問題を解消（部門内で連番を採番）
// - パフォーマンス改善：MAX(player_number) を毎回問い合わせず、インメモリでインクリメント
// - トランザクション単位をチーム毎にして失敗時はロールバック
// 配置場所と require_once のパスは環境に合わせて調整してください

session_start();
// 管理者チェック（必要なら有効化）
// if (!isset($_SESSION['admin_user'])) { header("Location: ../../login.php"); exit; }

require_once '../../../db_connect.php';

$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
    die("大会ID または 部門ID が指定されていません");
}

// distinction を取得（1=団体戦, 2=個人戦）
$sql = "SELECT distinction FROM departments WHERE id = :dept";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id, PDO::PARAM_INT);
$stmt->execute();
$department = $stmt->fetch(PDO::FETCH_ASSOC);

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

// 結果サマリ
$summary = [
    'teams_created' => 0,
    'players_created' => 0,
    'orders_created' => 0,
    'errors' => []
];

try {
    if ($distinction === 2) {
        // -------------------------------
        // 個人戦 CSV 登録処理（改良）
        // CSVフォーマット想定: name, furigana, team_name, ...（ヘッダあり）
        // -------------------------------
        $header = fgetcsv($csv);
        if ($header === false) throw new Exception("CSVが空です");

        // 部門内の現在の最大 player_number を一度取得してインメモリでインクリメント
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(p.player_number), 0) AS max_no
            FROM players p
            JOIN teams t ON p.team_id = t.id
            WHERE t.department_id = :dept
        ");
        $stmt->execute([':dept' => $department_id]);
        $row_no = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_player_number = (int)$row_no['max_no'] + 1;

        // 準備ステートメント
        $stmtSelectTeam = $pdo->prepare("SELECT id FROM teams WHERE name = :name LIMIT 1");
        $stmtInsertTeam = $pdo->prepare("INSERT INTO teams (name, abbreviation, department_id, withdraw_flg) VALUES (:name, '', :dept, 0)");
        $stmtInsertPlayer = $pdo->prepare("INSERT INTO players (name, furigana, player_number, team_id, substitute_flg) VALUES (:name, :furigana, :pnum, :team_id, 0)");

        while (($row = fgetcsv($csv)) !== false) {
            if (count($row) < 3) continue;
            $player_name     = convert_encoding(trim($row[0]));
            $player_furigana = convert_encoding(trim($row[1]));
            $team_name       = convert_encoding(trim($row[2]));

            if ($player_name === '' || $team_name === '') continue;

            // チーム取得 or 作成
            $stmtSelectTeam->execute([':name' => $team_name]);
            $team = $stmtSelectTeam->fetch(PDO::FETCH_ASSOC);

            if ($team) {
                $team_id = $team['id'];
            } else {
                $pdo->beginTransaction();
                try {
                    $stmtInsertTeam->execute([':name' => $team_name, ':dept' => $department_id]);
                    $team_id = $pdo->lastInsertId();
                    $pdo->commit();
                    $summary['teams_created']++;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $summary['errors'][] = "チーム作成エラー: {$team_name} - " . $e->getMessage();
                    continue;
                }
            }

            // players 登録（player_number はインメモリで採番）
            try {
                $stmtInsertPlayer->execute([
                    ':name' => $player_name,
                    ':furigana' => $player_furigana === '' ? null : $player_furigana,
                    ':pnum' => $next_player_number,
                    ':team_id' => $team_id
                ]);
                $next_player_number++;
                $summary['players_created']++;
            } catch (Exception $e) {
                $summary['errors'][] = "選手登録エラー: {$player_name} (team_id={$team_id}) - " . $e->getMessage();
                // 続行
            }
        }

    } else {
        // -------------------------------
        // 団体戦 CSV 登録処理（横流れ） - 改良版
        // CSVフォーマット想定: team_name, abbreviation, player1_name, player1_furigana, player2_name, player2_furigana, ...
        // - 部門内の player_number は一度 MAX を取得してインメモリでインクリメント
        // - team_number は一度 MAX を取得してインメモリでインクリメント
        // - 各チームはトランザクションで処理（失敗時はそのチームのみロールバック）
        // -------------------------------

        // 部門内の現在の最大 player_number を一度取得してインメモリでインクリメント
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(p.player_number), 0) AS max_no
            FROM players p
            JOIN teams t ON p.team_id = t.id
            WHERE t.department_id = :dept
        ");
        $stmt->execute([':dept' => $department_id]);
        $row_no = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_player_number = (int)$row_no['max_no'] + 1;

        // 部門内の現在の最大 team_number を一度取得してインメモリでインクリメント
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(team_number), 0) AS max_no FROM teams WHERE department_id = :dept");
        $stmt->execute([':dept' => $department_id]);
        $row_no = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_team_number = (int)$row_no['max_no'] + 1;

        // 準備ステートメント（再利用）
        $stmtInsertTeam = $pdo->prepare("INSERT INTO teams (name, abbreviation, department_id, team_number, withdraw_flg) VALUES (:name, :abbr, :dept, :tnum, 0)");
        $stmtInsertPlayer = $pdo->prepare("INSERT INTO players (name, furigana, player_number, team_id, substitute_flg) VALUES (:name, :furigana, :pnum, :team_id, 0)");
        $stmtInsertOrder = $pdo->prepare("INSERT INTO orders (team_id, player_id, order_detail) VALUES (:team_id, :player_id, :od)");

        while (($row = fgetcsv($csv)) !== false) {
            if (count($row) < 4) continue;
            $team_name = convert_encoding(trim($row[0]));
            if ($team_name === '') continue;
            $abbreviation = convert_encoding(trim($row[1]));

            // トランザクション開始（チーム単位）
            $pdo->beginTransaction();
            try {
                // teams 登録（team_number はインメモリで採番）
                $stmtInsertTeam->execute([
                    ':name' => $team_name,
                    ':abbr' => $abbreviation,
                    ':dept' => $department_id,
                    ':tnum' => $next_team_number
                ]);
                $team_id = $pdo->lastInsertId();
                $next_team_number++;
                $summary['teams_created']++;

                // 選手登録（先鋒〜補員） - CSVは2列目以降が選手名・フリガナのペア
                $order_detail = 1;
                for ($i = 2; $i < count($row); $i += 2) {
                    $player_name = convert_encoding(trim($row[$i] ?? ''));
                    $furigana = convert_encoding(trim($row[$i + 1] ?? ''));

                    if ($player_name === '') continue;

                    // players 登録（player_number はインメモリで採番）
                    $stmtInsertPlayer->execute([
                        ':name' => $player_name,
                        ':furigana' => $furigana === '' ? null : $furigana,
                        ':pnum' => $next_player_number,
                        ':team_id' => $team_id
                    ]);
                    $player_id = $pdo->lastInsertId();
                    $next_player_number++;
                    $summary['players_created']++;

                    // orders 登録（order_detail は 1 から順に）
                    $stmtInsertOrder->execute([
                        ':team_id' => $team_id,
                        ':player_id' => $player_id,
                        ':od' => $order_detail
                    ]);
                    $order_detail++;
                    $summary['orders_created']++;
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $summary['errors'][] = "チーム登録失敗: {$team_name} - " . $e->getMessage();
                // 続行（次の行へ）
            }
        }
    }

    fclose($csv);

    // 完了メッセージ（簡易）
    $msg = "CSVの登録が完了しました。\n";
    $msg .= "作成チーム: {$summary['teams_created']}\n";
    $msg .= "作成選手: {$summary['players_created']}\n";
    $msg .= "作成オーダー: {$summary['orders_created']}\n";
    if (!empty($summary['errors'])) {
        $msg .= "警告/エラー:\n" . implode("\n", $summary['errors']);
    }

    // ブラウザに戻す（alert で表示して戻る）
    echo "<script>alert(" . json_encode($msg) . "); history.back();</script>";
    exit;

} catch (Exception $e) {
    // 想定外のエラー
    fclose($csv);
    $err = "処理中にエラーが発生しました: " . $e->getMessage();
    echo "<script>alert(" . json_encode($err) . "); history.back();</script>";
    exit;
}