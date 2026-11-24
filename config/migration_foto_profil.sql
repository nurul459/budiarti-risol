-- Jalankan sekali jika ingin menambahkan kolom foto profil secara manual.
-- profil.php juga akan membuat kolom ini otomatis bila belum tersedia.
ALTER TABLE users
ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL;
