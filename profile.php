<?php
require 'function.php';

// Check if user is logged in
if(!isset($_SESSION['loggedin'])){
    header('location:login.php');
    exit();
}

// Get current user information
$current_user = getCurrentUser();
if (!$current_user) {
    header('location:logout.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY - Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard-index.css">
    <link rel="stylesheet" href="assets/css/dashboard-profile.css">
    <link rel="icon" href="assets/img/LogoBesar.png">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>VOINVENTORY</h2>
            <p>SIMPLFY WITH VXKNET</p>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item">
                <i class="fas fa-boxes"></i>
                Inventory Overview
            </a>
            <a href="stockin.php" class="menu-item">
                <i class="fas fa-arrow-down"></i>
                Stock In
            </a>
            <a href="stockout.php" class="menu-item">
                <i class="fas fa-arrow-up"></i>
                Stock Out
            </a>
            <a href="inventory-history.php" class="menu-item">
                <i class="fas fa-history"></i>
                Inventory History
            </a>
            <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="profile.php" class="menu-item active">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
                <a href="#" class="menu-item" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Sign Out
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-nav">
            <div class="breadcrumb">
                <span>Pages / Profile</span>
                <h1 style="margin-top: 5px; color: #333;">Profile Settings</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($current_user['nama'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($current_user['nama']) ?></span>
                    <span class="user-status">
                        <?= $current_user['login_type'] == 'google' ? 'Google Account' : 'Admin VxKNET' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="profile-container">
                <!-- Profile Information -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($current_user['nama'], 0, 1)) ?>
                        </div>
                        <div class="profile-info">
                            <h3><?= htmlspecialchars($current_user['nama']) ?></h3>
                            <p><?= htmlspecialchars($current_user['email'] ?? 'No email') ?></p>
                            <p style="color: #667eea; font-weight: 600;">
                                <?= $current_user['login_type'] == 'google' ? 'Google Account' : 'Admin Account' ?>
                            </p>
                        </div>
                    </div>

                    <h4 class="card-title">
                        <i class="fas fa-user-edit"></i>
                        Update Profile Information
                    </h4>

                    <?php if($current_user['login_type'] == 'google'): ?>
                        <div class="info-box">
                            <i class="fab fa-google"></i>
                            <strong>Google Account:</strong> Profil Anda dikelola oleh Google dan tidak dapat diubah di sistem ini.
                        </div>
                    <?php endif; ?>

                    <?php if(isset($success_profile)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= $success_profile ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($error_profile)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $error_profile ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="<?= $current_user['login_type'] == 'google' ? 'disabled-form' : '' ?>">
                        <div class="form-group">
                            <label for="nama">
                                <i class="fas fa-user"></i>
                                Nama Lengkap
                            </label>
                            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($current_user['nama']) ?>" readonly class="readonly-field">
                            <small style="color: #666; font-size: 12px;">Nama tidak dapat diubah karena terkait dengan data transaksi</small>
                        </div>

                        <div class="form-group">
                            <label for="adminID">
                                <i class="fas fa-id-card"></i>
                                Admin ID
                            </label>
                            <input type="text" id="adminID" name="adminID" value="<?= htmlspecialchars($current_user['adminID'] ?? '') ?>" required>
                            <small style="color: #666; font-size: 12px;">ID unik untuk identifikasi admin</small>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email
                            </label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>">
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="profile-card">
                    <h4 class="card-title">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </h4>

                    <?php if($current_user['login_type'] == 'google'): ?>
                        <div class="info-box">
                            <i class="fab fa-google"></i>
                            <strong>Google Account:</strong> Password Anda dikelola oleh Google. Silakan ubah password melalui akun Google Anda.
                        </div>
                    <?php endif; ?>

                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="<?= $current_user['login_type'] == 'google' ? 'disabled-form' : '' ?>">
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-key"></i>
                                Password Lama
                            </label>
                            <div class="password-wrapper">
                                <input type="password" id="current_password" name="current_password" required>
                                <i class="fa-solid fa-eye-slash" id="toggleCurrentPassword"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-lock"></i>
                                Password Baru
                            </label>
                            <div class="password-wrapper">
                                <input type="password" id="new_password" name="new_password" required>
                                <i class="fa-solid fa-eye-slash" id="toggleNewPassword"></i>
                            </div>
                            <small style="color: #666; font-size: 12px;">Minimal 6 karakter</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i>
                                Konfirmasi Password Baru
                            </label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <i class="fa-solid fa-eye-slash" id="toggleConfirmPassword"></i>
                            </div>
                        </div>

                        <button type="submit" name="update_password" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i>
                            Update Password
                        </button>
                    </form>

                    <div class="security-tips">
                        <h5>
                            <i class="fas fa-shield-alt"></i>
                            Tips Keamanan:
                        </h5>
                        <ul>
                            <li>Gunakan kombinasi huruf besar, kecil, angka, dan simbol</li>
                            <li>Minimal 8 karakter untuk keamanan yang lebih baik</li>
                            <li>Jangan gunakan informasi pribadi yang mudah ditebak</li>
                            <li>Ubah password secara berkala</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle functionality for profile page
        document.addEventListener("DOMContentLoaded", function () {
            // Current Password Toggle
            const toggleCurrentPassword = document.getElementById("toggleCurrentPassword");
            const currentPassword = document.getElementById("current_password");

            if (toggleCurrentPassword && currentPassword) {
                toggleCurrentPassword.addEventListener("click", function () {
                    const type = currentPassword.getAttribute("type") === "password" ? "text" : "password";
                    currentPassword.setAttribute("type", type);
                    this.classList.toggle("fa-eye-slash");
                    this.classList.toggle("fa-eye");
                });
            }

            // New Password Toggle
            const toggleNewPassword = document.getElementById("toggleNewPassword");
            const newPassword = document.getElementById("new_password");

            if (toggleNewPassword && newPassword) {
                toggleNewPassword.addEventListener("click", function () {
                    const type = newPassword.getAttribute("type") === "password" ? "text" : "password";
                    newPassword.setAttribute("type", type);
                    this.classList.toggle("fa-eye-slash");
                    this.classList.toggle("fa-eye");
                });
            }

            // Confirm Password Toggle
            const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
            const confirmPassword = document.getElementById("confirm_password");

            if (toggleConfirmPassword && confirmPassword) {
                toggleConfirmPassword.addEventListener("click", function () {
                    const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
                    confirmPassword.setAttribute("type", type);
                    this.classList.toggle("fa-eye-slash");
                    this.classList.toggle("fa-eye");
                });
            }
        });

        // Logout confirmation
        function confirmLogout() {
            if (confirm("Apakah Anda yakin ingin keluar dari sistem?")) {
                window.location.href = "logout.php";
            }
        }
    </script>
</body>
</html>