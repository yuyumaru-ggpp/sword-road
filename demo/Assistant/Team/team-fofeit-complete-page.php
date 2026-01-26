<?php
session_start();

/* ===============================
   セッションチェック
=============================== */
if (!isset($_SESSION['team_forfeit_complete'], $_SESSION['division_id'])) {
    header('Location: match_input.php');
    exit;
}

// フラグをクリア
unset($_SESSION['team_forfeit_complete']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>送信完了</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Hiragino Sans',Meiryo,sans-serif;
    background:#f5f5f5;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem;
}
.container{max-width:1100px;width:100%;}
.success-box{
    background:#fff;
    border:4px solid #22c55e;
    border-radius:24px;
    padding:4rem 3rem;
    text-align:center;
    margin-bottom:3rem;
}
.success-icon{
    width:80px;
    height:80px;
    background:#22c55e;
    border-radius:16px;
    margin:0 auto 1.5rem;
    display:flex;
    align-items:center;
    justify-content:center;
}
.success-icon::before{
    content:'✓';
    color:#fff;
    font-size:3.5rem;
    font-weight:bold;
}
.success-message{
    font-size:2.5rem;
    font-weight:bold;
    margin-bottom:1rem;
}
.success-sub{
    font-size:1.2rem;
    color:#666;
}
.button-container{
    display:flex;
    gap:2rem;
    justify-content:center;
    flex-wrap:wrap;
}
.action-button{
    padding:1.25rem 3.5rem;
    font-size:1.25rem;
    border:3px solid #000;
    border-radius:50px;
    background:#fff;
    cursor:pointer;
    transition:background-color 0.2s;
}
.action-button:hover{background:#f9fafb;}
</style>
</head>
<body>
<div class="container">
    <div class="success-box">
        <div class="success-icon"></div>
        <div class="success-message">不戦勝を登録しました</div>
        <div class="success-sub">試合結果が保存されました</div>
    </div>

    <div class="button-container">
        <button class="action-button"
            onclick="location.href='match_input.php?division_id=<?= $_SESSION['division_id'] ?>'">
            連続で入力する
        </button>
        <button class="action-button"
            onclick="location.href='../index.php'">
            部門選択画面に戻る
        </button>
    </div>
</div>
</body>
</html>