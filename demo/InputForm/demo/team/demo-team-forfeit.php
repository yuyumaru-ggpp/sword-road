<?php
session_start();

// セッションに試合番号がない場合は入力画面に戻す
if (!isset($_SESSION['match_number'])) {
    header('Location: match_input.php');
    exit;
}

$match_number = htmlspecialchars($_SESSION['match_number']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>団体戦不戦勝</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            padding: 1rem;
        }

        @media (min-width: 768px) {
            body {
                padding: 2rem;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
        }

        .header-text {
            font-size: 1rem;
        }

        .match-number-box {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #e5e7eb;
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
        }

        .match-number-label {
            font-size: 1rem;
            white-space: nowrap;
        }

        .match-number-value {
            font-size: 1rem;
            font-weight: 600;
        }

        @media (min-width: 768px) {
            .header {
                gap: 2rem;
                margin-bottom: 3rem;
            }
            
            .header-text {
                font-size: 1.5rem;
            }

            .match-number-box {
                padding: 0.75rem 2rem;
            }

            .match-number-label {
                font-size: 1.5rem;
            }

            .match-number-value {
                font-size: 1.5rem;
            }
        }

        .note {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 3rem;
        }

        @media (min-width: 768px) {
            .note {
                font-size: 1.125rem;
                margin-bottom: 4rem;
            }
        }

        .team-section {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 4rem;
            flex-wrap: wrap;
        }

        @media (min-width: 768px) {
            .team-section {
                gap: 8rem;
            }
        }

        .team-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .team-column {
                gap: 2rem;
            }
        }

        .team-input {
            width: 150px;
            padding: 0.75rem;
            font-size: 1.25rem;
            text-align: center;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.2s;
        }

        .team-input:focus {
            border-color: #3b82f6;
        }

        @media (min-width: 768px) {
            .team-input {
                width: 200px;
                padding: 1rem;
                font-size: 1.5rem;
            }
        }

        .vs-container {
            display: flex;
            align-items: center;
            align-self: stretch;
            justify-content: center;
        }

        .vs-text {
            font-size: 1.5rem;
            font-weight: normal;
        }

        @media (min-width: 768px) {
            .vs-text {
                font-size: 2rem;
            }
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }

        .player-change-button,
        .forfeit-button {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            background-color: white;
            border: 2px solid #000;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
            min-width: 150px;
        }

        @media (min-width: 768px) {
            .player-change-button,
            .forfeit-button {
                padding: 1rem 2.5rem;
                font-size: 1.25rem;
                min-width: 180px;
            }
        }

        .player-change-button:hover,
        .forfeit-button:hover {
            background-color: #f9fafb;
        }

        .player-change-button:active,
        .forfeit-button:active {
            background-color: #e5e7eb;
        }

        .action-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        @media (min-width: 768px) {
            .action-buttons {
                gap: 3rem;
            }
        }

        .action-button {
            padding: 0.875rem 2.5rem;
            font-size: 1.125rem;
            background-color: white;
            border: 2px solid #000;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.2s;
            white-space: nowrap;
        }

        @media (min-width: 768px) {
            .action-button {
                padding: 1rem 3rem;
                font-size: 1.25rem;
            }
        }

        .action-button:hover {
            background-color: #f9fafb;
        }

        .action-button:active {
            background-color: #e5e7eb;
        }

        .player-dropdown {
            display: none;
            margin-top: 1rem;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 350px;
        }

        .player-dropdown.active {
            display: block;
        }

        .player-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .player-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background-color: #f9fafb;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        @media (min-width: 768px) {
            .player-item {
                font-size: 1rem;
                padding: 1rem;
            }
        }

        .player-position {
            font-weight: 600;
            min-width: 60px;
            color: #374151;
        }

        .player-select {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.9rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.2s;
            background-color: white;
            cursor: pointer;
        }

        .player-select:focus {
            border-color: #3b82f6;
        }

        @media (min-width: 768px) {
            .player-select {
                font-size: 1rem;
            }
        }

        .close-dropdown {
            width: 100%;
            padding: 0.625rem;
            font-size: 0.9rem;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
            font-weight: 500;
        }

        .close-dropdown:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-text">団体戦</span>
            <span class="header-text">〇〇大会</span>
            <span class="header-text">〇〇部門</span>
            <span class="header-text">5人制用</span>
            <div class="match-number-box">
                <span class="match-number-label">試合番号</span>
                <span class="match-number-value" id="matchNumberDisplay"><?php echo $match_number; ?></span>
            </div>
        </div>
        
        <p class="note">※不戦勝ボタンは勝った方の選手を押してください。</p>
        
        <div class="team-section">
            <div class="team-column">
                <input type="text" class="team-input" placeholder="チームID" id="team1">
                <div class="button-group">
                    <button class="player-change-button" id="leftPlayerChange">選手変更</button>
                    <div class="player-dropdown" id="leftDropdown">
                        <div class="player-list" id="leftPlayerList">
                            <!-- 選手リストが動的に追加されます -->
                        </div>
                        <button class="close-dropdown" id="leftCloseDropdown">閉じる</button>
                    </div>
                    <button class="forfeit-button" id="leftButton">不戦勝</button>
                </div>
            </div>
            
            <div class="vs-container">
                <span class="vs-text">対</span>
            </div>
            
            <div class="team-column">
                <input type="text" class="team-input" placeholder="チームID" id="team2">
                <div class="button-group">
                    <button class="player-change-button" id="rightPlayerChange">選手変更</button>
                    <div class="player-dropdown" id="rightDropdown">
                        <div class="player-list" id="rightPlayerList">
                            <!-- 選手リストが動的に追加されます -->
                        </div>
                        <button class="close-dropdown" id="rightCloseDropdown">閉じる</button>
                    </div>
                    <button class="forfeit-button" id="rightButton">不戦勝</button>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <button class="action-button" id="confirmButton">決定</button>
            <button class="action-button" onclick="history.back()">説明に戻る</button>
        </div>
    </div>
    
    <script>
        let selectedWinner = null;
        
        const leftButton = document.getElementById('leftButton');
        const rightButton = document.getElementById('rightButton');
        const leftPlayerChange = document.getElementById('leftPlayerChange');
        const rightPlayerChange = document.getElementById('rightPlayerChange');
        const confirmButton = document.getElementById('confirmButton');
        const leftDropdown = document.getElementById('leftDropdown');
        const rightDropdown = document.getElementById('rightDropdown');
        const leftPlayerList = document.getElementById('leftPlayerList');
        const rightPlayerList = document.getElementById('rightPlayerList');
        const leftCloseDropdown = document.getElementById('leftCloseDropdown');
        const rightCloseDropdown = document.getElementById('rightCloseDropdown');
        
        // サンプルの選手データ（実際にはサーバーから取得）
        const leftTeamPlayers = [
            { id: 1, name: '選手1', position: '先鋒' },
            { id: 2, name: '選手2', position: '次鋒' },
            { id: 3, name: '選手3', position: '中堅' },
            { id: 4, name: '選手4', position: '副将' },
            { id: 5, name: '選手5', position: '大将' }
        ];
        
        const rightTeamPlayers = [
            { id: 8, name: '選手A', position: '先鋒' },
            { id: 9, name: '選手B', position: '次鋒' },
            { id: 10, name: '選手C', position: '中堅' },
            { id: 11, name: '選手D', position: '副将' },
            { id: 12, name: '選手E', position: '大将' }
        ];
        
        // チームの全選手リスト（選択肢用）
        const leftTeamAllPlayers = [
            { id: 1, name: '選手1' },
            { id: 2, name: '選手2' },
            { id: 3, name: '選手3' },
            { id: 4, name: '選手4' },
            { id: 5, name: '選手5' },
            { id: 6, name: '選手6' },
            { id: 7, name: '選手7' },
            { id: 15, name: '選手8' },
            { id: 16, name: '選手9' },
            { id: 17, name: '選手10' }
        ];
        
        const rightTeamAllPlayers = [
            { id: 8, name: '選手A' },
            { id: 9, name: '選手B' },
            { id: 10, name: '選手C' },
            { id: 11, name: '選手D' },
            { id: 12, name: '選手E' },
            { id: 13, name: '選手F' },
            { id: 14, name: '選手G' },
            { id: 18, name: '選手H' },
            { id: 19, name: '選手I' },
            { id: 20, name: '選手J' }
        ];
        
        function createPlayerList(players, allPlayers, container) {
            container.innerHTML = '';
            players.forEach(player => {
                const playerItem = document.createElement('div');
                playerItem.className = 'player-item';
                
                // セレクトボックスを作成
                let selectHTML = `<select class="player-select" data-position="${player.position}">`;
                selectHTML += `<option value="">選択なし</option>`;
                allPlayers.forEach(p => {
                    const selected = p.id === player.id ? 'selected' : '';
                    selectHTML += `<option value="${p.id}" ${selected}>${p.name}</option>`;
                });
                selectHTML += `</select>`;
                
                playerItem.innerHTML = `
                    <span class="player-position">${player.position}</span>
                    ${selectHTML}
                `;
                container.appendChild(playerItem);
            });
            
            // セレクトボックスの変更を監視
            container.querySelectorAll('.player-select').forEach(select => {
                select.addEventListener('change', function() {
                    const playerId = this.value;
                    const position = this.getAttribute('data-position');
                    const playerName = this.options[this.selectedIndex].text;
                    if (playerId) {
                        console.log(`${position}を選手ID ${playerId}（${playerName}）に変更`);
                    } else {
                        console.log(`${position}を未選択に変更`);
                    }
                    // 実際にはここでサーバーに保存する処理を追加
                });
            });
        }
        
        leftButton.addEventListener('click', function() {
            if (selectedWinner === 'left') {
                selectedWinner = null;
                leftButton.style.backgroundColor = 'white';
                leftButton.style.color = 'black';
                leftButton.style.borderColor = '#000';
            } else {
                selectedWinner = 'left';
                leftButton.style.backgroundColor = '#3b82f6';
                leftButton.style.color = 'white';
                leftButton.style.borderColor = '#3b82f6';
                
                rightButton.style.backgroundColor = 'white';
                rightButton.style.color = 'black';
                rightButton.style.borderColor = '#000';
            }
        });
        
        rightButton.addEventListener('click', function() {
            if (selectedWinner === 'right') {
                selectedWinner = null;
                rightButton.style.backgroundColor = 'white';
                rightButton.style.color = 'black';
                rightButton.style.borderColor = '#000';
            } else {
                selectedWinner = 'right';
                rightButton.style.backgroundColor = '#3b82f6';
                rightButton.style.color = 'white';
                rightButton.style.borderColor = '#3b82f6';
                
                leftButton.style.backgroundColor = 'white';
                leftButton.style.color = 'black';
                leftButton.style.borderColor = '#000';
            }
        });
        
        leftPlayerChange.addEventListener('click', function() {
            const team1 = document.getElementById('team1').value;
            
            if (!team1) {
                alert('チームIDを入力してください');
                return;
            }
            
            // プルダウンの表示切り替え
            const isActive = leftDropdown.classList.contains('active');
            leftDropdown.classList.toggle('active');
            rightDropdown.classList.remove('active');
            
            // 初回表示時に選手リストを生成
            if (!isActive && leftPlayerList.children.length === 0) {
                createPlayerList(leftTeamPlayers, leftTeamAllPlayers, leftPlayerList);
            }
        });
        
        rightPlayerChange.addEventListener('click', function() {
            const team2 = document.getElementById('team2').value;
            
            if (!team2) {
                alert('チームIDを入力してください');
                return;
            }
            
            // プルダウンの表示切り替え
            const isActive = rightDropdown.classList.contains('active');
            rightDropdown.classList.toggle('active');
            leftDropdown.classList.remove('active');
            
            // 初回表示時に選手リストを生成
            if (!isActive && rightPlayerList.children.length === 0) {
                createPlayerList(rightTeamPlayers, rightTeamAllPlayers, rightPlayerList);
            }
        });
        
        leftCloseDropdown.addEventListener('click', function() {
            leftDropdown.classList.remove('active');
            alert('選手情報を保存しました');
        });
        
        rightCloseDropdown.addEventListener('click', function() {
            rightDropdown.classList.remove('active');
            alert('選手情報を保存しました');
        });
        
        confirmButton.addEventListener('click', function() {
            const team1 = document.getElementById('team1').value;
            const team2 = document.getElementById('team2').value;
            
            if (!team1 || !team2) {
                alert('両方のチームIDを入力してください');
                return;
            }
            
            location.href = 'demo-action.php';
        });
    </script>
</body>
</html>