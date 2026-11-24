-- Tahap 10: CRM
CREATE TABLE IF NOT EXISTS crm_interaksi (
    id_interaksi INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    jenis_interaksi VARCHAR(50) NOT NULL,
    catatan TEXT NULL,
    tanggal_interaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dibuat_oleh VARCHAR(100) NULL,
    INDEX idx_crm_user (id_user),
    INDEX idx_crm_date (tanggal_interaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tahap 11: Gambar Kategori
ALTER TABLE categories ADD COLUMN gambar_category VARCHAR(255) NULL;
