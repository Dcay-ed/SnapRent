<?php
require __DIR__ . '/database/db.php';
$title = 'SnapRent';

session_start();
$isLoggedIn = isset($_SESSION['uid']);

/* ===================== TOP RENTED CAMERAS (FEATURED EQUIPMENT) ===================== */
$topCameras = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // Ambil 3 kamera dengan jumlah rentals terbanyak
        $sql = "
            SELECT 
                c.*,
                COUNT(r.id) AS total_rentals
            FROM cameras c
            LEFT JOIN rentals r ON r.camera_id = c.id
            GROUP BY c.id
            ORDER BY total_rentals DESC
            LIMIT 3
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $cameraId = (int)$row['id'];

            // ===== Ambil 1 gambar utama dari camera_images =====
            $imageUrl = null;
            $stmtImg = $pdo->prepare("
                SELECT * 
                FROM camera_images 
                WHERE camera_id = :cid 
                ORDER BY id ASC 
                LIMIT 1
            ");
            if ($stmtImg->execute([':cid' => $cameraId])) {
                $imgRow = $stmtImg->fetch(PDO::FETCH_ASSOC);
                if ($imgRow) {
                    // Sesuaikan nama kolom file gambar di sini
                    $fileName = $imgRow['file_name']
                        ?? ($imgRow['filename']
                        ?? ($imgRow['image_path'] ?? null));

                    if ($fileName) {
                        // Sesuaikan base path uploads jika perlu
                        $baseUpload = 'Dashboard/uploads/cameras';
                        $imageUrl = $baseUpload . '/' . $cameraId . '/' . rawurlencode($fileName);
                    }
                }
            }

            // Fallback jika camera_images belum ada untuk kamera ini
            if (!$imageUrl) {
                // Pakai placeholder default
                $imageUrl = 'auth/images/Camera.jpg';
            }

            // ===== Mapping nama kamera (sesuaikan dengan struktur tabel cameras) =====
            $name = $row['camera_name']
                ?? ($row['name']
                ?? ($row['title'] ?? ('Camera #' . $cameraId)));

            // ===== Mapping harga sewa per hari =====
            $price = $row['daily_price']
                ?? ($row['price_per_day']
                ?? ($row['price'] ?? null));

            $priceLabel = $price !== null
                ? 'Rp ' . number_format((float)$price, 0, ',', '.') . ' /hari'
                : 'Rp ——— /hari';

            $topCameras[] = [
                'id'            => $cameraId,
                'name'          => $name,
                'price_label'   => $priceLabel,
                'image_url'     => $imageUrl,
                'total_rentals' => (int)($row['total_rentals'] ?? 0),
            ];
        }
    } catch (Throwable $e) {
        $topCameras = [];
        // debug optional: error_log('Top cameras error: '.$e->getMessage());
    }
}

if ($isLoggedIn) {
    require __DIR__ . '/partials/header.php';
} else {
    require __DIR__ . '/partials/home-header.php';
}
?>

<section class="hero-wrap">

  <div class="hero-background" style="background-image: url('auth/images/BGCamera.jpg');"></div>
  
  <div class="container">
    <div class="hero-foreground" style="background-image: url('auth/images/BGCamera.jpg');">
      <div class="hero-content">
        <h1>SnapRent</h1>
        <div class="kicker">Rent Your Perfect Camera</div>
        <p class="lead">Affordable, flexible, and ready when you are</p>
        <a class="btn-primary rent-btn" href="<?php echo isset($_SESSION['uid']) ? 'Customer/index.php' : 'auth/login.php'; ?>">
          <span class="btn-text">Rent now</span>
          <span class="btn-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path d="M5 12h14m-7-7l7 7-7 7"/>
            </svg>
          </span>
        </a>
      </div>

      <div class="thumb-strip">
        <div class="thumb-item">
          <img src="auth/images/Camera.jpg" alt="Camera 1">
        </div>
        <div class="thumb-item">
          <img src="auth/images/dark-photography.jpg" alt="Camera 2">
        </div>
        <div class="thumb-item">
          <img src="auth/images/stefzn-wS3lR30P8qA-unsplash.jpg" alt="Camera 3">
        </div>
        <div class="thumb-item">
          <img src="auth/images/thumb-1920-478024.jpg" alt="Camera 4">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= Why Choose Us ================= -->
