<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>代表決定戦</title>
    <link rel="stylesheet" href="./team5page_v5.css">
</head>
<body>
    <div class="header">
        <div class="header-left">
            <span class="title">代表決定戦</span>
        </div>
    </div>

    <div class="team-name-row">
        <div class="label">チーム名</div>
        <div class="circles">
            <div class="circle"></div>
        </div>
    </div>

    <div class="name-row">
        <div class="label">名前</div>
        <input type="text" class="name-input" list="player-list">
    </div>

    <div class="middle">
        <div class="label"></div>
        <div class="middle-content">
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
            </div>
        </div>
    </div>

    <div class="name-row">
        <div class="label">名前</div>
        <input type="text" class="name-input" list="player-list">
    </div>

    <div class="team-name-row">
        <div class="label">チーム名</div>
        <div class="circles">
            <div class="circle"></div>
        </div>
    </div>

    <div class="footer">
        <a href="team.php" class="btn-next">送信</a>
        <a href="team4page.php." class="btn-next">戻る</a>
    </div>

    <datalist id="player-list">
        <option value="サンプル1"></option>
        <option value="サンプル2"></option>
        <option value="サンプル3"></option>
    </datalist>
</body>
</html>