<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トーナメント表</title>
    <a href="Resultonly.php" class="back-link">大会選択に戻る</a>
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
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .match:hover {
            background: #f8f9fa;
        }
        .team {
            background: #e63946;
            color: white;
            padding: 12px 15px;
            margin: 2px 0;
            border-radius: 4px;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .team a:hover {
            text-decoration: underline;
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

        /* スコアシートのスタイル */
        #scoreSheet {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .scoresheet-container {
            max-width: 1200px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            position: relative;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            padding: 5px 10px;
        }
        .close-btn:hover {
            color: #000;
        }
        .save-btn {
            margin: 20px auto;
            display: block;
            padding: 12px 40px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .save-btn:hover {
            background: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 13px;
        }
        th {
            background-color: #e8e8e8;
            font-weight: normal;
            height: 40px;
        }
        td {
            background-color: white;
            height: 70px;
        }
        .confirm-cell {
            height: 50px;
            background-color: #e8e8e8;
        }
        .prefecture-cell {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        input[type="text"], input[type="number"] {
            width: 90%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            text-align: center;
        }
        .editable-cell {
            height: 35px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>トーナメント表</h1>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="検索">
        </div>

        <div class="tournament" id="tournament">
            <!-- 1回戦 -->
            <div class="round">
                <div class="round-title">1回戦</div>
                <div class="match" data-round="1" data-match="1">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=A&round=1&match=1">名前A</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=B&round=1&match=1">名前B</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="2">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=C&round=1&match=2">名前C</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=D&round=1&match=2">名前D</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="3">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=E&round=1&match=3">名前E</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=F&round=1&match=3">名前F</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="4">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=G&round=1&match=4">名前G</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=H&round=1&match=4">名前H</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="5">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=I&round=1&match=5">名前I</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=J&round=1&match=5">名前J</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="6">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=K&round=1&match=6">名前K</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=L&round=1&match=6">名前L</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="7">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=M&round=1&match=7">名前M</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=N&round=1&match=7">名前N</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="1" data-match="8">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=O&round=1&match=8">名前O</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=P&round=1&match=8">名前P</a></span><span class="score">-</span></div>
                </div>
            </div>

            <!-- 2回戦 -->
            <div class="round">
                <div class="round-title">2回戦</div>
                <div class="match" data-round="2" data-match="1">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=A&round=2&match=1">名前A</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=D&round=2&match=1">名前D</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="2" data-match="2">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=E&round=2&match=2">名前E</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=H&round=2&match=2">名前H</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="2" data-match="3">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=I&round=2&match=3">名前I</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=L&round=2&match=3">名前L</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="2" data-match="4">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=M&round=2&match=4">名前M</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=P&round=2&match=4">名前P</a></span><span class="score">-</span></div>
                </div>
            </div>

            <!-- 準決勝 -->
            <div class="round">
                <div class="round-title">準決勝</div>
                <div class="match" data-round="3" data-match="1">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=A&round=3&match=1">名前A</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=H&round=3&match=1">名前H</a></span><span class="score">-</span></div>
                </div>
                <div class="match" data-round="3" data-match="2">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=I&round=3&match=2">名前I</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=P&round=3&match=2">名前P</a></span><span class="score">-</span></div>
                </div>
            </div>

            <!-- 決勝 -->
            <div class="round">
                <div class="round-title">決勝</div>
                <div class="match" data-round="4" data-match="1">
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=A&round=4&match=1">名前A</a></span><span class="score">-</span></div>
                    <div class="team"><span class="team-name"><a href="scoresheet.php?team=P&round=4&match=1">名前P</a></span><span class="score">-</span></div>
                </div>
            </div>

            <!-- 優勝 -->
            <div class="round">
                <div class="round-title">優勝</div>
                <div class="champion">
                    名前A
                </div>
            </div>
        </div>
    </div>
    