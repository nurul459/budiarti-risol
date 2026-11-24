<?php

$koneksi = mysqli_connect(
    "localhost",
    "root",
    "",
    "budiarti_risol"
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

?>