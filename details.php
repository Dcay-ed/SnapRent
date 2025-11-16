<?php
declare(strict_types=1);

/* ===================== KONEKSI (meniru products.php) ===================== */
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* cari file koneksi yang biasa dipakai */
$paths = [
  __DIR__ . '/database/db.php',
  __DIR__ . '/Database/db.php',
  __DIR__ . '/db.php',
  __DIR__ . '/includes/db.php',
];
$found = false;
foreach ($paths as $p) {
  if (is_file($p)) { require_once $p; $found = true; break; }
}
if (!$found) {
  http_response_code(500);
  echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari details.php.";
  exit;
}

/* deteksi koneksi */
$USE_PDO    = isset($pdo)  && ($pdo instanceof PDO);
$USE_MYSQLI = isset($conn) && ($conn instanceof mysqli);
if (!$USE_PDO && !$USE_MYSQLI) {
  http_response_code(500);
  echo "Koneksi DB tidak tersedia. Pastikan database/db.php membuat \$pdo (PDO) atau \$conn (MySQLi).";
  exit;
}

/* helper query kompatibel */
function db_row(string $sql, array $params = []) {
  global $USE_PDO, $USE_MYSQLI, $pdo, $conn;
  if ($USE_PDO) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch(PDO::FETCH_ASSOC);
  }
  $stmt = $conn->prepare($sql);
  if ($params){
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  return $res ? $res->fetch_assoc() : null;
}
function db_all(string $sql, array $params = []) : array {
  global $USE_PDO, $USE_MYSQLI, $pdo, $conn;
  if ($USE_PDO) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
  $stmt = $conn->prepare($sql);
  if ($params){
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/* ambil id fleksibel + fallback */
function resolve_camera_id(): ?int {
  $candidates = [];
  if (isset($_GET['id']))        $candidates[] = $_GET['id'];
  if (isset($_GET['camera_id'])) $candidates[] = $_GET['camera_id'];
  if (empty($candidates)) {
    $path = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('~/(\d+)(?:\D*$)~', $path, $m)) $candidates[] = $m[1];
  }
  foreach ($candidates as $raw){
    $id = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($id) return (int)$id;
  }
  return null;
}
$cameraId = resolve_camera_id();

if (!$cameraId) {
  $row = db_row("SELECT id FROM cameras WHERE status='available' ORDER BY id ASC LIMIT 1");
  if (!$row) $row = db_row("SELECT id FROM cameras ORDER BY id ASC LIMIT 1");
  if ($row && isset($row['id'])) {
    header("Location: details.php?id=".(int)$row['id'], true, 302);
    exit;
  }
  http_response_code(404);
  echo "Data kamera tidak tersedia. <a href='index-cameras.php'>Kembali ke daftar</a>";
  exit;
}

/* ===================== QUERY KAMERA ===================== */
$camera = db_row(
  "SELECT id, name, brand, type, problem, daily_price, status,
          condition_note, owner_id, created_at, updated_at
   FROM cameras WHERE id = ? LIMIT 1",
  [$cameraId]
);
if (!$camera) {
  http_response_code(404);
  echo "Kamera tidak ditemukan. <a href='index-cameras.php'>Kembali ke daftar</a>";
  exit;
}

/* ambil gambar & review */
$images = db_all(
  "SELECT id, filename, created_at FROM camera_images
   WHERE camera_id = ? ORDER BY id ASC",
  [$cameraId]
);

$reviews = db_all(
  "SELECT id, customer_id, rating, comment, created_at
   FROM reviews WHERE camera_id = ?
   ORDER BY created_at DESC, id DESC",
  [$cameraId]
);

$agg = db_row(
  "SELECT COUNT(*) total_reviews, AVG(rating) avg_rating
   FROM reviews WHERE camera_id = ?",
  [$cameraId]
);
$totalReviews = (int)($agg['total_reviews'] ?? 0);
$avgRating    = $agg['avg_rating'] !== null ? round((float)$agg['avg_rating'], 1) : null;

/* ===================== UTIL TAMPILAN ===================== */
/*
 * Helper path gambar:
 * - File fisik: Dashboard/uploads/cameras/{camera_id}/{filename}
 * - Kolom filename bisa berisi:
 *   - 'cameras/7/a7.jpg'
 *   - '7/a7.jpg'
 *   - 'a7.jpg'
 *   semuanya akan dikonversi ke URL yang benar.
 */
function build_img_url(array $img, int $cameraId): string {
  $f = ltrim((string)($img['filename'] ?? ''), '/');

  // jika sudah 'cameras/...'
  if (preg_match('~^cameras/~', $f)) {
    return 'Dashboard/uploads/' . $f;
  }

  // jika '7/a7.jpg' (diawali angka + slash)
  if (preg_match('~^\d+\/~', $f)) {
    return 'Dashboard/uploads/cameras/' . $f;
  }

  // default: hanya 'a7.jpg'
  return 'Dashboard/uploads/cameras/' . $cameraId . '/' . $f;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

/* bintang: kembalikan 5 span .star; yang kosong diberi style opacity:.4 */
function render_stars($score): string {
  $score = max(0, min(5, (float)$score));
  $full  = (int)floor($score);
  $half  = ($score - $full) >= 0.5 ? 1 : 0; // tidak dipakai visual half
  $empty = 5 - $full - $half;

  $html = str_repeat('<span class="star">★</span>', $full);
  if ($half) $html .= '<span class="star">★</span>';
  $html .= str_repeat('<span class="star" style="opacity:.4">★</span>', max(0,$empty));
  return $html;
}

/* distribusi rating 1-5 (untuk bar) */
$dist = [1=>0,2=>0,3=>0,4=>0,5=>0];
foreach ($reviews as $r) {
  $rt = (int)round((float)$r['rating']);
  if ($rt < 1) $rt = 1;
  if ($rt > 5) $rt = 5;
  $dist[$rt] += 1;
}
$maxBucket = max(1, max($dist)); // hindari 0

/* gambar utama */
$mainImageUrl = !empty($images) ? build_img_url($images[0], $cameraId) : null;

/* thumb selalu 5 slot (duplicate kalau kurang) */
$thumbImages = [];
if (!empty($images)) {
  $thumbImages = $images;
  $maxThumbs   = 5;
  $count       = count($thumbImages);
  if ($count > $maxThumbs) {
    $thumbImages = array_slice($thumbImages, 0, $maxThumbs);
  } elseif ($count > 0 && $count < $maxThumbs) {
    for ($i = $count; $i < $maxThumbs; $i++) {
      $thumbImages[] = $thumbImages[$i % $count];
    }
  }
}

/* teks turunan */
$cameraTitle = trim(($camera['brand'] ? $camera['brand'].' ' : '').$camera['name']);
$cameraType  = $camera['type'] ?: '-';
$priceDay    = (float)$camera['daily_price'];
$weeklyPrice = $priceDay > 0 ? (int)round($priceDay*7*0.85) : 0; // diskon 15%
$breadcrumb  = $cameraTitle !== '' ? $cameraTitle : 'Detail Kamera';

/* tanggal minimum = hari ini untuk input date */
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Detail Kamera - Sewa Kamera</title>

<style>
/* ===================== Design Tokens ===================== */
:root{
  --bg:#ffffff;
  --header-bg:#5f6c75;
  --text-color:#293743;
  --secondary-text:#637584;
  --card-bg:#f8f9fa;
  --border-color:#e5e9ee;
  --star-color:#FFC107;
  --button-bg:#293743;
  --button-text:#ffffff;
  --radius-lg:16px;
  --radius-md:8px;
  --shadow:0 4px 12px rgba(0,0,0,.08);
}

*{box-sizing:border-box}
html,body{
  margin:0;
  background:var(--bg);
  color:var(--text-color);
  font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif
}
a{color:inherit;text-decoration:none}

/* ===================== Header ===================== */
.header{
  position:fixed;
  inset:0 0 auto 0;
  height:64px;
  background:var(--header-bg);
  display:flex;
  align-items:center;
  gap:24px;
  padding:0 32px;
  z-index:10;
}
.header .brand{
  display:flex;
  align-items:center;
  gap:10px;
  color:#fff;
  font-weight:600
}
.header .brand .logo{
  width:30px;
  height:30px;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:rgba(255,255,255,.15);
  font-size:14px
}
.header .search{
  flex:1;
  display:flex;
  justify-content:center;
}
.header .search .searchbar{
  width:min(680px,86%);
  height:36px;
  border-radius:20px;
  background:#fff;
  display:flex;
  align-items:center;
  gap:10px;
  padding:0 14px;
}
.header .search input{
  flex:1;
  border:none;
  outline:none;
  font-size:14px;
  color:#3a4652;
}
.header .icons{
  display:flex;
  align-items:center;
  gap:18px;
  color:#fff;
}
.header .icons .ico{
  width:20px;
  height:20px;
  display:grid;
  place-items:center;
  cursor:pointer;
}

/* ===================== Page Frame ===================== */
.page{
  padding-top:80px;  /* offset header */
  padding-bottom:60px;
}
.container{
  width:min(1180px,92%);
  margin-inline:auto;
  padding:20px;
}

/* ===================== Breadcrumb ===================== */
.breadcrumb{
  display:flex;
  align-items:center;
  gap:8px;
  color:#627484;
  font-size:14px;
  margin:6px 0 18px;
}
.breadcrumb a{
  color:#627484;
  text-decoration:underline;
}
.breadcrumb b{
  color:var(--text-color);
  font-weight:500;
}

/* ===================== Main Grid (Gambar | Detail) ===================== */
.main{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:30px;
  margin-bottom:30px;
}

/* Left: gallery */
.gallery{
  display:flex;
  flex-direction:column;
  gap:16px;
}
.main-img{
  width:100%;
  aspect-ratio:1/1;
  background:#D9D9D9;
  border-radius:10px;
  position:relative;
  display:grid;
  place-items:center;
  color:#6b7280;
  font-size:14px;
}
.fav{
  position:absolute;
  right:12px;
  bottom:12px;
  width:44px;
  height:44px;
  border-radius:50%;
  background:#fff;
  display:grid;
  place-items:center;
  box-shadow:var(--shadow);
  cursor:pointer;
}
.thumbs{
  display:grid;
  grid-template-columns: repeat(5, 1fr);
  gap:12px;
  margin-top:10px;
}
.thumb{
  height:80px;
  background:#D9D9D9;
  border-radius:8px;
  display:grid;
  place-items:center;
  font-size:10px;
  color:#6b7280;
  cursor:pointer;
  transition:transform .12s ease;
}
.thumb:hover{transform:translateY(-2px)}

/* Right: details */
.title{
  font-size:28px;
  font-weight:600;
  line-height:1.15;
  margin-bottom:5px;
}
.type{
  font-size:14px;
  color:var(--secondary-text);
  margin-bottom:10px;
}
.ratingline{
  display:flex;
  align-items:center;
  gap:10px;
  margin:5px 0 15px;
}
.ratingline .val{
  font-weight:600;
  font-size:16px;
}
.stars{
  display:flex;
  gap:3px;
}
.star{
  color:var(--star-color);
  font-size:16px;
  line-height:1;
}
.users{
  font-size:14px;
  color:var(--secondary-text);
}

.pricecard{
  background:#e5e9ee;
  border-radius:var(--radius-md);
  padding:15px 20px;
  margin:10px 0 20px;
}
.price{
  font-size:28px;
  font-weight:700;
}
.per{
  font-size:16px;
}
.weekly{
  font-size:14px;
  margin-top:5px;
}

.form h3{
  font-size:20px;
  margin:8px 0 14px;
}
.dategrid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:18px;
}
.field label{
  font-size:14px;
  color:var(--text-color);
  margin-bottom:6px;
  display:block;
}
.datebox{
  display:flex;
  align-items:center;
  gap:8px;
  border:1px solid #ddd;
  border-radius:8px;
  background:#fff;
  padding:8px 12px;
}
.datebox input{
  flex:1;
  border:none;
  outline:none;
  font-size:14px;
  color:#111;
}
.btn{
  margin-top:14px;
  width:100%;
  height:44px;
  border-radius:6px;
  background:var(--button-bg);
  color:var(--button-text);
  font-weight:700;
  font-size:18px;
  cursor:pointer;
  transition:filter .2s ease;
}
.btn:hover{filter:brightness(.92)}

/* ===================== Specifications ===================== */
.spec{
  margin-bottom:30px;
  background:var(--card-bg);
  border-radius:var(--radius-lg);
  padding:20px 24px;
  box-shadow:var(--shadow);
}
.spec .heading{
  font-size:20px;
  font-weight:700;
  text-decoration:underline;
  margin-bottom:16px;
}
.specgrid{
  display:grid;
  grid-template-columns:repeat(2, 1fr);
  gap:10px 24px;
}
.srow{
  display:flex;
  justify-content:space-between;
  padding:8px 0;
  border-bottom:1px solid var(--border-color);
}
.srow .k{
  font-size:14px;
  color:var(--text-color);
}
.srow .v{
  font-size:14px;
  font-weight:600;
  color:var(--text-color);
}

/* ===================== Reviews ===================== */
.reviews{
  background:var(--card-bg);
  border-radius:var(--radius-lg);
  padding:20px 24px;
  box-shadow:var(--shadow);
}
.reviews .heading{
  font-size:20px;
  font-weight:700;
  margin-bottom:14px;
}
.rev-top{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:20px;
  align-items:center;
  margin-bottom:14px;
}
.overall{
  display:flex;
  flex-direction:column;
  align-items:center;
  text-align:center;
}
.overall .big{
  font-size:36px;
  font-weight:700;
}
.overall .stars{
  margin:8px 0;
}
.overall .count{
  font-size:14px;
  color:var(--text-color);
}
.dist .row{
  display:flex;
  align-items:center;
  gap:10px;
  margin:5px 0;
}
.dist .mini{
  font-size:14px;
  color:#111;
}
.bar{
  flex:1;
  height:8px;
  border-radius:4px;
  background:#E0E0E0;
  overflow:hidden;
}
.fill{
  height:100%;
  background:var(--star-color);
}

.filters{
  display:flex;
  gap:10px;
  margin:10px 0 18px;
}
.chip{
  padding:8px 14px;
  border:1px solid var(--text-color);
  border-radius:8px;
  font-size:14px;
  color:var(--text-color);
  background:#fff;
  cursor:pointer;
}
.chip:hover{background:#F4F6F8}

.list{
  display:flex;
  flex-direction:column;
  gap:14px;
}
.card{
  display:grid;
  grid-template-columns: 49px 1fr;
  gap:16px;
  background:#fff;
  border-radius:8px;
  padding:14px;
  box-shadow:var(--shadow);
}
.avatar{
  width:49px;
  height:49px;
  border-radius:50%;
  background:#D9D9D9;
}
.meta{
  display:flex;
  justify-content:space-between;
  margin-bottom:6px;
}
.name{
  font-size:14px;
  font-weight:700;
  color:#000;
}
.time{
  font-size:14px;
  color:var(--secondary-text);
}
.revstars{
  display:flex;
  gap:3px;
  margin-bottom:8px;
}
.rtext{
  font-size:14px;
  color:#000;
  line-height:1.55;
  margin-bottom:8px;
}
.help{
  display:flex;
  align-items:center;
  gap:6px;
  font-size:14px;
  color:#627484;
  cursor:pointer;
}
.help:hover{color:var(--text-color)}
</style>
</head>
<body>
  <!-- ================= Header ================= -->
  <header class="header">
    <div class="brand">
      <div class="logo">📷</div>
      <span>SnapRent</span>
    </div>
    <div class="search">
      <div class="searchbar">
        <input type="text" placeholder="Search"/>
        <span>🔍</span>
      </div>
    </div>
    <div class="icons">
      <div class="ico">👤</div>
      <div class="ico">🔔</div>
      <div class="ico">🛒</div>
    </div>
  </header>

  <main class="page">
    <div class="container">
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <a href="index-cameras.php">Cameras</a><span>›</span><b><?= e($breadcrumb) ?></b>
      </div>

      <!-- Main grid -->
      <section class="main">
        <!-- Left: Gallery -->
        <div class="gallery">
          <div class="main-img" id="mainBox">
            <?php if ($mainImageUrl): ?>
              <img id="mainImg"
                   src="<?= e($mainImageUrl) ?>"
                   alt="<?= e($cameraTitle) ?>"
                   style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
            <?php else: ?>
              Gambar Utama Kamera
            <?php endif; ?>
            <button class="fav">❤</button>
          </div>
          <div class="thumbs" id="thumbs">
            <?php if (!empty($thumbImages)): ?>
              <?php foreach ($thumbImages as $i=>$img):
                    $url = build_img_url($img, $cameraId); ?>
                <div class="thumb">
                  <img src="<?= e($url) ?>"
                       data-src="<?= e($url) ?>"
                       alt="thumb <?= $i+1 ?>"
                       style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="thumb">Gambar 1</div>
              <div class="thumb">Gambar 2</div>
              <div class="thumb">Gambar 3</div>
              <div class="thumb">Gambar 4</div>
              <div class="thumb">Gambar 5</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: Details -->
        <div>
          <h1 class="title"><?= e($cameraTitle) ?></h1>
          <p class="type"><?= e($cameraType) ?></p>

          <div class="ratingline">
            <span class="val"><?= $avgRating!==null ? e(number_format($avgRating,1)) : '—' ?></span>
            <div class="stars"><?= $avgRating!==null ? render_stars($avgRating) : render_stars(0) ?></div>
            <span class="users"><?= $totalReviews ? 'from '.e($totalReviews).' users' : 'no reviews yet' ?></span>
          </div>

          <div class="pricecard">
            <div class="price"><?= rp($priceDay) ?> <span class="per">/day</span></div>
            <?php if ($weeklyPrice>0): ?>
              <div class="weekly">Weekly rate: <?= rp($weeklyPrice) ?> (save 15%)</div>
            <?php endif; ?>
          </div>

          <div class="form">
            <h3>Select Rental Dates</h3>
            <div class="dategrid">
              <div class="field">
                <label>Start Date</label>
                <div class="datebox">
                  <input type="date" id="startDate" min="<?= e($today) ?>"/>
                  
                </div>
              </div>
              <div class="field">
                <label>End Date</label>
                <div class="datebox">
                  <input type="date" id="endDate" min="<?= e($today) ?>"/>
                
                </div>
              </div>
            </div>
            <button class="btn" id="rentNow">Rent Now</button>
          </div>
        </div>
      </section>

      <!-- Specifications -->
      <section class="spec">
        <div class="heading">Specifications</div>
        <div class="specgrid">
          <div class="srow"><span class="k">Sensor Type</span><span class="v"><?= e($camera['type'] ?: '—') ?></span></div>
          <div class="srow"><span class="k">Condition</span><span class="v"><?= e($camera['problem'] ?: '—') ?></span></div>
          <div class="srow"><span class="k">Status</span><span class="v"><?= e($camera['status'] ?: '—') ?></span></div>

          <div class="srow"><span class="k">Owner</span><span class="v">#<?= e($camera['owner_id']) ?></span></div>
          <div class="srow"><span class="k">Daily Price</span><span class="v"><?= rp($priceDay) ?></span></div>
          <div class="srow"><span class="k">Added</span><span class="v"><?= e($camera['created_at'] ?: '—') ?></span></div>

          <div class="srow"><span class="k">Updated</span><span class="v"><?= e($camera['updated_at'] ?: '—') ?></span></div>
          <div class="srow"><span class="k">Notes</span><span class="v"><?= e($camera['condition_note'] ?: '—') ?></span></div>
          <div class="srow"><span class="k">ID</span><span class="v">#<?= e($camera['id']) ?></span></div>

          <div class="srow"><span class="k">Brand</span><span class="v"><?= e($camera['brand'] ?: '—') ?></span></div>
        </div>
      </section>

      <!-- Reviews -->
      <section class="reviews">
        <div class="heading">Customer Review</div>

        <div class="rev-top">
          <div class="overall">
            <div class="big"><?= $avgRating!==null ? e(number_format($avgRating,1)) : '—' ?></div>
            <div class="stars"><?= $avgRating!==null ? render_stars($avgRating) : render_stars(0) ?></div>
            <div class="count"><?= $totalReviews ? 'Based from '.e($totalReviews).' users' : 'No reviews yet' ?></div>
          </div>

          <div class="dist">
            <?php
              for ($i=5; $i>=1; $i--) {
                $cnt = $dist[$i];
                $w   = $maxBucket>0 ? round(($cnt/$maxBucket)*100) : 0;
                echo '<div class="row">
                        <span class="mini">'.$i.'</span>
                        <div class="bar"><div class="fill" style="width:'.$w.'%"></div></div>
                        <span class="mini">'.e($cnt).'</span>
                      </div>';
              }
            ?>
          </div>
        </div>

        <div class="filters">
          <button class="chip" id="btnRecent">Most Recent</button>
          <button class="chip" id="btnAll">All Ratings</button>
        </div>

        <div class="list">
          <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $r): ?>
              <div class="card">
                <div class="avatar"></div>
                <div>
                  <div class="meta">
                    <span class="name">Cust #<?= e($r['customer_id']) ?></span>
                    <span class="time"><?= e($r['created_at']) ?></span>
                  </div>
                  <div class="revstars"><?= render_stars($r['rating']) ?></div>
                  <?php if (!empty($r['comment'])): ?>
                    <div class="rtext"><?= nl2br(e($r['comment'])) ?></div>
                  <?php endif; ?>
                  <div class="help" data-help="0">👍 <span>Helpfull (0)</span></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="card">
              <div class="avatar"></div>
              <div>
                <div class="meta">
                  <span class="name">—</span>
                  <span class="time">—</span>
                </div>
                <div class="revstars"><?= render_stars(0) ?></div>
                <div class="rtext">Belum ada ulasan.</div>
                <div class="help" data-help="0">👍 <span>Helpfull (0)</span></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>

<script>
// Validasi tanggal + info kamera
const startDate = document.getElementById('startDate');
const endDate   = document.getElementById('endDate');
const rentNow   = document.getElementById('rentNow');

// 🔹 1. Realtime: ubah min di End Date ketika Start Date dipilih
startDate?.addEventListener('change', () => {
  endDate.min = startDate.value;

  if (endDate.value < startDate.value) {
    endDate.value = startDate.value;
  }
});

// 🔹 2. Validasi saat menekan tombol Rent Now
rentNow?.addEventListener('click', (e) => {

  const start = startDate.value;
  const end   = endDate.value;

  const sDate = new Date(start);
  const eDate = new Date(end);

  // Cek input kosong
  if (!start || !end) {
    alert('Silakan pilih tanggal mulai dan tanggal selesai.');
    e.preventDefault();
    return;
  }

  // Cek end date < start date
  if (eDate < sDate) {
    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
    e.preventDefault();
    return;
  }


  // === alur baru: langsung ke payment.php ===
  const cameraId = <?= (int)$cameraId ?>;
  const url = `checkout.php?id=${cameraId}&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
  window.location.href = url;
});


// tombol "helpful"
document.querySelectorAll('.help').forEach(el => {
  el.addEventListener('click', () => {
    const span  = el.querySelector('span');
    const match = span.textContent.match(/\d+/);
    const n     = (match ? parseInt(match[0],10) : 0) + 1;
    span.textContent = `Helpfull (${n})`;
  });
});

document.getElementById('btnRecent')?.addEventListener('click', () => {
  alert('Menampilkan ulasan terbaru');
});
document.getElementById('btnAll')?.addEventListener('click', () => {
  alert('Menampilkan semua rating');
});

// Gallery: klik thumb -> ganti gambar utama
(function(){
  const mainImg = document.getElementById('mainImg');
  const thumbs  = document.getElementById('thumbs');
  if (!mainImg || !thumbs) return;
  thumbs.addEventListener('click', (e) => {
    const img = e.target.closest('img[data-src]');
    if (!img) return;
    mainImg.src = img.dataset.src;
  });
})();
</script>
</body>
</html>