<?php
// pages/reviews.php — SnapRent Admin/Staff: Monitoring Review Produk (versi UI+filter)

// ========== SESSION & ROLE GUARD ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi PDO dari index.php sudah ada
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "<div class='alert alert-danger'>Koneksi database (PDO) tidak tersedia.</div>";
    return;
}

// Batasi akses hanya OWNER & STAFF
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['OWNER', 'STAFF'], true)) {
    http_response_code(403);
    echo "<h2>Akses ditolak</h2><p>Halaman ini hanya untuk OWNER atau STAFF.</p>";
    return;
}

// Helper html escape
if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// Ambil kamera_id kalau admin klik "Detail"
$cameraId = isset($_GET['camera_id']) ? (int)$_GET['camera_id'] : 0;

// ===========================
// 1) STATS GLOBAL REVIEW
// ===========================
try {
    // Total produk yang sudah direview
    $sqlStats = "
        SELECT 
            COUNT(DISTINCT r.camera_id)                AS total_reviewed_products,
            COUNT(r.id)                                AS total_reviews,
            COALESCE(AVG(r.rating), 0)                 AS overall_avg_rating
        FROM reviews r
    ";
    $stStats = $pdo->query($sqlStats);
    $stats   = $stStats->fetch(PDO::FETCH_ASSOC) ?: [
        'total_reviewed_products' => 0,
        'total_reviews'           => 0,
        'overall_avg_rating'      => 0,
    ];

    // ===========================
    // 2) RINGKASAN REVIEW PER PRODUK
    //    HANYA PRODUK YANG PUNYA REVIEW
    // ===========================
    /**
     * SESUAIKAN JIKA PERLU:
     * - cameras.name  -> kolom nama produk
     * - reviews       -> tabel ulasan
     */
    $sqlSummary = "
        SELECT 
            c.id                         AS camera_id,
            c.name                       AS camera_name,
            c.brand                      AS brand,
            COUNT(r.id)                  AS review_count,
            COALESCE(AVG(r.rating), 0)   AS avg_rating
        FROM reviews r
        JOIN cameras c ON r.camera_id = c.id
        GROUP BY c.id, c.name, c.brand
        HAVING COUNT(r.id) > 0
        ORDER BY avg_rating DESC, review_count DESC, c.name ASC
    ";
    $st = $pdo->query($sqlSummary);
    $summaryRows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    echo "<div class='alert alert-danger'>Gagal mengambil data review: " . e($ex->getMessage()) . "</div>";
    return;
}

// ===========================
// 3) DETAIL REVIEW UNTUK 1 PRODUK (JIKA DIPILIH)
// ===========================
$detailCamera  = null;
$detailReviews = [];

