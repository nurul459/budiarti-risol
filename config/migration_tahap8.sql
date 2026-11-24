CREATE TABLE IF NOT EXISTS pengembalian_penukaran (
 id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
 id_order INT NOT NULL,
 id_user INT NOT NULL,
 jenis ENUM('Pengembalian','Penukaran') NOT NULL,
 alasan VARCHAR(100) NOT NULL,
 detail_alasan TEXT NULL,
 status ENUM('Diajukan','Diproses','Disetujui','Ditolak','Selesai') NOT NULL DEFAULT 'Diajukan',
 catatan_admin TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_pengajuan_order (id_order),
 KEY idx_pengajuan_user (id_user),
 KEY idx_pengajuan_status (status),
 CONSTRAINT fk_pengajuan_order FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT fk_pengajuan_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
