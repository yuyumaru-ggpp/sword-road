<?php
// public/tournament-departments.php - 保護者向け（個人=左 / 団体=右 レイアウト）
// 保存場所に合わせてパスを修正してください
require_once __DIR__ . '/../connect/db_connect.php';

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tournament_id <= 0) { http_response_code(400); echo "大会IDが指定されていません。"; exit; }

// エスケープ関数
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 大会情報
$stmt = $pdo->prepare("SELECT id, title, venue, event_date FROM tournaments WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tournament) { http_response_code(404); echo "大会が見つかりません。"; exit; }

// --- 部門ごとの件数を取得する ---
// 1) 個人戦件数: individual_matches の department_id ごとの件数
$individualCounts = [];
try {
    $sql = "SELECT department_id, COUNT(*) AS cnt FROM individual_matches WHERE department_id IS NOT NULL GROUP BY department_id";
    $stmt = $pdo->query($sql);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $individualCounts[(int)$r['department_id']] = (int)$r['cnt'];
    }
} catch (PDOException $e) {
    // 念のため空で続行
    $individualCounts = [];
}

// 2) 団体戦（カード数）: team_match_results に department_id があればそれを使い、なければ individual_matches の DISTINCT team_match_id を使う
$teamCounts = [];
try {
    // team_match_results に department_id カラムがあるか確認
    $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'team_match_results' AND COLUMN_NAME = 'department_id'");
    $check->execute();
    $hasDeptCol = (int)$check->fetchColumn() > 0;

    if ($hasDeptCol) {
        // team_match_results に department_id がある場合はそれを集計
        $sql = "SELECT department_id, COUNT(*) AS cnt FROM team_match_results WHERE department_id IS NOT NULL GROUP BY department_id";
        $stmt = $pdo->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $teamCounts[(int)$r['department_id']] = (int)$r['cnt'];
        }
    } else {
        // department_id が無い場合は individual_matches の DISTINCT team_match_id を部門ごとに数える
        $sql = "SELECT department_id, COUNT(DISTINCT team_match_id) AS cnt FROM individual_matches WHERE team_match_id IS NOT NULL GROUP BY department_id";
        $stmt = $pdo->query($sql);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $teamCounts[(int)$r['department_id']] = (int)$r['cnt'];
        }
    }
} catch (PDOException $e) {
    $teamCounts = [];
}

// --- 部門一覧を取得（並び順は既存に合わせる） ---
$sql = "
  SELECT d.id, d.name, d.distinction, d.del_flg
  FROM departments d
  WHERE d.tournament_id = :tid
  ORDER BY d.distinction ASC, d.id ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $tournament_id]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 分類：個人(2) と 団体(1) に振り分けし、件数を付与
