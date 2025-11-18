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

<section class="about">
        <div class="container about-content">
            <div class="about-text">
                <h2>About SnapRent</h2>
                <p>At SnapRent, we believe every moment deserves to be captured in the best quality possible. Since our founding in 2020, we've been committed to making professional-grade cameras accessible to everyone—from beginners to seasoned photographers.</p>
                <p>Our mission is to simplify the rental experience with an easy booking process, transparent pricing, and reliable customer support. Whether you're filming a short movie, traveling abroad, or shooting for your brand, we've got the perfect gear for you.</p>
                <a href="#" class="btn">Learn More</a>
            </div>
            <div class="about-image">
                <img src="../auth/images/WhatsApp Image 2025-11-18 at 10.07.41.jpeg" alt="About SnapRent">
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
        <h2 class="section-title">Our Mission & Vision</h2>
        <div class="mv-grid">
            <div class="mv-card">
                <div class="mv-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3>Our Mission</h3>
                <p>To make professional photography equipment accessible to everyone by providing affordable, reliable, and high-quality camera rentals with exceptional customer service.</p>
            </div>
            <div class="mv-card">
                <div class="mv-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </div>
                <h3>Our Vision</h3>
                <p>To become the leading camera rental platform that empowers creatives worldwide to capture their vision without boundaries.</p>
            </div>
        </div>
    </div>
</section>
<style>
  
/* ================= Mission & Vision Styles ================= */
.mission-vision {
    padding: 80px 0;
    background-color: #f8f9fa;
}

.mission-vision .section-title {
    text-align: center;
    margin-bottom: 60px;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
}

.mv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
    max-width: 1000px;
    margin: 0 auto;
}

.mv-card {
    background: white;
    border-radius: 16px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.mv-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #293743, #202327ff);
}

.mv-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.mv-icon {
    margin-bottom: 20px;
    color: #293743;
}

.mv-card h3 {
    font-size: 1.75rem;
    margin-bottom: 20px;
    color: #333;
    font-weight: 600;
}

.mv-card p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #666;
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mission-vision {
        padding: 60px 0;
    }
    
    .mission-vision .section-title {
        font-size: 2rem;
        margin-bottom: 40px;
    }
    
    .mv-grid {
        gap: 30px;
    }
    
    .mv-card {
        padding: 30px 20px;
    }
    
    .mv-card h3 {
        font-size: 1.5rem;
    }
    
    .mv-card p {
        font-size: 1rem;
    }
    
}
</style>
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
            
            // Add smooth scrolling for Learn More button
            const learnMoreBtn = document.querySelector('.btn-learn-more');
            if (learnMoreBtn) {
                learnMoreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const missionSection = document.querySelector('.mission-vision');
                    if (missionSection) {
                        missionSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>