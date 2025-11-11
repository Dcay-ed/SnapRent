<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapRent - Rent Your Perfect Camera</title>
    <link rel="stylesheet" href="globals.css">
    <link rel="stylesheet" href="\style\cameras.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header / Navbar -->
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Snaprent</h1>
                <h2>Rent Your Perfect Camera</h2>
                <p>Affordable, flexible, and ready when you are</p>
                <a href="#" class="btn-rent-now">Rent now</a>
            </div>
            <div class="hero-images">
                <img src="img/camera1.jpg" alt="Camera 1"> <!-- Ganti dengan path gambar -->
                <img src="img/camera2.jpg" alt="Camera 2"> <!-- Ganti dengan path gambar -->
                <img src="img/camera3.jpg" alt="Camera 3"> <!-- Ganti dengan path gambar -->
            </div>
        </div>
    </section>

    <!-- Featured Equipment Section -->
    <section class="featured-equipment">
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
                    <button><img src="img/search-icon.png" alt="Search"></button> <!-- Ganti dengan path ikon -->
                </div>
            </div>

            <!-- Product Grid -->
            <div class="product-grid">
                <?php
                // Contoh data produk. Di sini Anda bisa menggantinya dengan data dari database.
                $products = [
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                    ['name' => 'Nama kamera', 'price' => 'Rp ------/hari'],
                ];

                foreach ($products as $product) {
                    echo '
                    <div class="product-card">
                        <div class="product-image">
                            <!-- Placeholder image, ganti dengan gambar produk sebenarnya -->
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

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>SnapRent</h3>
                    <p>Professional camera rentals for photographers and videographers</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Cameras</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Categories</h3>
                    <ul>
                        <li><a href="#">DSLR</a></li>
                        <li><a href="#">Mirrorless</a></li>
                        <li><a href="#">Digicam</a></li>
                        <li><a href="#">Analog</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact</h3>
                    <ul>
                        <li><img src="img/phone-icon.png" alt="Phone"> (555) 123-4567</li> <!-- Ganti dengan path ikon -->
                        <li><img src="img/email-icon.png" alt="Email"> hello@snaprent.com</li> <!-- Ganti dengan path ikon -->
                        <li><img src="img/location-icon.png" alt="Location"> 123 Photo Street, Camera City</li> <!-- Ganti dengan path ikon -->
                        <li><img src="img/clock-icon.png" alt="Hours"> Mon-Fri: 9AM-6PM</li> <!-- Ganti dengan path ikon -->
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 CameraRent. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>