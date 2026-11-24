-- Migrasi fitur ulasan produk
-- Aman dijalankan pada database yang sudah ada.
CREATE TABLE IF NOT EXISTS ulasan (
    id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
    id_product INT NOT NULL,
    id_user INT NOT NULL,
    rating TINYINT NOT NULL,
    komentar TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ulasan_product (id_product),
    INDEX idx_ulasan_user (id_user),
    CONSTRAINT chk_ulasan_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
