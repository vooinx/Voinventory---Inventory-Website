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

// Handle form submissions
if(isset($_POST['tambah_barang'])){
    $id_stok = $_POST['id_stok'];
    $nama_barang = $_POST['nama_barang'];
    $kategori = $_POST['kategori'];
    $pemilik_barang = $_POST['pemilik_barang'];
    $no_telp = $_POST['no_telp'];
    $keterangan = $_POST['keterangan'];
    $stok = $_POST['stok'];
    
    $query = "INSERT INTO stok (id_stok, nama_barang, kategori, pemilik_barang, no_telp, keterangan, stok) 
              VALUES ('$id_stok', '$nama_barang', '$kategori', '$pemilik_barang', '$no_telp', '$keterangan', '$stok')";
    
    if(mysqli_query($conn, $query)){
        echo "<script>alert('Barang berhasil ditambahkan!');</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle edit barang
if(isset($_POST['edit_barang'])){
    $id_stok = $_POST['id_stok'];
    $nama_barang = $_POST['nama_barang'];
    $kategori = $_POST['kategori'];
    $pemilik_barang = $_POST['pemilik_barang'];
    $no_telp = $_POST['no_telp'];
    $keterangan = $_POST['keterangan'];
    $stok = $_POST['stok'];
    $old_id = $_POST['old_id'];
    
    $query = "UPDATE stok SET 
              id_stok = '$id_stok',
              nama_barang = '$nama_barang', 
              kategori = '$kategori', 
              pemilik_barang = '$pemilik_barang', 
              no_telp = '$no_telp', 
              keterangan = '$keterangan', 
              stok = '$stok' 
              WHERE id_stok = '$old_id'";
    
    if(mysqli_query($conn, $query)){
        echo "<script>alert('Barang berhasil diupdate!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

if(isset($_GET['delete'])){
    $id_stok = $_GET['delete'];
    
    // Cek apakah barang sudah pernah ada transaksi masuk/keluar
    $cek_transaksi_masuk = mysqli_query($conn, "SELECT COUNT(*) as count FROM barang_masuk WHERE id_stok = '$id_stok'");
    $cek_transaksi_keluar = mysqli_query($conn, "SELECT COUNT(*) as count FROM barang_keluar WHERE id_stok = '$id_stok'");
    
    $transaksi_masuk = mysqli_fetch_assoc($cek_transaksi_masuk)['count'];
    $transaksi_keluar = mysqli_fetch_assoc($cek_transaksi_keluar)['count'];
    
    if($transaksi_masuk > 0 || $transaksi_keluar > 0){
        echo "<script>
            alert('Barang tidak dapat dihapus karena sudah memiliki riwayat transaksi!\\n\\nTransaksi Masuk: $transaksi_masuk\\nTransaksi Keluar: $transaksi_keluar');
            window.location.href='index.php';
        </script>";
    } else {
        $query = "DELETE FROM stok WHERE id_stok = '$id_stok'";
        if(mysqli_query($conn, $query)){
            echo "<script>alert('Barang berhasil dihapus!'); window.location.href='index.php';</script>";
        }
    }
}

// Get all stock data - QUERY UNTUK DAPAT INFO TRANSAKSI
$result = mysqli_query($conn, "
    SELECT s.*, 
           COALESCE(SUM(bm.qty), 0) as total_masuk,
           COALESCE(SUM(bk.qty), 0) as total_keluar
    FROM stok s 
    LEFT JOIN barang_masuk bm ON s.id_stok = bm.id_stok
    LEFT JOIN barang_keluar bk ON s.id_stok = bk.id_stok
    GROUP BY s.id_stok
    ORDER BY s.id_stok ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOINVENTORY - Dashboard</title>
    <link rel="icon" href="assets/img/LogoBesar.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard-index.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>VOINVENTORY</h2>
            <p>SIMPLFY WITH VXKNET</p>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item active">
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

    <div class="main-content">
        <div class="top-nav">
            <div class="breadcrumb">
                <span>Pages / Inventory Overview</span>
                <h1 style="margin-top: 5px; color: #333;">Inventory Overview</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($current_user['nama'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($current_user['nama']) ?></span>
                    <span class="user-status">
                        <?= $current_user['login_type'] == 'google' ? 'Google Account' : 'Admin Account' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="content">
            <h2 class="page-title">Stock Barang</h2>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                    Tambah Barang
                </button>
                <a href="export-pdf.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i>
                    Export PDF
                </a>
            </div>

            <div class="table-container">
                <div class="table-header">
                   <div>
                        <label>Show 
                            <select id="entriesSelect" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="999">All</option>
                            </select> entries
                        </label>
                    </div>
                    <div>
                        <label>Search: 
                            <input type="text" id="tableSearch" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                        </label>
                    </div>
                </div>
                
                <table id="stockTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>ID Stok</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Pemilik</th>
                            <th>No. Telp</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_assoc($result)): 
                            $dalam_transaksi = ($row['total_masuk'] > 0 || $row['total_keluar'] > 0);
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $row['id_stok'] ?></td>
                            <td><?= $row['nama_barang'] ?></td>
                            <td><?= $row['kategori'] ?></td>
                            <td><?= $row['pemilik_barang'] ?></td>
                            <td><?= $row['no_telp'] ?></td>
                            <td><?= $row['stok'] ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="openEditModal('<?= $row['id_stok'] ?>', '<?= $row['nama_barang'] ?>', '<?= $row['kategori'] ?>', '<?= $row['pemilik_barang'] ?>', '<?= $row['no_telp'] ?>', '<?= addslashes($row['keterangan']) ?>', '<?= $row['stok'] ?>')">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                
                                <?php if(!$dalam_transaksi): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteItem('<?= $row['id_stok'] ?>')">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="Tidak dapat dihapus - sudah ada transaksi">
                                        <i class="fas fa-lock"></i>
                                        Terkunci
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-info btn-sm" onclick="viewKeterangan('<?= addslashes($row['keterangan']) ?>', '<?= $row['nama_barang'] ?>')">
                                    <i class="fas fa-info-circle"></i>
                                    Keterangan
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tambahBarangModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Barang Baru</h3>
                <span class="close" onclick="closeModal('tambahBarangModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ID Stok</label>
                            <input type="text" name="id_stok" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Mousepad / Deskmats">Mousepad / Deskmats</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Headset">Headset</option>
                                <option value="In-Ear Monitor">In-Ear Monitor</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pemilik Barang</label>
                            <input type="text" name="pemilik_barang" required>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telp" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" rows="3" placeholder="Masukkan keterangan barang (opsional)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Stok</label>
                        <input type="number" name="stok" min="0" required>
                    </div>
                    
                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('tambahBarangModal')">Batal</button>
                        <button type="submit" name="tambah_barang" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editBarangModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Barang</h3>
                <span class="close" onclick="closeModal('editBarangModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="old_id" id="edit_old_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>ID Stok</label>
                            <input type="text" name="id_stok" id="edit_id_stok" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" id="edit_kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Mouse">Mouse</option>
                                <option value="Mousepad / Deskmats">Mousepad / Deskmats</option>
                                <option value="Keyboard">Keyboard</option>
                                <option value="Headset">Headset</option>
                                <option value="In-Ear Monitor">In-Ear Monitor</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" id="edit_nama_barang" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pemilik Barang</label>
                            <input type="text" name="pemilik_barang" id="edit_pemilik_barang" required>
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="text" name="no_telp" id="edit_no_telp" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Stok</label>
                        <input type="number" name="stok" id="edit_stok" min="0" required>
                    </div>
                    
                    <div style="text-align: right; margin-top: 30px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editBarangModal')">Batal</button>
                        <button type="submit" name="edit_barang" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="keteranganModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Keterangan Barang: <span id="keterangan-title"></span></h3>
                <span class="close" onclick="closeModal('keteranganModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="keterangan-box" id="keterangan-content">
                        <!-- Keterangan akan ditampilkan di sini -->
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 30px;">
                    <button type="button" class="btn btn-primary" onclick="closeModal('keteranganModal')">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <script src="src/js/script.js"></script>
</body>
</html>
