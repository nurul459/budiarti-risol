<?php

include "auth.php";
include "../config/koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kategori.php?pesan=hapus_berhasil");
    exit;
}

$id = (int) $_GET['id'];

// Cek apakah kategori masih digunakan oleh produk
$cek_produk = mysqli_query(
    $koneksi,
    "SELECT id_product
     FROM products
     WHERE id_category = $id
     LIMIT 1"
);

if (mysqli_num_rows($cek_produk) > 0) {

    header("Location: kategori.php?pesan=digunakan");
    exit;
}

// Hapus kategori
$query = mysqli_query(
    $koneksi,
    "DELETE FROM categories
     WHERE id_category = $id"
);

if ($query) {

    header("Location: kategori.php");
    exit;

} else {

    header("Location: kategori.php?pesan=hapus_gagal");
    exit;

}

?>