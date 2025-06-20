//Db name : `inventorybarang`
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adminID VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(50) UNIQUE,                  
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE stok (
    id_stok VARCHAR(10) PRIMARY KEY,
    nama_barang VARCHAR(100) NOT NULL,
    kategori VARCHAR(50),
    pemilik_barang VARCHAR(100),
    no_telp VARCHAR(15),
    keterangan TEXT,
    stok INT DEFAULT 0
);

CREATE TABLE barang_masuk (
    id_masuk VARCHAR(10) PRIMARY KEY,
    id_stok VARCHAR(10) NOT NULL,
    qty INT NOT NULL,
    penerima VARCHAR(50),
    tanggal_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_stok) REFERENCES stok(id_stok),
    FOREIGN KEY (penerima) REFERENCES admin(nama)
);

CREATE TABLE barang_keluar (
    id_keluar VARCHAR(10) PRIMARY KEY,
    id_stok VARCHAR(10) NOT NULL,
    qty INT NOT NULL,
    tanggal_keluar DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_stok) REFERENCES stok(id_stok)
);

CREATE TABLE riwayat_penyimpanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi VARCHAR(20) NOT NULL UNIQUE,
    nama_pemilik VARCHAR(100) NOT NULL,
    nama_barang VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(15),
    tanggal_masuk DATETIME NOT NULL,
    tanggal_keluar DATETIME NULL,
    durasi_hari INT DEFAULT 0,
    biaya_penyimpanan INT DEFAULT 0,
    status_penyimpanan VARCHAR(20) DEFAULT 'Masih Disimpan'
);