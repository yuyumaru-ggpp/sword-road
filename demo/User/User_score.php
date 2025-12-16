<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>将棋トーナメント表</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'MS Gothic', 'Hiragino Sans', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 2px solid #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            min-height: 40px;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
            white-space: nowrap;
        }
        .prefecture {
            background: #fff;
            font-weight: bold;
            width: 100px;
        }
        .rank-col {
            width: 120px;
        }
        .stats-col {
            width: 80px;
        }
        .representative {
            width: 80px;
            position: relative;
        }
        .bracket-cell {
            height: 80px;
            position: relative;
            padding: 0;
        }
        .mini-bracket {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
            align-items: center;
            padding: 5px;
        }
        .bracket-row {
            display: flex;
            width: 100%;
            height: 30px;
            position: relative;
        }
        .bracket-box {
            border: 1px solid #000;
            flex: 1;
            margin: 1px;
            background: white;
        }
        .input-field {
            width: 100%;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 12px;
            padding: 2px;
        }
        .time-row th {
            background: #fff;
            text-align: left;
            padding-left: 10px;
        }
        .vertical-text {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            padding: 10px 5px;
        }
        .large-cell {
            height: 150px;
        }
        .stats-input {
            width: 100%;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // 都道府県データ
        $prefectures = ['', '', '', '']; // 空白行を含む
        
        // 階級
        $ranks = ['先鋒', '次鋒', '中堅', '副将', '大将'];
        
        // 統計列
        $stats = ['勝者数', '総本数', '勝敗', '代表'];
        ?>

        <table>
            <thead>
                <tr>
                    <th class="prefecture">都道府県</th>
                    <?php foreach ($ranks as $rank): ?>
                    <th class="rank-col"><?php echo $rank; ?></th>
                    <?php endforeach; ?>
                    <?php foreach ($stats as $stat): ?>
                    <th class="stats-col"><?php echo $stat; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- 上部チーム -->
                <tr>
                    <td class="prefecture large-cell">
                        <input type="text" class="input-field" placeholder="都道府県名">
                    </td>
                    <?php foreach ($ranks as $index => $rank): ?>
                    <td class="large-cell"></td>
                    <?php endforeach; ?>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell representative">
                        <div class="vertical-text">本選から</div>
                    </td>
                </tr>

                <!-- ミニトーナメント行 -->
                <tr>
                    <td class="prefecture"></td>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <td class="bracket-cell">
                        <div class="mini-bracket">
                            <div class="bracket-row">
                                <div class="bracket-box"></div>
                                <div class="bracket-box"></div>
                            </div>
                            <div class="bracket-row">
                                <div class="bracket-box"></div>
                            </div>
                        </div>
                    </td>
                    <?php endfor; ?>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                <!-- 中央行 -->
                <tr>
                    <td class="prefecture large-cell"></td>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <td class="large-cell"></td>
                    <?php endfor; ?>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                </tr>

                <!-- 下部チーム -->
                <tr>
                    <td class="prefecture large-cell">
                        <input type="text" class="input-field" placeholder="都道府県名">
                    </td>
                    <?php foreach ($ranks as $index => $rank): ?>
                    <td class="large-cell"></td>
                    <?php endforeach; ?>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell"></td>
                    <td class="large-cell representative">
                        <div class="vertical-text">本選から</div>
                    </td>
                </tr>

                <!-- 試合時間行 -->
                <tr class="time-row">
                    <th colspan="11">試合時間</th>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ccc;">
            <h3>使い方</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>都道府県名の欄をクリックして入力できます</li>
                <li>各マス目をクリックして選手名や結果を入力できます</li>
                <li>ミニトーナメントは各階級での対戦を表示します</li>
            </ul>
        </div>
    </div>

    <script>
        // すべてのセルを編集可能にする
        document.querySelectorAll('td:not(.prefecture):not(.bracket-cell)').forEach(cell => {
            if (!cell.querySelector('input') && !cell.classList.contains('representative')) {
                cell.contentEditable = true;
                cell.style.cursor = 'text';
                
                cell.addEventListener('focus', function() {
                    this.style.background = '#ffffcc';
                });
                
                cell.addEventListener('blur', function() {
                    this.style.background = 'white';
                });
            }
        });

        // ミニブラケットのボックスも編集可能に
        document.querySelectorAll('.bracket-box').forEach(box => {
            box.contentEditable = true;
            box.style.cursor = 'text';
            
            box.addEventListener('focus', function() {
                this.style.background = '#ffffcc';
            });
            
            box.addEventListener('blur', function() {
                this.style.background = 'white';
            });
        });

        // データ保存機能（ローカルストレージ）
        function saveData() {
            const data = {};
            document.querySelectorAll('[contenteditable="true"]').forEach((el, index) => {
                data[`cell_${index}`] = el.textContent;
            });
            document.querySelectorAll('input').forEach((el, index) => {
                data[`input_${index}`] = el.value;
            });
            localStorage.setItem('tournamentData', JSON.stringify(data));
        }

        // データ読み込み
        function loadData() {
            const saved = localStorage.getItem('tournamentData');
            if (saved) {
                const data = JSON.parse(saved);
                document.querySelectorAll('[contenteditable="true"]').forEach((el, index) => {
                    if (data[`cell_${index}`]) {
                        el.textContent = data[`cell_${index}`];
                    }
                });
                document.querySelectorAll('input').forEach((el, index) => {
                    if (data[`input_${index}`]) {
                        el.value = data[`input_${index}`];
                    }
                });
            }
        }

        // ページ読み込み時にデータを復元
        window.addEventListener('load', loadData);

        // 入力時に自動保存
        document.addEventListener('input', saveData);
    </script>
</body>
</html>