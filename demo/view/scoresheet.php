<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>剣道大会スコアシート</title>
    <style>
        body {
            font-family: 'MS Gothic', 'Osaka-Mono', monospace;
            padding: 20px;
            background-color: white;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>都道府県</th>
                    <th>先鋒</th>
                    <th>次鋒</th>
                    <th>中堅</th>
                    <th>副将</th>
                    <th>大将</th>
                    <th>勝者数</th>
                    <th>総本数</th>
                    <th>代表</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="prefecture-cell" rowspan="2"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td rowspan="2"></td>
                    <td rowspan="2"></td>
                    <td rowspan="4"></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="prefecture-cell" rowspan="2"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td rowspan="2"></td>
                    <td rowspan="2"></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="confirm-cell">確認</td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                    <td class="confirm-cell"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

