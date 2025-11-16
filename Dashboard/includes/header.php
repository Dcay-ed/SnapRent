<?php
// Pastikan session jalan (biasanya sudah dari index.php, tapi aman kita cek)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Ambil 5 order rental terbaru untuk notifikasi.
 * Asumsi:
 *  - $pdo sudah dibuat di index.php (PDO)
 *  - Tabel: rentals, customers, accounts, cameras
 */
$notifications = [];

if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare("
      SELECT
        rn.id,
        rn.created_at,
        rn.status,
        rn.start_date,
        rn.end_date,
        rn.total_price,
        cam.name      AS camera_name,
        acc.username  AS customer_name
      FROM rentals rn
      JOIN customers c
        ON c.customer_id = rn.customer_id
      JOIN accounts acc
        ON acc.id = c.customer_id
      JOIN cameras cam
        ON cam.id = rn.camera_id
      ORDER BY rn.created_at DESC
      LIMIT 5
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    $notifications = [];
  }
}

// Helper rupiah kalau belum ada
if (!function_exists('rupiah')) {
  function rupiah($number){
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnapRent - Camera Rental Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    background-color: #e8ecf3; 
    display: flex;
    height: 100vh;
    overflow: hidden;
  }

  /* Sidebar - Sesuai Gambar */
  .sidebar { 
    width: 220px; 
    background-color: #ffffff; 
    height: 100vh; 
    padding: 25px 15px; 
    position: fixed; 
    display: flex; 
    flex-direction: column; 
    z-index: 1000;
    overflow-y: auto;
    border-top-right-radius: 25px;
    border-bottom-right-radius: 25px;
  }

  .logo { 
    display: flex; 
    align-items: center; 
    gap: 5px; 
    font-size: 36px; 
    font-weight: 700; 
    color: #6b8cbb; 
    margin-bottom: 40px; 
    padding-left: 10px;
    font-family: 'Arial', sans-serif;
  }

  .logo .logo-s {
    color: #6b8cbb;
  }

  .logo .logo-text {
    color: #2c3e50;
    font-weight: 400;
  }

  .menu { 
    flex: 1; 
    overflow-y: auto;
  }

  .menu-title { 
    font-size: 15px; 
    color: #2c3e50; 
    margin-bottom: 15px; 
    font-weight: 700; 
    text-decoration: underline;
    padding-left: 10px;
  }

  .menu-item { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
    padding: 12px 15px; 
    margin-bottom: 5px; 
    color: #4a5568; 
    text-decoration: none; 
    border-radius: 10px; 
    transition: all 0.3s;
    font-size: 15px;
    font-weight: 500;
  }

  .menu-item:hover { 
    background-color: #e8f0f8; 
    color: #3d5a80;
  }

  .menu-item.active { 
    background-color: #3d5a80; 
    color: white;
  }

  .menu-item i { 
    font-size: 18px; 
    width: 22px;
    text-align: center;
  }

  .logout-section { 
    border-top: 1px solid #e2e8f0; 
    padding-top: 15px; 
    margin-top: auto; 
  }

  /* Main Content */
  .main-content { 
    margin-left: 220px; 
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
    background-color: #e8ecf3;
  }

  /* Header - Sesuai Gambar */
  header { 
    background-color: #ffffff; 
    padding: 18px 35px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    flex-shrink: 0;
    border-bottom: 1px solid #e2e8f0;
    position: relative;
    z-index: 1100;
  }

  header h1 { 
    font-size: 26px; 
    color: #2c3e50; 
    margin: 0; 
    font-weight: 600;
  }

  .header-actions { 
    display: flex; 
    align-items: center; 
    gap: 20px; 
  }

  .header-actions .bell-icon { 
    font-size: 22px; 
    color: #6b8cbb; 
    cursor: pointer; 
    transition: color 0.3s, transform 0.15s;
    position: relative;
  }

  .header-actions .bell-icon:hover { 
    color: #3d5a80; 
    transform: translateY(-1px);
  }

  /* Dot kecil di icon bell (indikasi ada notif) */
  .bell-icon::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background-color: #22c55e;
    box-shadow: 0 0 0 4px rgba(34,197,94,0.25);
  }

  .user-profile { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    cursor: pointer; 
    transition: all 0.3s; 
  }

  .user-avatar {
    width: 45px;
    height: 45px;
    background-color: #6b8cbb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
  }

  .user-profile .fa-chevron-down {
    font-size: 12px;
    color: #4a5568;
  }

  /* Content Area - Fit dalam satu layar */
  .content-area { 
    padding: 20px 35px; 
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: #e8ecf3;
  }

  /* Sales Overview Section */
  .sales-overview { 
    background-color: #d4dfe9; 
    padding: 20px 25px; 
    border-radius: 18px; 
    margin-bottom: 20px; 
  }

  .section-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 18px; 
  }

  .section-header h4 { 
    font-size: 24px; 
    color: #2c3e50; 
    margin: 0; 
    font-weight: 600;
  }

  .filter-dropdown { 
    padding: 8px 18px; 
    border: none; 
    background-color: #a8bdd4; 
    border-radius: 8px; 
    color: #2c3e50; 
    font-weight: 600; 
    cursor: pointer;
    font-size: 13px;
  }

  /* Stats Cards - Reduced size */
  .stats-cards { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 18px; 
  }

  .stat-card { 
    background-color: white; 
    padding: 20px; 
    border-radius: 15px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }

  .stat-card h5 { 
    font-size: 14px; 
    color: #4a5568; 
    margin-bottom: 12px; 
    font-weight: 600; 
  }

  .stat-value { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    margin-bottom: 8px; 
  }

  .stat-value .number { 
    font-size: 36px; 
    font-weight: 700; 
    color: #2c3e50; 
  }

  .badge-positive { 
    background-color: #6b8cbb; 
    color: white; 
    padding: 4px 10px; 
    border-radius: 18px; 
    font-size: 11px; 
    font-weight: 600; 
  }

  .subtitle { 
    font-size: 12px; 
    color: #718096; 
  }

  /* Bottom Section - Adjusted height */
  .bottom-section { 
    display: grid; 
    grid-template-columns: 1fr 1.5fr; 
    gap: 20px; 
    margin-bottom: 20px; 
  }

  /* Top Rented - Reduced size */
  .top-rented { 
    background-color: white; 
    padding: 20px; 
    border-radius: 18px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }

  .top-rented h5 { 
    font-size: 22px; 
    color: #2c3e50; 
    margin-bottom: 18px; 
    font-weight: 600;
  }

  .rented-items { 
    display: flex; 
    gap: 12px; 
    flex-wrap: wrap; 
  }

  .rented-item { 
    flex: 1; 
    min-width: 110px; 
    background-color: #e2e8f0; 
    padding: 15px 12px; 
    border-radius: 12px; 
    text-align: center; 
    position: relative; 
  }

  .rank { 
    position: absolute; 
    top: 8px; 
    left: 8px; 
    background-color: #3d5a80; 
    color: white; 
    padding: 0; 
    border-radius: 50%; 
    font-size: 11px; 
    font-weight: 700; 
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .rank.second { background-color: #5a7ba8; }
  .rank.third { background-color: #7593b8; }

  .item-image { 
    width: 70px; 
    height: 70px; 
    background-color: white; 
    border-radius: 10px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    margin: 30px auto 12px; 
    border: 1px solid #e2e8f0;
  }

  .item-image i { 
    font-size: 35px; 
    color: #4a5568; 
  }

  .item-name { 
    font-weight: 700; 
    color: #2c3e50; 
    margin-bottom: 4px; 
    font-size: 14px; 
  }

  .item-count { 
    font-size: 12px; 
    color: #718096; 
    font-style: italic; 
  }

  /* Performance Overview - Reduced height */
  .performance-overview { 
    background-color: #7593b8; 
    padding: 20px; 
    border-radius: 18px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }

  .performance-overview h5 { 
    font-size: 22px; 
    color: white; 
    margin-bottom: 15px; 
    font-weight: 600;
  }

  /* Recent Rental Table - Compact */
  .recent-rental { 
    background-color: white; 
    padding: 20px 25px; 
    border-radius: 18px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
  }

  .recent-rental h5 { 
    font-size: 22px; 
    color: #2c3e50; 
    margin-bottom: 15px; 
    font-weight: 600;
  }

  .table-modern { 
    width: 100%; 
    border-collapse: collapse; 
  }

  .table-modern thead { 
    background-color: #d4dfe9; 
  }

  .table-modern th { 
    padding: 12px 18px; 
    text-align: left; 
    font-weight: 600; 
    color: #2c3e50; 
    font-size: 13px; 
  }

  .table-modern td { 
    padding: 12px 18px; 
    border-bottom: 1px solid #f0f0f0; 
    color: #4a5568;
    font-size: 13px;
    font-weight: 500;
  }

  .table-modern tbody tr:hover { 
    background-color: #f7fafc; 
  }

  .status-badge { 
    padding: 6px 18px; 
    border-radius: 18px; 
    font-size: 12px; 
    font-weight: 600; 
    display: inline-block; 
  }

  .status-booked  { background-color: #7593b8; color: white; }
  .status-done    { background-color: #a8bdd4; color: #2c3e50; }
  .status-verified{ background-color: #16a34a; color: white; }
  .status-pending { background-color: #f59e0b; color: white; }
  .status-failed  { background-color: #ef4444; color: white; }

  /* Cards for other pages */
  .page-card { 
    background-color: white; 
    padding: 22px; 
    border-radius: 18px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
    margin-bottom: 18px; 
  }

  /* Buttons */
  .btn-modern { 
    padding: 9px 18px; 
    border-radius: 10px; 
    font-weight: 600; 
    transition: all 0.3s; 
    border: none; 
    cursor: pointer;
    font-size: 14px;
  }

  .btn-primary-modern { 
    background-color: #6b8cbb; 
    color: white; 
  }

  .btn-primary-modern:hover { 
    background-color: #5a7ba8; 
  }

  .btn-outline-modern { 
    background-color: transparent; 
    border: 2px solid #6b8cbb; 
    color: #6b8cbb; 
  }

  .btn-outline-modern:hover { 
    background-color: #6b8cbb; 
    color: white; 
  }

  /* Responsive */
  @media (max-width: 1200px) {
    .stats-cards { grid-template-columns: repeat(2, 1fr); }
    .bottom-section { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .sidebar { 
      width: 70px; 
      padding: 20px 8px;
      border-radius: 0;
    }
    .logo span, 
    .menu-title, 
    .menu-item span { 
      display: none; 
    }
    .logo {
      justify-content: center;
      padding-left: 0;
    }
    .menu-item {
      justify-content: center;
      padding: 12px;
    }
    .main-content { 
      margin-left: 70px; 
    }
    .stats-cards { 
      grid-template-columns: 1fr; 
    }
    .rented-items { 
      flex-direction: column; 
    }
    header h1 { 
      font-size: 20px; 
    }
    .content-area { 
      padding: 18px; 
    }
  }

  .table-dark { --bs-table-bg: transparent; color: #2c3e50; }
  .card { border: none; }
  .thumb { 
    width: 48px; 
    height: 48px; 
    object-fit: cover; 
    border-radius: 8px; 
    border: 1px solid rgba(0,0,0,.08); 
  }

  .badge-owner { background-color: #dc3545; color: white; }
  .badge-staff { background-color: #6b8cbb; color: white; }
  .badge-customer { background-color: #6c757d; color: white; }

  /* Custom scrollbar */
  .content-area::-webkit-scrollbar {
    width: 6px;
  }

  .content-area::-webkit-scrollbar-track {
    background: transparent;
  }

  .content-area::-webkit-scrollbar-thumb {
    background: #a8bdd4;
    border-radius: 3px;
  }

  .content-area::-webkit-scrollbar-thumb:hover {
    background: #7593b8;
  }

  /* ================== NOTIFICATION PANEL ================== */

  .notification-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.35);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease-out;
    z-index: 1150;
  }
  .notification-backdrop.active {
    opacity: 1;
    pointer-events: auto;
  }

  .notification-wrapper {
    position: fixed;
    top: 80px;
    right: 40px;
    width: 380px;
    max-height: 560px;
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 24px 60px rgba(15,23,42,0.25);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 1200;
  }
  .notification-wrapper.open {
    display: flex;
  }

  .notification-header {
    padding: 16px 18px 10px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .notification-header-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
  }

  .notification-header-subtitle {
    font-size: 11px;
    color: #6b7280;
  }

  .notification-list {
    padding: 8px 0 8px;
    overflow-y: auto;
  }

  .notification-item {
    display: flex;
    gap: 12px;
    padding: 10px 16px;
    align-items: flex-start;
  }

  .notification-item:hover {
    background: #f9fafb;
  }

  .notif-avatar {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    overflow: hidden;
    background: #e0f2fe;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #111827;
    font-weight: 600;
  }

  .notification-body {
    flex: 1;
  }

  .notification-title-line {
    font-size: 13px;
    color: #111827;
    margin-bottom: 2px;
  }
  .notification-title-line strong {
    font-weight: 600;
  }

  .notification-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 4px;
  }

  .notif-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #22c55e;
  }

  .notification-desc {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
  }

  .notification-empty {
    padding: 16px;
    font-size: 12px;
    color: #9ca3af;
  }

  .notification-list::-webkit-scrollbar {
    width: 5px;
  }
  .notification-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 999px;
  }
  .notification-list::-webkit-scrollbar-track {
    background: transparent;
  }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const bell = document.querySelector('.bell-icon');
      const panel = document.querySelector('.notification-wrapper');
      const backdrop = document.querySelector('.notification-backdrop');

      if (!bell || !panel || !backdrop) return;

      function openPanel() {
        panel.classList.add('open');
        backdrop.classList.add('active');
      }

      function closePanel() {
        panel.classList.remove('open');
        backdrop.classList.remove('active');
      }

      function togglePanel() {
        if (panel.classList.contains('open')) {
          closePanel();
        } else {
          openPanel();
        }
      }

      bell.addEventListener('click', function (e) {
        e.stopPropagation();
        togglePanel();
      });

      backdrop.addEventListener('click', closePanel);

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closePanel();
        }
      });

      panel.addEventListener('click', function (e) {
        e.stopPropagation();
      });

      document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && !bell.contains(e.target)) {
          closePanel();
        }
      });
    });
  </script>
