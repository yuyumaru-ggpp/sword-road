<?php
session_start();
require_once '../../../db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// 必須パラメータ（大会ID・部門ID）
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
    die("大会ID または 部門ID が指定されていません");
}

// 検索キーワード（GET）
$keyword = trim((string)($_GET['keyword'] ?? ''));

// 検索結果（単一表示用）
$player = null;
$message = "";

// 検索処理（GET）
if ($keyword !== "") {
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.furigana,
            p.player_number,
            t.withdraw_flg AS team_withdraw
        FROM players p
        LEFT JOIN teams t ON p.team_id = t.id
        WHERE t.department_id = :dept
        AND (p.name LIKE :kw1 OR CAST(p.player_number AS CHAR) LIKE :kw2)
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':dept' => (int)$department_id,
        ':kw1'  => "%{$keyword}%",
        ':kw2'  => "%{$keyword}%"
    ]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        $message = "該当する選手が見つかりませんでした。";
    }
}

// 部門の全選手一覧取得（表示用）
$sql = "
    SELECT 
        p.id,
        p.name,
        p.furigana,
        p.player_number,
        t.withdraw_flg AS team_withdraw
    FROM players p
    LEFT JOIN teams t ON p.team_id = t.id
    WHERE t.department_id = :dept
    ORDER BY p.player_number ASC, p.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id, PDO::PARAM_INT);
$stmt->execute();
$player_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>選手変更・個人戦</title>
    <link rel="stylesheet" href="../../css/player_change/individual.css">
    <style>
        /* 最低限のスタイル（必要に応じて外部CSSに移してください） */
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .search-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem;
            font-size: 1rem;
        }

        .search-button {
            padding: 0.5rem 1rem;
        }

        .player-list {
            margin-top: 1rem;
        }

        .player-row {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .player-row:hover {
            background: #fafafa;
        }

        .player-number {
            color: #333;
            margin-right: 0.5rem;
        }

        .withdraw {
            color: red;
            font-weight: bold;
            margin-left: 0.5rem;
        }

        .breadcrumb {
            margin-bottom: 1rem;
        }

        .breadcrumb-link {
            margin-right: 0.5rem;
            color: #0078d4;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="breadcrumb">
        <a href="../tournament-detail.php?id=<?= htmlspecialchars($tournament_id) ?>" class="breadcrumb-link">メニュー</a>
        <a href="player-category-select.php?id=<?= htmlspecialchars($tournament_id) ?>" class="breadcrumb-link">選手変更</a>
        <span class="breadcrumb-link">個人戦</span>
    </div>

    <div class="container">
        <h1 class="title">選手変更・個人戦</h1>

        <?php if ($message): ?>
            <p style="color:red;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- 検索フォーム（GET） -->
        <form method="GET" action="individual.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($tournament_id) ?>">
            <input type="hidden" name="dept" value="<?= htmlspecialchars($department_id) ?>">
            <div class="search-container">
                <input type="text" name="keyword" class="search-input" placeholder="選手名またはIDを入力してください"
                    value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" class="search-button">検索</button>
            </div>
        </form>

        <!-- 検索結果（見つかった場合のみ表示） -->
        <?php if ($player): ?>
            <section>
                <h2>検索結果</h2>
                <div style="padding:0.75rem; border:1px solid #ddd; margin-bottom:1rem;">
                    <div><strong>選手番号：</strong><?= htmlspecialchars($player['player_number']) ?></div>
                    <div><strong>選手名：</strong><?= htmlspecialchars($player['name']) ?>
                        <?php if (!empty($player['team_withdraw']) && $player['team_withdraw'] == 1): ?>
                            <span class="withdraw">（棄権）</span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top:0.5rem;">
                        <button type="button" onclick="location.href='individual_edit.php?player=<?= urlencode($player['id']) ?>&id=<?= urlencode($tournament_id) ?>&dept=<?= urlencode($department_id) ?>'">詳細を見る</button>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <hr style="margin: 1.5rem 0;">

        <!-- 全選手一覧 -->
        <h2>選手一覧</h2>
        <div class="player-list">
            <?php if (empty($player_list)): ?>
                <p>この部門に選手が登録されていません。</p>
            <?php else: ?>
                <?php foreach ($player_list as $p): ?>
                    <div class="player-row"
                        onclick="location.href='individual_edit.php?player=<?= urlencode($p['id']) ?>&id=<?= urlencode($tournament_id) ?>&dept=<?= urlencode($department_id) ?>'">
                        <div>
                            <span class="player-number"><?= htmlspecialchars($p['player_number']) ?></span>
                            <span><?= htmlspecialchars($p['name']) ?></span>
                        </div>
                        <div>
                            <?php if (!empty($p['team_withdraw']) && $p['team_withdraw'] == 1): ?>
                                <span class="withdraw">（棄権）</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <!-- 個別ページからこの一覧に戻るボタン -->
        <button type="button"
            class="action-button secondary"
            onclick="location.href='category_select.php?id=<?= urlencode($tournament_id) ?>&dept=<?= urlencode($department_id) ?>&keyword=<?= urlencode($keyword ?? '') ?>'">
            一覧に戻る
        </button>
    </div>
</body>

</html>