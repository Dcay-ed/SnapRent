<?php
include('database/db.php');
// home.php — SnapRent landing (mockup-matched + autoplay carousel)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SnapRent</title>

<link rel="stylesheet" href="style/home.css" >
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
window.addEventListener('scroll', () => {
  document.body.classList.toggle('scrolled', window.scrollY > 10);
});
</script>

</head>
<body>

<!-- ================= Header ================= -->
<header class="header">
  <div class="container header-inner">
    <div class="brand">
      <img src="SnapRent\style\design\logo snaprent.png" alt="SnapRent logo">
      <div> </div>
    </div>

    <nav class="nav">
      <a class="active" href="#">Home</a>
      <a href="#">Cameras</a>
      <a href="#">About Us</a>
      <a href="#">FAQ</a>
    </nav>

    <div class="actions">
      <div class="icon-btn" title="Account">👤</div>
      <div class="icon-btn" title="Notifications">🔔</div>
      <div class="icon-btn" title="Cart">🛒</div>
    </div>
  </div>
</header>

<!-- ================= Hero ================= -->
<section class="hero-wrap">
  <!-- Background layer with blur -->
  <div class="hero-background"></div>
  
  <!-- Foreground layer without blur -->
  <div class="container">
    <div class="hero-foreground">
      <div class="hero-content">
        <h1>Snaprent</h1>
        <div class="kicker">Rent Your Perfect Camera</div>
        <p class="lead">Affordable, flexible, and ready when you are</p>
        <a class="btn-primary" href="#">Rent now</a>
      </div>

      <div class="thumb-strip">
        <img src="img/rectangle-1386.png" alt="Camera 1">
        <img src="img/rectangle-1387.png" alt="Camera 2">
        <img src="img/rectangle-1388.png" alt="Camera 3">
        <img src="img/rectangle-1386.png" alt="Camera 4">
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
      <div class="card"><div class="ci">⏱️</div><h4>Real-Time Availability</h4><p>Instantly check which cameras are available and reserve them right away</p></div>
      <div class="card"><div class="ci">🪙</div><h4>Affordable Daily Rates</h4><p>Transparent pricing with no hidden costs, rent high-end cameras at budget-friendly prices</p></div>
      <div class="card"><div class="ci">⚡</div><h4>Fast & Easy Booking</h4><p>Simple online reservation system — book your gear in just a few clicks</p></div>
      <div class="card"><div class="ci">🧰</div><h4>Reliable Equipment</h4><p>Every camera is tested and maintained to ensure top performance</p></div>
    </div>

    <div class="why-grid bottom">
      <div class="card"><div class="ci">⭐</div><h4>Trusted by Creators</h4><p>Hundreds of photographers and videographers rely on our service every month</p></div>
      <div class="card"><div class="ci">📷</div><h4>Wide Camera Selection</h4><p>From mirrorless to professional DSLRs, find exactly what you need for your next shoot</p></div>
    </div>
  </div>
</section>

<!-- ================= Featured Equipment ================= -->
<section class="featured">
  <div class="container">
    <h3 class="section-title">FEATURED EQUIPMENT</h3>
    <p class="section-sub">Discover our most rented cameras, perfect for any creative project</p>

    <div class="pills">
      <button class="pill active" type="button">Mirrorless</button>
      <button class="pill" type="button">DSLR</button>
      <button class="pill" type="button">Digicam</button>
      <button class="pill" type="button">Analog</button>
    </div>

    <div class="carousel" id="carousel">
      <button class="chev" id="chevLeft" aria-label="Previous">❮</button>

      <div class="stage" id="stage">
        <!-- Kiri (kecil) -->
        <div class="cam">
          <div class="thumb" aria-hidden="true"></div>
          <div class="meta">
            <div class="title">Nama kamera</div>
            <div class="price">Rp ——— /hari</div>
          </div>
        </div>

        <!-- Tengah (besar) -->
        <div class="cam big">
          <div class="thumb" aria-hidden="true"></div>
          <div class="meta center">
            <div class="title">Nama kamera</div>
            <div class="price">Rp ——— /hari</div>
          </div>
        </div>

        <!-- Kanan (kecil) -->
        <div class="cam">
          <div class="thumb" aria-hidden="true"></div>
          <div class="meta">
            <div class="title">Nama kamera</div>
            <div class="price">Rp ——— /hari</div>
          </div>
        </div>
      </div>

      <button class="chev" id="chevRight" aria-label="Next">❯</button>
    </div>
  </div>
</section>

<!-- ================= Our Brand ================= -->
<section class="brand-band">
  <div class="container">
    <h3>OUR BRAND</h3>
    <div class="brand-row">
      <div class="brand-pill">Canon</div>
      <div class="brand-pill">Sony</div>
      <div class="brand-pill">Nikon</div>
      <div class="brand-pill">Fujifilm</div>
      <div class="brand-pill">Panasonic</div>
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
        <div class="head"><div class="avatar"></div><div><div class="name">Sarah Johnson</div><div class="stars">★★★★★</div></div></div>
        <p>Pelayanan sangat memuaskan! Kamera yang saya sewa dalam kondisi prima dan proses pengembaliannya mudah sekali. Pasti akan sewa lagi di sini.</p>
        <div class="date">15 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head"><div class="avatar"></div><div><div class="name">Budi Santoso</div><div class="stars">★★★★★</div></div></div>
        <p>Harga terjangkau untuk kualitas kamera profesional. Tim support sangat responsif membantu saya yang baru belajar fotografi.</p>
        <div class="date">12 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head"><div class="avatar"></div><div><div class="name">Maya Wijaya</div><div class="stars">★★★★★</div></div></div>
        <p>Perfect untuk project wedding photography! Equipment lengkap dan terawat dengan baik. Highly recommended untuk photographer!</p>
        <div class="date">10 Maret 2025</div>
      </div>
    </div>
  </div>
</section>

<!-- ================= Footer ================= -->
<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div>
        <h5>SnapRent</h5>
        <p>Professional camera rentals for photographers and videographers</p>
      </div>
      <div>
        <h5>Quick Links</h5>
        <div class="links">
          <a href="#">Home</a><a href="#">Cameras</a><a href="#">About</a><a href="#">Contact</a>
        </div>
      </div>
      <div>
        <h5>Categories</h5>
        <div class="links">
          <a href="#">DSLR</a><a href="#">Mirrorless</a><a href="#">Digicam</a><a href="#">Analog</a>
        </div>
      </div>
      <div>
        <h5>Contact</h5>
        <div class="links">
          <a href="#">(555) 123-4567</a><a href="#">hello@snaprent.com</a><a href="#">123 Photo Street, Camera City</a><a href="#">Mon-Fri: 9AM-6PM</a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div>© 2024 SnapRent. All rights reserved.</div>
      <div class="footer-mini">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
        <a href="#">Cookie Policy</a>
      </div>
    </div>
  </div>
</footer>

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
    
    if(Math.abs(diff) > 50) { // Minimum swipe distance
      if(diff > 0) {
        rotate(1); // Swipe left - next
      } else {
        rotate(-1); // Swipe right - previous
      }
    }
  });
})();
</script>
</body>
</html>