</head>
<body>

<!-- backdrop gelap di belakang panel notif -->
<div class="notification-backdrop"></div>

<!-- Panel Notifikasi (order baru dari tabel rentals) -->
<div class="notification-wrapper">
  <div class="notification-header">
    <div>
      <div class="notification-header-title">Notifications</div>
      <div class="notification-header-subtitle">Order rental terbaru</div>
    </div>
  </div>

  <div class="notification-list">
    <?php if (empty($notifications)): ?>
      <div class="notification-empty">
        Belum ada order baru.
      </div>
    <?php else: ?>
      <?php foreach ($notifications as $row): 
        $initial = strtoupper(substr($row['customer_name'], 0, 1));
        $created = $row['created_at'] ? strtotime($row['created_at']) : null;
        $createdLabel = $created ? date('d M Y H:i', $created) : '';
        $status = ucfirst($row['status'] ?? '');
      ?>
        <div class="notification-item">
          <div class="notif-avatar">
            <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
          </div>
          <div class="notification-body">
            <div class="notification-title-line">
              <strong><?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
              membuat order baru
            </div>
            <div class="notification-meta">
              <?php if ($createdLabel): ?>
                <span><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <?php if ($status): ?>
                <span>&bull; <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <span class="notif-dot"></span>
            </div>
            <div class="notification-desc">
              Kamera <strong><?= htmlspecialchars($row['camera_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
              Periode: 
              <?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>
              s/d
              <?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?><br>
              Total: <?= rupiah($row['total_price']) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
