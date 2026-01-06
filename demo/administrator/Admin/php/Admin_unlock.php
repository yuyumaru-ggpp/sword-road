<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会のロック解除</title>
    <link rel="stylesheet" href="../css/Admin_unlock.css">
</head>
<body>
    <div class="breadcrumb">
        <a href="Admin_top.php" class="breadcrumb-link">メニュー></a>
        <a href="#" class="breadcrumb-link">大会ロック解除></a>
    </div>
    
    <div class="container">
        <h1 class="title">大会のロック</h1>
        
        <div class="search-container">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" class="search-input" placeholder="IDまたは大会名">
            </div>
            <button class="search-button" onclick="searchTournaments()">検索</button>
        </div>
        
        <div class="tournament-list-container">
            <div class="tournament-list" id="tournamentList">
                <!-- トーナメントリストがここに動的に生成されます -->
            </div>
        </div>
        
        <div class="back-button-container">
            <button class="back-button" onclick="location.href='Admin_top.php'">戻る</button>
        </div>
    </div>

    <script>
        // 大会データ（実際にはPHPからJSONで取得することを想定）
        let tournaments = [
            { id: 19, name: '春季トーナメント', locked: true },
            { id: 18, name: '冬季選手権大会', locked: true },
            { id: 17, name: '秋の大会', locked: false },
            { id: 16, name: '夏季大会', locked: true },
            { id: 15, name: '新人戦', locked: false }
        ];

        // ページ読み込み時に大会リストを表示
        window.addEventListener('DOMContentLoaded', function() {
            displayTournaments(tournaments);
        });

        // 大会リストを表示する関数
        function displayTournaments(data) {
            const listContainer = document.getElementById('tournamentList');
            listContainer.innerHTML = '';

            if (data.length === 0) {
                listContainer.innerHTML = '<div style="text-align: center; padding: 2rem; color: #6b7280;">該当する大会が見つかりません</div>';
                return;
            }

            data.forEach(tournament => {
                const row = document.createElement('div');
                row.className = 'tournament-row';

                const id = document.createElement('span');
                id.className = 'tournament-id';
                id.textContent = `ID ${tournament.id}`;

                const name = document.createElement('span');
                name.className = 'tournament-name';
                name.textContent = tournament.name;

                const status = document.createElement('span');
                status.className = 'lock-status';
                status.textContent = tournament.locked ? 'ロック中' : '解除済み';
                status.style.color = tournament.locked ? '#ef4444' : '#10b981';

                const button = document.createElement('button');
                button.className = 'lock-icon';
                button.textContent = tournament.locked ? '🔒' : '🔓';
                button.title = tournament.locked ? 'クリックして解除' : 'クリックしてロック';
                button.onclick = () => toggleLock(tournament.id);

                row.appendChild(id);
                row.appendChild(name);
                row.appendChild(status);
                row.appendChild(button);

                listContainer.appendChild(row);
            });
        }

        // ロック状態を切り替える関数
        function toggleLock(tournamentId) {
            const tournament = tournaments.find(t => t.id === tournamentId);
            if (tournament) {
                const action = tournament.locked ? '解除' : 'ロック';
                if (confirm(`ID ${tournamentId} の大会を${action}しますか？`)) {
                    tournament.locked = !tournament.locked;
                    
                    // 実際のシステムでは、ここでPHPにAjaxリクエストを送信
                    // 例: updateTournamentLock(tournamentId, tournament.locked);
                    
                    displayTournaments(tournaments);
                    
                    alert(`大会を${action}しました`);
                }
            }
        }

        // 検索機能
        function searchTournaments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            
            if (searchTerm === '') {
                displayTournaments(tournaments);
                return;
            }

            const filtered = tournaments.filter(tournament => {
                const idMatch = tournament.id.toString().includes(searchTerm);
                const nameMatch = tournament.name.toLowerCase().includes(searchTerm);
                return idMatch || nameMatch;
            });

            displayTournaments(filtered);
        }

        // Enterキーで検索
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchTournaments();
                }
            });
        });

        // PHPと連携する場合の例（コメントアウト）
        /*
        function updateTournamentLock(tournamentId, isLocked) {
            fetch('update-tournament-lock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: tournamentId,
                    locked: isLocked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('ロック状態を更新しました');
                } else {
                    console.error('エラー:', data.message);
                }
            })
            .catch(error => {
                console.error('通信エラー:', error);
            });
        }
        */
    </script>
</body>
</html>