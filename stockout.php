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

// Handle form submission untuk barang keluar
if(isset($_POST['tambah_barang_keluar'])){
    $id_keluar = $_POST['id_keluar'];
    $id_stok = $_POST['id_stok'];
    $qty = $_POST['qty'];
    
    // Cek apakah barang pernah di-stock in
    $cek_stock_in = mysqli_query($conn, "SELECT COUNT(*) as count FROM barang_masuk WHERE id_stok = '$id_stok'");
    $stock_in_data = mysqli_fetch_assoc($cek_stock_in);
    
    if($stock_in_data['count'] == 0){
        echo "<script>alert('Barang ini belum pernah di-stock in! Silakan stock in terlebih dahulu.');</script>";
    } else {
        // Cek stok tersedia
        $cek_stok = mysqli_query($conn, "SELECT stok FROM stok WHERE id_stok = '$id_stok'");
        $data_stok = mysqli_fetch_assoc($cek_stok);
        
        if($data_stok['stok'] >= $qty){
            // Insert ke tabel barang_keluar
            $query_keluar = "INSERT INTO barang_keluar (id_keluar, id_stok, qty) 
                            VALUES ('$id_keluar', '$id_stok', '$qty')";
            
            if(mysqli_query($conn, $query_keluar)){
                // Update stok di tabel stok
                $query_update = "UPDATE stok SET stok = stok - $qty WHERE id_stok = '$id_stok'";
                mysqli_query($conn, $query_update);
                
                echo "<script>alert('Barang keluar berhasil dicatat!'); window.location.href='stockout.php';</script>";
            } else {
                echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            echo "<script>alert('Stok tidak mencukupi! Stok tersedia: " . $data_stok['stok'] . "');</script>";
        }
    }
}

// Get data barang keluar
$result_keluar = mysqli_query($conn, "
    SELECT bk.*, s.nama_barang 
    FROM barang_keluar bk 
    JOIN stok s ON bk.id_stok = s.id_stok 
    ORDER BY bk.tanggal_keluar DESC
");

// Get data stok yang tersedia untuk dropdown - HANYA yang pernah di-stock in
$result_stok = mysqli_query($conn, "
    SELECT DISTINCT s.id_stok, s.nama_barang, s.stok
    FROM stok s 
    INNER JOIN barang_masuk bm ON s.id_stok = bm.id_stok
    WHERE s.stok > 0
    ORDER BY s.nama_barang ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY - Stock Out</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard-stockout.css">
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
            <a href="stockout.php" class="menu-item active">
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
                <span>Pages / Stock Out</span>
                <h1 style="margin-top: 5px; color: #333;">Stock Out</h1>
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
            <h2 class="page-title">Barang Keluar</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
                <!-- Form Input -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-minus-circle" style="color: #dc3545; margin-right: 10px;"></i>
                            Tambah Barang Keluar
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        $stok_kosong = mysqli_num_rows($result_stok) == 0;
                        if($stok_kosong): 
                        ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tidak ada barang dengan stok tersedia untuk dikeluarkan.
                            <br><small><strong>Catatan:</strong> Hanya barang yang sudah pernah stock in yang bisa dikeluarkan.</small>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" <?= $stok_kosong ? 'style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                            <div class="form-group">
                                <label>ID Keluar</label>
                                <input type="text" name="id_keluar" placeholder="Contoh: OUT001" required <?= $stok_kosong ? 'disabled' : '' ?>>
                            </div>
                            
                            <div class="form-group">
                                <label>Pilih Barang</label>
                                <select name="id_stok" required <?= $stok_kosong ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Barang --</option>
                                    <?php 
                                    // Reset pointer untuk dropdown
                                    mysqli_data_seek($result_stok, 0);
                                    while($stok = mysqli_fetch_assoc($result_stok)): 
                                    ?>
                                    <option value="<?= $stok['id_stok'] ?>">
                                        <?= $stok['id_stok'] ?> - <?= $stok['nama_barang'] ?> (Stok: <?= $stok['stok'] ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Jumlah</label>
                                <input type="number" name="qty" min="1" value="1" required <?= $stok_kosong ? 'disabled' : '' ?>>
                            </div>
                            
                            <button type="submit" name="tambah_barang_keluar" class="btn btn-danger" style="width: 100%;" <?= $stok_kosong ? 'disabled' : '' ?>>
                                <i class="fas fa-save"></i>
                                Simpan Barang Keluar
                            </button>
                        </form>
                    </div>
                </div>

                <!-- History Table -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title">
                            <i class="fas fa-history" style="color: #667eea; margin-right: 10px;"></i>
                            History Barang Keluar
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
                                    <th>ID Keluar</th>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($keluar = mysqli_fetch_assoc($result_keluar)): ?>
                                <tr>
                                    <td><?= $keluar['id_keluar'] ?></td>
                                    <td>
                                        <strong><?= $keluar['nama_barang'] ?></strong><br>
                                        <small style="color: #666;">ID: <?= $keluar['id_stok'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger">-<?= $keluar['qty'] ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($keluar['tanggal_keluar'])) ?></td>
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
