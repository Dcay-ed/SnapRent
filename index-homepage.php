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

<link rel="stylesheet" href="style/home-homepage.css" >
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
      <img src="style/design/logo snaprent.png" alt="SnapRent logo">
    </div>

    <nav class="nav">
      <a class="active" href="#">Home</a>
      <a href="camera.php">Cameras</a>
      <a href="#">About Us</a>
      <a href="#">FAQ</a>
    </nav>

    <div class="actions">
      <!-- Account -->
      <button class="icon-btn" title="Account" aria-label="Account">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.76-3.58-5-8-5Z"/>
        </svg>
      </button>
      <!-- Notifications -->
      <button class="icon-btn" title="Notifications" aria-label="Notifications">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm7-6V11a7 7 0 1 0-14 0v5L3 18v2h18v-2Z"/>
        </svg>
      </button>
      <!-- Cart -->
      <button class="icon-btn" title="Cart" aria-label="Cart">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="currentColor" d="M7 18a2 2 0 1 0 2 2 2 2 0 0 0-2-2Zm10 0a2 2 0 1 0 2 2 2 2 0 0 0-2-2ZM7.2 14h9.86a1 1 0 0 0 .97-.76l1.73-6.9H6.42L6 4H3v2h2l2.2 8Z"/>
        </svg>
      </button>
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

    <div class="pills">
      <button class="pill active" type="button">Mirrorless</button>
      <button class="pill" type="button">DSLR</button>
      <button class="pill" type="button">Digicam</button>
      <button class="pill" type="button">Analog</button>
    </div>

    <div class="carousel" id="carousel">
      <button class="chev" id="chevLeft" aria-label="Previous">
        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m15 18-6-6 6-6v12Z"/></svg>
      </button>

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
          <a href="#">Home</a><a href="camera.php">Cameras</a><a href="#">About</a><a href="#">Contact</a>
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
          <a href="#">
            <!-- WhatsApp -->
            <svg class="icon icon-16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 3.9A10 10 0 0 0 3.6 17.8L3 21l3.3-.6A10 10 0 1 0 20 3.9Zm-8 2a7.9 7.9 0 0 1 7.9 8 7.9 7.9 0 0 1-11.4 7l-.6.1.1-.6A7.9 7.9 0 0 1 12 5.9Zm-3.1 2.9c-.2 0-.5.1-.6.3-.4.4-1 1.1-1 2.1 0 1 .7 2 1 2.4.2.3 1.9 3.1 4.6 4.1.6.2 1 .3 1.4.4.6.2 1.3.1 1.8-.4.3-.3.7-.8.8-1.1.1-.3.1-.6 0-.7s-.3-.2-.6-.3l-1.8-.8c-.3-.1-.5 0-.6.1l-.5.6c-.1.1-.2.1-.3.1-.1 0-.3-.1-.5-.2a7.7 7.7 0 0 1-2.4-1.9c-.6-.7-.8-1.2-.9-1.4-.1-.1 0-.2.1-.3l.4-.4c.2-.2.2-.4.2-.6l-.1-.7c0-.3-.1-.5-.3-.6-.2-.1-.4-.1-.6-.1Z"/></svg>
            <span>(555) 123-4567</span>
          </a>
          <a href="#">
            <!-- Email -->
            <svg class="icon icon-16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v1l10 6 10-6V6a2 2 0 0 0-2-2Zm0 5.2-8 4.8L4 9.2V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2Z"/></svg>
            <span>hello@snaprent.com</span>
          </a>
          <a href="#">
            <!-- Location -->
            <svg class="icon icon-16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 14.5 9 2.5 2.5 0 0 1 12 11.5Z"/></svg>
            <span>123 Photo Street, Camera City</span>
          </a>
          <a href="#">
            <!-- Instagram (rounded square + lens) -->
            <svg class="icon icon-16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 6a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm6.5-2.5a1.5 1.5 0 1 0 1.5 1.5 1.5 1.5 0 0 0-1.5-1.5Z"/></svg>
            <span>Mon-Fri: 9AM-6PM</span>
          </a>
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
    
    if(Math.abs(diff) > 50) {
      if(diff > 0) rotate(1);      // Swipe kiri -> next
      else        rotate(-1);      // Swipe kanan -> prev
    }
  });
})();
</script>
</body>
</html>
