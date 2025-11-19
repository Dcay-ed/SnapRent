<?php 
declare(strict_types=1);

/* ===================== ERROR REPORTING ===================== */
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* ===================== SESSION (pakai $_SESSION['uid']) ===================== */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$customerId = $_SESSION['uid'] ?? ($_SESSION['user_id'] ?? null);

/* ===================== KONEKSI (meniru products.php) ===================== */
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

/* ===================== AJAX: UPDATE HELPFUL DI DB ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'helpful') {
  header('Content-Type: application/json; charset=utf-8');

  // pastikan login
  if (!$customerId) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'LOGIN_REQUIRED'
    ]);
    exit;
  }

  $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
  if ($reviewId <= 0) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'INVALID_REVIEW'
    ]);
    exit;
  }

  // batasi 1x per session per review
  if (!isset($_SESSION['helpful_clicked']) || !is_array($_SESSION['helpful_clicked'])) {
    $_SESSION['helpful_clicked'] = [];
  }
  if (!empty($_SESSION['helpful_clicked'][$reviewId])) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'ALREADY'
    ]);
    exit;
  }

  try {
    if ($USE_PDO) {
      // naikkan helpful +1
      $up = $pdo->prepare("UPDATE reviews SET helpful = helpful + 1 WHERE id = :id");
      $up->execute([':id' => $reviewId]);

      // ambil nilai terbaru
      $st = $pdo->prepare("SELECT helpful FROM reviews WHERE id = :id");
      $st->execute([':id' => $reviewId]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      $newCount = (int)($row['helpful'] ?? 0);
    } else {
      // MySQLi
      $up = $conn->prepare("UPDATE reviews SET helpful = helpful + 1 WHERE id = ?");
      $up->bind_param("i", $reviewId);
      $up->execute();

      $st = $conn->prepare("SELECT helpful FROM reviews WHERE id = ?");
      $st->bind_param("i", $reviewId);
      $st->execute();
      $res = $st->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $newCount = (int)($row['helpful'] ?? 0);
    }

    // tandai di session -> 1 id user cuma bisa sekali per review (per session)
    $_SESSION['helpful_clicked'][$reviewId] = true;

    echo json_encode([
      'status'  => 'ok',
      'helpful' => $newCount
    ]);
    exit;

  } catch (Throwable $e) {
    echo json_encode([
      'status'  => 'error',
      'message' => 'DB_ERROR'
    ]);
    exit;
  }
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
  "SELECT id,
          name,
          brand,
          type,
          description,
          problem,
          daily_price,
          status,
          condition_note,
          owner_id,
          created_at,
          updated_at
   FROM cameras
   WHERE id = ?
   LIMIT 1",
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
  "SELECT id, customer_id, rating, comment, created_at, helpful
   FROM reviews
   WHERE camera_id = ?
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

/* ===================== CEK SUDAH DI WISHLIST BELUM ===================== */
$inWishlist = false;
if ($customerId) {
  $rowWish = db_row(
    "SELECT id FROM wishlists WHERE customer_id = ? AND camera_id = ? LIMIT 1",
    [$customerId, $cameraId]
  );
  if ($rowWish && isset($rowWish['id'])) {
    $inWishlist = true;
  }
}

