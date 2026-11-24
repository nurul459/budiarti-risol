<?php

include "auth.php";
include "../config/koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php");
    exit;
}

$id = (int) $_GET['id'];

$query = mysqli_query(
    $koneksi,
    "DELETE FROM products
     WHERE id_product = $id"
);

if ($query) {

    header("Location: produk.php");
    exit;

} else {

    echo "Produk tidak dapat dihapus karena masih digunakan dalam data pesanan.";
    echo "<br><br>";
    echo "<a href='produk.php'>Kembali ke Produk</a>";

}

?>