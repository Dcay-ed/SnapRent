<?php
// home.php — SnapRent Landing (gradient header #293743, sections #CFD9E1, minimalist icons, autoplay carousel)
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
:root{
  --bg:#ffffff;
  --ink:#0f172a;
  --slate:#293743;
  --slate-800:#1f2b36;
  --muted:#5b6c7c;
  --soft:#CFD9E1; /* permintaan: #CFD9E1 */
  --card:#ffffff;
  --shadow-lg: 0 18px 48px rgba(0,0,0,.22);
  --shadow:    0 12px 28px rgba(0,0,0,.16);
  --shadow-sm: 0  8px 18px rgba(0,0,0,.12);
}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font-family:"Poppins",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
a{text-decoration:none;color:inherit}
.container{width:min(1180px,92%);margin-inline:auto}

/* =============== Header (linear gradient #293743) =============== */
.header{
  position:sticky;top:0;z-index:60;
  background:linear-gradient(180deg, #293743 0%, rgba(41,55,67,0.85) 60%, rgba(41,55,67,0) 100%);
  -webkit-backdrop-filter:saturate(140%) blur(4px);
  backdrop-filter:saturate(140%) blur(4px);
}
.header-inner{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
.brand{display:flex;align-items:center;gap:10px}
.brand img{width:38px;height:38px;border-radius:50%;object-fit:cover;box-shadow:0 4px 10px rgba(0,0,0,.25)}
.brand .title{font-weight:700;font-size:16px;letter-spacing:.2px;color:#e2e8f0;background:#1f2937;padding:6px 12px;border-radius:12px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.06)}
.nav{display:flex;align-items:center;gap:6px;background:rgba(17,24,39,.25);padding:6px;border-radius:999px;box-shadow:0 6px 18px rgba(0,0,0,.18)}
.nav a{padding:8px 14px;border-radius:999px;font-size:13px;color:#f8fafc;opacity:.9}
.nav a.active{background:#ffffff;color:var(--slate);box-shadow:0 6px 18px rgba(0,0,0,.18)}
.actions{display:flex;align-items:center;gap:10px}
.icon-btn{
  width:34px;height:34px;border-radius:12px;display:grid;place-items:center;
  background:#11182733;color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,.07)
}
.icon-btn svg{width:18px;height:18px;stroke:#fff}

/* =============== Hero =============== */
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

/* =============== Why Choose Us (minimal icons) =============== */
.why{background:var(--slate);padding:60px 0 66px}
.why h2{margin:0;text-align:center;color:#fff;letter-spacing:.6px}
.why .sub{margin:8px auto 28px;text-align:center;color:#cbd5e1;font-size:14px}
.why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px}
.card{background:#fff;border-radius:20px;padding:22px;box-shadow:var(--shadow-sm);color:var(--slate)}
.card .ci{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:#f1f5f9;margin-bottom:10px}
.card .ci svg{width:22px;height:22px;stroke:#1f2b36}
.card h4{margin:6px 0 6px;font-size:16px}
.card p{margin:0;color:#475569;font-size:12.6px;line-height:1.55}
.why-grid.bottom{grid-template-columns:repeat(2,1fr);margin-top:22px}

/* =============== Featured Equipment (Carousel) — bg #CFD9E1 =============== */
.featured{background:var(--soft);padding:50px 0 70px}
.section-title{text-align:center;color:var(--slate);margin:0;font-weight:800;letter-spacing:.4px}
.section-sub{margin-top:8px;text-align:center;color:#3c566d;font-size:14px}
.pills{display:flex;gap:10px;justify-content:center;margin:22px 0 28px}
.pill{padding:8px 16px;border-radius:999px;background:#fff;border:1px solid #cbd5e1;color:#334155;font-size:12px;cursor:pointer}
.pill.active{background:#293743;color:#fff;border-color:#293743;box-shadow:0 6px 14px rgba(0,0,0,.12)}
.carousel{display:grid;grid-template-columns:64px 1fr 64px;align-items:center;gap:14px}
.chev{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:#fff;box-shadow:var(--shadow-sm);cursor:pointer;user-select:none}
.chev svg{width:18px;height:18px;stroke:#1f2b36}
.stage{display:grid;grid-template-columns:1fr 1.35fr 1fr;gap:24px;align-items:end}
.item{background:#fff;border-radius:20px;box-shadow:var(--shadow-sm);padding:22px;height:220px;display:flex;align-items:center;justify-content:center;position:relative;transition:.35s ease}
.item.big{height:330px;transform:translateY(-6px)}
.item .label{text-align:center;color:#334155;position:absolute;left:0;right:0;bottom:16px}
.item .price{margin-top:4px;font-size:12px;font-style:italic;color:#64748b}

/* =============== Our Brand (disesuaikan mockup, pakai images-1..5) =============== */
.brand-band{background:#293743;padding:38px 0}
.brand-band h3{text-align:center;color:#fff;margin:0 0 16px}
.brand-row{display:flex;gap:20px;justify-content:center;flex-wrap:wrap}
.brand-item{
  background:#0b1218;border-radius:16px;padding:14px 24px;min-width:140px;height:72px;
  display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-sm)
}
.brand-item img{max-height:42px;max-width:140px;object-fit:contain;filter:none}

/* =============== Testimonials — bg #CFD9E1 =============== */
.testi{background:var(--soft);padding:56px 0 26px}
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

/* =============== Footer =============== */
.footer{background:#293743;color:#fff;margin-top:0;padding:28px 0 0}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1.2fr;gap:28px;padding-bottom:18px}
.footer h5{margin:0 0 10px;font-size:16px}
.footer p,.footer a{color:#e5e7eb;font-size:14px}
.links a{display:block;margin:6px 0}
.footer-bottom{border-top:1px solid #d9d9d978;display:flex;align-items:center;justify-content:space-between;padding:12px 0;margin-top:8px}
.footer-mini a{margin-left:18px;color:#e5e7eb;font-size:14px}

/* =============== Responsive =============== */
@media (max-width:1020px){
  .why-grid{grid-template-columns:repeat(2,1fr)}
  .why-grid.bottom{grid-template-columns:1fr}
  .carousel{grid-template-columns:1fr}
  .stage{grid-template-columns:1fr;gap:16px}
  .item{height:220px}
  .item.big{height:260px;transform:none}
  .testi-row{grid-template-columns:1fr}
  .footer-top{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- =============== Header =============== -->
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
      <button class="icon-btn" title="Account" aria-label="Account">
        <!-- user -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
        </svg>
      </button>
      <button class="icon-btn" title="Notifications" aria-label="Notifications">
        <!-- bell -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 17h12"/><path d="M7 17V10a5 5 0 0 1 10 0v7"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
        </svg>
      </button>
      <button class="icon-btn" title="Cart" aria-label="Cart">
        <!-- cart -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/>
          <path d="M3 4h2l2 12h10l2-8H7"/><path d="M5 6h14"/>
        </svg>
      </button>
    </div>
  </div>
</header>

<!-- =============== Hero =============== -->
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

<!-- =============== Why Choose Us (ikon minimalis) =============== -->
<section class="why">
  <div class="container">
    <h2>WHY CHOOSE US</h2>
    <p class="sub">Experience camera rental made simple, fast, and reliable</p>

    <div class="why-grid">
      <div class="card">
        <div class="ci">
          <!-- clock -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
          </svg>
        </div>
        <h4>Real-Time Availability</h4>
        <p>Instantly check which cameras are available and reserve them right away</p>
      </div>

      <div class="card">
        <div class="ci">
          <!-- tag (price) -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 13.5 12 21 3 12 10.5 4.5 20 13.5Z"/><circle cx="8.5" cy="8.5" r="1.5"/>
          </svg>
        </div>
        <h4>Affordable Daily Rates</h4>
        <p>Transparent pricing with no hidden costs, rent high-end cameras at budget-friendly prices</p>
      </div>

      <div class="card">
        <div class="ci">
          <!-- bolt -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2 3 14h7l-1 8 11-12h-7l1-8z"/>
          </svg>
        </div>
        <h4>Fast & Easy Booking</h4>
        <p>Simple online reservation system — book your gear in just a few clicks</p>
      </div>

      <div class="card">
        <div class="ci">
          <!-- shield-check -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l7 3v6c0 4.97-3.05 7.94-7 9-3.95-1.06-7-4.03-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>
          </svg>
        </div>
        <h4>Reliable Equipment</h4>
        <p>Every camera is tested and maintained to ensure top performance</p>
      </div>
    </div>

    <div class="why-grid bottom">
      <div class="card">
        <div class="ci">
          <!-- star -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l3 6 7 .9-5 4.8 1.4 7.3L12 18l-6.4 4.9L7 14.7 2 9.9 9 9z"/>
          </svg>
        </div>
        <h4>Trusted by Creators</h4>
        <p>Hundreds of photographers and videographers rely on our service every month</p>
      </div>

      <div class="card">
        <div class="ci">
          <!-- camera -->
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 8h3l2-2h6l2 2h3v10H4z"/><circle cx="12" cy="13" r="4"/>
          </svg>
        </div>
        <h4>Wide Camera Selection</h4>
        <p>From mirrorless to professional DSLRs, find exactly what you need for your next shoot</p>
      </div>
    </div>
  </div>
</section>

<!-- =============== Featured Equipment (Autoplay Carousel) =============== -->
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
        <!-- chevron-left -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>

      <div class="stage">
        <div class="item" id="itemLeft">
          <div class="label">
            <div class="name">Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>

        <div class="item big" id="itemCenter">
          <div class="label">
            <div class="name">Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>

        <div class="item" id="itemRight">
          <div class="label">
            <div class="name">Nama kamera</div>
            <div class="price">Rp ------/hari</div>
          </div>
        </div>
      </div>

      <button class="chev" id="chevRight" aria-label="Next">
        <!-- chevron-right -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 6l6 6-6 6"/>
        </svg>
      </button>
    </div>
  </div>
</section>

<!-- =============== Our Brand (pakai gambar brand kamu) =============== -->
<section class="brand-band">
  <div class="container">
    <h3>OUR BRAND</h3>
    <div class="brand-row">
      <div class="brand-item"><img src="img/images-1.png" alt="Brand 1"></div>
      <div class="brand-item"><img src="img/images-2.png" alt="Brand 2"></div>
      <div class="brand-item"><img src="img/images-3.png" alt="Brand 3"></div>
      <div class="brand-item"><img src="img/images-4.png" alt="Brand 4"></div>
      <div class="brand-item"><img src="img/images-5.png" alt="Brand 5"></div>
    </div>
  </div>
</section>

<!-- =============== Testimonials (bg #CFD9E1) =============== -->
<section class="testi">
  <div class="container">
    <h3>WHAT OUR CUSTOMERS SAY</h3>
    <p class="sub">Real feedback from our valued clients</p>

    <div class="testi-row">
      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div><div class="name">Name</div><div class="stars">★★★★★</div></div>
        </div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div><div class="name">Name</div><div class="stars">★★★★★</div></div>
        </div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
      </div>
      <div class="review">
        <div class="head">
          <div class="avatar"></div>
          <div><div class="name">Name</div><div class="stars">★★★★★</div></div>
        </div>
        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry’s standard dummy text ever since the 1500s…</p>
        <div class="date">15 Maret 2025</div>
      </div>
    </div>
  </div>
</section>

<!-- =============== Footer =============== -->
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
        <a href="#">Privacy Policy</a><a href="#">Terms of Service</a><a href="#">Cookie Policy</a>
      </div>
    </div>
  </div>
</footer>

<script>
/* ===== Autoplay Carousel (vanilla) ===== */
(function(){
  const data = [
    {name:'Nama kamera', price:'Rp ------/hari'},
    {name:'Nama kamera', price:'Rp ------/hari'},
    {name:'Nama kamera', price:'Rp ------/hari'},
    {name:'Nama kamera', price:'Rp ------/hari'},
    {name:'Nama kamera', price:'Rp ------/hari'},
  ];
  let idx = 0, n = data.length;

  const itemLeft   = document.getElementById('itemLeft');
  const itemCenter = document.getElementById('itemCenter');
  const itemRight  = document.getElementById('itemRight');
  const chevL      = document.getElementById('chevLeft');
  const chevR      = document.getElementById('chevRight');
  const stageWrap  = document.getElementById('carousel');

  function fill(card, obj){
    card.querySelector('.name').textContent  = obj.name;
    card.querySelector('.price').textContent = obj.price;
  }
  function render(){
    fill(itemLeft,   data[(idx - 1 + n) % n]);
    fill(itemCenter, data[(idx + 0) % n]);
    fill(itemRight,  data[(idx + 1) % n]);

    itemLeft.classList.remove('big');
    itemCenter.classList.add('big');
    itemRight.classList.remove('big');
  }
  function next(){ idx = (idx + 1) % n; render(); }
  function prev(){ idx = (idx - 1 + n) % n; render(); }

  let timer = null;
  const INTERVAL = 3000;
  function play(){ stop(); timer = setInterval(next, INTERVAL); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  chevR.addEventListener('click', next);
  chevL.addEventListener('click', prev);
  stageWrap.addEventListener('mouseenter', stop);
  stageWrap.addEventListener('mouseleave', play);
  document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); else play(); });

  render(); play();
})();
</script>
</body>
</html>