/* ===================== UTIL TAMPILAN ===================== */
function build_img_url(array $img, int $cameraId): string {
  $f = ltrim((string)($img['filename'] ?? ''), '/');
  if (preg_match('~^cameras/~', $f)) {
    return 'Dashboard/uploads/' . $f;
  }
  if (preg_match('~^\d+\/~', $f)) {
    return 'Dashboard/uploads/cameras/' . $f;
  }
  return 'Dashboard/uploads/cameras/' . $cameraId . '/' . $f;
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

function render_stars($score): string {
  $score = max(0, min(5, (float)$score));
  $full  = (int)floor($score);
  $half  = ($score - $full) >= 0.5 ? 1 : 0;
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
$maxBucket = max(1, max($dist));

/* gambar utama */
$mainImageUrl = !empty($images) ? build_img_url($images[0], $cameraId) : null;

/* thumb selalu 5 slot */
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

/* description untuk area Specifications (pakai kolom description, fallback) */
$descriptionText = trim((string)($camera['description'] ?? ''));
if ($descriptionText === '') {
  $descriptionText = trim((string)($camera['problem'] ?? ''));
}
if ($descriptionText === '') {
  $descriptionText = trim((string)($camera['condition_note'] ?? ''));
}
if ($descriptionText === '') {
  $descriptionText = '—';
}

/* pecah deskripsi jadi bullet "•" per baris */
$descriptionLines = array_values(array_filter(
  array_map('trim', preg_split("/\r\n|\n|\r/", (string)$descriptionText)),
  fn($v) => $v !== ''
));
if (!$descriptionLines) {
  $descriptionLines = [$descriptionText];
}

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
  --header-bg:#293743;
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
  font-family:"SF Pro Text","Poppins",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif
}
a{color:inherit;text-decoration:none}

/* ===================== Header Modern ===================== */
.header{
  position:fixed;
  inset:0 0 auto 0;
  height:64px;
  background:var(--header-bg);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:0 24px;
  z-index:20;
  box-shadow:0 1px 4px rgba(0,0,0,.18);
}
.header-inner{
  width:min(1180px,100%);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
}

/* brand kiri (logo + text) */
.brand{
  display:flex;
  align-items:center;
  gap:10px;
  color:#f9fafb;
}
.brand-mark{
  width:32px;
  height:32px;
  border-radius:9px;
  background:rgba(15,23,42,.7);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
}
.brand-mark img{
  width:100%;
  height:100%;
  object-fit:contain;
}
.brand-text{
  display:flex;
  flex-direction:column;
  line-height:1.1;
}
.brand-name{
  font-size:16px;
  font-weight:600;
}
.brand-tagline{
  font-size:11px;
  opacity:.75;
}

/* tengah: search ala Apple */
.header-center{
  flex:1;
  display:flex;
  justify-content:center;
}
.searchbar{
  width:min(520px,100%);
  height:32px;
  border-radius:999px;
  background:rgba(255,255,255,.12);
  display:flex;
  align-items:center;
  padding:0 12px;
  gap:8px;
  border:1px solid rgba(148,163,184,.4);
  backdrop-filter:blur(22px);
}
.searchbar input{
  flex:1;
  border:none;
  outline:none;
  background:transparent;
  font-size:13px;
  color:#e5e7eb;
}
.searchbar input::placeholder{
  color:rgba(209,213,219,.78);
}
.search-icon{
  font-size:13px;
  opacity:.85;
}

/* kanan: icon menu */
.header-actions{
  display:flex;
  align-items:center;
  gap:10px;
}
.icon-button{
  width:30px;
  height:30px;
  border-radius:999px;
  border:none;
  background:rgba(15,23,42,.35);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:transform .16s ease, background .16s ease, box-shadow .16s ease;
  color:#f9fafb;
  font-size:14px;
}
.icon-button:hover{
  background:rgba(15,23,42,.7);
  box-shadow:0 4px 10px rgba(0,0,0,.35);
  transform:translateY(-1px);
}
.icon-button a{
  display:flex;
  align-items:center;
  justify-content:center;
  width:100%;
  height:100%;
}

/* ===================== Page Frame ===================== */
.page{
  padding-top:80px;
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
  display:flex;
  align-items:center;
  justify-content:center;
  gap:4px;
  box-shadow:var(--shadow);
  cursor:pointer;
  border:none;
  font-size:18px;
  transition:
    transform .18s ease,
    box-shadow .18s ease,
    background .18s ease,
    color .18s ease;
}

/* state saat sudah di-wishlist */
.fav-added{
  background:#ffeff1;
  color:#e11d48;
}

/* class animasi pop */
.fav-anim{
  animation:fav-pop .3s ease;
}

/* efek pop kecil */
@keyframes fav-pop{
  0%   { transform:scale(1);   box-shadow:var(--shadow); }
  40%  { transform:scale(1.18); box-shadow:0 8px 18px rgba(0,0,0,.18); }
  100% { transform:scale(1);   box-shadow:var(--shadow); }
}
.fav-badge{
  display:none;
  width:18px;
  height:18px;
  border-radius:50%;
  background:#e11d48;
  color:#fff;
  font-size:11px;
  align-items:center;
  justify-content:center;
}

/* toast wishlist */
.toast-wishlist{
  position:fixed;
  left:50%;
  bottom:24px;
  transform:translateX(-50%) translateY(40px);
  background:#111827;
  color:#f9fafb;
  padding:10px 16px;
  border-radius:999px;
  font-size:14px;
  box-shadow:0 10px 25px rgba(0,0,0,.25);
  opacity:0;
  pointer-events:none;
  transition:opacity .2s ease, transform .2s ease;
  z-index:999;
}
.toast-wishlist.show{
  opacity:1;
  transform:translateX(-50%) translateY(0);
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
  grid-template-columns: 1.1fr 1.5fr; /* kiri: sensor+brand, kanan: description lebar */
  column-gap:40px;
}
.spec-left{
  display:flex;
  flex-direction:column;
}
.spec-right{
  display:flex;
  flex-direction:column;
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
.desc-body{
  margin-top:6px;
  padding-top:4px;
  border-top:1px solid var(--border-color);
  font-size:14px;
  color:var(--text-color);
  line-height:1.6;
}
.desc-line{
  margin-bottom:4px;
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

/* chip active state */
.chip.active{
  background: var(--button-bg);
  color: var(--button-text);
  border-color: var(--button-bg);
}

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
.help-locked{
  opacity:.55;
  cursor:default;
  pointer-events:none;
}
</style>
</head>
<body>
  <!-- ================= Header ================= -->
  <header class="header">
    <div class="header-inner">
      <!-- Brand kiri -->
      <a href="index.php" class="brand">
        <div class="brand-mark">
          <img src="auth/images/logo.png" alt="SnapRent Logo">
        </div>
        <div class="brand-text">
          <span class="brand-name">SnapRent</span>
          <span class="brand-tagline">Camera Rental</span>
        </div>
      </a>

      <!-- Search tengah -->
      <div class="header-center">
        <div class="searchbar">
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="Search cameras, lenses, brands...">
        </div>
      </div>

      <!-- Actions kanan -->
      <div class="header-actions">
        <button class="icon-button" title="Profile">
          <a href="customer/index.php">👤</a>
        </button>
        <button class="icon-button" title="Notifications">
          <a href="customer/notification.php">🔔</a>
        </button>
        <button class="icon-button" title="Bookings">
          <a href="customer/booking.php">🛒</a>
        </button>
      </div>
    </div>
  </header>

  <!-- Toast wishlist -->
  <div class="toast-wishlist" id="wishlistToast">Added to wishlist</div>

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
            <button
              class="fav<?= $inWishlist ? ' fav-added' : '' ?>"
              type="button"
              id="favBtn"
              data-camera-id="<?= (int)$cameraId ?>"
              data-initial="<?= $inWishlist ? '1' : '0' ?>"
            >
              ❤
              <span
                class="fav-badge"
                id="favBadge"
                style="<?= $inWishlist ? 'display:flex;' : 'display:none;' ?>"
              >❤️</span>
            </button>
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
          <!-- KIRI: Sensor & Brand -->
          <div class="spec-left">
            <div class="srow">
              <span class="k">Sensor Type</span>
              <span class="v"><?= e($camera['type'] ?: '—') ?></span>
            </div>
            <div class="srow">
              <span class="k">Brand</span>
              <span class="v"><?= e($camera['brand'] ?: '—') ?></span>
            </div>
          </div>

          <!-- KANAN: Description -->
          <div class="spec-right">
            <div class="srow" style="border-bottom:none;padding-bottom:4px;">
              <span class="k">Description</span>
              <span class="v"></span>
            </div>
            <div class="desc-body">
              <?php foreach ($descriptionLines as $line): ?>
                <div class="desc-line">• <?= e($line) ?></div>
              <?php endforeach; ?>
            </div>
          </div>
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
          <?php foreach ($reviews as $r): 
            $helpCount = (int)($r['helpful'] ?? 0);
            $already   = !empty($_SESSION['helpful_clicked'][$r['id'] ?? 0]);
          ?>
            <div class="card"
                data-rating="<?= e($r['rating']) ?>"
                data-created="<?= e($r['created_at']) ?>">
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
                <div class="help<?= $already ? ' help-locked' : '' ?>"
                    data-help="<?= $helpCount ?>"
                    data-review-id="<?= (int)$r['id'] ?>"
                    data-locked="<?= $already ? '1' : '0' ?>">
                  👍 <span>Helpfull (<?= $helpCount ?>)</span>
                </div>
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
// ===================== Validasi tanggal + alur ke checkout =====================
const startDate = document.getElementById('startDate');
const endDate   = document.getElementById('endDate');
const rentNow   = document.getElementById('rentNow');

startDate?.addEventListener('change', () => {
  endDate.min = startDate.value;
  if (endDate.value < startDate.value) {
    endDate.value = startDate.value;
  }
});

rentNow?.addEventListener('click', (e) => {
  const start = startDate.value;
  const end   = endDate.value;
  const sDate = new Date(start);
  const eDate = new Date(end);

  if (!start || !end) {
    alert('Silakan pilih tanggal mulai dan tanggal selesai.');
    e.preventDefault();
    return;
  }
  if (eDate < sDate) {
    alert('Tanggal selesai tidak boleh sebelum tanggal mulai.');
    e.preventDefault();
    return;
  }

  const cameraId = <?= (int)$cameraId ?>;
  const url = `checkout.php?id=${cameraId}&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
  window.location.href = url;
});

// ===================== HELPFUL AJAX =====================
// ambil user id dari PHP
const CURRENT_USER_ID = <?= $customerId ? (int)$customerId : 'null' ?>;
// URL untuk AJAX (pakai halaman ini sendiri + query ?id=)
const HELP_URL = "details.php?id=<?= (int)$cameraId ?>";

// tombol "helpful" – 1 user hanya bisa sekali per review (dibatasi di session & DB)
document.querySelectorAll('.help').forEach(el => {
  el.addEventListener('click', async () => {
    // harus login
    if (!CURRENT_USER_ID) {
      alert('Silakan login terlebih dahulu untuk memberi tanda helpful pada review.');
      return;
    }

    // kalau sudah di-lock di session (class / data-locked)
    if (el.dataset.locked === '1') {
      alert('Kamu sudah menandai review ini sebagai helpful.');
      return;
    }

    const reviewId = el.getAttribute('data-review-id');
    if (!reviewId) return;

    const form = new FormData();
    form.append('action', 'helpful');
    form.append('review_id', reviewId);

    try {
      const res  = await fetch(HELP_URL, {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
      });
      const data = await res.json();

      if (data.status === 'ok') {
        const span = el.querySelector('span');
        if (span && typeof data.helpful !== 'undefined') {
          span.textContent = `Helpfull (${data.helpful})`;
        }
        el.dataset.locked = '1';
        el.classList.add('help-locked');
      } else if (data.message === 'ALREADY') {
        alert('Kamu sudah menandai review ini sebelumnya.');
        el.dataset.locked = '1';
        el.classList.add('help-locked');
      } else if (data.message === 'LOGIN_REQUIRED') {
        alert('Silakan login terlebih dahulu.');
      } else {
        alert('Gagal menyimpan helpful. Coba lagi nanti.');
      }
    } catch (err) {
      console.error(err);
      alert('Gagal koneksi ke server.');
    }
  });
});

// ===================== Filter & sort reviews =====================
(function(){
  const btnRecent = document.getElementById('btnRecent');
  const btnAll    = document.getElementById('btnAll');
  const list      = document.querySelector('.list');
  if (!list || !btnRecent || !btnAll) return;

  // Ambil semua card review yang punya data-rating (skip placeholder)
  const allCards = Array.from(list.querySelectorAll('.card[data-rating]'));

  // helper: set chip active
  function setActive(btn){
    [btnRecent, btnAll].forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }

  // helper: render ulang urutan card ke dalam .list
  function renderSorted(cards){
    cards.forEach(c => list.appendChild(c));
  }

  // Sort by tanggal terbaru
  function sortByRecent(cards){
    return [...cards].sort((a,b) => {
      const da = new Date((a.dataset.created || '').replace(' ', 'T'));
      const db = new Date((b.dataset.created || '').replace(' ', 'T'));
      return db - da; // terbaru duluan
    });
  }

  // Sort by rating tertinggi, kalau rating sama → terbaru duluan
  function sortByRating(cards){
    return [...cards].sort((a,b) => {
      const ra = parseFloat(a.dataset.rating || '0');
      const rb = parseFloat(b.dataset.rating || '0');
      if (rb !== ra) return rb - ra; // rating tinggi dulu
      const da = new Date((a.dataset.created || '').replace(' ', 'T'));
      const db = new Date((b.dataset.created || '').replace(' ', 'T'));
      return db - da; // terbaru duluan
    });
  }

  // Default: Most Recent aktif
  setActive(btnRecent);
  renderSorted(sortByRecent(allCards));

  btnRecent.addEventListener('click', () => {
    setActive(btnRecent);
    renderSorted(sortByRecent(allCards));
  });

  btnAll.addEventListener('click', () => {
    setActive(btnAll);
    renderSorted(sortByRating(allCards));
  });
})();

// ===================== Gallery: klik thumb -> ganti gambar utama =====================
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

/* ====== FAV → TOGGLE WISHLIST (ADD / REMOVE via add_wishlist.php) ====== */
(function(){
  const btn   = document.getElementById('favBtn');
  const badge = document.getElementById('favBadge');
  const toast = document.getElementById('wishlistToast');

  if (!btn) return;

  // state awal dari server (sudah di-wishlist atau belum)
  let isWish = (btn.dataset.initial === '1');

  btn.addEventListener('click', async () => {
    const cid = btn.dataset.cameraId;
    if (!cid) return;

    const fd = new FormData();
    fd.append('camera_id', cid);

    try{
      const res = await fetch('customer/add_wishlist.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });

      const data = await res.json().catch(() => null);

      if (!res.ok || !data || data.status !== 'ok') {
        if (toast){
          toast.textContent = (data && data.message) || 'Gagal update wishlist';
          toast.classList.add('show');
          setTimeout(() => toast.classList.remove('show'), 1500);
        }
        return;
      }

      // toggle state dari server
      isWish = !!data.in_wishlist;

      if (isWish){
        btn.classList.add('fav-added');
        if (badge) badge.style.display = 'flex';
      } else {
        btn.classList.remove('fav-added');
        if (badge) badge.style.display = 'none';
      }

      // ---- TRIGGER ANIMASI POP SETIAP KALI KLIK BERHASIL ----
      btn.classList.remove('fav-anim');     // reset kalau masih ada
      void btn.offsetWidth;                 // force reflow biar animasi bisa diulang
      btn.classList.add('fav-anim');        // jalankan animasi
      setTimeout(() => {
        btn.classList.remove('fav-anim');   // bersihkan class setelah animasi
      }, 300);

      // toast text
      if (toast){
        toast.textContent = data.message || (isWish ? 'Added to wishlist' : 'Removed from wishlist');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 1500);
      }

    } catch(e){
      if (toast){
        toast.textContent = 'Gagal koneksi ke server';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 1500);
      }
    }
  });
})();
</script>
</body>
</html>
