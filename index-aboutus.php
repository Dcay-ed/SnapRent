<?php
require __DIR__ . '/database/db.php';
$title = 'SnapRent - About Us';

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


<!-- ================= Stats Section ================= -->
<section class="stats">
    <div class="container stats-container">
        <div class="stat-item">
            <div class="stat-number">1000+</div>
            <div class="stat-label">Happy Renters</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">100+</div>
            <div class="stat-label">Cameras</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">99%</div>
            <div class="stat-label">Positive Feedback</div>
        </div>
    </div>
</section>

    <!-- ================= Founders Section ================= -->
<section class="founders">
    <div class="container">
        <h2 class="section-title">Our Founders</h2>
        <div class="founders-grid">
            <div class="founder-card">
                <div class="founder-image">👨‍💼</div>
                <h3 class="founder-name">John Anderson</h3>
                <div class="founder-role">CEO</div>
                <p class="founder-bio">Visionary leader with extensive experience in the photography industry.</p>
            </div>
            <div class="founder-card">
                <div class="founder-image">👩‍💻</div>
                <h3 class="founder-name">Sarah Chen</h3>
                <div class="founder-role">CTO</div>
                <p class="founder-bio">Tech expert driving innovation in our rental platform and services.</p>
            </div>
            <div class="founder-card">
                <div class="founder-image">👨‍🎨</div>
                <h3 class="founder-name">Michael Rodriguez</h3>
                <div class="founder-role">CIO</div>
                <p class="founder-bio">Strategic thinker ensuring our information systems support business goals.</p>
            </div>
        </div>
    </div>
</section>

<!-- ================= Mission & Vision ================= -->
<section class="mission-vision">
    <div class="container">
        <div class="mv-grid">
            <div class="mv-card">
                <h3>Our Mission</h3>
                <p>To make professional photography equipment accessible to everyone by providing affordable, reliable, and high-quality camera rentals with exceptional customer service.</p>
            </div>
            <div class="mv-card">
                <h3>Our Vision</h3>
                <p>To become the leading camera rental platform that empowers creatives worldwide to capture their vision without boundaries.</p>
            </div>
        </div>
    </div>
</section>
    <!-- Footer -->
    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script>
        // Simple script for active navigation link
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>