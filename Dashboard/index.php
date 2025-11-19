<?php
// index.php — SnapRent Admin Dashboard (protected)

// Start output buffering FIRST before any output
ob_start();

require __DIR__ . '/../auth/auth.php';
require_login($pdo);

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Ambil user yang sedang login dari helper currentUser($pdo).
 * Helper ini biasanya baca dari session (uid/role) dan cek ke DB.
 */
$user = currentUser($pdo);

/**
 * Normalisasi ID & role:
 *  - Gunakan $_SESSION['uid'] sebagai ID utama untuk SEMUA role.
 *  - Kalau belum ada tapi $user['id'] tersedia, sinkronkan.
 */
if (!isset($_SESSION['uid']) && isset($user['id'])) {
  $_SESSION['uid'] = (int)$user['id'];
}

$currentUserId = (int)($_SESSION['uid'] ?? 0);
$currentRole   = strtoupper((string)($user['role'] ?? ($_SESSION['role'] ?? '')));

/**
 * Batasi akses Dashboard:
 *  Hanya OWNER dan STAFF yang boleh masuk.
 */
if (!in_array($currentRole, ['OWNER', 'STAFF'], true)) {
  http_response_code(403);
  echo "<h2>Akses ditolak</h2><p>Halaman Dashboard hanya dapat diakses oleh OWNER dan STAFF.</p>";
  // Selesai — jangan lanjut render dashboard
  ob_end_flush();
  exit;
}

// Pastikan upload dir ada
$UPLOAD_DIR = __DIR__ . '/uploads';
if (!is_dir($UPLOAD_DIR)) {
  @mkdir($UPLOAD_DIR, 0775, true);
}

// Helpers lokal
function rupiah($number){ return 'Rp '.number_format((float)$number,0,',','.'); }
function idr_compact($n){
  $n = (float)$n;
  if ($n >= 1000000000) return round($n/1000000000).'M';
  if ($n >= 1000000)    return round($n/1000000).'JT';
  if ($n >= 1000)       return round($n/1000).'Rb';
  return (string)number_format($n,0,',','.');
}

$page = $_GET['page'] ?? 'dashboard';
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

// $user sudah di-set di atas dari currentUser($pdo)

// Global widgets
$users_list = $pdo->query("
  SELECT id, username, email, role, created_at 
  FROM accounts 
  ORDER BY created_at DESC 
  LIMIT 200
")->fetchAll();

$top_products = $pdo->query("
  SELECT cam.name, COUNT(*) cnt 
  FROM rentals rn 
  JOIN cameras cam ON cam.id = rn.camera_id 
  GROUP BY cam.id, cam.name 
  ORDER BY cnt DESC 
  LIMIT 5
")->fetchAll();

// Include header
require_once __DIR__ . '/includes/header.php';

// Include sidebar
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
  <!-- Header -->
  <header>
    <h1>Welcome Back, <?= e($user['username']) ?>!</h1>
    <div class="header-actions">
      <i class="fas fa-bell bell-icon"></i>
    </div>
  </header>

  <section class="content-area">
    <?php
    // Route to appropriate page
    // ✅ SUDAH DITAMBAHKAN 'reviews'
    $allowed_pages = ['dashboard', 'orders', 'users', 'reports', 'products', 'rentals', 'reviews'];

    if (in_array($page, $allowed_pages, true)) {
      $page_file = __DIR__ . '/pages/' . $page . '.php';
      if (file_exists($page_file)) {
        require_once $page_file;
      } else {
        echo '<div class="alert alert-danger">Page file not found.</div>';
      }
    } else {
      echo '<div class="alert alert-warning">Page not found.</div>';
    }
    ?>
  </section>
</div>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';

// Flush output buffer at the end
ob_end_flush();
?>
