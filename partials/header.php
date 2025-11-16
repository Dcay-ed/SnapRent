<?php
// partials/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($title)) { $title = 'SnapRent'; }

// Get current page for active nav indicator
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['uid']);
$userRole = $_SESSION['role'] ?? '';
$username = $_SESSION['uname'] ?? '';
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
            <button class="icon-btn" title="Notifications" aria-label="Notifications">
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

            <!-- Logout Button -->
          <a href="customer/index.php" class="icon-btn logout-btn" title="Customer Dashboard">
            <img src="../style/Group.png" alt="logout icon">
          </a>
          </div>
        <?php else: ?>
          <!-- Notification & Cart Buttons for non-logged in users -->
          <button class="icon-btn" title="Notifications" aria-label="Notifications">
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

<script>
// Enhanced Navigation with Advanced Animations
document.addEventListener('DOMContentLoaded', function() {
  const nav = document.getElementById('mainNav');
  const indicator = document.getElementById('navIndicator');
  const navLinks = nav.querySelectorAll('.nav-link');
  
  // Initialize indicator position
  function initializeIndicator() {
    const activeLink = nav.querySelector('.nav-link.active');
    if (activeLink) {
      updateIndicator(activeLink, true);
    } else {
      updateIndicator(navLinks[0], true);
      navLinks[0].classList.add('active');
    }
    indicator.classList.add('active');
  }
  
  // Update indicator position with advanced animation
  function updateIndicator(activeLink, immediate = false) {
    const linkRect = activeLink.getBoundingClientRect();
    const navRect = nav.getBoundingClientRect();
    
    const targetWidth = linkRect.width;
    const targetPosition = linkRect.left - navRect.left;
    
    if (immediate) {
      indicator.style.width = `${targetWidth}px`;
      indicator.style.transform = `translateX(${targetPosition}px)`;
    } else {
      // Advanced animation with multiple properties
      indicator.style.transition = 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
      indicator.style.width = `${targetWidth}px`;
      indicator.style.transform = `translateX(${targetPosition}px)`;
      
      // Add bounce effect
      indicator.style.animation = 'none';
      setTimeout(() => {
        indicator.style.animation = 'bounceIndicator 0.3s ease';
      }, 10);
    }
  }
  
  // Add click handlers with enhanced animations
  navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Remove active class from all links
      navLinks.forEach(l => {
        l.classList.remove('active');
        l.style.animation = '';
      });
      
      // Add active class to clicked link with animation
      this.classList.add('active');
      this.style.animation = 'clickBounce 0.4s ease';
      
      // Update indicator with smooth animation
      updateIndicator(this);
      
      // Navigate after animation
      setTimeout(() => {
        window.location.href = this.getAttribute('href');
      }, 400);
    });
    
    // Hover effects
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
  
  // Update on resize
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      const activeLink = nav.querySelector('.nav-link.active');
      if (activeLink) {
        updateIndicator(activeLink, true);
      }
    }, 100);
  });
  
  // Add CSS animations
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
    
    @keyframes iconSpin {
      0% { transform: rotate(0deg) scale(1); }
      50% { transform: rotate(10deg) scale(1.2); }
      100% { transform: rotate(0deg) scale(1.1); }
    }

    /* Enhanced Actions Styling */
    .actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-menu {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-welcome {
      font-size: 14px;
      color: #2d3748;
      font-weight: 500;
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: #e53e3e;
      color: white;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .btn-logout:hover {
      background: #c53030;
      transform: translateY(-1px);
    }

    .btn-ghost, .btn-light {
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn-ghost {
      background: transparent;
      color: #4a5568;
      border: 1px solid #cbd5e0;
    }

    .btn-ghost:hover {
      background: #f7fafc;
      border-color: #a0aec0;
    }

    .btn-light {
      background: #4299e1;
      color: white;
      border: 1px solid #4299e1;
    }

    .btn-light:hover {
      background: #3182ce;
      border-color: #3182ce;
    }
  `;
  document.head.appendChild(style);
  
  // Initialize
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