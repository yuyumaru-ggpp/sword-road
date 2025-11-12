<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人戦demo</title>
    <link rel="stylesheet" href="solo.css">
</head>

<body>
    <!-- ヘッダー -->
    <div class="header">
        <h1>個人戦</h1>
        <h1>○○大会</h1>
        <h1>○○部門</h1>
    </div>
    <div class="role-header">
        <div class="role-numbers">
            <div>1</div>
            <div>2</div>
            <div>3</div>
        </div>
    </div>
    <!-- チームA -->
    <div class="player-section">


        <div class="player-row">
            <div class="name-input-wrapper">
                <label for="teamA">名前</label>
                <input type="text" name="teamA" id="teamA" list="player-list" placeholder="選手ID">
            </div>
            <div class="role-selects">
                <select name="teamA-role1" required>
                    <option value="">▲</option>
                    <option value="メ">メ</option>
                    <option value="コ">コ</option>
                    <option value="ド">ド</option>
                    <option value="ツ">ツ</option>
                    <option value="反">反</option>
                    <option value="判">判</option>
                </select>
                <select name="teamA-role2">
                    <option value="">▲</option>
                    <option value="メ">メ</option>
                    <option value="コ">コ</option>
                    <option value="ド">ド</option>
                    <option value="ツ">ツ</option>
                    <option value="反">反</option>
                    <option value="判">判</option>
                </select>
                <select name="teamA-role3">
                    <option value="">▲</option>
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

    <!-- 一本勝ちセクション -->
    <div class="win-section">
        <select name="win-role" required>
            <option value="">一本勝</option>
            <option value="引き分け">引き分け</option>
            <option value="一本勝ち">一本勝ち</option>
            <option value="延長">延長</option>
        </select>
    </div>

    <!-- チームB -->
    <div class="player-section">
        <div class="player-row">
            <div class="name-input-wrapper">
                <label for="teamB">名前</label>
                <input type="text" name="teamB" id="teamB" list="player-list" placeholder="選手ID">
            </div>
            <div class="role-selects">
                <select name="teamB-role1" required>
                    <option value="">▼</option>
                    <option value="メ">メ</option>
                    <option value="コ">コ</option>
                    <option value="ド">ド</option>
                    <option value="ツ">ツ</option>
                    <option value="反">反</option>
                    <option value="判">判</option>
                </select>
                <select name="teamB-role2">
                    <option value="">▼</option>
                    <option value="メ">メ</option>
                    <option value="コ">コ</option>
                    <option value="ド">ド</option>
                    <option value="ツ">ツ</option>
                    <option value="反">反</option>
                    <option value="判">判</option>
                </select>
                <select name="teamB-role3">
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

    <!-- データベースから候補を表示するようにする。 -->
    <datalist id="player-list">
        <option value="サンプル1"></option>
        <option value="サンプル2"></option>
        <option value="サンプル3"></option>
    </datalist>

    <!-- ボタンエリア -->
    <div class="button-area">
        <button type="submit" class="btn-submit">決定</button>
        <a href="../demo.php">
            <button type="button" class="btn-back">戻る</button>
        </a>
    </div>
</body>

</html>