<?php
// booking.php — Halaman My Booking & Wishlist (Customer)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========== CEK LOGIN DENGAN STANDAR BARU (uid) ==========
if (!isset($_SESSION['uid']) || (int)$_SESSION['uid'] <= 0) {
    // Belum login → lempar ke halaman login
    header("Location: ../auth/login.php");
    exit;
}

// Ambil ID customer dari session (id di tabel accounts)
$customerId = (int)$_SESSION['uid'];

// (Opsional) role kalau mau dipakai nanti
$currentRole = strtoupper((string)($_SESSION['role'] ?? ''));

// ========== KONEKSI DB ==========
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
    echo "Tidak menemukan file database/db.php. Pastikan path benar relatif dari booking.php.";
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "Koneksi DB (PDO) tidak tersedia.";
    exit;
}

// helper escape
if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('rupiah')) {
    function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }
}

// helper gambar (booking.php di /customer → ke ../Dashboard/uploads/...)
function build_cam_thumb_url($cameraId, $filename): string {
    $cameraId = (int)$cameraId;
    $f = ltrim((string)$filename, '/');

    if ($f === '') {
        return '../Dashboard/uploads/placeholder.jpg'; // opsional
    }
    if (preg_match('~^cameras/~', $f)) {
        return '../Dashboard/uploads/'.$f;
    }
    if (preg_match('~^\d+\/~', $f)) {
        return '../Dashboard/uploads/cameras/'.$f;
    }
    return '../Dashboard/uploads/cameras/'.$cameraId.'/'.$f;
}

// ========== FLASH MESSAGE UNTUK REVIEW ==========
$flashMsg  = null;
$flashType = null;

