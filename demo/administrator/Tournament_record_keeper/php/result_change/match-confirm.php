<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>変更確認</title>
    <link rel="stylesheet" href="../css/match-confirm-style.css">
</head>
<body>
    <div class="container">
        <table class="result-table">
            <thead>
                <tr>
                    <th class="team-header"></th>
                    <th>先鋒</th>
                    <th>次鋒</th>
                    <th>中堅</th>
                    <th>副将</th>
                    <th>大将</th>
                    <th>勝者数</th>
                    <th>得本数</th>
                    <th>代表戦</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="team-name"></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="total-cell"></td>
                    <td class="total-cell"></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
                <tr>
                    <td class="team-name"></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                    <td class="total-cell"></td>
                    <td class="total-cell"></td>
                    <td class="result-cell">
                        <div class="cell-content"></div>
                        <div class="cell-divider"></div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="button-container">
            <button class="action-button" onclick="history.back()">キャンセル</button>
            <button class="action-button" onclick="alert('変更を保存しました'); location.href='match-select.php'">この結果で変更</button>
        </div>
    </div>
</body>
</html>