
<?php

include "auth.php";
include "../config/koneksi.php";

/* Pastikan permintaan berasal dari form POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan.php");
    exit;
}

/* Validasi data yang dikirim */
$id_order = filter_input(INPUT_POST, 'id_order', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

/* Status yang diperbolehkan */
$status_valid = [
    'Menunggu Pembayaran',
    'Menunggu Verifikasi',
    'Diproses',
    'Dikirim',
    'Selesai'
];

if (
    !$id_order ||
    $id_order < 1 ||
    !in_array($status, $status_valid, true)
) {
    header("Location: pesanan.php");
    exit;
}

/*
 * Update status saja.
 * Jangan mengurangi stok di sini karena stok sudah
 * dikurangi ketika pesanan berhasil dibuat.
 */
$stmt = mysqli_prepare(
    $koneksi,
    "UPDATE orders
     SET status = ?
     WHERE id_order = ?"
);

if (!$stmt) {
    echo "Gagal menyiapkan perubahan status pesanan.";
    exit;
}

mysqli_stmt_bind_param($stmt, "si", $status, $id_order);

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header("Location: detail_pesanan.php?id=" . $id_order);
    exit;

} else {

    mysqli_stmt_close($stmt);

    echo "Gagal mengubah status pesanan.";
    exit;
}

?>