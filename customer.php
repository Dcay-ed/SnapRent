<?php
// Simulasi data pengguna (dalam aplikasi nyata, ini diambil dari database)
$user = [
    'username' => 'Username',
    'email' => 'Username@gmail.com',
    'phone' => '08*********',
    'profile_pic' => 'https://via.placeholder.com/150?text=Profile+Photo'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapRent - Personal Info</title>
    <style>
        :root {
            --primary-color: #5D6D7E;
            --secondary-color: #2C3E50;
            --light-bg: #F5F5F5;
            --white: #FFFFFF;
            --gray: #E0E0E0;
            --red: #E74C3C;
            --border-color: #CCCCCC;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-bg);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 200px;
            background-color: var(--white);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
        }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar-header img {
            width: 30px;
            height: 30px;
        }

        .sidebar-menu {
            padding: 30px 20px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(90deg, #5D6D7E, #2C3E50);
            color: white;
        }

        .sidebar-menu i {
            font-size: 18px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-footer a:hover {
            background-color: #f0f0f0;
        }

        /* Main Content */
        .main-content {
            margin-left: 200px;
            width: calc(100% - 200px);
            padding: 20px;
        }

        .header {
            background-color: var(--primary-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-logo img {
            width: 30px;
            height: 30px;
        }

        .header-icons {
            display: flex;
            gap: 20px;
        }

        .header-icons i {
            font-size: 24px;
            cursor: pointer;
            position: relative;
        }

        .header-icons .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            width: 12px;
            height: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
        }

        /* Profile Section */
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .profile-header {
            background-color: var(--secondary-color);
            padding: 30px 40px;
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
        }

        .upload-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            margin-left: auto;
        }

        .profile-info {
            padding: 30px 40px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-label {
            font-weight: bold;
            font-size: 16px;
        }

        .info-value {
            font-size: 16px;
            color: #555;
        }

        .edit-btn {
            background-color: transparent;
            border: 1px solid #555;
            color: #555;
            padding: 6px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }

        .change-password-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }

        .remove-account-btn {
            background-color: var(--red);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Icons (using Font Awesome style) */
        .fa {
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            font-weight: normal;
            font-stretch: normal;
            line-height: 1;
            font-family: FontAwesome;
            font-size: inherit;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
        }

        .fa-user::before { content: "\f007"; }
        .fa-folder-check::before { content: "\f07c"; }
        .fa-bell::before { content: "\f0f3"; }
        .fa-shopping-cart::before { content: "\f07a"; }
        .fa-sign-out::before { content: "\f2f5"; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="https://via.placeholder.com/30?text=C" alt="Logo">
            <span>SnapRent</span>
        </div>

        <div class="sidebar-menu">
            <a href="#" class="active"><i class="fa fa-user"></i> Profile</a>
            <a href="#"><i class="fa fa-folder-check"></i> Booking</a>
            <a href="#">Notification</a>
        </div>

        <div class="sidebar-footer">
            <a href="#"><i class="fa fa-sign-out"></i> Log Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-logo">
                <img src="https://via.placeholder.com/30?text=C" alt="Logo">
                <span>SnapRent</span>
            </div>
            <div class="header-icons">
                <i class="fa fa-user"></i>
                <i class="fa fa-bell"><span class="notification-dot">1</span></i>
                <i class="fa fa-shopping-cart"></i>
            </div>
        </div>

        <!-- Profile Section -->
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?= $user['profile_pic'] ?>" alt="Profile Picture">
                </div>
                <div class="profile-name"><?= htmlspecialchars($user['username']) ?></div>
                <button class="upload-btn">Upload Photo</button>
            </div>

            <div class="profile-info">
                <!-- Username -->
                <div class="info-item">
                    <div>
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Email -->
                <div class="info-item">
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Phone Number -->
                <div class="info-item">
                    <div>
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                    <button class="edit-btn">Edit</button>
                </div>

                <!-- Password -->
                <div class="info-item">
                    <div>
                        <div class="info-label">Password</div>
                        <div class="info-value">••••••••••••</div>
                    </div>
                    <button class="change-password-btn">Change Password</button>
                </div>

                <!-- Account Removal -->
                <div class="info-item">
                    <div>
                        <div class="info-label">Account Removal</div>
                    </div>
                    <button class="remove-account-btn">Remove Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional: JavaScript for interactivity -->
    <script>
        // Contoh sederhana untuk tombol Edit
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                alert('Fitur Edit belum diimplementasikan. Dalam aplikasi nyata, akan membuka form edit.');
            });
        });

        document.querySelector('.change-password-btn').addEventListener('click', () => {
            alert('Fitur Change Password belum diimplementasikan.');
        });

        document.querySelector('.remove-account-btn').addEventListener('click', () => {
            if (confirm('Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan.')) {
                alert('Akun telah dihapus (simulasi).');
            }
        });
    </script>
</body>
</html>