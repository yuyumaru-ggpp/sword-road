<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登録確認</title>
    <link rel="stylesheet" href="../css/tournament-register-confirm-style.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="Admin_registration_create.php" class="breadcrumb-link">大会登録></a>
        <a href="#" class="breadcrumb-link">登録確認></a>
    </div>
    
    <div class="container">
        <h1 class="page-title">以下の情報でよろしいですか？</h1>
        
        <!-- 大会情報セクション -->
        <div class="confirm-section">
            <h2 class="section-title">大会情報</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">大会名称</span>
                    <span class="info-value" id="tournamentName">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">大会会場</span>
                    <span class="info-value" id="venue">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">大会開催日</span>
                    <span class="info-value" id="eventDate">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">試合会場数</span>
                    <span class="info-value" id="courtCount">-</span>
                </div>
            </div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">戻る</button>
            <button class="action-button primary-button" onclick="submitRegistration()">確定</button>
        </div>
    </div>
    
    <script>
        // localStorageから大会情報を取得
        const tournamentDataStr = localStorage.getItem('tournamentData');
        console.log('localStorageの生データ:', tournamentDataStr);
        
        const tournamentData = JSON.parse(tournamentDataStr || '{}');
        console.log('取得した大会情報:', tournamentData);
        console.log('各フィールド:', {
            name: tournamentData.tournament_name,
            venue: tournamentData.venue,
            date: tournamentData.event_date,
            count: tournamentData.court_count
        });
        
        // 大会情報を表示
        if (tournamentData.tournament_name) {
            document.getElementById('tournamentName').textContent = tournamentData.tournament_name;
        } else {
            console.error('大会名称がありません');
        }
        
        if (tournamentData.venue) {
            document.getElementById('venue').textContent = tournamentData.venue;
        } else {
            console.error('大会会場がありません');
        }
        
        if (tournamentData.event_date) {
            // 日付をフォーマット (YYYY-MM-DD -> YYYY年MM月DD日)
            const dateStr = tournamentData.event_date;
            const [year, month, day] = dateStr.split('-');
            const formatted = `${year}年${parseInt(month)}月${parseInt(day)}日`;
            document.getElementById('eventDate').textContent = formatted;
        } else {
            console.error('大会開催日がありません');
        }
        
        if (tournamentData.court_count) {
            document.getElementById('courtCount').textContent = tournamentData.court_count + '試合場';
        } else {
            console.error('試合会場数がありません');
        }
        
        function submitRegistration() {
            if (!tournamentDataStr) {
                alert('大会情報が正しく取得できませんでした');
                return;
            }
            
            // ここで実際の登録処理を行う
            console.log('登録データ:', tournamentData);
            alert('登録が完了しました');
            
            // localStorageをクリア
            localStorage.removeItem('tournamentData');
            
            // 大会一覧画面に遷移
            location.href = 'tournament-list.php';
        }
    </script>
</body>
</html>