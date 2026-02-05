<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>大会登録</title>
    <link rel="stylesheet" href="../../css/Admin_registration_create.css">
</head>
<body>

<div class="breadcrumb">
    <a href="Admin_top.php" class="breadcrumb-link">メニュー ></a>
    <a href="Admin_selection.php" class="breadcrumb-link">大会登録・名称変更 ></a>
    <a href="#" class="breadcrumb-link">大会登録 ></a>
</div>

<div class="container">
    <h2>大会情報</h2>

    <form action="tournament-register-confirm.php" method="POST" id="tournamentForm">

        <div class="form-grid">

            <div class="form-group">
                <label>大会名称<span class="required">*</span></label>
                <input type="text" name="tournament_name" id="tournament-name" class="form-input" required>
            </div>

            <div class="form-group">
                <label>大会会場<span class="required">*</span></label>
                <input type="text" name="venue" id="venue" class="form-input" required>
            </div>

            <div class="form-group">
                <label>大会開催日<span class="required">*</span></label>
                <input type="date" name="event_date" id="event-date" class="form-input" required>
            </div>

            <div class="form-group">
                <label>大会パスワード<span class="required">*</span></label>
                <input type="password" name="tournament_password" id="tournament-password" class="form-input" required>
            </div>

            <div class="form-group">
                <label>大会パスワード（再入力）<span class="required">*</span></label>
                <input type="password" id="tournament-password-confirm" class="form-input" required>
            </div>

            <div class="form-group">
                <label>試合会場数<span class="required">*</span></label>
                <select name="court_count" id="court-count" class="form-input" required>
                    <option value="">選択してください</option>
                    <option value="4">4試合場</option>
                    <option value="5">5試合場</option>
                    <option value="6">6試合場</option>
                    <option value="7">7試合場</option>
                    <option value="8">8試合場</option>
                    <option value="9">9試合場</option>
                    <option value="10">10試合場</option>
                </select>
            </div>

        </div>

        <div class="button-container">
            <button type="button" class="action-button" onclick="location.href='Admin_selection.php'">戻る</button>
            <button type="submit" class="action-button">確認画面へ</button>
        </div>

    </form>
</div>

<script>
document.getElementById('tournamentForm').addEventListener('submit', function(e) {

    const pass = document.getElementById('tournament-password').value;
    const pass2 = document.getElementById('tournament-password-confirm').value;

    if (pass !== pass2) {
        alert('大会パスワードが一致しません');
        e.preventDefault();
    }
});
</script>

</body>
</html>