<?php
/**
 * Helpers for keeping the ERP tables compatible with databases created in
 * earlier stages. CREATE TABLE IF NOT EXISTS does not alter an existing table,
 * so we explicitly add missing columns before using them.
 */
function erpColumnExists($koneksi, $table, $column) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $q = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

function erpAddColumnIfMissing($koneksi, $table, $column, $definition) {
    if (!erpColumnExists($koneksi, $table, $column)) {
        return mysqli_query($koneksi, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
    return true;
}

function ensurePaymentTable($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS pembayaran (
        id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
        id_order INT NOT NULL,
        metode_pembayaran VARCHAR(30) NOT NULL,
        jumlah DECIMAL(12,2) NOT NULL DEFAULT 0,
        status_pembayaran ENUM('Menunggu Verifikasi','Terverifikasi','Ditolak','Belum Dibayar','Lunas') NOT NULL DEFAULT 'Menunggu Verifikasi',
        bukti_pembayaran VARCHAR(255) NULL,
        tanggal_bayar DATETIME NULL,
        catatan VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_pembayaran_order (id_order),
        KEY idx_pembayaran_status (status_pembayaran)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($koneksi, $sql)) {
        return false;
    }

    // Upgrade tables created by older stages instead of relying on CREATE TABLE IF NOT EXISTS.
    $columns = [
        'metode_pembayaran' => "VARCHAR(30) NOT NULL DEFAULT ''",
        'jumlah' => "DECIMAL(12,2) NOT NULL DEFAULT 0",
        'status_pembayaran' => "ENUM('Menunggu Verifikasi','Terverifikasi','Ditolak','Belum Dibayar','Lunas') NOT NULL DEFAULT 'Menunggu Verifikasi'",
        'bukti_pembayaran' => "VARCHAR(255) NULL",
        'tanggal_bayar' => "DATETIME NULL",
        'catatan' => "VARCHAR(500) NULL",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    foreach ($columns as $column => $definition) {
        if (!erpAddColumnIfMissing($koneksi, 'pembayaran', $column, $definition)) {
            return false;
        }
    }
    return true;
}

function ensureShippingTable($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS pengiriman (
        id_pengiriman INT AUTO_INCREMENT PRIMARY KEY,
        id_order INT NOT NULL,
        kurir VARCHAR(100) NOT NULL DEFAULT '',
        nomor_resi VARCHAR(100) NOT NULL DEFAULT '',
        status_pengiriman ENUM('Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai','Gagal') NOT NULL DEFAULT 'Belum Diproses',
        tanggal_kirim DATETIME NULL,
        estimasi_sampai DATE NULL,
        catatan VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_pengiriman_order (id_order),
        KEY idx_resi (nomor_resi),
        KEY idx_status_pengiriman (status_pengiriman)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if (!mysqli_query($koneksi, $sql)) {
        return false;
    }

    $columns = [
        'kurir' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'nomor_resi' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'status_pengiriman' => "ENUM('Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai','Gagal') NOT NULL DEFAULT 'Belum Diproses'",
        'tanggal_kirim' => "DATETIME NULL",
        'estimasi_sampai' => "DATE NULL",
        'catatan' => "VARCHAR(500) NULL",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    foreach ($columns as $column => $definition) {
        if (!erpAddColumnIfMissing($koneksi, 'pengiriman', $column, $definition)) {
            return false;
        }
    }
    return true;
}

function ensureWishlistTable($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS wishlist (
        id_wishlist INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        id_product INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wishlist_user_product (id_user, id_product),
        KEY idx_wishlist_user (id_user),
        KEY idx_wishlist_product (id_product)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return mysqli_query($koneksi, $sql) !== false;
}

function ensureCategoryImageColumn($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id_category INT AUTO_INCREMENT PRIMARY KEY,
        nama_category VARCHAR(150) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($koneksi, $sql);
    return erpAddColumnIfMissing($koneksi, 'categories', 'gambar_category', "VARCHAR(255) NULL");
}

function ensureCrmTable($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS crm_interaksi (
        id_interaksi INT AUTO_INCREMENT PRIMARY KEY,
        id_user INT NOT NULL,
        jenis_interaksi VARCHAR(50) NOT NULL,
        catatan TEXT NULL,
        tanggal_interaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        dibuat_oleh VARCHAR(100) NULL,
        INDEX idx_crm_user (id_user),
        INDEX idx_crm_date (tanggal_interaksi)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return mysqli_query($koneksi, $sql) !== false;
}

?>