<section class="why">
  <div class="container">
    <h2>WHY CHOOSE US</h2>
    <p class="sub">Experience camera rental made simple, fast, and reliable</p>

    <div class="why-grid">
      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- clock -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2Zm1 11h-4V7h2v4h2Z"/></svg>
        </div>
        <h4>Real-Time Availability</h4>
        <p>Instantly check which cameras are available and reserve them right away</p>
      </div>

      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- currency-dollar -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="M13 3h-2v2.06A5 5 0 0 0 7 10c0 2.76 2.24 4 5 4s3 .9 3 2-1 2-3 2-3-.9-3-2H7c0 2.22 1.64 3.67 4 3.93V21h2v-1.07C15.36 19.67 17 18.22 17 16c0-2.76-2.24-4-5-4s-3-.9-3-2 1-2 3-2 3 .9 3 2h2c0-2.22-1.64-3.67-4-3.93Z"/></svg>
        </div>
        <h4>Affordable Daily Rates</h4>
        <p>Transparent pricing with no hidden costs, rent high-end cameras at budget-friendly prices</p>
      </div>

      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- bolt -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="M13 2 4 14h6v8l9-12h-6Z"/></svg>
        </div>
        <h4>Fast & Easy Booking</h4>
        <p>Simple online reservation system — book your gear in just a few clicks</p>
      </div>

      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- wrench-screwdriver -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="M7 2 5 4l3 3-2 2L3 6 1 8l5 5 4-4 2 2-4 4 5 5 2-2-3-3 2-2 3 3 2-2-3-3 3-3-2-2-3 3-2-2 3-3-2-2-3 3L7 2Z"/></svg>
        </div>
        <h4>Reliable Equipment</h4>
        <p>Every camera is tested and maintained to ensure top performance</p>
      </div>
    </div>

    <div class="why-grid bottom">
      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- star -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z"/></svg>
        </div>
        <h4>Trusted by Creators</h4>
        <p>Hundreds of photographers and videographers rely on our service every month</p>
      </div>
      <div class="card">
        <div class="ci" aria-hidden="true">
          <!-- camera -->
          <svg class="icon icon-28" viewBox="0 0 24 24"><path fill="currentColor" d="M9 3 7.5 5H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.5L15 3H9Zm3 14a5 5 0 1 1 5-5 5.006 5.006 0 0 1-5 5Zm0-2a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z"/></svg>
        </div>
        <h4>Wide Camera Selection</h4>
        <p>From mirrorless to professional DSLRs, find exactly what you need for your next shoot</p>
      </div>
    </div>
  </div>
</section>

<!-- ================= Featured Equipment ================= -->
<section class="featured">
  <div class="container">
    <h3 class="section-title">FEATURED EQUIPMENT</h3>
    <p class="section-sub">Discover our most rented cameras, perfect for any creative project</p>

    <!-- Pills Mirrorless / DSLR / Digicam / Analog dihapus sesuai permintaan -->

    <div class="carousel" id="carousel">
      <button class="chev" id="chevLeft" aria-label="Previous">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m15 18-6-6 6-6v12Z"/></svg>
      </button>

      <div class="stage" id="stage">
        <?php
          // Render 3 slot: kiri, tengah (big), kanan
          $slots = 3;
          for ($i = 0; $i < $slots; $i++):
            $camData = $topCameras[$i] ?? null;
            $isBig   = ($i === 1);
            $title   = $camData['name'] ?? 'Nama kamera';
            $price   = $camData['price_label'] ?? 'Rp ——— /hari';
            $img     = $camData['image_url'] ?? null;
        ?>
          <div class="cam<?php echo $isBig ? ' big' : ''; ?>">
            <div 
              class="thumb" 
              aria-hidden="true"
              <?php if ($img): ?>
                style="background-image: url('<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>');"
              <?php endif; ?>
            ></div>
            <div class="meta<?php echo $isBig ? ' center' : ''; ?>">
              <div class="title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="price"><?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <button class="chev" id="chevRight" aria-label="Next">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m9 6 6 6-6 6V6Z"/></svg>
      </button>
    </div>
  </div>
</section>

<!-- ================= Our Brand ================= -->
<section class="brand-band">
  <div class="container">
    <h3>OUR BRAND</h3>
    <div class="brand-row">

      <div class="brand-pill" data-brand="canon">
        <span>Canon</span>
      </div>

      <div class="brand-pill" data-brand="sony">
        <span>Sony</span>
      </div>

      <div class="brand-pill" data-brand="nikon">
        <span>Nikon</span>
      </div>

      <div class="brand-pill" data-brand="fujifilm">
        <span>Fujifilm</span>
      </div>

      <div class="brand-pill" data-brand="panasonic">
        <span>Panasonic</span>
      </div>

    </div>
  </div>
</section>

