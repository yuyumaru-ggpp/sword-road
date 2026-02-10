<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// 大会ID・部門ID取得
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
    die("大会IDまたは部門IDが指定されていません");
}

// 部門情報取得
$sql = "SELECT name, distinction FROM departments WHERE id = :dept AND del_flg = 0";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id, PDO::PARAM_INT);
$stmt->execute();
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    die("部門が存在しません");
}

$dept_name = $department['name'];
$distinction = (int)$department['distinction']; // 1=団体戦, 2=個人戦
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV登録画面</title>
    <link rel="stylesheet" href="../../css/csv_connection/addtion_top.css">
</head>

<body>

    <div class="container">

        <div class="breadcrumb">
            メニュー > 登録・閲覧・削除 > 部門選択 > <?= htmlspecialchars($dept_name) ?> >
        </div>

        <h1 class="page-title"><?= htmlspecialchars($dept_name) ?>（<?= $distinction === 1 ? "団体戦" : "個人戦" ?>）</h1>

        <div class="upload-section card">
            <div class="card-header">
                <?php if ($distinction === 2): ?>
                    <h2>個人戦 CSV アップロード</h2>
                    <p class="card-description">形式：選手名,フリガナ,所属名</p>
                <?php else: ?>
                    <h2>団体戦 CSV アップロード</h2>
                    <p class="card-description">形式：チーム名,略称,先鋒,フリガナ,...（1行＝1チーム）</p>
                <?php endif; ?>
            </div>

            <div class="card-content">
                <form id="csvForm" action="csv_upload.php?id=<?= $tournament_id ?>&dept=<?= $department_id ?>"
                    method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input id="csvFile" type="file" name="csv_file" accept=".csv" required class="native-file-input">
                        <button type="button" class="btn btn-outline btn-file" id="fileBtn">
                            ファイルを選択
                        </button>
                        <span id="fileName" class="file-name">選択されていません</span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-upload">CSVを登録する</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="actions">
            <?php if ($distinction === 2): ?>
                <!-- 個人戦 -->
                <a href="Admin_addition_view.php?id=<?= $tournament_id ?>&dept=<?= $department_id ?>" class="btn-view">
                    登録済みデータを見る
                </a>
            <?php else: ?>
                <!-- 団体戦 -->
                <a href="Admin_team_list.php?id=<?= $tournament_id ?>&dept=<?= $department_id ?>" class="btn-view">
                    登録済みチームを見る
                </a>
            <?php endif; ?>
            <button class="back-button" onclick="location.href='./Admin_addition_selection_department.php?id=<?= urlencode($tournament_id) ?>'">戻る</button>
        </div>
    </div>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('csvFile');
        const fileBtn = document.getElementById('fileBtn');
        const fileName = document.getElementById('fileName');

        fileBtn.addEventListener('click', () => fileInput.click());
        fileBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files.length > 0) {
                fileName.textContent = fileInput.files[0].name;
                fileName.style.color = '#111827';
            } else {
                fileName.textContent = '選択されていません';
                fileName.style.color = '#6b7280';
            }
        });
    });
</script>

</html>