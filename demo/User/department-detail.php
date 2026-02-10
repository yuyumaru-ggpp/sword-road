<?php
require_once __DIR__ . '/../connect/db_connect.php';

/* =========================
   params
========================= */
$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$dept_id       = isset($_GET['dept']) ? (int)$_GET['dept'] : 2;
$q             = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($tournament_id <= 0 || $dept_id <= 0) {
  http_response_code(400);
  exit("大会ID と 部門ID を指定してください。");
}

function esc($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* =========================
   大会・部門取得
========================= */
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id=? LIMIT 1");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM departments WHERE id=? AND tournament_id=? LIMIT 1");
$stmt->execute([$dept_id,$tournament_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$tournament || !$department){
  http_response_code(404);
  exit("大会または部門が見つかりません。");
}

$distinction = (int)$department['distinction'];

$matches=[];

/* =========================
   個人戦データ取得
========================= */
if($distinction === 2){

  $sql = "
    SELECT
      im.*,
      pa.name AS a_name,
      pb.name AS b_name
    FROM individual_matches im
    LEFT JOIN players pa ON pa.id = im.player_a_id
    LEFT JOIN players pb ON pb.id = im.player_b_id
    WHERE im.department_id = ?
    ORDER BY im.match_field, im.individual_match_num
  ";

  $stmt=$pdo->prepare($sql);
  $stmt->execute([$dept_id]);
  $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

  if($q!==''){
    $qLower = mb_strtolower($q);
    foreach($rows as $r){
      $hay = mb_strtolower(($r['a_name']??'').' '.($r['b_name']??''));
      if(strpos($hay,$qLower)!==false) $matches[]=$r;
    }
  }else{
    $matches=$rows;
  }
}

/* =========================
   場ごとにグループ化
========================= */
$grouped=[];
foreach($matches as $m){
  $grouped[$m['match_field'] ?? '未設定'][]=$m;
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=esc($tournament['title'])?></title>

<style>
body{
  font-family:sans-serif;
  max-width:1100px;
  margin:auto;
  padding:16px;
  background:#f5f5f5;
}
h1{border-bottom:3px solid #007bff}

.match-card{
  background:#fff;
  padding:12px;
  border-radius:8px;
  margin:10px 0;
  box-shadow:0 2px 4px rgba(0,0,0,.1);
}

/* ★ 色 */
.tech-a{color:#d9534f;font-weight:bold;}
.tech-b{color:#0275d8;font-weight:bold;}

.search-bar{display:flex;gap:8px;margin-bottom:10px}
input{padding:8px}
button{padding:8px 16px}

@media(max-width:768px){
  .row{font-size:.9em}
}
</style>
</head>

<body>

<a href="tournament-department.php?id=<?=esc($tournament_id)?>">← 戻る</a>

<h1><?=esc($tournament['title'])?> — <?=esc($department['name'])?></h1>

<form method="get" class="search-bar">
  <input type="hidden" name="id" value="<?=$tournament_id?>">
  <input type="hidden" name="dept" value="<?=$dept_id?>">
  <input type="text" name="q" value="<?=esc($q)?>" placeholder="選手名検索">
  <button>検索</button>
</form>

<p><strong><?=count($matches)?> 試合</strong></p>


<?php foreach($grouped as $field=>$list): ?>
<h3>場 <?=esc($field)?></h3>

<?php foreach($list as $m): ?>

<?php
/* =================================================
   ★★★ 技振り分け＆先取1本だけ色付けロジック ★★★
================================================= */

$techs = [
 ['name'=>$m['first_technique'],  'winner'=>$m['first_winner']],
 ['name'=>$m['second_technique'], 'winner'=>$m['second_winner']],
 ['name'=>$m['third_technique'],  'winner'=>$m['third_winner']]
];

$aTech=[];
$bTech=[];

$firstSide='';
$firstIndexA=-1;
$firstIndexB=-1;

foreach($techs as $t){
  if(!$t['name']) continue;

  $w=strtolower((string)$t['winner']);

  $isA=($w==='a'||$w==='red'||$t['winner']==$m['player_a_id']);
  $isB=($w==='b'||$w==='white'||$t['winner']==$m['player_b_id']);

  if($isA){
    if($firstSide===''){
      $firstSide='a';
      $firstIndexA=count($aTech);
    }
    $aTech[]=$t['name'];
  }

  if($isB){
    if($firstSide===''){
      $firstSide='b';
      $firstIndexB=count($bTech);
    }
    $bTech[]=$t['name'];
  }
}

/* 勝者 */
$fw=strtolower((string)$m['final_winner']);
$isAWinner=($fw==='a'||$fw==='red'||$m['final_winner']==$m['player_a_id']);
$isBWinner=($fw==='b'||$fw==='white'||$m['final_winner']==$m['player_b_id']);
?>

<div class="match-card">

<div>試合番号 <?=esc($m['individual_match_num'])?></div>

<div class="row" style="display:flex;align-items:center;justify-content:space-between">

<!-- A -->
<div style="flex:1;text-align:left;<?=$isAWinner?'font-weight:bold':''?>">
<?=esc($m['a_name'])?>
</div>

<!-- 技表示 -->
<div style="white-space:nowrap">

<?php foreach($aTech as $i=>$t): ?>
  <span class="<?=($i===$firstIndexA)?'tech-a':''?>">
    <?=esc($t)?>
  </span>
<?php endforeach; ?>

  ー

<?php foreach($bTech as $i=>$t): ?>
  <span class="<?=($i===$firstIndexB)?'tech-b':''?>">
    <?=esc($t)?>
  </span>
<?php endforeach; ?>

</div>

<!-- B -->
<div style="flex:1;text-align:right;<?=$isBWinner?'font-weight:bold':''?>">
<?=esc($m['b_name'])?>
</div>

</div>
</div>

<?php endforeach; ?>
<?php endforeach; ?>

</body>
</html>
