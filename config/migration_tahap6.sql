-- Tahap 6: Manajemen Pengiriman
CREATE TABLE IF NOT EXISTS pengiriman (
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
    KEY idx_status_pengiriman (status_pengiriman),
    CONSTRAINT fk_pengiriman_order FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
