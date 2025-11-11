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
?>

<section class="hero-wrap">

  <div class="hero-background" style="background-image: url('images/BGCamera.jpg');"></div>
  
  <div class="container">
    <div class="hero-foreground" style="background-image: url('images/BGCamera.jpg');">
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
          <img src="images/BGCamera.jpg" alt="Camera 1">
        </div>
        <div class="thumb-item">
          <img src="images/BGCamera.jpg" alt="Camera 2">
        </div>
        <div class="thumb-item">
          <img src="images/BGCamera.jpg" alt="Camera 3">
        </div>
        <div class="thumb-item">
          <img src="images/BGCamera.jpg" alt="Camera 4">
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ================= Featured Equipment Section ================= -->
<section class="featured-equipment" id="featured">
    <div class="container">
        <h2>FEATURED EQUIPMENT</h2>

        <!-- Category Filter -->
        <div class="category-filter">
            <button class="category-btn active">Mirrorless</button>
            <button class="category-btn">DSLR</button>
            <button class="category-btn">Digicam</button>
            <button class="category-btn">Analog</button>
            <div class="search-box">
                <input type="text" placeholder="Search">
                <button><img src="img/search-icon.png" alt="Search"></button>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
            <?php
            // Your product data here
            $products = [
                ['name' => 'Sony A7 III', 'price' => 'Rp 150.000/hari'],
                ['name' => 'Canon EOS R5', 'price' => 'Rp 200.000/hari'],
                // ... tambahkan produk lainnya
            ];

            foreach ($products as $product) {
                echo '
                <div class="product-card">
                    <div class="product-image">
                        <img src="img/placeholder-camera.jpg" alt="' . htmlspecialchars($product['name']) . '">
                    </div>
                    <div class="product-info">
                        <h3>' . htmlspecialchars($product['name']) . '</h3>
                        <p class="price">' . htmlspecialchars($product['price']) . '</p>
                        <a href="#" class="btn-rent">Rent now</a>
                    </div>
                </div>
                ';
            }
            ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
// JavaScript untuk nav animation (sama seperti di index.php)
document.addEventListener('DOMContentLoaded', function() {
    const nav = document.getElementById('mainNav');
    const indicator = document.getElementById('navIndicator');
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
        link.addEventListener('click', function(e) {
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
});
</script>
</body>
</html>