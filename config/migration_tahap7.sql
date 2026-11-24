CREATE TABLE IF NOT EXISTS pembayaran (
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
    KEY idx_pembayaran_status (status_pembayaran),
    CONSTRAINT fk_pembayaran_order FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
