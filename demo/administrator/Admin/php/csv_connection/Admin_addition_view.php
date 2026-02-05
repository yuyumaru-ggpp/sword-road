<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
  header("Location: ../../login.php");
  exit;
}

// GETパラメータ取得
$tournament_id = $_GET['id'] ?? null;
$department_id = $_GET['dept'] ?? null;

if (!$tournament_id || !$department_id) {
  die("大会ID または 部門ID が指定されていません");
}

// 大会名取得
$sql = "SELECT title FROM tournaments WHERE id = :tid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tid', $tournament_id);
$stmt->execute();
$tournament = $stmt->fetch();

// 部門名取得
$sql = "SELECT name FROM departments WHERE id = :did";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':did', $department_id);
$stmt->execute();
$department = $stmt->fetch();

// 選手一覧取得
$sql = "
    SELECT 
        p.id,
        p.player_number,
        p.name AS player_name,
        p.furigana,
        t.name AS team_name
    FROM players p
    JOIN teams t ON p.team_id = t.id
    WHERE t.department_id = :dept
    ORDER BY p.player_number ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':dept', $department_id);
$stmt->execute();
$players = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>選手一覧 - 大会管理システム</title>
  <link rel="stylesheet" href="../../css/csv_connection/csv_view.css">
</head>

<body>
  <div class="container">
    <div class="card">

      <!-- ヘッダー -->
      <div class="card-header">
        <div class="header-content">
          <h1 class="card-title"><?= htmlspecialchars($department['name']) ?> - 選手一覧</h1>
          <p class="card-description"><?= htmlspecialchars($tournament['title']) ?> の登録選手一覧です</p>
        </div>

        <!-- 削除ボタン（フォーム） -->
        <form action="delete_players.php" method="POST" id="delete-form"
          onsubmit="return confirm('選択した選手を削除しますか？');">
          <input type="hidden" name="dept" value="<?= $department_id ?>">
          <input type="hidden" name="id" value="<?= $tournament_id ?>">

          <button type="submit" class="btn btn-destructive">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 6h18"></path>
              <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
              <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
              <line x1="10" y1="11" x2="10" y2="17"></line>
              <line x1="14" y1="11" x2="14" y2="17"></line>
            </svg>
            <span class="btn-text-full">選択した選手を削除</span>
            <span class="btn-text-short">削除</span>
          </button>
        </form>
      </div>

      <!-- テーブル -->
      <div class="card-content">
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th class="col-checkbox">
                  <input type="checkbox" id="select-all" class="checkbox">
                </th>
                <th class="col-number">選手番号</th>
                <th>選手名</th>
                <th class="col-furigana">フリガナ</th>
                <th>所属</th>
                <th class="col-action">操作</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($players as $p): ?>
                <tr>
                  <td class="col-checkbox">
                    <input type="checkbox" class="checkbox row-checkbox"
                      name="delete_ids[]" form="delete-form"
                      value="<?= $p['id'] ?>">
                  </td>

                  <td class="col-number">
                    <span class="badge"><?= str_pad($p['player_number'], 3, "0", STR_PAD_LEFT) ?></span>
                  </td>

                  <td class="col-name"><?= htmlspecialchars($p['player_name']) ?></td>

                  <td class="col-furigana"><?= htmlspecialchars($p['furigana']) ?></td>

                  <td>
                    <span class="badge badge-outline"><?= htmlspecialchars($p['team_name']) ?></span>
                  </td>

                  <td class="col-action">
                    <button class="btn btn-outline btn-sm"
                      onclick="if(confirm('削除しますか？')) location.href='delete_players.php?single=<?= $p['id'] ?>&id=<?= $tournament_id ?>&dept=<?= $department_id ?>'">
                      <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                      </svg>
                      <span class="btn-text-full">削除</span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>

          </table>
        </div>
      </div>
    </div>
    <button type="button" class="btn btn-outline" onclick="history.back();">
      前のページへ戻る
    </button>
  </div>

  <script>
    // 全選択チェックボックス
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    selectAll.addEventListener('change', function() {
      rowCheckboxes.forEach(cb => cb.checked = this.checked);
    });

    rowCheckboxes.forEach(cb => {
      cb.addEventListener('change', function() {
        const allChecked = [...rowCheckboxes].every(c => c.checked);
        const someChecked = [...rowCheckboxes].some(c => c.checked);
        selectAll.checked = allChecked;
        selectAll.indeterminate = someChecked && !allChecked;
      });
    });
  </script>

</body>

</html>