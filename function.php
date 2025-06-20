<?php
session_start();

    //cek Login
    function cekLogin() {
        if (!isset($_SESSION['log'])) {
            header('Location: Login.php');
            exit;
        }
    }
    //Membuat Koneksi ke database
    $conn = new mysqli("localhost", "root", "", "inventorybarang");

    if($conn->connect_error){
        echo "ini error: ", $conn->connect_error;
    }


 // Get current user info
    function getCurrentUser() {
        global $conn;
        if (isset($_SESSION['email'])) {
            // Google OAuth users
            return [
                'nama' => $_SESSION['name'],
                'email' => $_SESSION['email'],
                'login_type' => 'google'
            ];
        } elseif (isset($_SESSION['adminID'])) {
            // regular users
            $adminID = $_SESSION['adminID'];
            $query = mysqli_query($conn, "SELECT nama, email, adminID FROM admin WHERE adminID='$adminID'");
            if ($query && mysqli_num_rows($query) > 0) {
                $user = mysqli_fetch_assoc($query);
                $user['login_type'] = 'regular';
                return $user;
            }
        }
        return null;
    }


    // Function untuk insert ke riwayat_penyimpanan
    function insertInventoryHistory($id_stok, $jenis_transaksi, $qty, $admin_name, $conn) {
        // Generate transaction ID
        $query_count = "SELECT COUNT(*) as total FROM riwayat_penyimpanan";
        $result_count = mysqli_query($conn, $query_count);
        $count = mysqli_fetch_assoc($result_count)['total'];
        $id_transaksi = 'TRX' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        
        // Hitung biaya penyimpanan (default untuk transaksi baru)
        $biaya_penyimpanan = ($jenis_transaksi == 'MASUK') ? 50000 : 0;
        
        // Insert ke riwayat_penyimpanan
        $query_insert = "INSERT INTO riwayat_penyimpanan 
                        (id_transaksi, id_stok, jenis_transaksi, qty, tanggal, admin, biaya_penyimpanan, durasi_hari, keterangan_history) 
                        VALUES (?, ?, ?, ?, NOW(), ?, ?, 0, ?)";
        
        $stmt = mysqli_prepare($conn, $query_insert);
        $keterangan = ($jenis_transaksi == 'MASUK') ? 'Barang masuk ke gudang' : 'Barang keluar dari gudang';
        
        mysqli_stmt_bind_param($stmt, "sssisis", 
            $id_transaksi, 
            $id_stok, 
            $jenis_transaksi, 
            $qty, 
            $admin_name, 
            $biaya_penyimpanan, 
            $keterangan
        );
        
        return mysqli_stmt_execute($stmt);
    }

    //  profile info update
    if(isset($_POST['update_profile'])){
        $current_user = getCurrentUser();
        $new_adminID = mysqli_real_escape_string($conn, $_POST['adminID']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        if($current_user['login_type'] == 'regular'){
            $current_adminID = $_SESSION['adminID'];
            
            // Check if adminID already exists (except current user)
            $check_query = mysqli_query($conn, "SELECT adminID FROM admin WHERE adminID='$new_adminID' AND adminID != '$current_adminID'");
            if(mysqli_num_rows($check_query) > 0){
                $error_profile = "Admin ID sudah digunakan oleh admin lain!";
            } else {
                // Update admin table
                $update_query = "UPDATE admin SET adminID='$new_adminID', email='$email' WHERE adminID='$current_adminID'";
                if(mysqli_query($conn, $update_query)){
                    $success_profile = "Profil berhasil diupdate!";
                    // Update session with new adminID
                    $_SESSION['adminID'] = $new_adminID;
                } else {
                    $error_profile = "Gagal mengupdate profil: " . mysqli_error($conn);
                }
            }
        } else {
            $error_profile = "Akun Google tidak dapat mengubah profil di sistem ini!";
        }
    }

    // password update
    if(isset($_POST['update_password'])){
        $current_user = getCurrentUser();
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if(empty($current_password) || empty($new_password) || empty($confirm_password)){
            $error = "Semua field harus diisi!";
        } elseif($new_password !== $confirm_password){
            $error = "Password baru dan konfirmasi password tidak cocok!";
        } elseif(strlen($new_password) < 6){
            $error = "Password baru minimal 6 karakter!";
        } else {
            // Check current password
            if($current_user['login_type'] == 'regular'){
                $adminID = $_SESSION['adminID'];
                $query = mysqli_query($conn, "SELECT password FROM admin WHERE adminID='$adminID'");
                $admin_data = mysqli_fetch_assoc($query);
                
                if(md5($current_password) === $admin_data['password']){
                    // Update password with MD5
                    $hashed_password = md5($new_password);
                    $update_query = "UPDATE admin SET password='$hashed_password' WHERE adminID='$adminID'";
                    
                    if(mysqli_query($conn, $update_query)){
                        $success = "Password berhasil diupdate!";
                    } else {
                        $error = "Gagal mengupdate password: " . mysqli_error($conn);
                    }
                } else {
                    $error = "Password lama tidak benar!";
                }
            } else {
                $error = "Akun Google tidak dapat mengubah password di sistem ini!";
            }
        }
    }
?>