CREATE TABLE IF NOT EXISTS visitor_logs (
    id_visitor BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_key CHAR(64) NOT NULL,
    halaman VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    waktu_kunjungan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visitor_date (visitor_key, tanggal),
    INDEX idx_date (tanggal),
    INDEX idx_page (halaman)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
