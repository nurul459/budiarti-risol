
<?php
session_start();
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";

ensureWishlistTable($koneksi);

// Pastikan user login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$id_product = (int) ($_GET['id'] ?? 0);

if ($id_product <= 0) {
    header("Location: produk.php");
    exit;
}

// Periksa produk
$cek = mysqli_query($koneksi, "
    SELECT id_product
    FROM products
    WHERE id_product = $id_product
");

if (!$cek || mysqli_num_rows($cek) === 0) {
    header("Location: produk.php");
    exit;
}

// Periksa apakah produk sudah ada di wishlist
$cek_wishlist = mysqli_query($koneksi, "
    SELECT id_wishlist
    FROM wishlist
    WHERE id_user = $id_user
    AND id_product = $id_product
");

if (!$cek_wishlist) {
    die("Gagal memeriksa wishlist: " . mysqli_error($koneksi));
}

if (mysqli_num_rows($cek_wishlist) > 0) {

    // Jika sudah ada, hapus
    mysqli_query($koneksi, "
        DELETE FROM wishlist
        WHERE id_user = $id_user
        AND id_product = $id_product
    ");

} else {

    // Jika belum ada, tambahkan
    mysqli_query($koneksi, "
        INSERT INTO wishlist (id_user, id_product)
        VALUES ($id_user, $id_product)
    ");
}

// Kembali ke halaman menu
header("Location: produk.php");
exit;
?>
