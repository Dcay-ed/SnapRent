<?php
session_start();

/* ===================== CEK LOGIN CUSTOMER ===================== */
if (!isset($_SESSION['uid']) || (($_SESSION['role'] ?? '') !== 'CUSTOMER')) {
    header("Location: ../auth/login.php");
    exit;
}

$accountId = (int) $_SESSION['uid'];

/* ===================== KONEKSI DATABASE ===================== */
$paths = [
    __DIR__ . '/../database/db.php',
    __DIR__ . '/../Database/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../includes/db.php',
];
$found = false;
foreach ($paths as $p) {
    if (is_file($p)) { require_once $p; $found = true; break; }
}
if (!$found) {
    http_response_code(500);
    echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari notification.php.";
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "Koneksi DB (PDO) tidak tersedia.";
    exit;
}

/* ===================== HELPER ===================== */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Format waktu: 24 Nov 2018 at 9:30 AM */
function formatTime(?string $datetime): string {
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    return date('d M Y \a\t g:i A', $ts);
}

/** Format tanggal saja: 24 Nov 2018 */
function formatDateOnly(?string $date): string {
    if (!$date) return '';
    $ts = strtotime($date);
    if ($ts === false) return $date;
    return date('d M Y', $ts);
}

/* ===================== QUERY RENTALS (RELASI ACCOUNTS → CUSTOMERS → RENTALS) ===================== */
/*
   Struktur DB (snaprent.sql):
   - accounts  : id, customer_id, username, email, phone, role, ...
   - customers : customer_id, customer_code, full_name, address
   - rentals   : customer_id (FK ke customers.customer_id)
   - cameras   : data produk kamera
   - v_rental_payment_status : view status pembayaran terakhir

   Di sini kita ambil semua rentals untuk akun yang sedang login:
   accounts.id (session uid) -> accounts.customer_id
   -> customers.customer_id -> rentals.customer_id
*/

$notifOrders    = [];
$notifDeadlines = [];

$sqlBase = "
  FROM rentals rn
  JOIN customers c
    ON c.customer_id = rn.customer_id
  JOIN accounts a
    ON a.customer_id = c.customer_id
  JOIN cameras cam
    ON cam.id = rn.camera_id
  LEFT JOIN v_rental_payment_status v
    ON v.rental_id = rn.id
  WHERE a.id = :aid
    AND (
      rn.status IN ('confirmed','rented')
      OR v.last_payment_status = 'verified'
    )
";

/* ORDER BERHASIL (payment sukses / sewa dikonfirmasi) */
$stmt = $pdo->prepare("
  SELECT rn.id,
         rn.total_price,
         rn.start_date,
         rn.end_date,
         rn.created_at,
         cam.name AS camera_name
  $sqlBase
  ORDER BY rn.created_at DESC
  LIMIT 50
");
$stmt->execute([':aid' => $accountId]);
$notifOrders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* DEADLINE PENGEMBALIAN (end_date <= hari ini) */
$today = date('Y-m-d');
$stmt2 = $pdo->prepare("
  SELECT rn.id,
         rn.total_price,
         rn.start_date,
         rn.end_date,
         rn.created_at,
         cam.name AS camera_name
  $sqlBase
    AND rn.end_date <= :today
  ORDER BY rn.end_date ASC
  LIMIT 50
");
$stmt2->execute([
  ':aid'   => $accountId,
  ':today' => $today,
]);
$notifDeadlines = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ===================== BANGUN ARRAY NOTIFICATIONS UNTUK DESAIN CARD ===================== */

$notifications = [];

// 1) Payment sukses → label oranye (Payment Success)
foreach ($notifOrders as $row) {
    $camera = $row['camera_name'] ?? 'Kamera';
    $start  = $row['start_date'] ?? null;
    $end    = $row['end_date'] ?? null;
    $time   = $row['created_at'] ?? $start;

    $notifications[] = [
        'type'  => 'payment', // nanti kita map ke label 'orange'
        'title' => "Pembayaran berhasil untuk sewa {$camera}",
        'desc'  => "Pembayaran rental kamera berhasil dikonfirmasi. "
                 . "Silakan ambil kamera di toko pada tanggal " . formatDateOnly($start) . ".",
        'sender'=> 'SnapRent',
        'time'  => formatTime($time),
    ];
}

// 2) Deadline pengembalian → label biru (Return Deadline)
foreach ($notifDeadlines as $row) {
    $camera = $row['camera_name'] ?? 'Kamera';
    $end    = $row['end_date'] ?? null;
    $time   = $end;

    $notifications[] = [
        'type'  => 'deadline', // nanti kita map ke label 'blue'
        'title' => "Pengembalian {$camera} segera jatuh tempo",
        'desc'  => "Batas pengembalian kamera adalah pada "
                 . formatDateOnly($end)
                 . ". Mohon kembalikan kamera tepat waktu untuk menghindari denda.",
        'sender'=> 'SnapRent',
        'time'  => formatTime($time),
    ];
}

/* (Opsional) bisa di-sort berdasarkan waktu terbaru */
usort($notifications, function($a, $b){
    return strtotime($b['time']) <=> strtotime($a['time']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification - SnapRent</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Font Awesome, biar icon <i class="fas fa-users"> dll muncul -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="notification-page">
    <div class="container">
        <!-- Sidebar tetap seperti desain awal -->
    <aside class="sidebar">

        <!-- BACK TO HOME -->
        <a href="../index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <!-- MENU UTAMA -->
        <nav>
            <a href="index.php"><i class="fas fa-users"></i> Profile</a>
            <a href="booking.php"><i class="far fa-folder-open"></i> Booking</a>
            <a href="notification.php" class="active"><i class="far fa-bell"></i> Notification</a>
        </nav>

        <!-- LOG OUT PALING BAWAH -->
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="sidebar-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

        <!-- Main content pakai desain NOTIFICATIONS card (tidak diubah) -->
        <main class="main-content">
            <div class="notif-inner">
                <h1 class="notif-title">NOTIFICATIONS</h1>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <p style="font-size:13px; color:#7a8197;">
                            Anda belum memiliki notifikasi pembayaran atau pengembalian kamera.
                        </p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <?php
                                $typeClass = '';
                                $typeLabel = '';

                                switch ($notif['type']) {
                                    case 'payment':
                                        $typeClass = 'orange';
                                        $typeLabel = 'Payment Success';
                                        break;
                                    case 'deadline':
                                        $typeClass = 'blue';
                                        $typeLabel = 'Return Deadline';
                                        break;
                                }
                            ?>
                            <div class="notification-item">
                                <div class="close-btn">×</div>
                                <div class="notification-content">
                                    <span class="label <?= e($typeClass) ?>"><?= e($typeLabel) ?></span>
                                    <h3><?= e($notif['title']) ?></h3>
                                    <p><?= e($notif['desc']) ?></p>
                                    <span class="sender"><?= e($notif['sender']) ?></span>
                                </div>
                                <div class="notification-time">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?= e($notif['time']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
