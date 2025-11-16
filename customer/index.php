<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'username' => 'Username',
        'email' => 'Username@gmail.com',
        'phone' => '08*********',
        'profile_pic' => 'https://via.placeholder.com/150?text=Profile+Photo'
    ];
}
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Info - SnapRent</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <i class="fas fa-camera"></i>
            <span>SnapRent</span>
        </div>
        <div class="header-icons">
            <a href="#" class="icon"><i class="far fa-user-circle"></i></a>
            <a href="#" class="icon"><i class="far fa-bell"></i><span class="badge">3</span></a>
            <a href="#" class="icon"><i class="fas fa-shopping-cart"></i></a>
        </div>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav>
                <a href="index.php" class="active"><i class="fas fa-users"></i> Profile</a>
                <a href="booking.php"><i class="far fa-folder-open"></i> Booking</a>
                <a href="notification.php"><i class="far fa-bell"></i> Notification</a>
                <div class="sidebar-footer">
                <div class="logout-section">
                    <a href="../auth/logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Log Out</span>
                    </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h1>Personal Info</h1>

            <div class="profile-header">
                <img src="<?= $user['profile_pic'] ?>" alt="Profile Picture" class="profile-pic">
                <h2><?= htmlspecialchars($user['username']) ?></h2>
                <button class="upload-btn">Upload Photo</button>
            </div>

            <div class="info-card">
                <!-- Username -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Username</strong>
                        <p><?= htmlspecialchars($user['username']) ?></p>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Email -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Email</strong>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Phone Number -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Phone Number</strong>
                        <p><?= htmlspecialchars($user['phone']) ?></p>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Username (again) - sesuai gambar -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Username</strong>
                        <p><?= htmlspecialchars($user['username']) ?></p>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Password -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Password</strong>
                        <button class="change-pass-btn">Change Password</button>
                    </div>
                </div>

                <!-- Account Removal -->
                <div class="info-row">
                    <div class="info-label">
                        <strong>Account Removal</strong>
                        <button class="remove-account-btn">Remove Account</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                alert('Edit feature not implemented in demo.');
            });
        });

        document.querySelector('.change-pass-btn').addEventListener('click', () => {
            alert('Change password feature not implemented in demo.');
        });

        document.querySelector('.remove-account-btn').addEventListener('click', () => {
            if (confirm('Are you sure you want to remove your account? This action cannot be undone.')) {
                alert('Account removal feature not implemented in demo.');
            }
        });

        document.querySelector('.upload-btn').addEventListener('click', () => {
            alert('Upload photo feature not implemented in demo.');
        });
    </script>
</body>
</html>