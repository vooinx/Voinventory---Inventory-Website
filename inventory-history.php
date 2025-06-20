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

// Fungsi untuk menghitung biaya berdasarkan durasi
function calculateStorageCost($days) {
    if ($days <= 1) {
        return 50000; // 1 hari = 50rb
    } elseif ($days <= 3) {
        return 120000; // 3 hari = 120rb
    } elseif ($days <= 7) {
        return 270000; // 1 minggu = 270rb
    } else {
        // Untuk lebih dari 1 minggu, hitung per minggu
        $weeks = ceil($days / 7);
        return $weeks * 270000;
    }
}

// OTOMATIS SYNC DATA KE RIWAYAT_PENYIMPANAN
// Query untuk ambil data dari JOIN (seperti biasa)
$query_source = "
    SELECT 
        CONCAT('TRX', LPAD(ROW_NUMBER() OVER (ORDER BY bm.tanggal_masuk), 4, '0')) as transaction_id,
        s.pemilik_barang,
        s.nama_barang,
        s.no_telp,
        bm.tanggal_masuk,
        bk.tanggal_keluar,
        CASE 
            WHEN bk.tanggal_keluar IS NOT NULL THEN
                DATEDIFF(bk.tanggal_keluar, bm.tanggal_masuk)
            ELSE
                DATEDIFF(NOW(), bm.tanggal_masuk)
        END as durasi_hari,
        CASE 
            WHEN bk.tanggal_keluar IS NOT NULL THEN 'Selesai'
            ELSE 'Masih Disimpan'
        END as status_penyimpanan,
        bm.id_stok,
        bm.qty
    FROM barang_masuk bm
    JOIN stok s ON bm.id_stok = s.id_stok
    LEFT JOIN barang_keluar bk ON bm.id_stok = bk.id_stok 
        AND DATE(bm.tanggal_masuk) <= DATE(bk.tanggal_keluar)
    ORDER BY bm.tanggal_masuk ASC
";

$result_source = mysqli_query($conn, $query_source);

// OTOMATIS INSERT/UPDATE KE RIWAYAT_PENYIMPANAN
while($row = mysqli_fetch_assoc($result_source)) {
    $cost = calculateStorageCost($row['durasi_hari']);
    
    // Insert atau update ke riwayat_penyimpanan
    $sync_query = "INSERT INTO riwayat_penyimpanan (
        id_transaksi, nama_pemilik, nama_barang, no_telepon, 
        tanggal_masuk, tanggal_keluar, durasi_hari, biaya_penyimpanan, status_penyimpanan
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        nama_pemilik = VALUES(nama_pemilik),
        nama_barang = VALUES(nama_barang),
        no_telepon = VALUES(no_telepon),
        tanggal_masuk = VALUES(tanggal_masuk),
        tanggal_keluar = VALUES(tanggal_keluar),
        durasi_hari = VALUES(durasi_hari),
        biaya_penyimpanan = VALUES(biaya_penyimpanan),
        status_penyimpanan = VALUES(status_penyimpanan)";
    
    $stmt = mysqli_prepare($conn, $sync_query);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssiss", 
            $row['transaction_id'],
            $row['pemilik_barang'],
            $row['nama_barang'],
            $row['no_telp'],
            $row['tanggal_masuk'],
            $row['tanggal_keluar'],
            $row['durasi_hari'],
            $cost,
            $row['status_penyimpanan']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// SEKARANG AMBIL DATA DARI RIWAYAT_PENYIMPANAN DENGAN URUTAN YANG BENAR
$query_history = "
    SELECT 
        id_transaksi,
        nama_pemilik,
        nama_barang,
        no_telepon,
        tanggal_masuk,
        tanggal_keluar,
        durasi_hari,
        biaya_penyimpanan,
        status_penyimpanan
    FROM riwayat_penyimpanan 
    ORDER BY 
        CAST(SUBSTRING(id_transaksi, 4) AS UNSIGNED) DESC
";

$result_history = mysqli_query($conn, $query_history);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY - Inventory History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard-history.css">
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
            <a href="inventory-history.php" class="menu-item active">
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
                <span>Pages / Inventory History</span>
                <h1 style="margin-top: 5px; color: #333;">Inventory History</h1>
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

        <div class="content">
            <h2 class="page-title">Riwayat Penyimpanan Barang</h2>
            
            <div class="summary-cards">
                <?php
                $total_revenue = 0;
                $active_storage = 0;
                $completed_storage = 0;
                
                // Reset pointer untuk perhitungan summary
                mysqli_data_seek($result_history, 0);
                while($row = mysqli_fetch_assoc($result_history)) {
                    $total_revenue += $row['biaya_penyimpanan'];
                    
                    if($row['status_penyimpanan'] == 'Masih Disimpan') {
                        $active_storage++;
                    } else {
                        $completed_storage++;
                    }
                }
                
                // Reset pointer lagi untuk tabel
                mysqli_data_seek($result_history, 0);
                ?>
                
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="summary-icon revenue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Total Pendapatan</h3>
                            <p class="summary-value">Rp <?= number_format($total_revenue, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="summary-icon active">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Masih Disimpan</h3>
                            <p class="summary-value"><?= $active_storage ?> Item</p>
                        </div>
                    </div>
                </div>
                
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="summary-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="summary-info">
                            <h3>Selesai</h3>
                            <p class="summary-value"><?= $completed_storage ?> Item</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title-section">
                        <h3 class="card-title">
                            <i class="fas fa-list" style="color: #667eea; margin-right: 10px;"></i>
                            Riwayat Lengkap Penyimpanan
                        </h3>
                        <a href="export-history.php" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-file-pdf"></i>
                            Export PDF
                        </a>
                    </div>
                    <div class="search-container">
                        <label style="display: flex; align-items: center; gap: 8px; margin: 0;">
                            Search: 
                            <input type="text" id="tableSearch" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 250px;">
                        </label>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table id="stockTable">
                            <thead>
                                <tr>
                                    <th>ID Transaksi</th>
                                    <th>Nama Pemilik</th>
                                    <th>Nama Barang</th>
                                    <th>No. Telepon</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Tanggal Keluar</th>
                                    <th>Durasi</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($history = mysqli_fetch_assoc($result_history)): ?>
                                <?php 
                                    $duration_text = $history['durasi_hari'] . ' hari';
                                    if($history['durasi_hari'] >= 7) {
                                        $weeks = floor($history['durasi_hari'] / 7);
                                        $remaining_days = $history['durasi_hari'] % 7;
                                        $duration_text = $weeks . ' minggu';
                                        if($remaining_days > 0) {
                                            $duration_text .= ' ' . $remaining_days . ' hari';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= $history['id_transaksi'] ?></strong></td>
                                    <td><?= htmlspecialchars($history['nama_pemilik']) ?></td>
                                    <td><strong><?= htmlspecialchars($history['nama_barang']) ?></strong></td>
                                    <td><?= htmlspecialchars($history['no_telepon']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($history['tanggal_masuk'])) ?></td>
                                    <td>
                                        <?= $history['tanggal_keluar'] ? date('d/m/Y H:i', strtotime($history['tanggal_keluar'])) : '-' ?>
                                    </td>
                                    <td>
                                        <span class="duration-badge"><?= $duration_text ?></span>
                                    </td>
                                    <td>
                                        <span class="cost-amount">Rp <?= number_format($history['biaya_penyimpanan'], 0, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <?php if($history['status_penyimpanan'] == 'Masih Disimpan'): ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Masih Disimpan
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
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
