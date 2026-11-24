<?php

session_start();
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";

/* PESAN ERROR */
function tampilkanError($pesan)
{
    global $file_bukti_server;

    if (!empty($file_bukti_server) && file_exists($file_bukti_server)) {
        unlink($file_bukti_server);
    }

    http_response_code(400);

    echo '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pemesanan Gagal</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            body {
                background: #F8EFE5;
                font-family: Arial, sans-serif;
                padding: 40px 20px;
            }
            .error-box {
                max-width: 550px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 15px;
                text-align: center;
            }
            .error-box h1 {
                color: #542D1C;
            }
            .error-box p {
                color: #555;
                line-height: 1.6;
            }
            .error-box a {
                display: inline-block;
                margin-top: 15px;
                padding: 12px 22px;
                background: #542D1C;
                color: white;
                text-decoration: none;
                border-radius: 8px;
            }
        </style>
    
<style id="budiarti-brand-palette">
:root{--br-cream:#F8EFE5;--br-brown:#542D1C;--br-soft:#EBD3C0;--br-accent:#C99B78;--br-white:#FFFFFF;--br-text-muted:#80634F;--br-border:#E5D3C3;}
body{background-color:var(--br-cream);color:var(--br-brown);}
.admin-wrapper .sidebar,.sidebar,.side{background-color:var(--br-brown);}
.admin-wrapper .top-header,.main-content>.top-header{background-color:var(--br-white);color:var(--br-brown);border-bottom:1px solid var(--br-border);}
.navbar,header.navbar,.site-header{background-color:var(--br-brown);}
.card,.stat-card,.content-card,.table-container,.form-card,.panel,.account-dropdown{border-color:var(--br-border);}
button.btn-primary,.btn-primary,.button-primary,.add-button,.btn-tambah{background-color:var(--br-brown);color:#fff;border-color:var(--br-brown);}
button.btn-secondary,.btn-secondary,.button-secondary{background-color:var(--br-accent);color:var(--br-brown);}
input,select,textarea{border-color:var(--br-border);}
input:focus,select:focus,textarea:focus{border-color:var(--br-accent);outline-color:var(--br-accent);}
</style>
</head>
    <body>
        <div class="error-box">
            <h1>Pemesanan Belum Berhasil</h1>
            <p>' . htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') . '</p>
            <a href="cart.php">Kembali ke Keranjang</a>
        </div>
    </body>
    </html>';

    exit;
}

$file_bukti_server = null;

/* CEK LOGIN */
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

if ($id_user <= 0) {
    header("Location: login.php");
    exit;
}

/* CEK KERANJANG */
if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit;
}

/* CEK CHECKOUT */
if (
    !isset($_SESSION['checkout']) ||
    !is_array($_SESSION['checkout'])
) {
    header("Location: checkout.php");
    exit;
}

$checkout = $_SESSION['checkout'];

$kolom_checkout = [
    'nama_penerima',
    'no_hp',
    'alamat',
    'provinsi',
    'kota',
    'kecamatan',
    'kode_pos',
    'metode'
];

foreach ($kolom_checkout as $kolom) {

    if (
        !isset($checkout[$kolom]) ||
        trim((string) $checkout[$kolom]) === ''
    ) {
        tampilkanError("Data checkout belum lengkap.");
    }

}

/* DATA CHECKOUT */
$nama_penerima = trim($checkout['nama_penerima']);
$no_hp = trim($checkout['no_hp']);
$alamat = trim($checkout['alamat']);
$provinsi = trim($checkout['provinsi']);
$kota = trim($checkout['kota']);
$kecamatan = trim($checkout['kecamatan']);
$kode_pos = trim($checkout['kode_pos']);
$metode_pembayaran = trim($checkout['metode']);

if (!in_array($metode_pembayaran, ['Qris', 'Transfer Bank', 'COD'], true)) {
    tampilkanError("Metode pembayaran tidak valid.");
}

/* VALIDASI KERANJANG */
$keranjang = [];

foreach ($_SESSION['cart'] as $id_product => $jumlah) {

    $id_product = filter_var($id_product, FILTER_VALIDATE_INT);
    $jumlah = filter_var($jumlah, FILTER_VALIDATE_INT);

    if (
        $id_product === false ||
        $id_product <= 0 ||
        $jumlah === false ||
        $jumlah <= 0
    ) {
        tampilkanError("Data keranjang tidak valid.");
    }

    $keranjang[$id_product] = $jumlah;
}

