-- Tahap 3: Pengaturan Website
-- Jalankan jika ingin menambahkan kolom secara manual sebelum membuka admin/pengaturan.php.
ALTER TABLE settings ADD COLUMN logo VARCHAR(255) NULL AFTER alamat;
ALTER TABLE settings ADD COLUMN favicon VARCHAR(255) NULL AFTER logo;