// ========== HANDLE POST: SIMPAN / UPDATE REVIEW ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_review') {
    $rentalId = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
    $cameraId = isset($_POST['camera_id']) ? (int)$_POST['camera_id'] : 0;
    $rating   = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment  = trim($_POST['comment'] ?? '');
    $feeling  = $_POST['feeling'] ?? '';
    $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

    if ($rating < 1 || $rating > 5) {
        $flashMsg  = 'Silakan pilih rating bintang 1–5.';
        $flashType = 'danger';
    } elseif ($cameraId <= 0) {
        $flashMsg  = 'Data kamera tidak valid.';
        $flashType = 'danger';
    } else {
        // prefix kesan ke comment (opsional)
        $prefix = '';
        if ($feeling === 'good')   $prefix = '[Bagus] ';
        if ($feeling === 'ok')     $prefix = '[OK] ';
        if ($feeling === 'bad')    $prefix = '[Kurang] ';

        $fullComment = $prefix . $comment;

        if ($reviewId > 0) {
            // UPDATE review (tanpa kolom updated_at)
            $st = $pdo->prepare("
                UPDATE reviews
                SET rating = ?, comment = ?
                WHERE id = ? AND customer_id = ?
            ");
            $st->execute([$rating, $fullComment, $reviewId, $customerId]);
            $flashMsg  = 'Review berhasil diperbarui.';
            $flashType = 'success';
        } else {
            // INSERT review baru
            $st = $pdo->prepare("
                INSERT INTO reviews (camera_id, customer_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $st->execute([$cameraId, $customerId, $rating, $fullComment]);
            $flashMsg  = 'Review berhasil disimpan.';
            $flashType = 'success';
        }
    }

    // Optional: redirect agar form tidak resubmit saat refresh
    header("Location: booking.php");
    exit;
}

// ========== TENTUKAN TAB ==========
$tab = isset($_GET['page']) ? $_GET['page'] : 'booking';

// ========== QUERY WISHLIST JIKA TAB WISHLIST ==========
$wishlistItems = [];
if ($tab === 'wishlist') {
    $sql = "
        SELECT
            w.id            AS wishlist_id,
            w.created_at    AS wishlist_created_at,
            c.id            AS camera_id,
            c.name,
            c.brand,
            c.type,
            c.daily_price,
            c.status,
            (
                SELECT ci.filename
                FROM camera_images ci
                WHERE ci.camera_id = c.id
                ORDER BY ci.id DESC
                LIMIT 1
            ) AS image
        FROM wishlists w
        INNER JOIN cameras c ON c.id = w.camera_id
        WHERE w.customer_id = ?
        ORDER BY w.created_at DESC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$customerId]);
    $wishlistItems = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// ========== QUERY MY BOOKINGS (HISTORY) JIKA TAB BOOKING ==========
$bookings = [];
if ($tab !== 'wishlist') {
    $sql = "
        SELECT
            r.id          AS rental_id,
            r.camera_id,
            r.start_date,
            r.end_date,
            r.total_price,
            r.status,
            r.created_at,
            c.name,
            c.brand,
            c.type,
            (
                SELECT ci.filename
                FROM camera_images ci
                WHERE ci.camera_id = r.camera_id
                ORDER BY ci.id DESC
                LIMIT 1
            ) AS image,
            (
                SELECT rv.id
                FROM reviews rv
                WHERE rv.camera_id = r.camera_id
                  AND rv.customer_id = r.customer_id
                ORDER BY rv.id DESC
                LIMIT 1
            ) AS review_id,
            (
                SELECT rv.rating
                FROM reviews rv
                WHERE rv.camera_id = r.camera_id
                  AND rv.customer_id = r.customer_id
                ORDER BY rv.id DESC
                LIMIT 1
            ) AS last_rating,
            (
                SELECT rv.comment
                FROM reviews rv
                WHERE rv.camera_id = r.camera_id
                  AND rv.customer_id = r.customer_id
                ORDER BY rv.id DESC
                LIMIT 1
            ) AS last_comment
        FROM rentals r
        INNER JOIN cameras c ON c.id = r.camera_id
        WHERE r.customer_id = ?
        ORDER BY r.created_at DESC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$customerId]);
    $bookings = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking - SnapRent</title>

    <!-- CSS utama customer -->
    <link rel="stylesheet" href="assets/style.css">

    <!-- FONT AWESOME -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ======= STYLE GLOBAL (bukan sidebar) ======= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        /* ====== WRAPPER KONTEN BARU BOOKING + WISHLIST ====== */
        .content-wrapper{
            flex:1;
            padding:24px;
        }
        .page-title{
            font-size:22px;
            font-weight:600;
            margin-bottom:4px;
        }
        .page-subtitle{
            font-size:14px;
            color:#6b7280;
            margin-bottom:20px;
        }
        .tabs{
            display:flex;
            gap:12px;
            border-bottom:1px solid #e5e7eb;
            margin-bottom:16px;
        }
        .tab-link{
            padding:8px 14px;
            font-size:14px;
            border-radius:999px;
            cursor:pointer;
            text-decoration:none;
            color:#4b5563;
            background:transparent;
        }
        .tab-link.active{
            background:#111827;
            color:#fff;
        }

        /* ====== FLASH MESSAGE SEDERHANA ====== */
        .flash{
            padding:10px 12px;
            border-radius:8px;
            margin-bottom:12px;
            font-size:14px;
        }
        .flash-success{
            background:#dcfce7;
            color:#166534;
            border:1px solid #4ade80;
        }
        .flash-danger{
            background:#fee2e2;
            color:#b91c1c;
            border:1px solid #fca5a5;
        }

        /* ====== WISHLIST LIST ====== */
        .wishlist-list{
            display:flex;
            flex-direction:column;
            gap:12px;
        }
        .wishlist-card{
            display:flex;
            gap:12px;
            align-items:center;
            padding:10px 12px;
            border-radius:10px;
            border:1px solid #e5e7eb;
            background:#ffffff;
        }
        .wishlist-thumb{
            width:64px;
            height:64px;
            border-radius:8px;
            background:#e5e7eb;
            overflow:hidden;
            flex-shrink:0;
        }
        .wishlist-thumb img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .wishlist-info{
            flex:1;
            display:flex;
            flex-direction:column;
            gap:2px;
        }
        .wishlist-title{
            font-size:15px;
            font-weight:600;
            color:#111827;
        }
        .wishlist-meta{
            font-size:13px;
            color:#6b7280;
        }
        .wishlist-price{
            font-size:14px;
            font-weight:600;
            margin-top:4px;
        }
        .wishlist-right{
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            gap:6px;
            font-size:12px;
        }
        .wishlist-date{
            color:#9ca3af;
        }
        .wishlist-view{
            padding:4px 10px;
            font-size:12px;
            border-radius:999px;
            border:1px solid #111827;
            background:#fff;
            cursor:pointer;
            text-decoration:none;
            color:#111827;
        }
        .wishlist-empty{
            padding:18px;
            border-radius:10px;
            border:1px dashed #d1d5db;
            font-size:14px;
            color:#6b7280;
            text-align:center;
            background:#f9fafb;
        }

        /* ====== CARD MODERN UNTUK MY BOOKINGS ====== */
        .booking-cards{
            display:flex;
            flex-direction:column;
            gap:14px;
        }
        .booking-card{
            display:grid;
            grid-template-columns:72px 1fr 210px;
            gap:14px;
            padding:14px 16px;
            border-radius:12px;
            background:#ffffff;
            border:1px solid #e5e7eb;
            box-shadow:0 4px 10px rgba(15,23,42,0.04);
        }
        .booking-thumb{
            width:72px;
            height:72px;
            border-radius:10px;
            background:#e5e7eb;
            overflow:hidden;
        }
        .booking-thumb img{
            width:100%;
            height:100%;
            object-fit:cover;
        }
        .booking-main{
            display:flex;
            flex-direction:column;
            gap:4px;
        }
        .booking-title{
            font-size:15px;
            font-weight:600;
            color:#111827;
        }
        .booking-meta{
            font-size:13px;
            color:#6b7280;
        }
        .booking-dates{
            font-size:13px;
            color:#4b5563;
        }
        .booking-price{
            font-size:14px;
            font-weight:600;
            margin-top:4px;
        }
        .booking-side{
            display:flex;
            flex-direction:column;
            align-items:flex-end;
            justify-content:space-between;
            gap:8px;
            font-size:12px;
        }
        .status-badge{
            display:inline-flex;
            align-items:center;
            padding:4px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:500;
        }
        .status-pending{
            background:#fef3c7;
            color:#92400e;
        }
        .status-confirmed{
            background:#dcfce7;
            color:#166534;
        }
        .status-rented{
            background:#dbeafe;
            color:#1d4ed8;
        }
        .status-returned{
            background:#e5e7eb;
            color:#374151;
        }
        .status-cancelled{
            background:#fee2e2;
            color:#b91c1c;
        }
        .booking-created{
            color:#9ca3af;
        }

        /* ====== RATING BINTANG & FORM REVIEW ====== */
        .review-section{
            margin-top:8px;
            padding-top:8px;
            border-top:1px dashed #e5e7eb;
        }
        .review-label{
            font-size:13px;
            font-weight:600;
            margin-bottom:4px;
            color:#111827;
        }
        .review-existing{
            font-size:13px;
            color:#4b5563;
            margin-bottom:6px;
        }
        .review-existing strong{
            font-weight:600;
        }
        .review-form{
            display:flex;
            flex-direction:column;
            gap:6px;
            margin-top:4px;
        }
        .review-row{
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            gap:10px;
            font-size:13px;
        }
        .rating-group{
            display:inline-flex;
            flex-direction:row-reverse;
        }
        .rating-group input{
            display:none;
        }
        .rating-group label{
            font-size:18px;
            color:#d1d5db;
            cursor:pointer;
            padding:0 2px;
        }
        .rating-group input:checked ~ label,
        .rating-group label:hover,
        .rating-group label:hover ~ label{
            color:#fbbf24;
        }
        .review-select{
            padding:5px 8px;
            border-radius:999px;
            border:1px solid #d1d5db;
            font-size:13px;
            background:#f9fafb;
            outline:none;
        }
        .review-comment{
            width:100%;
            min-height:50px;
            padding:6px 8px;
            border-radius:8px;
            border:1px solid #e5e7eb;
            font-size:13px;
            resize:vertical;
        }
        .review-submit{
            align-self:flex-end;
            padding:5px 12px;
            font-size:13px;
            border-radius:999px;
            border:none;
            background:#111827;
            color:#fff;
            cursor:pointer;
        }
        .review-submit:hover{
            background:#020617;
        }
        .rating-stars-display{
            color:#facc15;
            font-size:13px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px){
            .booking-card{
                grid-template-columns:60px 1fr;
            }
            .booking-side{
                align-items:flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar (SAMA DENGAN index.php customer) -->
        <aside class="sidebar">

            <!-- BACK TO HOME -->
            <a href="../index.php" class="back-home">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>

            <nav>
                <!-- MENU UTAMA -->
                <a href="index.php"><i class="fas fa-users"></i> Profile</a>
                <a href="booking.php" class="active"><i class="far fa-folder-open"></i> Booking</a>
                <a href="notification.php"><i class="far fa-bell"></i> Notification</a>
            </nav>

            <!-- LOG OUT PALING BAWAH -->
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Log Out</span>
                </a>
            </div>
        </aside>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="page-title">My Booking</div>
            <div class="page-subtitle">Kelola pemesanan dan daftar wishlist kamera Anda.</div>

            <!-- Tabs -->
            <div class="tabs">
                <a href="booking.php" class="tab-link <?= $tab === 'wishlist' ? '' : 'active' ?>">My Bookings</a>
                <a href="booking.php?page=wishlist" class="tab-link <?= $tab === 'wishlist' ? 'active' : '' ?>">Wishlist</a>
            </div>

            <!-- Flash message -->
            <?php if ($flashMsg): ?>
                <div class="flash flash-<?= e($flashType ?? 'success') ?>">
                    <?= e($flashMsg) ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'wishlist'): ?>
                <!-- ========== WISHLIST CONTENT ========== -->
                <?php if (!empty($wishlistItems)): ?>
                    <div class="wishlist-list">
                        <?php foreach ($wishlistItems as $row): ?>
                            <?php
                                $thumbUrl = null;
                                if (!empty($row['image'])) {
                                    $thumbUrl = build_cam_thumb_url($row['camera_id'], $row['image']);
                                }
                            ?>
                            <div class="wishlist-card">
                                <div class="wishlist-thumb">
                                    <?php if ($thumbUrl): ?>
                                        <img src="<?= e($thumbUrl) ?>" alt="<?= e($row['name']) ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:11px;color:#6b7280;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="wishlist-info">
                                    <div class="wishlist-title">
                                        <?= e($row['brand'].' '.$row['name']) ?>
                                    </div>
                                    <div class="wishlist-meta">
                                        <?= e($row['type'] ?: '-') ?> · Status: <?= e(ucfirst($row['status'] ?? '-')) ?>
                                    </div>
                                    <div class="wishlist-price">
                                        <?= rupiah($row['daily_price']) ?> <span style="font-size:12px;color:#6b7280;">/hari</span>
                                    </div>
                                </div>

                                <div class="wishlist-right">
                                    <?php if (!empty($row['wishlist_created_at'])): ?>
                                        <div class="wishlist-date">
                                            Added: <?= e($row['wishlist_created_at']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <a class="wishlist-view" href="../details.php?id=<?= (int)$row['camera_id'] ?>">
                                        View Detail
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="wishlist-empty">
                        Belum ada kamera di wishlist kamu.<br>
                        Buka halaman detail kamera dan tekan tombol ❤ untuk menambahkan ke wishlist.
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ========== MY BOOKINGS CONTENT (CARD MODERN + REVIEW) ========== -->
                <?php if (!empty($bookings)): ?>
                    <div class="booking-cards">
                        <?php foreach ($bookings as $row): ?>
                            <?php
                                $thumbUrl = null;
                                if (!empty($row['image'])) {
                                    $thumbUrl = build_cam_thumb_url($row['camera_id'], $row['image']);
                                }

                                // status badge
                                $status = strtolower((string)$row['status']);
                                $statusClass = 'status-pending';
                                if ($status === 'confirmed') $statusClass = 'status-confirmed';
                                elseif ($status === 'rented') $statusClass = 'status-rented';
                                elseif ($status === 'returned') $statusClass = 'status-returned';
                                elseif ($status === 'cancelled') $statusClass = 'status-cancelled';

                                $reviewId    = (int)($row['review_id'] ?? 0);
                                $lastRating  = $row['last_rating'] !== null ? (int)$row['last_rating'] : 0;
                                $lastComment = (string)($row['last_comment'] ?? '');

                                // deteksi feeling dari prefix di comment
                                $lastFeeling     = '';
                                $displayComment  = $lastComment;

                                if (strpos($lastComment, '[Bagus] ') === 0) {
                                    $lastFeeling    = 'good';
                                    $displayComment = substr($lastComment, 8);
                                }
                                elseif (strpos($lastComment, '[OK] ') === 0) {
                                    $lastFeeling    = 'ok';
                                    $displayComment = substr($lastComment, 5);
                                }
                                elseif (strpos($lastComment, '[Kurang] ') === 0) {
                                    $lastFeeling    = 'bad';
                                    $displayComment = substr($lastComment, 9);
                                }
                            ?>
                            <div class="booking-card">
                                <!-- Thumbnail -->
                                <div class="booking-thumb">
                                    <?php if ($thumbUrl): ?>
                                        <img src="<?= e($thumbUrl) ?>" alt="<?= e($row['name']) ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:11px;color:#6b7280;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info Utama -->
                                <div class="booking-main">
                                    <div class="booking-title">
                                        <?= e($row['brand'].' '.$row['name']) ?>
                                    </div>
                                    <div class="booking-meta">
                                        <?= e($row['type'] ?: '-') ?>
                                    </div>
                                    <div class="booking-dates">
                                        <?= e($row['start_date']) ?> s/d <?= e($row['end_date']) ?>
                                    </div>
                                    <div class="booking-price">
                                        <?= rupiah($row['total_price']) ?>
                                    </div>

                                    <!-- Review section -->
                                    <div class="review-section">
                                        <div class="review-label">
                                            <?= $reviewId ? 'Your Review' : 'Berikan Review' ?>
                                        </div>

                                        <?php if ($reviewId && $lastRating > 0): ?>
                                            <div class="review-existing">
                                                <span class="rating-stars-display">
                                                    <?php for ($s=1;$s<=5;$s++): ?>
                                                        <?= $s <= $lastRating ? '★' : '☆' ?>
                                                    <?php endfor; ?>
                                                </span>
                                                <?php if ($displayComment !== ''): ?>
                                                    &nbsp;– <?= e($displayComment) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="post" class="review-form">
                                            <input type="hidden" name="action" value="save_review">
                                            <input type="hidden" name="rental_id" value="<?= (int)$row['rental_id'] ?>">
                                            <input type="hidden" name="camera_id" value="<?= (int)$row['camera_id'] ?>">
                                            <input type="hidden" name="review_id" value="<?= $reviewId ?>">

                                            <div class="review-row">
                                                <span>Pilih rating:</span>
                                                <div class="rating-group">
                                                    <?php for ($star=5; $star>=1; $star--):
                                                        $id = 'rating_'.$row['rental_id'].'_'.$star;
                                                    ?>
                                                        <input
                                                            type="radio"
                                                            name="rating"
                                                            id="<?= e($id) ?>"
                                                            value="<?= $star ?>"
                                                            <?= ($lastRating === $star ? 'checked' : '') ?>
                                                        >
                                                        <label for="<?= e($id) ?>">★</label>
                                                    <?php endfor; ?>
                                                </div>

                                                <select name="feeling" class="review-select">
                                                    <option value="">Pilih kesan</option>
                                                    <option value="good" <?= $lastFeeling === 'good' ? 'selected' : '' ?>>Bagus</option>
                                                    <option value="ok"   <?= $lastFeeling === 'ok' ? 'selected' : '' ?>>OK</option>
                                                    <option value="bad"  <?= $lastFeeling === 'bad' ? 'selected' : '' ?>>Kurang</option>
                                                </select>
                                            </div>

                                            <textarea
                                                name="comment"
                                                class="review-comment"
                                                placeholder="Tulis komentar kamu di sini (opsional)..."
                                            ><?= e($displayComment) ?></textarea>

                                            <button type="submit" class="review-submit">
                                                <?= $reviewId ? 'Update Review' : 'Kirim Review' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Side Info -->
                                <div class="booking-side">
                                    <div class="status-badge <?= $statusClass ?>">
                                        <?= e(ucfirst($row['status'])) ?>
                                    </div>
                                    <div class="booking-created">
                                        Ordered: <?= e($row['created_at']) ?>
                                    </div>
                                    <a class="wishlist-view" href="../details.php?id=<?= (int)$row['camera_id'] ?>">
                                        View Detail
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="wishlist-empty">
                        Belum ada riwayat booking.<br>
                        Silakan sewa kamera melalui halaman katalog terlebih dahulu.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