if ($cameraId > 0) {
    // Ambil info kamera
    $stCam = $pdo->prepare("SELECT id, name, brand FROM cameras WHERE id = :cid");
    $stCam->execute([':cid' => $cameraId]);
    $detailCamera = $stCam->fetch(PDO::FETCH_ASSOC);

    if ($detailCamera) {
        /**
         * SESUAIKAN JIKA PERLU:
         * - reviews.comment     -> kolom teks review
         * - reviews.created_at  -> kolom tanggal dibuat
         * - customers.customer_id & customers.full_name
         * - accounts.customer_id & accounts.username
         */
        $sqlDetail = "
            SELECT 
                r.id,
                r.rating,
                r.comment,
                r.created_at,
                cust.full_name,
                acc.username
            FROM reviews r
            LEFT JOIN customers cust 
                ON r.customer_id = cust.customer_id
            LEFT JOIN accounts acc 
                ON cust.customer_id = acc.customer_id
            WHERE r.camera_id = :cid
            ORDER BY r.created_at DESC, r.id DESC
        ";
        $stDet = $pdo->prepare($sqlDetail);
        $stDet->execute([':cid' => $cameraId]);
        $detailReviews = $stDet->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Helper buat tampilan bintang rating
function render_stars(int $rating): string {
    $rating = max(0, min(5, $rating));
    $full  = str_repeat('★', $rating);
    $empty = str_repeat('☆', 5 - $rating);
    return "<span class=\"rating-stars\">{$full}{$empty}</span>";
}

$overallAvg = (float)($stats['overall_avg_rating'] ?? 0);
$totalReviewedProducts = (int)($stats['total_reviewed_products'] ?? 0);
$totalReviews = (int)($stats['total_reviews'] ?? 0);
?>

<style>
/* Sedikit styling khusus halaman review (boleh di-copy ke CSS global kalau mau) */
.page-header h1 {
  font-weight: 600;
}

.review-stat-card {
  border-radius: 14px;
  padding: 18px 20px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.53), #ffffffff);
  color: #000000ff;
  box-shadow: 0 5px 15px rgba(15,23,42,0.55);
}

.review-stat-title {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.7;
  margin-bottom: 4px;
}

.review-stat-value {
  font-size: 1.8rem;
  font-weight: 700;
}

.review-stat-sub {
  font-size: 0.9rem;
  opacity: 0.85;
}

.table-review-products th {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.badge-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 500;
}

.badge-soft-primary {
  background: rgba(59,130,246,0.1);
  color: #1d4ed8;
}

.badge-soft-amber {
  background: rgba(245,158,11,0.1);
  color: #b45309;
}

.badge-soft-slate {
  background: rgba(148,163,184,0.12);
  color: #475569;
}

.rating-stars {
  font-size: 0.95rem;
  letter-spacing: 0.08em;
}

.table-review-products tbody tr:hover {
  background: #f9fafb;
}

.btn-outline-primary.btn-xs {
  padding: 4px 10px;
  font-size: 0.78rem;
  border-radius: 999px;
}

/* detail table */
.table-review-detail td {
  vertical-align: top;
}
</style>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Product Reviews</h1>
    <p class="text-muted mb-0">Pantau performa review untuk produk yang sudah pernah dinilai pelanggan.</p>
  </div>
</div>

<!-- =============== STAT KECIL DI ATAS =============== -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="review-stat-card">
      <div class="review-stat-title">Produk yang Direview</div>
      <div class="review-stat-value"><?= $totalReviewedProducts ?></div>
      <div class="review-stat-sub">Total kamera yang sudah menerima ulasan</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="review-stat-card" style="background: linear-gradient(135deg,#ffffffff,#ffffffff);">
      <div class="review-stat-title">Total Review</div>
      <div class="review-stat-value"><?= $totalReviews ?></div>
      <div class="review-stat-sub">Jumlah semua review yang masuk</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="review-stat-card" style="background: linear-gradient(135deg,#ffffffff,#ffffffff);">
      <div class="review-stat-title">Rata-rata Rating Global</div>
      <div class="review-stat-value">
        <?= number_format($overallAvg, 1, ',', '.') ?>
        <span style="font-size:1rem; opacity:0.9;">/ 5</span>
      </div>
      <div class="review-stat-sub">
        <?= render_stars((int)round($overallAvg)) ?>
      </div>
    </div>
  </div>
</div>

<!-- =============== RINGKASAN REVIEW PER PRODUK (HANYA YANG PUNYA REVIEW) =============== -->
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h2 class="h6 mb-0">Produk dengan Review</h2>
          <small class="text-muted">
            Menampilkan hanya kamera yang sudah memiliki minimal 1 review.
          </small>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle table-review-products">
            <thead class="table-light">
              <tr>
                <th style="width: 70px;">ID</th>
                <th>Produk</th>
                <th>Brand</th>
                <th class="text-center" style="width: 130px;">Jml Review</th>
                <th class="text-center" style="width: 180px;">Rata-rata Rating</th>
                <th class="text-end" style="width: 130px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($summaryRows)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    Belum ada review sama sekali di sistem.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($summaryRows as $row): 
                    $avg   = (float)$row['avg_rating'];
                    $count = (int)$row['review_count'];

                    // Badge rating
                    if ($avg >= 4.5) {
                        $ratingBadgeClass = 'badge-soft-primary';
                        $ratingLabel      = 'Sangat Bagus';
                    } elseif ($avg >= 3.5) {
                        $ratingBadgeClass = 'badge-soft-amber';
                        $ratingLabel      = 'Bagus';
                    } else {
                        $ratingBadgeClass = 'badge-soft-slate';
                        $ratingLabel      = 'Perlu Perhatian';
                    }
                ?>
                  <tr>
                    <td>#<?= e($row['camera_id']) ?></td>
                    <td>
                      <div class="fw-semibold"><?= e($row['camera_name']) ?></div>
                    </td>
                    <td>
                      <span class="badge-pill badge-soft-slate">
                        <?= e($row['brand']) ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge-pill badge-soft-primary">
                        <?= $count ?> review
                      </span>
                    </td>
                    <td class="text-center">
                      <div><?= number_format($avg, 1, ',', '.') ?>/5</div>
                      <div><?= render_stars((int)round($avg)) ?></div>
                      <div class="mt-1">
                        <span class="badge-pill <?= $ratingBadgeClass ?>">
                          <?= e($ratingLabel) ?>
                        </span>
                      </div>
                    </td>
                    <td class="text-end">
                      <a href="?page=reviews&camera_id=<?= (int)$row['camera_id'] ?>#detail"
                         class="btn btn-outline-primary btn-xs">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =============== DETAIL REVIEW UNTUK 1 PRODUK =============== -->
<?php if ($cameraId > 0 && $detailCamera): ?>
  <div id="detail" class="row g-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h2 class="h6 mb-0">
              Detail Review: <?= e($detailCamera['name']) ?> (<?= e($detailCamera['brand']) ?>)
            </h2>
            <small class="text-muted">
              Total <?= count($detailReviews) ?> review.
            </small>
          </div>
          <a href="?page=reviews" class="btn btn-sm btn-light">Kembali ke semua produk</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle table-review-detail">
              <thead class="table-light">
                <tr>
                  <th style="width: 70px;">ID</th>
                  <th style="width: 120px;">Rating</th>
                  <th>Review</th>
                  <th style="width: 220px;">Customer</th>
                  <th style="width: 170px;">Tanggal</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($detailReviews)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                      Belum ada review untuk produk ini.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($detailReviews as $rev): ?>
                    <tr>
                      <td>#<?= (int)$rev['id'] ?></td>
                      <td>
                        <?php
                          $rating = (int)($rev['rating'] ?? 0);
                          echo $rating . ' / 5<br>' . render_stars($rating);
                        ?>
                      </td>
                      <td><?= nl2br(e($rev['comment'] ?? '')) ?></td>
                      <td>
                        <?php
                          $nama  = $rev['full_name'] ?? '';
                          $uname = $rev['username'] ?? '';
                          if ($nama) {
                              echo e($nama);
                              if ($uname) {
                                  echo ' <small class="text-muted">(@' . e($uname) . ')</small>';
                              }
                          } elseif ($uname) {
                              echo '@' . e($uname);
                          } else {
                              echo '<span class="text-muted">-</span>';
                          }
                        ?>
                      </td>
                      <td>
                        <?php
                          $tgl = $rev['created_at'] ?? '';
                          echo $tgl ? e($tgl) : '<span class="text-muted">-</span>';
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