$individuals = [];
$teams = [];
$others = [];
foreach ($departments as $d) {
    $did = (int)$d['id'];
    $dist = (int)($d['distinction'] ?? 0);
    // 個人件数・団体件数を安全に取得（存在しなければ0）
    $individual_count = isset($individualCounts[$did]) ? (int)$individualCounts[$did] : 0;
    $team_count = isset($teamCounts[$did]) ? (int)$teamCounts[$did] : 0;

    // 表示用配列に件数フィールドを追加
    $d['individual_match_count'] = $individual_count;
    $d['team_card_count'] = $team_count;

    if ($dist === 2) $individuals[] = $d;
    elseif ($dist === 1) $teams[] = $d;
    else $others[] = $d;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>部門を選択 - <?= esc($tournament['title']) ?></title>
  <style>
    :root{
      --bg:#f6f8fb; --card:#fff; --accent:#0b5ed7; --muted:#6b7280;
      --radius:12px; --gap:14px; --max-width:1100px;
      font-family:system-ui,-apple-system,"Segoe UI","Noto Sans JP",sans-serif;
    }
    html,body{height:100%;margin:0;background:var(--bg);color:#111}
    .wrap{max-width:var(--max-width);margin:28px auto;padding:18px;box-sizing:border-box}
    .header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
    h1{margin:0;font-size:1.2rem}
    .meta{color:var(--muted);font-size:0.95rem}
    .card{background:var(--card);padding:16px;border-radius:var(--radius);box-shadow:0 6px 18px rgba(11,94,215,0.06)}
    /* 2カラムレイアウト */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:var(--gap);margin-top:14px;align-items:start}
    .col{display:flex;flex-direction:column;gap:12px}
    .col h3{margin:0 0 6px 0;font-size:1rem;color:#0b5ed7}
    .dept{display:flex;align-items:center;justify-content:space-between;padding:12px;border-radius:10px;background:#fff;border:1px solid #e9f0ff;text-decoration:none;color:inherit;cursor:pointer}
    .dept .left{display:flex;flex-direction:column}
    .dept .name{font-weight:700}
    .dept .sub{color:var(--muted);font-size:0.92rem;margin-top:6px}
    .badge{background:#eef6ff;color:var(--accent);padding:6px 10px;border-radius:999px;font-weight:700}
    .empty{padding:12px;color:#666;background:#fff;border-radius:8px;border:1px dashed #e6eefc}
    .note{color:var(--muted);font-size:0.92rem;margin-top:12px}
    .back{display:inline-block;margin-top:12px;padding:8px 12px;background:#f1f5f9;border-radius:8px;text-decoration:none;color:#111}
    /* 視覚的強調（件数0は薄く） */
    .dept.zero{opacity:0.6}
    @media (max-width:820px){
      .two-col{grid-template-columns:1fr;gap:12px}
      .col{order:0}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div>
        <h1><?= esc($tournament['title']) ?></h1>
        <div class="meta">開催日: <?= esc(substr($tournament['event_date'] ?? '',0,10) ?: '未定') ?>　会場: <?= esc($tournament['venue'] ?? '未設定') ?></div>
      </div>
      <div><a class="back" href="../index.php">&larr; 大会一覧へ戻る</a></div>
    </div>

    <div class="card" role="region" aria-label="部門選択">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div style="font-weight:700">部門を選んでください</div>
        <div class="meta">※ 左：個人戦 / 右：団体戦</div>
      </div>

      <div class="two-col" aria-live="polite">
        <!-- 左カラム：個人戦 -->
        <div class="col" aria-label="個人戦">
          <h3>個人戦</h3>
          <?php if (empty($individuals)): ?>
            <div class="empty">個人戦の部門は登録されていません。</div>
          <?php else: ?>
            <?php foreach ($individuals as $d):
              $did = (int)$d['id'];
              $name = esc($d['name']);
              $count = (int)($d['individual_match_count'] ?? 0);
              $cls = $count === 0 ? 'dept zero' : 'dept';
              // 個人戦は個人戦用ページへ
              $link = "department-detail.php?id=" . urlencode($tournament_id) . "&dept=" . urlencode($did);
            ?>
              <a class="<?= $cls ?>" href="<?= $link ?>" aria-label="<?= $name ?> の試合を見る">
                <div class="left">
                  <div class="name"><?= $name ?></div>
                  <div class="sub"><?= $count ?> 件<?= $count === 0 ? '（未登録）' : '' ?></div>
                </div>
                <div class="badge"><?= $count ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- 右カラム：団体戦 -->
        <div class="col" aria-label="団体戦">
          <h3>団体戦</h3>
          <?php if (empty($teams)): ?>
            <div class="empty">団体戦の部門は登録されていません。</div>
          <?php else: ?>
            <?php foreach ($teams as $d):
              $did = (int)$d['id'];
              $name = esc($d['name']);
              $count = (int)($d['team_card_count'] ?? 0);
              $cls = $count === 0 ? 'dept zero' : 'dept';
              // 団体戦は団体戦用ページへ
              $link = "department-team.php?id=" . urlencode($tournament_id) . "&dept=" . urlencode($did);
            ?>
              <a class="<?= $cls ?>" href="<?= $link ?>" aria-label="<?= $name ?> の試合を見る">
                <div class="left">
                  <div class="name"><?= $name ?></div>
                  <div class="sub"><?= $count ?> 件<?= $count === 0 ? '（未登録）' : '' ?></div>
                </div>
                <div class="badge"><?= $count ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($others)): ?>
        <div style="margin-top:12px">
          <h3>その他の部門</h3>
          <div class="grid" style="margin-top:8px">
            <?php foreach ($others as $d):
              $did = (int)$d['id'];
              $name = esc($d['name']);
              $count = max((int)($d['individual_match_count'] ?? 0), (int)($d['team_card_count'] ?? 0));
              $link = "department-team.php?id=" . urlencode($tournament_id) . "&dept=" . urlencode($did);
            ?>
              <a class="dept <?= $count === 0 ? 'zero' : '' ?>" href="<?= $link ?>">
                <div class="left">
                  <div class="name"><?= $name ?></div>
                  <div class="sub"><?= $count ?> 件<?= $count === 0 ? '（未登録）' : '' ?></div>
                </div>
                <div class="badge"><?= $count ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="note">部門をタップすると、その部門の試合一覧と結果が表示されます。</div>
    </div>
  </div>
</body>
</html>