<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo" 
       style="display:flex; align-items:center; justify-content:left; gap:6px; margin-top:-40px; margin-bottom:30px;">
    <img src="includes/logo/logo snaprent.png" 
         alt="SnapRent Logo"
         style="width:150px; height:150px; object-fit:contain; border-radius:6px;">
  </div>

  <nav class="menu">
    <p class="menu-title">Menu</p>
    <a href="?page=dashboard" class="menu-item <?= $page==='dashboard'?'active':'' ?>">
      <i class="fas fa-th-large"></i>
      <span>Dashboard</span>
    </a>
    <a href="?page=products" class="menu-item <?= $page==='products'?'active':'' ?>">
      <i class="fas fa-box"></i>
      <span>Product</span>
    </a>
    <a href="?page=orders" class="menu-item <?= $page==='orders'?'active':'' ?>">
      <i class="fas fa-camera"></i>
      <span>Rentals</span>
    </a>
    <a href="?page=reports" class="menu-item <?= $page==='reports'?'active':'' ?>">
      <i class="fas fa-chart-bar"></i>
      <span>Reports</span>
    </a>
    <a href="?page=users" class="menu-item <?= $page==='users'?'active':'' ?>">
      <i class="fas fa-user"></i>
      <span>User</span>
    </a>
  </nav>

  <div class="logout-section">
    <a href="../admin/logout.php" class="menu-item">
      <i class="fas fa-sign-out-alt"></i>
      <span>Log Out</span>
    </a>
  </div>
</aside>
