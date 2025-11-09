<?php
// home.php — SnapRent landing (mockup-matched + autoplay carousel)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SnapRent</title>

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= Design Tokens ================= */
:root{
  --bg:#ffffff;
  --slate:#293743;
  --ink:#0f172a;
  --soft:#e6edf3;
  --card:#ffffff;
  --shadow-lg: 0 18px 48px rgba(0,0,0,.22);
  --shadow: 0 12px 28px rgba(0,0,0,.16);
  --shadow-sm: 0 8px 18px rgba(0,0,0,.12);
}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
a{text-decoration:none;color:inherit}
.container{width:min(1180px,92%);margin-inline:auto}

/* ================= Header ================= */
.header{position:sticky;top:0;z-index:60;backdrop-filter:saturate(140%) blur(10px)}
.header-inner{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
.brand{display:flex;align-items:center;gap:10px}
.brand img{width:38px;height:38px;border-radius:50%;object-fit:cover;box-shadow:0 4px 10px rgba(0,0,0,.25)}
.brand .title{font-weight:700;font-size:16px;letter-spacing:.2px;color:#e2e8f0;background:#1f2937;padding:6px 12px;border-radius:12px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.06)}
.nav{display:flex;align-items:center;gap:6px;background:rgba(17,24,39,.25);padding:6px;border-radius:999px;box-shadow:0 6px 18px rgba(0,0,0,.18)}
.nav a{padding:8px 14px;border-radius:999px;font-size:13px;color:#f8fafc;opacity:.9}
.nav a.active{background:#ffffff;color:var(--slate);box-shadow:0 6px 18px rgba(0,0,0,.18)}
.actions{display:flex;align-items:center;gap:10px}
.icon-btn{width:34px;height:34px;border-radius:12px;display:grid;place-items:center;background:#11182733;color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,.07)}

/* ================= Hero ================= */
.hero-wrap{position:relative;padding:26px 0 18px}
.hero-vignette{position:absolute;inset:0;pointer-events:none;background:
  radial-gradient(1100px 160px at 50% -4%, rgba(0,0,0,.35), transparent 70%),
  radial-gradient(1200px 200px at 50% 100%, rgba(0,0,0,.2), transparent 70%);
filter:blur(18px);opacity:.9;transform:translateY(-16px)}
.hero{position:relative;overflow:hidden;border-radius:28px;min-height:420px;background:url('img/rectangle-1460.png') center/cover no-repeat;box-shadow:var(--shadow-lg)}
.hero::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,.55) 0%, rgba(0,0,0,.38) 40%, rgba(0,0,0,.16) 100%)}
.hero-content{position:relative;padding:54px;color:#fff;max-width:720px}
.hero h1{margin:0 0 8px;font-size:68px;line-height:1;font-weight:800;letter-spacing:.2px}
.hero .kicker{margin:0 0 2px;font-size:22px;font-weight:600;opacity:.95}
.hero .lead{margin:0 0 18px;font-size:16px;opacity:.9}
.btn-primary{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 20px;background:#1f2937;color:#fff;border-radius:999px;font-weight:600;box-shadow:0 10px 20px rgba(0,0,0,.28)}
.btn-primary:active{transform:translateY(1px)}
.thumb-strip{position:absolute;left:54px;bottom:24px;display:flex;gap:12px;z-index:2}
.thumb-strip img{width:86px;height:86px;border-radius:16px;object-fit:cover;background:#fff;box-shadow:0 10px 20px rgba(0,0,0,.25);outline:2px solid rgba(255,255,255,.6)}

/* ================= Why ================= */
.why{background:var(--slate);padding:60px 0 66px}
.why h2{margin:0;text-align:center;color:#fff;letter-spacing:.6px}
.why .sub{margin:8px auto 28px;text-align:center;color:#cbd5e1;font-size:14px}
.why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
.card{background:#fff;border-radius:20px;padding:22px;box-shadow:var(--shadow-sm);color:var(--slate)}
.card .ci{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:#f1f5f9;margin-bottom:10px;font-size:20px}
.card h4{margin:6px 0 6px;font-size:16px}
.card p{margin:0;color:#475569;font-size:12.6px;line-height:1.55}
.why-grid.bottom{grid-template-columns:repeat(2,1fr);margin-top:22px}

/* ================= Featured ================= */
.featured{background:var(--soft);padding:50px 0 70px}
.section-title{text-align:center;color:var(--slate);margin:0;font-weight:800;letter-spacing:.4px}
.section-sub{margin-top:8px;text-align:center;color:#3c566d;font-size:14px}
.pills{display:flex;gap:10px;justify-content:center;margin:22px 0 28px}
.pill{padding:8px 16px;border-radius:999px;background:#fff;border:1px solid #cbd5e1;color:#334155;font-size:12px;cursor:pointer}
.pill.active{background:#293743;color:#fff;border-color:#293743;box-shadow:0 6px 14px rgba(0,0,0,.12)}

.carousel{display:grid;grid-template-columns:64px 1fr 64px;align-items:center;gap:14px}
.chev{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:#fff;box-shadow:var(--shadow-sm);cursor:pointer;user-select:none}
.stage{display:grid;grid-template-columns:1fr 1.35fr 1fr;gap:24px;align-items:end}
.item{background:#fff;border-radius:20px;box-shadow:var(--shadow-sm);padding:22px;height:220px;display:flex;align-items:end;justify-content:center;opacity:.95;
  transition:height .4s ease, transform .4s ease, box-shadow .4s ease, opacity .4s ease}
.item.big{height:330px;box-shadow:var(--shadow);opacity:1;transform:translateY(-2px)}
.item .label{text-align:center;color:#334155}
.item .price{margin-top:4px;font-size:12px;font-style:italic;color:#64748b}

/* ================= Brands ================= */
.brand-band{background:#293743;padding:40px 0}
.brand-band h3{text-align:center;color:#fff;margin:0 0 16px}
.brand-row{display:flex;gap:24px;justify-content:center;flex-wrap:wrap}
.brand-pill{background:#0b1218;border-radius:16px;padding:22px 34px;min-width:120px;color:#fff;font-weight:800;display:grid;place-items:center;box-shadow:var(--shadow-sm)}

/* ================= Testimonials ================= */
.testi{padding:56px 0 26px}
.testi h3{text-align:center;color:#1f2b36;margin:0}
.testi .sub{text-align:center;color:#586b7e;margin:6px 0 24px}
.testi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.review{background:#fff;border-radius:18px;box-shadow:var(--shadow-sm);padding:18px 18px}
.review .head{display:flex;gap:12px;align-items:center;margin-bottom:8px}
.avatar{width:38px;height:38px;border-radius:999px;background:#e2e8f0}
.name{font-weight:600;color:#284466}
.stars{font-size:12px;color:#f59e0b}
.review p{margin:6px 0 6px;color:#4a5a68;font-size:13px;line-height:1.55;text-align:justify}
.date{font-size:12px;color:#6b7b8c}

/* ================= Footer ================= */
.footer{background:#293743;color:#fff;margin-top:26px;padding:28px 0 0}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1.2fr;gap:28px;padding-bottom:18px}
.footer h5{margin:0 0 10px;font-size:16px}
.footer p,.footer a{color:#e5e7eb;font-size:14px}
.links a{display:block;margin:6px 0}
.footer-bottom{border-top:1px solid #d9d9d978;display:flex;align-items:center;justify-content:space-between;padding:12px 0;margin-top:8px}
.footer-mini a{margin-left:18px;color:#e5e7eb;font-size:14px}

/* ================= Responsive ================= */
@media (max-width: 1020px){
  .why-grid{grid-template-columns:repeat(2,1fr)}
  .why-grid.bottom{grid-template-columns:1fr}
  .carousel{grid-template-columns:1fr}
  .stage{grid-template-columns:1fr;gap:16px}
  .item{height:220px}
  .item.big{height:260px}
  .testi-row{grid-template-columns:1fr}
  .footer-top{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- ================= Header ================= -->
<header class="header">
  <div class="container header-inner">
    <div class="brand">
      <img src="img/48bcabbb-9ac2-4333-b37c-76ddcd64064b-1.png" alt="SnapRent logo">
      <div class="title">SnapRent</div>
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
  <div class="hero-vignette"></div>
  <div class="container">
    <div class="hero">
      <div class="hero-content">
        <h1>Snaprent</h1>
        <div class="kicker">Rent Your Perfect Camera</div>
        <p class="lead">Affordable, flexible, and ready when you are</p>
        <a class="btn-primary" href="#">Rent now</a>
      </div>

      <div class="thumb-strip">
        <img src="img/rectangle-1386.png" alt="">
        <img src="img/rectangle-1387.png" alt="">
        <img src="img/rectangle-1388.png" alt="">
        <img src="img/rectangle-1386.png" alt="">
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
      <div class="chev" id="chevLeft">❮</div>

      <div class="stage" id="stage">
        <div class="item">
          <div class="label">
            <div>Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>

        <div class="item big">
          <div class="label">
            <div>Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>

        <div class="item">
          <div class="label">
            <div>Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>
      </div>

      <div class="chev" id="chevRight">❯</div>
    </div>
  </div>
</section>

<!-- ================= Our Brand ================= -->
<section class="brand-band">
  <div class="container">
    <h3>OUR BRAND</h3>
    <div class="brand-row">
      <div class="brand-pill">Canon</div>
      <div class="brand-pill">Canon</div>
      <div class="brand-pill">Canon</div>
      <div class="brand-pill">Canon</div>
      <div class="brand-pill">Canon</div>
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
        <div class="head"><div class="avatar"></div><div><div class="name">Name</div><div class="stars">★★★★★</div></div></div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head"><div class="avatar"></div><div><div class="name">Name</div><div class="stars">★★★★★</div></div></div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head"><div class="avatar"></div><div><div class="name">Name</div><div class="stars">★★★★★</div></div></div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
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
      <div>© 2024 CameraRent. All rights reserved.</div>
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
    // pastikan kartu tengah selalu yang 'big'
    Array.from(stage.children).forEach(el => el.classList.remove('big'));
    if(stage.children[1]) stage.children[1].classList.add('big');
  }

  function rotate(dir = 1){
    // dir=1: ke kanan; dir=-1: ke kiri
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
})();
</script>
</body>
</html>
