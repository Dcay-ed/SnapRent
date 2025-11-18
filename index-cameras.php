<?php
require __DIR__ . '/database/db.php';
$title = 'SnapRent - Cameras';

session_start();
$isLoggedIn = isset($_SESSION['uid']); 

if ($isLoggedIn) {
    require __DIR__ . '/partials/header.php'; 
} else {
    require __DIR__ . '/partials/home-header.php'; 
}

// ================== AMBIL DATA KAMERA + GAMBAR ==================
try {
    $stmt = $pdo->query("
        SELECT 
            cam.id,
            cam.name,
            cam.brand,
            cam.type,
            cam.daily_price,
            cam.status,
            (
                SELECT ci.filename
                FROM camera_images ci
                WHERE ci.camera_id = cam.id
                ORDER BY ci.id ASC
                LIMIT 1
            ) AS image
        FROM cameras cam
        WHERE cam.status = 'available'
        ORDER BY cam.created_at DESC
    ");
    $cameras = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cameras = [];
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
        <?php
        // Ambil ID kamera pertama dari hasil query jika tersedia
        $first_camera_id = !empty($cameras) ? (int)$cameras[0]['id'] : null;
        // Tentukan URL tujuan tombol "Rent now" di hero
        $hero_rent_url = $first_camera_id ? 'details.php?id=' . $first_camera_id : (isset($_SESSION['uid']) ? 'Customer/index.php' : 'auth/login.php');
        ?>
        <a class="btn-primary rent-btn" href="<?php echo $hero_rent_url; ?>">
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

<!-- =============== CSS khusus untuk FEATURED EQUIPMENT (boleh dipindah ke file .css) =============== -->
<style>
  .featured-equipment{
    background:#d2deea;
    padding:80px 0 96px;
  }

  .featured-equipment h2{
    text-align:center;
    font-size:24px;
    letter-spacing:0.14em;
    font-weight:700;
    color:#293743;
    margin:0 0 32px;
  }

  .category-filter{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:32px;
  }

  .category-buttons{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
  }

  /* MODIFIKASI: Gaya Category Button sesuai gambar */
  .category-btn{
    border-radius:20px;
    padding:6px 16px;
    border:1px solid #c4cedd;
    background:#ffffff;
    font-size:13px;
    font-weight:500;
    color:#293743;
    cursor:pointer;
    transition:all .2s ease;
  }

  .category-btn.active{
    background:#293743;
    color:#ffffff;
    border-color: #293743;
  }

  /* MODIFIKASI: Search Box sesuai gambar */
  .search-box{
    display:flex;
    align-items:center;
    gap:8px;
    padding:6px 14px;
    background:#ffffff;
    border:1px solid #c4cedd;
    border-radius:20px;
    min-width: 200px;
  }

  .search-box input{
    border:none;
    outline:none;
    background:transparent;
    font-size:13px;
    flex:1;
  }

  .search-box button{
    border:none;
    background:transparent;
    padding:0;
    cursor:pointer;
    color: #4b5b6a;
  }

  .search-box button svg {
    width: 16px;
    height: 16px;
  }

  .product-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:24px; /* Mengurangi jarak antar card */
  }

  /* MODIFIKASI: Product Card sesuai gambar */
  .product-card{
    text-align:center;
    background: #ffffff;
    border-radius:16px;
    padding:16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Bayangan lebih halus */
    transition: transform 0.15s ease;
  }

  .product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
  }

  .product-image{
    width:100%;
    aspect-ratio:1/1;
    border-radius:16px;
    overflow:hidden;
    background:#e4e8f0;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .product-image img{
    width:100%;
    height:100%;
    object-fit:cover;
  }

  .product-info{
    margin-top:12px;
  }

  .product-name{
    font-size:14px;
    font-weight:600;
    color:#293743;
    margin:0 0 4px;
  }

  .product-price{
    font-size:12px;
    color:#4b5b6a;
    margin:0 0 12px;
    font-style:italic;
  }

  /* MODIFIKASI: Tombol Rent Now sesuai gambar */
  .btn-rent{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:4px 16px;
    border-radius:20px;
    background:#1f2b38;
    color:#ffffff;
    font-size:11px;
    font-weight:500;
    text-decoration:none;
    transition: all 0.15s ease;
  }

  .btn-rent:hover{
    background:#18212c;
  }

  @media (max-width:1024px){
    .product-grid{
      grid-template-columns:repeat(3,minmax(0,1fr));
    }
  }
  @media (max-width:768px){
    .product-grid{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .search-box input{
      min-width:140px;
    }
  }
  @media (max-width:540px){
    .product-grid{
      grid-template-columns:1fr;
    }
  }
</style>

<!-- ================= Featured Equipment Section ================= -->
<section class="featured-equipment" id="featured">
    <div class="container">
        <h2>FEATURED EQUIPMENT</h2>

        <!-- Category Filter -->
        <div class="category-filter">
            <div class="category-buttons">
                <button class="category-btn active" data-category="mirrorless">Mirrorless</button>
                <button class="category-btn" data-category="dslr">DSLR</button>
                <button class="category-btn" data-category="digicam">Digicam</button>
                <button class="category-btn" data-category="analog">Analog</button>
            </div>
            <!-- MODIFIKASI: Search Box dengan ikon di dalam input -->
            <div class="search-box">
                <input type="text" id="productSearch" placeholder="Search">
                <button type="button">
                    <!-- Menggunakan SVG inline untuk ikon kaca pembesar -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
            <?php if (empty($cameras)): ?>
                <p>Belum ada produk yang diupload.</p>
            <?php else: ?>
                <?php foreach ($cameras as $cam): 
                    $imgUrl = !empty($cam['image'])
                        ? 'Dashboard/uploads/cameras/' . $cam['id'] . '/' . $cam['image']
                        : 'img/placeholder-camera.jpg';

                    $searchText = strtolower($cam['name'] . ' ' . $cam['brand']);
                    $typeLower  = strtolower($cam['type']); // Analog/Digicam/DSLR/Mirrorless
                ?>
                    <div class="product-card"
                         data-category="<?= htmlspecialchars($typeLower); ?>"
                         data-search="<?= htmlspecialchars($searchText); ?>">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($imgUrl); ?>" alt="<?= htmlspecialchars($cam['name']); ?>">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($cam['name']); ?></h3>
                            <p class="product-price">
                                Rp <?= number_format((float)$cam['daily_price'], 0, ',', '.'); ?>/hari
                            </p>
                            <a href="details.php?id=<?= (int)$cam['id']; ?>" class="btn-rent">Rent now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
// JavaScript untuk nav animation (sama seperti di index.php) + filter & search
document.addEventListener('DOMContentLoaded', function() {
    // ===== NAV INDICATOR =====
    const nav = document.getElementById('mainNav');
    const indicator = document.getElementById('navIndicator');
    if (nav && indicator) {
        const navLinks = nav.querySelectorAll('a');
        
        function updateIndicator(activeLink) {
            const linkRect = activeLink.getBoundingClientRect();
            const navRect = nav.getBoundingClientRect();
            
            indicator.style.width = `${linkRect.width}px`;
            indicator.style.transform = `translateX(${linkRect.left - navRect.left}px)`;
        }
        
        const activeLink = nav.querySelector('a.active');
        if (activeLink) {
            updateIndicator(activeLink);
        }
        
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                updateIndicator(this);
            });
        });
        
        window.addEventListener('resize', function() {
            const activeLink = nav.querySelector('a.active');
            if (activeLink) {
                updateIndicator(activeLink);
            }
        });
    }

    // ===== FEATURED EQUIPMENT: CATEGORY FILTER + SEARCH =====
    const categoryButtons = document.querySelectorAll('.category-btn');
    const searchInput     = document.getElementById('productSearch');
    const cards           = document.querySelectorAll('.product-card');

    let activeCategory = 'all'; // awal: semua

    function applyFilter() {
        const term = (searchInput?.value || '').toLowerCase().trim();

        cards.forEach(card => {
            const cardCat   = (card.dataset.category || '').toLowerCase();
            const cardText  = (card.dataset.search || '').toLowerCase();

            const matchCategory = (activeCategory === 'all') || (cardCat === activeCategory);
            const matchSearch   = (term === '') || cardText.includes(term);

            card.style.display = (matchCategory && matchSearch) ? '' : 'none';
        });
    }

    categoryButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            categoryButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeCategory = (btn.dataset.category || 'all').toLowerCase();
            applyFilter();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            applyFilter();
        });
    }
});
</script>
</body>
</html>