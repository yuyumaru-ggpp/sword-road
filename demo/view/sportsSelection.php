<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>競技カテゴリー選択</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Hiragino Sans", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section {
            margin-bottom: 40px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
            color: #333;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 12px;
        }

        .category-list li:last-child {
            margin-bottom: 0;
        }

        .category-link {
            display: block;
            padding: 15px 20px;
            background-color: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            transition: all 0.2s;
            font-size: 16px;
        }

        .category-link:active {
            background-color: #e9ecef;
            transform: scale(0.98);
        }

        .category-link::before {
            content: "• ";
            margin-right: 8px;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 20px 15px;
            }

            h2 {
                font-size: 20px;
            }

            .category-link {
                font-size: 15px;
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h2>個人戦</h2>
            <a href="../index.php" class="back-link">大会一覧に戻る</a>
            <ul class="category-list">
                <li><a href="Resultonly.php?category=elementary_under4_individual" class="category-link">小学生4年以下個人</a></li>
                <li><a href="Resultonly.php?category=elementary_over5_individual" class="category-link">小学生5年以上個人</a></li>
                <li><a href="Resultonly.php?category=junior_high_boys_individual" class="category-link">中学生男子個人</a></li>
                <li><a href="Resultonly.php?category=junior_high_girls_individual" class="category-link">中学生女子個人</a></li>
            </ul>
        </div>

        <div class="section">
            <h2>団体戦</h2>
            <ul class="category-list">
                <li><a href="Resultonly.php?category=elementary_team" class="category-link">小学生団体</a></li>
                <li><a href="Resultonly.php?category=junior_high_boys_team" class="category-link">中学生男子団体</a></li>
                <li><a href="Resultonly.php?category=junior_high_girls_team" class="category-link">中学生女子団体</a></li>
            </ul>
        </div>
    </div>
</body>
</html>