<!-- ================= Testimonials ================= -->
<section class="testi">
  <div class="container">
    <h3>WHAT OUR CUSTOMERS SAY</h3>
    <p class="sub">Real feedback from our valued clients</p>

    <div class="testi-row">
      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div>
            <div class="name">Sarah Johnson</div>
            <div class="stars" role="img" aria-label="5 out of 5">
              <!-- 5 solid stars -->
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
            </div>
          </div>
        </div>
        <p>Pelayanan sangat memuaskan! Kamera yang saya sewa dalam kondisi prima dan proses pengembaliannya mudah sekali. Pasti akan sewa lagi di sini.</p>
        <div class="date">15 Maret 2025</div>
      </div>

      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div>
            <div class="name">Budi Santoso</div>
            <div class="stars" role="img" aria-label="5 out of 5">
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
            </div>
          </div>
        </div>
        <p>Harga terjangkau untuk kualitas kamera profesional. Tim support sangat responsif membantu saya yang baru belajar fotografi.</p>
        <div class="date">12 Maret 2025</div>
      </div>

      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div>
            <div class="name">Maya Wijaya</div>
            <div class="stars" role="img" aria-label="5 out of 5">
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
              <svg viewBox="0 0 24 24"><path d="m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1 3-6Z" fill="currentColor"/></svg>
            </div>
          </div>
        </div>
        <p>Perfect untuk project wedding photography! Equipment lengkap dan terawat dengan baik. Highly recommended untuk photographer!</p>
        <div class="date">10 Maret 2025</div>
      </div>
    </div>
  </div>
</section>

<!-- ================= Footer ================= -->
<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
// ===== Featured carousel: autoplay + manual controls =====
(function(){
  const stage = document.getElementById('stage');
  const left = document.getElementById('chevLeft');
  const right = document.getElementById('chevRight');
  const carousel = document.getElementById('carousel');

  function applySizes(){
    // Ensure middle card is always 'big'
    Array.from(stage.children).forEach(el => el.classList.remove('big'));
    if(stage.children[1]) stage.children[1].classList.add('big');
  }

  function rotate(dir = 1){
    // dir=1: to right; dir=-1: to left
    if(dir > 0){
      stage.appendChild(stage.firstElementChild);
    }else{
      stage.insertBefore(stage.lastElementChild, stage.firstElementChild);
    }
    applySizes();
  }

  // Manual buttons
  left.addEventListener('click', () => rotate(-1));
  right.addEventListener('click', () => rotate(1));

  // Autoplay (pause on hover)
  let timer = null;
  const INTERVAL = 4000;

  function start(){
    if(timer) return;
    timer = setInterval(() => rotate(1), INTERVAL);
  }
  function stop(){
    if(!timer) return;
    clearInterval(timer); timer = null;
  }

  // Enhanced Brand Animation
  function initBrandAnimation() {
    const brandPills = document.querySelectorAll('.brand-pill');
    
    brandPills.forEach((pill) => {
      pill.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.05)';
        this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.3)';
      });
      
      pill.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = '0 10px 24px rgba(0,0,0,.28)';
      });
    });
  }

  // Rent Button Animation
  function initRentButton() {
    const rentBtn = document.querySelector('.rent-btn');
    
    rentBtn.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-3px)';
      this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.4)';
    });
    
    rentBtn.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '0 10px 20px rgba(0,0,0,.28)';
    });
    
    rentBtn.addEventListener('click', function(e) {
      // Add ripple effect
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.7);
        transform: scale(0);
        animation: ripple 0.6s linear;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
      `;
      
      this.appendChild(ripple);
      
      setTimeout(() => {
        ripple.remove();
      }, 600);
    });
  }

  // Initialize when page loads
  document.addEventListener('DOMContentLoaded', function() {
    initBrandAnimation();
    initRentButton();
  });

  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  // Initialize
  applySizes();
  start();

  // Accessibility: keyboard support for arrows
  [left, right].forEach(btn=>{
    btn.tabIndex = 0;
    btn.addEventListener('keydown', e=>{
      if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); btn.click(); }
    });
  });

  // Touch/swipe support for mobile
  let startX = 0;
  stage.addEventListener('touchstart', e => {
    startX = e.touches[0].clientX;
  });

  stage.addEventListener('touchend', e => {
    const endX = e.changedTouches[0].clientX;
    const diff = startX - endX;
    
    if(Math.abs(diff) > 50) {
      if(diff > 0) rotate(1);      // Swipe kiri -> next
      else        rotate(-1);      // Swipe kanan -> prev
    }
  });
})();
</script>

<style>
  /* Ripple animation for rent button */
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }

  /* Override thumbnail agar gambar rapi dan muncul di semua kartu */
  .featured .cam .thumb {
    width: 100%;
    height: 260px;              /* sesuaikan kalau perlu */
    border-radius: 24px;
    background-color: #f4f6f9;
    background-position: center center;
    background-repeat: no-repeat;
    background-size: contain;   /* pakai contain supaya kamera tidak kepotong aneh */
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
  }
</style>
</body>
</html>
