<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トーナメント表</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Hiragino Sans', 'Meiryo', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .search-box {
            margin-bottom: 20px;
            text-align: center;
        }
        .search-box input {
            padding: 10px 15px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 20px;
            font-size: 14px;
        }
        .tournament {
            display: flex;
            justify-content: space-around;
            align-items: center;
            min-height: 600px;
            overflow-x: auto;
        }
        .round {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            min-width: 180px;
            height: 100%;
        }
        .match {
            display: flex;
            flex-direction: column;
            margin: 10px 0;
            position: relative;
        }
        .team {
            background: #e63946;
            color: white;
            padding: 12px 15px;
            margin: 2px 0;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team:hover {
            background: #d62828;
            transform: translateX(5px);
        }
        .team.winner {
            box-shadow: 0 0 10px rgba(230, 57, 70, 0.5);
        }
        .team.loser {
            background: #adb5bd;
        }
        .team-name {
            flex: 1;
        }
        .score {
            background: white;
            color: #333;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        .connector {
            position: absolute;
            border: 2px solid #dee2e6;
        }
        .round-title {
            text-align: center;
            font-weight: bold;
            color: #495057;
            margin-bottom: 15px;
            padding: 8px;
            background: #e9ecef;
            border-radius: 4px;
        }
        .champion {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            font-size: 18px;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏆 トーナメント表</h1>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 検索">
        </div>

        <?php
        // トーナメントデータ
        $tournament = [
            'round1' => [
                ['名前A', '名前B'],
                ['名前C', '名前D'],
                ['名前E', '名前F'],
                ['名前G', '名前H'],
                ['名前I', '名前J'],
                ['名前K', '名前L'],
                ['名前M', '名前N'],
                ['名前O', '名前P']
            ],
            'round2' => [
                ['名前A', '名前D'],
                ['名前E', '名前H'],
                ['名前I', '名前L'],
                ['名前M', '名前P']
            ],
            'round3' => [
                ['名前A', '名前H'],
                ['名前I', '名前P']
            ],
            'final' => [
                ['名前A', '名前P']
            ]
        ];

        // スコアデータ（ランダム生成）
        function generateScore() {
            return rand(0, 100);
        }
        ?>

        <div class="tournament">
            <!-- 1回戦 -->
            <div class="round">
                <div class="round-title">1回戦</div>
                <?php foreach ($tournament['round1'] as $match): ?>
                <div class="match">
                    <?php foreach ($match as $team): ?>
                    <div class="team">
                        <span class="team-name"><?php echo htmlspecialchars($team); ?></span>
                        <span class="score">スコア</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 2回戦 -->
            <div class="round">
                <div class="round-title">2回戦</div>
                <?php foreach ($tournament['round2'] as $match): ?>
                <div class="match">
                    <?php foreach ($match as $team): ?>
                    <div class="team">
                        <span class="team-name"><?php echo htmlspecialchars($team); ?></span>
                        <span class="score">スコア</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 準決勝 -->
            <div class="round">
                <div class="round-title">準決勝</div>
                <?php foreach ($tournament['round3'] as $match): ?>
                <div class="match">
                    <?php foreach ($match as $team): ?>
                    <div class="team">
                        <span class="team-name"><?php echo htmlspecialchars($team); ?></span>
                        <span class="score">スコア</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 決勝 -->
            <div class="round">
                <div class="round-title">決勝</div>
                <?php foreach ($tournament['final'] as $match): ?>
                <div class="match">
                    <?php foreach ($match as $team): ?>
                    <div class="team">
                        <span class="team-name"><?php echo htmlspecialchars($team); ?></span>
                        <span class="score">スコア</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 優勝 -->
            <div class="round">
                <div class="round-title">優勝</div>
                <div class="champion">
                    🏆 名前A
                </div>
            </div>
        </div>
    </div>

    <script>
        // 検索機能
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const teams = document.querySelectorAll('.team');
            
            teams.forEach(team => {
                const teamName = team.querySelector('.team-name').textContent.toLowerCase();
                if (teamName.includes(searchTerm)) {
                    team.style.background = '#2a9d8f';
                } else {
                    if (!team.classList.contains('loser')) {
                        team.style.background = '#e63946';
                    }
                }
            });
        });

        // チームクリックで勝者表示
        document.querySelectorAll('.team').forEach(team => {
            team.addEventListener('click', function() {
                this.classList.toggle('winner');
            });
        });
    </script>
</body>
</html>