/* UPLOAD BUKTI PEMBAYARAN */
$bukti_pembayaran = null;

if (in_array($metode_pembayaran, ['Qris', 'Transfer Bank'], true)) {

    if (
        !isset($_FILES['bukti_pembayaran']) ||
        $_FILES['bukti_pembayaran']['error'] !== UPLOAD_ERR_OK
    ) {
        tampilkanError("Silakan upload bukti pembayaran terlebih dahulu.");
    }

    $file = $_FILES['bukti_pembayaran'];

    if ($file['size'] > 2 * 1024 * 1024) {
        tampilkanError("Ukuran bukti pembayaran maksimal 2 MB.");
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        tampilkanError("File bukti pembayaran tidak valid.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $format_gambar = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($format_gambar[$mime])) {
        tampilkanError("Format file harus JPG, PNG, atau WEBP.");
    }

    $folder_upload = __DIR__ . "/../assets/uploads/bukti_pembayaran/";

    if (
        !is_dir($folder_upload) &&
        !mkdir($folder_upload, 0755, true)
    ) {
        tampilkanError("Folder upload tidak dapat dibuat.");
    }

    $nama_file = 'bukti_' . bin2hex(random_bytes(12)) .
                 '.' . $format_gambar[$mime];

    $file_bukti_server = $folder_upload . $nama_file;

    if (!move_uploaded_file($file['tmp_name'], $file_bukti_server)) {
        tampilkanError("Bukti pembayaran gagal diunggah.");
    }

    $bukti_pembayaran = 'bukti_pembayaran/' . $nama_file;
}

/* STATUS PESANAN */
// Semua pesanan baru menunggu verifikasi admin, termasuk COD.
// Setelah admin memverifikasi pembayaran di admin/pembayaran.php,
// status pembayaran akan berubah menjadi Terverifikasi dan pesanan dapat diproses.
$status_pesanan = 'Menunggu Verifikasi';

/* MULAI TRANSAKSI */
if (!mysqli_begin_transaction($koneksi)) {
    tampilkanError("Sistem gagal memulai transaksi.");
}

try {

    $daftar_produk = [];
    $total_harga = 0;

    /* CEK PRODUK DAN STOK */
    $stmt_produk = mysqli_prepare(
        $koneksi,
        "SELECT id_product, nama_product, harga, stok
         FROM products
         WHERE id_product = ?
         FOR UPDATE"
    );

    if (!$stmt_produk) {
        throw new Exception("Gagal memeriksa produk.");
    }

    foreach ($keranjang as $id_product => $jumlah) {

        mysqli_stmt_bind_param(
            $stmt_produk,
            "i",
            $id_product
        );

        mysqli_stmt_execute($stmt_produk);

        $hasil = mysqli_stmt_get_result($stmt_produk);
        $produk = mysqli_fetch_assoc($hasil);

        if (!$produk) {
            throw new Exception("Produk tidak ditemukan.");
        }

        $stok = (int) $produk['stok'];

        if ($stok < $jumlah) {
            throw new Exception(
                "Stok produk " . $produk['nama_product'] .
                " tidak mencukupi. Stok tersedia: " . $stok
            );
        }

        $harga = (float) $produk['harga'];

        if (!is_finite($harga) || $harga < 0) {
            throw new Exception("Harga produk tidak valid.");
        }

        $subtotal = $harga * $jumlah;
        $total_harga += $subtotal;

        $daftar_produk[$id_product] = [
            'id_product' => $id_product,
            'harga' => $harga,
            'jumlah' => $jumlah,
            'subtotal' => $subtotal
        ];
    }

    mysqli_stmt_close($stmt_produk);

    /* HITUNG TOTAL */
    $ongkir = 7000;
    $total_pembayaran = $total_harga + $ongkir;

    /* SIMPAN PESANAN UTAMA */
    $stmt_order = mysqli_prepare(
        $koneksi,
        "INSERT INTO orders (
            id_user,
            nama_penerima,
            no_hp,
            alamat,
            provinsi,
            kota,
            kecamatan,
            kode_pos,
            metode_pembayaran,
            total_harga,
            ongkir,
            total_pembayaran,
            status,
            bukti_pembayaran
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt_order) {
        throw new Exception("Gagal menyiapkan data pesanan.");
    }

    mysqli_stmt_bind_param(
        $stmt_order,
        "issssssssdddss",
        $id_user,
        $nama_penerima,
        $no_hp,
        $alamat,
        $provinsi,
        $kota,
        $kecamatan,
        $kode_pos,
        $metode_pembayaran,
        $total_harga,
        $ongkir,
        $total_pembayaran,
        $status_pesanan,
        $bukti_pembayaran
    );

    if (!mysqli_stmt_execute($stmt_order)) {
        throw new Exception("Pesanan gagal disimpan.");
    }

    $id_order = mysqli_insert_id($koneksi);

    // Catat pembayaran ke modul pembayaran agar transaksi dapat dikelola admin.
    if (!ensurePaymentTable($koneksi)) {
        throw new Exception("Tabel pembayaran belum siap.");
    }
    $status_pembayaran_awal = 'Menunggu Verifikasi';
    $stmt_payment = mysqli_prepare($koneksi, "INSERT INTO pembayaran (id_order, metode_pembayaran, jumlah, status_pembayaran, bukti_pembayaran) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_payment) {
        throw new Exception("Gagal menyiapkan data pembayaran.");
    }
    mysqli_stmt_bind_param($stmt_payment, "isdss", $id_order, $metode_pembayaran, $total_pembayaran, $status_pembayaran_awal, $bukti_pembayaran);
    if (!mysqli_stmt_execute($stmt_payment)) {
        throw new Exception("Data pembayaran gagal disimpan.");
    }
    mysqli_stmt_close($stmt_payment);

    mysqli_stmt_close($stmt_order);

    /* SIMPAN DETAIL PESANAN */
    $stmt_detail = mysqli_prepare(
        $koneksi,
        "INSERT INTO order_details (
            id_order,
            id_product,
            jumlah,
            harga,
            subtotal
        ) VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt_detail) {
        throw new Exception("Gagal menyiapkan detail pesanan.");
    }

    foreach ($daftar_produk as $produk) {

        $id_product = $produk['id_product'];
        $jumlah = $produk['jumlah'];
        $harga = $produk['harga'];
        $subtotal = $produk['subtotal'];

        mysqli_stmt_bind_param(
            $stmt_detail,
            "iiidd",
            $id_order,
            $id_product,
            $jumlah,
            $harga,
            $subtotal
        );

        if (!mysqli_stmt_execute($stmt_detail)) {
            throw new Exception("Detail pesanan gagal disimpan.");
        }
    }

    mysqli_stmt_close($stmt_detail);

    /* KURANGI STOK */
    $stmt_stok = mysqli_prepare(
        $koneksi,
        "UPDATE products
         SET stok = stok - ?
         WHERE id_product = ?
         AND stok >= ?"
    );

    if (!$stmt_stok) {
        throw new Exception("Gagal memperbarui stok.");
    }

    foreach ($daftar_produk as $produk) {

        $id_product = $produk['id_product'];
        $jumlah = $produk['jumlah'];

        mysqli_stmt_bind_param(
            $stmt_stok,
            "iii",
            $jumlah,
            $id_product,
            $jumlah
        );

        if (!mysqli_stmt_execute($stmt_stok)) {
            throw new Exception("Stok gagal diperbarui.");
        }

        if (mysqli_stmt_affected_rows($stmt_stok) !== 1) {
            throw new Exception("Stok produk tidak mencukupi.");
        }
    }

    mysqli_stmt_close($stmt_stok);

    /* COMMIT */
    if (!mysqli_commit($koneksi)) {
        throw new Exception("Transaksi gagal diselesaikan.");
    }

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    tampilkanError($e->getMessage());
}

/* SIMPAN ID PESANAN */
$_SESSION['order_success'] = $id_order;

/* KOSONGKAN KERANJANG */
unset($_SESSION['cart']);
unset($_SESSION['checkout']);

/* KE HALAMAN SUKSES */
header("Location: success.php?id=" . $id_order);
exit;

?>