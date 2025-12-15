<?php
// カテゴリー名の定義
$categories = [
    'elementary_under4_individual' => '小学生4年以下個人',
    'elementary_over5_individual' => '小学生5年以上個人',
    'junior_high_boys_individual' => '中学生男子個人',
    'junior_high_girls_individual' => '中学生女子個人',
    'elementary_team' => '小学生団体',
    'junior_high_boys_team' => '中学生男子団体',
    'junior_high_girls_team' => '中学生女子団体'
];

// URLパラメータからカテゴリーを取得
$category = isset($_GET['category']) ? $_GET['category'] : '';
$category_name = isset($categories[$category]) ? $categories[$category] : '○○部門';

// サンプルデータ（実際はデータベースから取得）
$tournaments = [
    [
        'title' => '男子個人決勝',
        'matches' => [
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'コ', 'team2' => '○○○○', 'team2_name' => '(団体名)'],
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'メ', 'team2' => '○○○○', 'team2_name' => '(団体名)']
        ]
    ],
    [
        'title' => '男子個人準決勝',
        'matches' => [
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'コ', 'team2' => '○○○○', 'team2_name' => '(団体名)'],
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'メ', 'team2' => '○○○○', 'team2_name' => '(団体名)']
        ]
    ],
    [
        'title' => '男子団体準々決勝',
        'matches' => [
            ['team1' => '○○○○○', 'team1_name' => '(団体名)', 'score' => 'コ-メ', 'team2' => '○○○○', 'team2_name' => '(団体名)'],
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'メ', 'team2' => '○○○○', 'team2_name' => '(団体名)'],
            ['team1' => '○○○○', 'team1_name' => '(団体名)', 'score' => 'ド-コ-コ', 'team2' => '○○○○', 'team2_name' => '(団体名)']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - トーナメント結果</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Hiragino Sans", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            background-color: #f5f5f5;
            padding: 15px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background-color: #fff;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .back-link {
            display: inline-block;
            color: #007bff;
            text-decoration: none;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .back-link::before {
            content: "← ";
        }

        h1 {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .info {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .tournament-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 10px;
        }

        .search-box {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        .results {
            padding: 20px;
        }

        .tournament-section {
            margin-bottom: 30px;
        }

        .tournament-section:last-child {
            margin-bottom: 0;
        }

        .tournament-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #333;
        }

        .tournament-title::before {
            content: "・";
            margin-right: 5px;
        }

        .match {
            background-color: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.6;
        }

        .match:last-child {
            margin-bottom: 0;
        }

        .match-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .team {
            flex: 1;
            min-width: 0;
        }

        .team-name {
            font-weight: normal;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .team-group {
            color: #666;
            font-size: 12px;
            white-space: nowrap;
        }

        .score {
            padding: 0 10px;
            font-weight: bold;
            white-space: nowrap;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            .search-box {
                padding: 12px 15px;
            }

            .results {
                padding: 15px;
            }

            .match {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-link">カテゴリー選択に戻る</a>
            <h1>大会名</h1>
            <div class="info">・日時</div>
            <div class="info">・場所</div>
            <a href="#" class="tournament-link"><?php echo htmlspecialchars($category_name); ?>　トーナメントで見る</a>
        </div>

        <div class="search-box">
            <input type="text" class="search-input" placeholder="🔍 検索">
        </div>

        <div class="results">
            <?php foreach ($tournaments as $tournament): ?>
                <div class="tournament-section">
                    <div class="tournament-title"><?php echo htmlspecialchars($tournament['title']); ?></div>
                    <?php foreach ($tournament['matches'] as $match): ?>
                        <div class="match">
                            <div class="match-line">
                                <div class="team">
                                    <div class="team-name"><?php echo htmlspecialchars($match['team1']); ?></div>
                                    <div class="team-group"><?php echo htmlspecialchars($match['team1_name']); ?></div>
                                </div>
                                <div class="score"><?php echo htmlspecialchars($match['score']); ?></div>
                                <div class="team">
                                    <div class="team-name"><?php echo htmlspecialchars($match['team2']); ?></div>
                                    <div class="team-group"><?php echo htmlspecialchars($match['team2_name']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>