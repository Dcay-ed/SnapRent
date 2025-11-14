<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification - SnapRent</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <aside class="sidebar">
            <nav>
                <a href="index.php"><i class="fas fa-users"></i> Profile</a>
                <a href="booking.php"><i class="far fa-folder-open"></i> Booking</a>
                <a href="notification.php" class="active"><i class="far fa-bell"></i> Notification</a>
                <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </nav>
        </aside>
        <main class="main-content">
            <h1>Notifications</h1>
            <p>Anda tidak memiliki notifikasi baru.</p>
        </main>
    </div>
</body>
</html>