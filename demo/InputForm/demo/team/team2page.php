<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ページ2</title>
    <link rel="stylesheet" href="./team2page_2.css">
</head>
<body>
    <div class="header">
        <div class="header-left">
            <span class="title">団体戦　○○大会　○○部門</span>
        </div>
        <div class="header-right">
            <button class="btn-white">先鋒</button>
            <a href="team3page.php" class="btn-next">次へ</a>
            <a href="team.php" class="btn-back">戻る</a>
        </div>
    </div>

    <div class="numbers-row">
        <span>1</span>
        <span>2</span>
        <span>3</span>
    </div>

    <div class="team-name-row">
        <div class="label">チーム名</div>
        <div class="circles">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
        </div>
    </div>

    <div class="name-row">
        <div class="label">名前</div>
        <input type="text" class="name-input" list="player-list">
    </div>

    <div class="middle">
        <div class="line"></div>
        <div class="select-group">
            <select>
                <option value="">▼</option>
                <option value="メ">メ</option>
                <option value="コ">コ</option>
                <option value="ド">ド</option>
                <option value="ツ">ツ</option>
                <option value="反">反</option>
                <option value="判">判</option>
            </select>
            <select>
                <option value="">▼</option>
                <option value="メ">メ</option>
                <option value="コ">コ</option>
                <option value="ド">ド</option>
                <option value="ツ">ツ</option>
                <option value="反">反</option>
                <option value="判">判</option>
            </select>
            <select>
                <option value="">▼</option>
                <option value="メ">メ</option>
                <option value="コ">コ</option>
                <option value="ド">ド</option>
                <option value="ツ">ツ</option>
                <option value="反">反</option>
                <option value="判">判</option>
            </select>
        </div>
        <div class="line"></div>
        <button class="btn-result">引分け</button>
    </div>

    <div class="name-row">
        <div class="label">名前</div>
        <input type="text" class="name-input" list="player-list">
    </div>

    <div class="team-name-row">
        <div class="label">チーム名</div>
        <div class="circles">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
        </div>
    </div>

    <div class="footer">
        <button class="btn-cancel">取り消し</button>
    </div>

    <datalist id="player-list">
        <option value="サンプル1"></option>
        <option value="サンプル2"></option>
        <option value="サンプル3"></option>
    </datalist>
</body>
</html>