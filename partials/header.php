  <?php
  // partials/header.php
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  if (!isset($title)) { $title = 'SnapRent'; }

  // Get current page for active nav indicator
  $current_page = basename($_SERVER['PHP_SELF']);

  // Check if user is logged in
  $isLoggedIn = isset($_SESSION['uid']);
  $userRole  = $_SESSION['role'] ?? '';
  $username  = $_SESSION['uname'] ?? '';
  $userId    = $_SESSION['uid'] ?? null;

  /* ================== KONEKSI DB ================== */
  $pdoAvailable = false;
  if (isset($pdo) && $pdo instanceof PDO) {
    $pdoAvailable = true;
  } else {
    // header.php ada di /partials -> db di /database/db.php
    $dbPath = __DIR__ . '/../database/db.php';
    if (is_file($dbPath)) {
      require_once $dbPath;
      if (isset($pdo) && $pdo instanceof PDO) {
        $pdoAvailable = true;
      }
    }
  }

  /* ================== NOTIFIKASI DARI rentals ==================
    ambil dari table rentals, field status
    hanya tampil jika rentals.status = 'confirmed'
  =============================================================== */
  $notifOrders    = [];
  $notifDeadlines = [];
  $notifCount     = 0;

  // helper format rupiah
  if (!function_exists('sr_rupiah')) {
    function sr_rupiah($number) {
      return 'Rp ' . number_format((float)$number, 0, ',', '.');
    }
  }

  if ($isLoggedIn && $pdoAvailable && $userId) {
    // base filter: customer ini + status confirmed
    $sqlBase = "
      FROM rentals rn
      JOIN cameras cam ON cam.id = rn.camera_id
      WHERE rn.customer_id = :uid
        AND rn.status = 'confirmed'
    ";

    // Notif order berhasil
    $stmt = $pdo->prepare("
      SELECT rn.id,
            rn.total_price,
            rn.start_date,
            rn.end_date,
            cam.name AS camera_name
      $sqlBase
      ORDER BY rn.created_at DESC
      LIMIT 5
    ");
    $stmt->execute([':uid' => $userId]);
    $notifOrders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Notif deadline pengembalian: end_date <= hari ini
    $today = date('Y-m-d');
    $stmt2 = $pdo->prepare("
      SELECT rn.id,
            rn.total_price,
            rn.start_date,
            rn.end_date,
            cam.name AS camera_name
      $sqlBase
        AND rn.end_date <= :today
      ORDER BY rn.end_date ASC
      LIMIT 5
    ");
    $stmt2->execute([
      ':uid'   => $userId,
      ':today' => $today,
    ]);
    $notifDeadlines = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $notifCount = count($notifOrders) + count($notifDeadlines);
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars($title); ?></title>

  <link rel="stylesheet" href="style/home.css" >
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  </head>

  <body>
  <!-- ================= Header ================= -->
  <header class="header">
    <div class="container header-inner">
      <div class="brand">
        <img src="../auth/images/logo.png" alt="SnapRent Logo">
        <div></div><!-- spacer untuk scaling logo -->
      </div>

      <!-- NAV + ACTIONS dengan layout yang tepat -->
      <div class="mid">
        <nav class="nav" id="mainNav">
          <div class="nav-indicator" id="navIndicator">
            <div class="indicator-glow"></div>
            <div class="indicator-pulse"></div>
          </div>
          <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" data-page="index.php">
            <span class="nav-text">Home</span>
            <div class="nav-underline"></div>
          </a>
          <a href="index-cameras.php" class="nav-link <?php echo $current_page == 'index-cameras.php' ? 'active' : ''; ?>" data-page="index-cameras.php">
            <span class="nav-text">Cameras</span>
            <div class="nav-underline"></div>
          </a>
          <a href="index-aboutus.php" class="nav-link <?php echo $current_page == 'index-aboutus.php' ? 'active' : ''; ?>" data-page="index-aboutus.php">
            <span class="nav-text">About Us</span>
            <div class="nav-underline"></div>
          </a>
          <a href="index-faq.php" class="nav-link <?php echo $current_page == 'index-faq.php' ? 'active' : ''; ?>" data-page="index-faq.php">
            <span class="nav-text">FAQ</span>
            <div class="nav-underline"></div>
          </a>
        </nav>

        <div class="actions">
          <?php if($isLoggedIn): ?>
            <div class="user-menu">
              <span class="user-welcome">Welcome, <?php echo htmlspecialchars($username); ?></span>
              
              <!-- Notification & Cart Buttons -->
              <button class="icon-btn js-notif-toggle" title="Notifications" aria-label="Notifications">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                  <path fill="currentColor" d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm7-6V11a7 7 0 1 0-14 0v5L3 18v2h18v-2Z"/>
                </svg>
                <?php if($notifCount > 0): ?>
                  <span class="notif-dot"></span>
                <?php endif; ?>
              </button>       
              <a href="customer/booking.php" 
                class="icon-btn" 
                title="Cart" 
                aria-label="Cart"
                style="display:flex; align-items:center; justify-content:center;">
                
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true" 
                    style="width:22px; height:22px;">
                  <path fill="currentColor" 
                    d="M7 18a2 2 0 1 0 2 2 2 2 0 0 0-2-2Zm10 0a2 2 0 1 0 2 2 2 2 0 0 0-2-2ZM7.2 14h9.86a1 1 0 0 0 .97-.76l1.73-6.9H6.42L6 4H3v2h2l2.2 8Z"/>
                </svg>

                <span class="cart-count">0</span>
              </a>
              <!-- Logout Button -->
              <a href="customer/index.php" class="icon-btn logout-btn" title="Customer Dashboard">
                <img src="../style/Group.png" alt="logout icon">
              </a>
            </div>
          <?php else: ?>
            <!-- Notification & Cart Buttons for non-logged in users -->
            <button class="icon-btn js-notif-toggle" title="Notifications" aria-label="Notifications">
              <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm7-6V11a7 7 0 1 0-14 0v5L3 18v2h18v-2Z"/>
              </svg>
            </button>
            
            <button class="icon-btn" title="Cart" aria-label="Cart">
              <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="currentColor" d="M7 18a2 2 0 1 0 2 2 2 2 0 0 0-2-2Zm10 0a2 2 0 1 0 2 2 2 2 0 0 0-2-2ZM7.2 14h9.86a1 1 0 0 0 .97-.76l1.73-6.9H6.42L6 4H3v2h2l2.2 8Z"/>
              </svg>
              <span class="cart-count">0</span>
            </button>

            <!-- Login/Signup Buttons -->
            <a href="auth/login.php" class="btn btn-ghost">Login</a>
            <a href="auth/register.php" class="btn btn-light">Sign Up</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- ================= NOTIFICATION PANEL ================= -->
  <div class="notif-panel" id="notifPanel" aria-hidden="true">
    <div class="notif-card">
      <div class="notif-header">
        <span class="notif-title">Notifications</span>
        <?php if ($notifCount > 0): ?>
          <span class="notif-pill"><?php echo $notifCount; ?></span>
        <?php endif; ?>
      </div>

      <div class="notif-list">
        <?php if ($notifCount === 0): ?>
          <div class="notif-empty">
            <p>No notifications yet</p>
            <span>We’ll let you know when your rental is confirmed or near return date.</span>
          </div>
        <?php else: ?>

          <?php foreach ($notifOrders as $n): ?>
            <?php
              $start = date('d M Y', strtotime($n['start_date']));
              $end   = date('d M Y', strtotime($n['end_date']));
            ?>
            <div class="notif-item">
              <div class="notif-avatar notif-avatar-order">SR</div>
              <div class="notif-body">
                <div class="notif-row">
                  <span class="notif-name">Order berhasil</span>
                  <span class="notif-time"><?php echo htmlspecialchars($start); ?></span>
                </div>
                <div class="notif-main">
                  <?php echo htmlspecialchars($n['camera_name']); ?>
                </div>
                <div class="notif-sub">
                  Item Details: <?php echo htmlspecialchars($n['camera_name']); ?><br>
                  Rental period: <?php echo $start . ' – ' . $end; ?><br>
                  Total: <strong><?php echo sr_rupiah($n['total_price']); ?></strong>
                </div>
              </div>
              <span class="notif-status-dot"></span>
            </div>
          <?php endforeach; ?>

          <?php foreach ($notifDeadlines as $n): ?>
            <?php $end = date('d M Y', strtotime($n['end_date'])); ?>
            <div class="notif-item">
              <div class="notif-avatar notif-avatar-deadline">!</div>
              <div class="notif-body">
                <div class="notif-row">
                  <span class="notif-name">Return deadline</span>
                  <span class="notif-time"><?php echo htmlspecialchars($end); ?></span>
                </div>
                <div class="notif-main">
                  Time to return <span class="notif-link"><?php echo htmlspecialchars($n['camera_name']); ?></span>
                </div>
                <div class="notif-sub">
                  Please return your camera today to avoid extra charges.
                </div>
              </div>
              <span class="notif-status-dot notif-status-dot-deadline"></span>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const nav = document.getElementById('mainNav');
    const indicator = document.getElementById('navIndicator');
    const navLinks = nav.querySelectorAll('.nav-link');
    
    function initializeIndicator() {
      const activeLink = nav.querySelector('.nav-link.active');
      if (activeLink) {
        updateIndicator(activeLink, true);
      } else if (navLinks[0]) {
        updateIndicator(navLinks[0], true);
        navLinks[0].classList.add('active');
      }
      indicator.classList.add('active');
    }
    
    function updateIndicator(activeLink, immediate = false) {
      const linkRect = activeLink.getBoundingClientRect();
      const navRect = nav.getBoundingClientRect();
      const targetWidth = linkRect.width;
      const targetPosition = linkRect.left - navRect.left;
      
      if (immediate) {
        indicator.style.width = `${targetWidth}px`;
        indicator.style.transform = `translateX(${targetPosition}px)`;
      } else {
        indicator.style.transition = 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        indicator.style.width = `${targetWidth}px`;
        indicator.style.transform = `translateX(${targetPosition}px)`;
        indicator.style.animation = 'none';
        setTimeout(() => {
          indicator.style.animation = 'bounceIndicator 0.3s ease';
        }, 10);
      }
    }
    
    navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        navLinks.forEach(l => {
          l.classList.remove('active');
          l.style.animation = '';
        });
        this.classList.add('active');
        this.style.animation = 'clickBounce 0.4s ease';
        updateIndicator(this);
        setTimeout(() => {
          window.location.href = this.getAttribute('href');
        }, 400);
      });
      
      link.addEventListener('mouseenter', function() {
        if (!this.classList.contains('active')) {
          this.style.transform = 'translateY(-2px) scale(1.05)';
        }
      });
      
      link.addEventListener('mouseleave', function() {
        if (!this.classList.contains('active')) {
          this.style.transform = 'translateY(0) scale(1)';
        }
      });
    });
    
    // ===== NOTIFICATION PANEL =====
    const notifPanel   = document.getElementById('notifPanel');
    const notifToggles = document.querySelectorAll('.js-notif-toggle');

    function closeNotif() {
      notifPanel.classList.remove('open');
      notifPanel.setAttribute('aria-hidden', 'true');
    }

    notifToggles.forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifPanel.classList.toggle('open');
        const isOpen = notifPanel.classList.contains('open');
        notifPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      });
    });

    document.addEventListener('click', function(e) {
      if (!notifPanel.contains(e.target)) {
        closeNotif();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeNotif();
      }
    });

    const style = document.createElement('style');
    style.textContent = `
      @keyframes bounceIndicator {
        0% { transform: translateX(var(--start-pos)) scaleX(1); }
        50% { transform: translateX(calc(var(--start-pos) + 5px)) scaleX(0.95); }
        100% { transform: translateX(var(--target-pos)) scaleX(1); }
      }
      @keyframes clickBounce {
        0% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-4px) scale(1.05); }
        100% { transform: translateY(-2px) scale(1); }
      }

      .notif-dot {
        position: absolute;
        top: 7px;
        right: 7px;
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #10B981;
        box-shadow: 0 0 0 3px rgba(16,185,129,.25);
      }
      .notif-panel {
        position: fixed;
        top: 76px;
        right: 32px;
        z-index: 100;
        pointer-events: none;
        opacity: 0;
        transform: translateY(-8px);
        transition: all .2s ease;
      }
      .notif-panel.open {
        pointer-events: auto;
        opacity: 1;
        transform: translateY(0);
      }
      .notif-card {
        width: 360px;
        max-width: calc(100vw - 32px);
        background: #ffffff;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15,23,42,.18);
        padding: 16px 0 8px;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(20px);
      }
      .notif-header {
        padding: 0 20px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(148,163,184,.35);
      }
      .notif-title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
      }
      .notif-pill {
        min-width: 26px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        background: #eef2ff;
        color: #4f46e5;
      }
      .notif-list {
        max-height: 420px;
        overflow-y: auto;
        padding: 6px 8px 8px;
      }
      .notif-empty { padding: 16px 20px 18px; }
      .notif-empty p {
        margin: 0 0 4px;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
      }
      .notif-empty span {
        font-size: 12px;
        color: #6b7280;
      }
      .notif-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 16px;
        transition: background .18s ease, transform .18s ease, box-shadow .18s ease;
      }
      .notif-item:hover {
        background: #f9fafb;
        transform: translateY(-1px);
        box-shadow: 0 12px 30px rgba(15,23,42,.06);
      }
      .notif-avatar {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        background-color: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        color: #111827;
      }
      .notif-avatar-order {
        background: linear-gradient(135deg,#4f46e5,#6366f1);
        color:#f9fafb;
      }
      .notif-avatar-deadline {
        background: linear-gradient(135deg,#f97316,#ea580c);
        color:#fff7ed;
      }
      .notif-body {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
      }
      .notif-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px;
      }
      .notif-name {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .notif-time {
        font-size: 12px;
        color: #9ca3af;
        flex-shrink: 0;
      }
      .notif-main { font-size: 13px; color: #111827; }
      .notif-sub  { font-size: 12px; color: #6b7280; margin-top: 2px; }
      .notif-link { font-weight: 600; color: #111827; }
      .notif-status-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #22c55e;
        align-self: flex-start;
        margin-top: 6px;
        box-shadow: 0 0 0 3px rgba(34,197,94,.25);
      }
      .notif-status-dot-deadline {
        background: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,.25);
      }
      @media (max-width: 640px) {
        .notif-panel {
          right: 16px;
          left: 16px;
        }
        .notif-card { width: 100%; }
      }
    `;
    document.head.appendChild(style);

    initializeIndicator();
  });

  // Scroll effect
  let scrollTimeout;
  window.addEventListener('scroll', function () {
    if (!scrollTimeout) {
      scrollTimeout = setTimeout(function() {
        if (window.scrollY > 100) {
          document.body.classList.add('scrolled');
        } else {
          document.body.classList.remove('scrolled');
        }
        scrollTimeout = null;
      }, 10);
    }
  });
  </script>
