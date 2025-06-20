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
    // If we can't get user info, logout
    header('location:logout.php');
    exit();
}

// Handle form submission untuk barang masuk
if(isset($_POST['tambah_barang_masuk'])){
    $id_masuk = $_POST['id_masuk'];
    $id_stok = $_POST['id_stok'];
    $qty = $_POST['qty'];
    $penerima = $_POST['penerima'];
    
    // Insert ke tabel barang_masuk
    $query_masuk = "INSERT INTO barang_masuk (id_masuk, id_stok, qty, penerima) 
                    VALUES ('$id_masuk', '$id_stok', '$qty', '$penerima')";
    
    if(mysqli_query($conn, $query_masuk)){
        // Update stok di tabel stok
        // $query_update = "UPDATE stok SET stok = stok + $qty WHERE id_stok = '$id_stok'";
        // mysqli_query($conn, $query_update);
        
        echo "<script>alert('Barang masuk berhasil dicatat!'); window.location.href='stockin.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Get data barang masuk
$result_masuk = mysqli_query($conn, "
    SELECT bm.*, s.nama_barang 
    FROM barang_masuk bm 
    JOIN stok s ON bm.id_stok = s.id_stok 
    ORDER BY bm.tanggal_masuk DESC
");

// Get data stok untuk dropdown
$result_stok = mysqli_query($conn, "SELECT s.id_stok, s.nama_barang, s.stok, IFNULL(SUM(bm.qty), 0) as total_stockin FROM stok s LEFT JOIN barang_masuk bm ON s.id_stok = bm.id_stok GROUP BY s.id_stok HAVING total_stockin < s.stok");



// Get data admin untuk dropdown penerima
$result_admin = mysqli_query($conn, "SELECT nama FROM admin ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY - Stock In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard-stockin.css">
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
            <a href="stockin.php" class="menu-item active">
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
                <a href="profile.php" class="menu-item">
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
                <span>Pages / Stock In</span>
                <h1 style="margin-top: 5px; color: #333;">Stock In</h1>
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
            <h2 class="page-title">Barang Masuk</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                <!-- Form Input -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle" style="color: #28a745; margin-right: 10px;"></i>
                            Tambah Barang Masuk
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        $stok_kosong = mysqli_num_rows($result_stok) == 0;
                        if($stok_kosong): 
                        ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tidak ada barang dengan stok tersedia untuk dimasukkan.
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="" <?= $stok_kosong ? 'style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                            <div class="form-group">
                                <label>ID Masuk</label>
                                <input type="text" name="id_masuk" placeholder="Contoh: IN001" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Pilih Barang</label>
                                <select name="id_stok" required>
                                    <option value="">-- Pilih Barang --</option>
                                    <?php while($stok = mysqli_fetch_assoc($result_stok)): ?>
                                    <option value="<?= $stok['id_stok'] ?>">
                                        <?= $stok['id_stok'] ?> - <?= $stok['nama_barang'] ?> (Stok: <?= $stok['stok'] ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Jumlah</label>
                                <input type="number" name="qty" min="1" value="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Diterima Oleh</label>
                                <select name="penerima" required>
                                    <option value="">-- Pilih Admin --</option>
                                    <?php while($admin = mysqli_fetch_assoc($result_admin)): ?>
                                    <option value="<?= $admin['nama'] ?>">
                                        <?= $admin['nama'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" name="tambah_barang_masuk" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-save"></i>
                                Simpan Barang Masuk
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History Table -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title">
                            <i class="fas fa-history" style="color: #667eea; margin-right: 10px;"></i>
                            History Barang Masuk
                        </h3>
                        <div class="search-container">
                            <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                                Search: 
                                <input type="text" id="tableSearch" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 200px;">
                            </label>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <table id="stockTable">
                            <thead>
                                <tr>
                                    <th>ID Masuk</th>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th>Penerima</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($masuk = mysqli_fetch_assoc($result_masuk)): ?>
                                <tr>
                                    <td><?= $masuk['id_masuk'] ?></td>
                                    <td>
                                        <strong><?= $masuk['nama_barang'] ?></strong><br>
                                        <small style="color: #666;">ID: <?= $masuk['id_stok'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">+<?= $masuk['qty'] ?></span>
                                    </td>
                                    <td><?= $masuk['penerima'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($masuk['tanggal_masuk'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="src/js/script.js"></script>
</body>
</html>