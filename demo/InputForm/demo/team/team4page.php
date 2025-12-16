<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦 - 試合結果入力</title>
    <link rel="stylesheet" href="team4page.css">
</head>
<body>
    <!-- 試合結果選択モーダル -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="result-selection-modal">
            <div class="modal-title">ここは試合結果の決定方法で</div>
            <div class="modal-subtitle">引き分け(引分け)、一本勝ち(一本勝)、延長、から選びます</div>
            <div class="result-buttons">
                <button class="result-btn" onclick="selectResult('引分け')">引分け</button>
                <button class="result-btn" onclick="selectResult('一本勝')">一本勝</button>
                <button class="result-btn" onclick="selectResult('延長')">延長</button>
                <button class="result-btn" onclick="selectResult('赤不戦勝')">赤不戦勝</button>
                <button class="result-btn" onclick="selectResult('白不戦勝')">白不戦勝</button>
            </div>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="container">
        <div class="header">
            <span class="tournament-info">○○大会</span>　
            <span class="tournament-info">○○部門</span>
            <button class="captain-btn">大将</button>
            <div class="action-buttons-top">
                <button class="btn-submit">送信</button>
                <a href="team3page.php" button class="btn-back">戻る</a>
                <button class="btn-undecided" onclick="location.href='team5page.php'">代表決定戦</button>
            </div>
        </div>
        
        <div class="team-section">
            <div class="player-row">
                <span class="label">名前</span>
                <div class="selected-value" onclick="showDropdown(this)">選択してください</div>
                <div class="player-numbers">
                    <span>1</span>
                    <span>2</span>
                    <span>3</span>
                </div>
            </div>
        </div>
        
        <div class="separator"></div>
        
        <div class="team-section">
            <div class="player-row">
                <span class="label">名前</span>
                <div class="selected-value" onclick="showDropdown(this)">選択してください</div>
                <div class="player-numbers">
                    <span>1</span>
                    <span>2</span>
                    <span>3</span>
                </div>
            </div>
        </div>
        
        <div class="action-buttons-bottom">
            <button class="btn-cancel">取り消し</button>
        </div>
    </div>
    
    <script>
        // ページ読み込み時にモーダルを表示
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalOverlay').classList.remove('hidden');
        });

        // 試合結果を選択
        function selectResult(result) {
            console.log('選択された結果: ' + result);
            
            // モーダルを閉じる
            closeModal();
            
            // ここで選択結果を処理
            // 例: フォームに値を設定、データベースに保存など
            alert('選択された結果: ' + result);
        }

        // モーダルを閉じる
        function closeModal() {
            document.getElementById('modalOverlay').classList.add('hidden');
        }

        // モーダルの外側をクリックしたら閉じる
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ドロップダウンメニューを表示（将来の拡張用）
        function showDropdown(element) {
            alert('ここは選手の名前を選択する項目です。');
        }

        // 送信ボタン
        document.querySelector('.btn-submit').addEventListener('click', function() {
            alert('このボタンを押したときに送信処理を実行します。');
        });

        // 戻るボタン
        document.querySelector('.btn-back').addEventListener('click', function() {
            if (confirm('前のページに戻るボタンです。')) {
                window.history.back();
            }
        });

        // 取り消しボタン
        document.querySelector('.btn-cancel').addEventListener('click', function() {
            if (confirm('入力内容を取り消すボタンです。')) {
                location.reload();
            }
        });
    </script>
</body>
</html>