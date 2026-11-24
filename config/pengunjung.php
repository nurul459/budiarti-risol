<?php
/**
 * Pelacak pengunjung sederhana untuk website Budiarti Risol.
 * visitor_key dipakai untuk statistik pengunjung anonim, sedangkan id_user
 * dipakai untuk mengetahui kunjungan terakhir pelanggan yang sudah login.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function pengunjung_column_exists($koneksi, $column) {
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    $q = mysqli_query($koneksi, "SHOW COLUMNS FROM visitor_logs LIKE '$column'");
    return $q && mysqli_num_rows($q) > 0;
}

function init_tabel_pengunjung($koneksi)
{
    static $ready = false;
    if ($ready) return true;

    $created = mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS visitor_logs (
        id_visitor BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        visitor_key CHAR(64) NOT NULL,
        id_user INT NULL,
        halaman VARCHAR(100) NOT NULL,
        tanggal DATE NOT NULL,
        waktu_kunjungan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visitor_date (visitor_key, tanggal),
        INDEX idx_user_date (id_user, waktu_kunjungan),
        INDEX idx_date (tanggal),
        INDEX idx_page (halaman)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!$created) return false;

    $required = [
        'visitor_key' => "CHAR(64) NULL",
        'id_user' => "INT NULL",
        'halaman' => "VARCHAR(100) NULL",
        'tanggal' => "DATE NULL",
        'waktu_kunjungan' => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($required as $column => $definition) {
        if (!pengunjung_column_exists($koneksi, $column)) {
            if (!mysqli_query($koneksi, "ALTER TABLE visitor_logs ADD COLUMN `$column` $definition")) {
                return false;
            }
        }
    }

    @mysqli_query($koneksi, "UPDATE visitor_logs SET halaman='unknown' WHERE halaman IS NULL OR halaman='' ");
    @mysqli_query($koneksi, "UPDATE visitor_logs SET tanggal=DATE(COALESCE(waktu_kunjungan,NOW())) WHERE tanggal IS NULL");

    $ready = true;
    return true;
}

function catat_pengunjung($koneksi, $halaman = null)
{
    if (!init_tabel_pengunjung($koneksi)) return false;

    if ($halaman === null) {
        $halaman = basename($_SERVER['PHP_SELF'] ?? 'unknown');
    }

    if (empty($_SESSION['visitor_key'])) {
        $_SESSION['visitor_key'] = hash(
            'sha256',
            session_id() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['REMOTE_ADDR'] ?? '')
        );
    }

    $visitor_key = $_SESSION['visitor_key'];
    $id_user = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null;
    $tanggal = date('Y-m-d');

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO visitor_logs (visitor_key, id_user, halaman, tanggal, waktu_kunjungan)
         VALUES (?, ?, ?, ?, NOW())"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'siss', $visitor_key, $id_user, $halaman, $tanggal);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    return false;
}
?>
