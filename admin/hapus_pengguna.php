<?php
include "auth.php";
include "../config/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_user']) || !is_numeric($_POST['id_user'])) {
    header('Location: pengguna.php?pesan=hapus_gagal');
    exit;
}

$id_user = (int) $_POST['id_user'];

if ($id_user <= 0) {
    header('Location: pengguna.php?pesan=hapus_gagal');
    exit;
}

if ($id_user === (int) $_SESSION['id_user']) {
    header('Location: pengguna.php?pesan=hapus_diri_sendiri');
    exit;
}

// Jangan hapus akun yang masih memiliki pesanan agar riwayat transaksi tetap utuh.
$stmt = mysqli_prepare($koneksi, 'SELECT COUNT(*) FROM orders WHERE id_user = ?');
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $jumlah_pesanan);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ((int)$jumlah_pesanan > 0) {
    header('Location: pengguna.php?pesan=hapus_memiliki_pesanan');
    exit;
}

$stmt = mysqli_prepare($koneksi, 'DELETE FROM users WHERE id_user = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id_user);
$berhasil = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
mysqli_stmt_close($stmt);

header('Location: pengguna.php?pesan=' . ($berhasil ? 'hapus_berhasil' : 'hapus_gagal'));
exit;
