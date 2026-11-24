
<?php
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* DATA AKUN */
$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

/* CEK ID PRODUK */
if (!isset($_GET['id'])) {
    header("Location: produk.php");
    exit;
}

$id_product = (int) $_GET['id'];

/* MENGAMBIL DATA PRODUK */
$query = mysqli_query(
    $koneksi,
    "SELECT products.*, categories.nama_category
     FROM products
     JOIN categories
     ON products.id_category = categories.id_category
     WHERE products.id_product = $id_product"
);

$produk = mysqli_fetch_assoc($query);

if (!$produk) {
    echo "Produk tidak ditemukan.";
    exit;
}

// Pastikan tabel ulasan tersedia tanpa menghapus data lama.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS ulasan (
    id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
    id_product INT NOT NULL,
    id_user INT NOT NULL,
    rating TINYINT NOT NULL,
    komentar TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ulasan_product (id_product),
    INDEX idx_ulasan_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* PROSES SIMPAN ULASAN */
$pesanUlasan = "";

if (isset($_POST['kirim_ulasan'])) {

    if (!$isLogin) {
        header("Location: login.php");
        exit;
    }

    $id_user = (int) $_SESSION['id_user'];
    $rating = (int) ($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $pesanUlasan = "Silakan pilih rating bintang 1 sampai 5.";
    } elseif ($komentar === "") {
        $pesanUlasan = "Komentar tidak boleh kosong.";
    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO ulasan
            (id_product, id_user, rating, komentar)
            VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iiis",
            $id_product,
            $id_user,
            $rating,
            $komentar
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            header("Location: detail.php?id=" . $id_product . "#konten-ulasan");
            exit;
        } else {
            $pesanUlasan = "Ulasan gagal dikirim. Silakan coba lagi.";
        }

        mysqli_stmt_close($stmt);
    }
}

/* MENGAMBIL DATA ULASAN */
$queryUlasan = mysqli_query(
    $koneksi,
    "SELECT ulasan.*, users.nama
     FROM ulasan
     JOIN users ON ulasan.id_user = users.id_user
     WHERE ulasan.id_product = $id_product
     ORDER BY ulasan.created_at DESC"
);

/* MENGHITUNG RATA-RATA RATING */
$queryRating = mysqli_query(
    $koneksi,
    "SELECT AVG(rating) AS rata_rating,
            COUNT(*) AS jumlah_ulasan
     FROM ulasan
     WHERE id_product = $id_product"
);

$dataRating = mysqli_fetch_assoc($queryRating);

$rataRating = $dataRating['rata_rating']
    ? number_format($dataRating['rata_rating'], 1)
    : "0.0";

$jumlahUlasan = (int) $dataRating['jumlah_ulasan'];

/* MENENTUKAN KOMPOSISI PRODUK */
$namaProduk = strtolower($produk['nama_product']);

if (strpos($namaProduk, 'mayo') !== false) {
    $komposisi = 'Kulit risol, mayones, isian sesuai resep, dan bumbu pilihan.';
} elseif (strpos($namaProduk, 'ayam') !== false) {
    $komposisi = 'Kulit risol, daging ayam, bumbu pilihan, dan bahan pelengkap.';
} elseif (strpos($namaProduk, 'matcha') !== false) {
    $komposisi = 'Kulit risol, isian rasa matcha, dan bahan pelengkap sesuai resep.';
} elseif (
    strpos($namaProduk, 'coklat') !== false ||
    strpos($namaProduk, 'cokelat') !== false
) {
    $komposisi = 'Kulit risol, isian cokelat, dan bahan pelengkap sesuai resep.';
} elseif (strpos($namaProduk, 'keju') !== false) {
    $komposisi = 'Kulit risol, keju, dan bumbu pilihan.';
} elseif (strpos($namaProduk, 'pisang') !== false) {
    $komposisi = 'Kulit risol, pisang, cokelat, dan bahan pelengkap.';
} elseif (strpos($namaProduk, 'sayur') !== false) {
    $komposisi = 'Kulit risol, sayuran, dan bumbu pilihan.';
} elseif (strpos($namaProduk, 'beef') !== false) {
    $komposisi = 'Kulit risol, daging sapi, dan bumbu pilihan.';
} elseif (strpos($namaProduk, 'paket') !== false) {
    $komposisi = 'Paket kombinasi pilihan risol berkualitas tinggi buatan Budiarti Risol.';
} else {
    $komposisi = $produk['deskripsi'];
}

/* MAPPING GAMBAR PRODUK */
$namaProdukGambar = trim($produk['nama_product']);

$daftarGambar = [
    'Risol Matcha' => 'matchaa.jpeg',
    'Risol Keju' => 'kejuu.jpeg',
    'Risol Pisang Coklat' => 'piscok.jpeg',
    'Risol Coklat' => 'chocooo.jpg',
    'Risol Cokelat' => 'chocooo.jpg',
    'Risol Sayur' => 'sayur.jpg',
    'Risol Beef' => 'beef.jpg',
    'Risol Ayam' => 'ayam.jpg',
    'Risol Mayo' => 'mayo.jpg',
    'Paket Hemat 1' => 'pakethemat1.jpeg',
    'Paket Hemat 2' => 'pakethemat2.jpeg',
    'Paket Hemat 3' => 'paketramai.jpeg',
    'Paket Ramai' => 'paketramai.jpeg'
];

$folderGambar = __DIR__ . '/../assets/images/';
$urlGambar = '../assets/images/';
$fileGambar = '';

/* UTAMAKAN GAMBAR DARI DATABASE */
$gambarDatabase = basename(trim($produk['gambar'] ?? ''));

if (
    $gambarDatabase !== '' &&
    is_file($folderGambar . $gambarDatabase)
) {
    $fileGambar = $gambarDatabase;
} elseif (isset($daftarGambar[$namaProdukGambar])) {

    $namaFile = $daftarGambar[$namaProdukGambar];

    if (is_file($folderGambar . $namaFile)) {
        $fileGambar = $namaFile;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($produk['nama_product']); ?> - Budiarti Risol
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        /* HALAMAN DETAIL */

        .detail-page {
            padding: 45px 7%;
        }

        .breadcrumb {
            margin-bottom: 25px;
            color: #777;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #d47100;
            text-decoration: none;
        }

        .detail-container {
            background: white;
            border-radius: 18px;
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 45px;
            box-shadow: 0 4px 18px rgba(0,0,0,.08);
        }

        /* FOTO PRODUK */

        .detail-image {
            width: 100%;
            height: 557px;
            border-radius: 18px;
            overflow: hidden;
            background: #fff5e8;
        }

        .detail-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .detail-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8a6b58;
            background: #fff5e8;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }

        /* INFORMASI PRODUK */

        .detail-info {
            padding: 10px 0;
        }

        .detail-category {
            color: #d47100;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .detail-info h1 {
            font-family: Georgia, serif;
            font-size: 36px;
            color: #542D1C;
            margin-bottom: 10px;
        }

        .rating {
            color: #e7a624;
            margin-bottom: 18px;
        }

        .rating span {
            color: #777;
            font-size: 13px;
        }

        .detail-price {
            font-size: 30px;
            font-weight: bold;
            color: #5b2810;
            margin-bottom: 18px;
        }

        .detail-description {
            color: #000;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .option-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .variant-options {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .variant {
            padding: 10px 18px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
        }

        .variant.active {
            background: #542D1C;
            color: white;
            border-color: #542D1C;
        }

        /* JUMLAH PRODUK */

        .quantity-area {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quantity {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .quantity button {
            width: 40px;
            height: 40px;
            border: none;
            background: #f4f0eb;
            font-size: 18px;
            cursor: pointer;
        }

        .quantity input {
            width: 45px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 16px;
        }

        .cart-button {
            width: 100%;
            border: none;
            background: #542D1C;
            color: white;
            padding: 15px;
            border-radius: 9px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .cart-button:hover {
            background: #6a3214;
        }

        .cart-button:disabled {
            background: #999;
            cursor: not-allowed;
        }

        /* FITUR PRODUK */

        .product-features {
            margin-top: 25px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .feature {
            text-align: center;
            padding: 18px 10px;
            background: #4A2115;
            border-radius: 10px;
        }

        .feature-icon {
            font-size: 25px;
            margin-bottom: 7px;
        }

        .feature h4 {
            font-size: 13px;
            margin-bottom: 4px;
            color: #fff;
        }

        .feature p {
            color: #fff;
            font-size: 11px;
        }

        /* BAGIAN TAB */

        .detail-tabs {
            margin-top: 35px;
            background: white;
            border-radius: 15px;
            padding: 30px;
            color: #000;
            min-height: 180px;
        }

        .tabs {
            position: relative;
            display: flex;
            align-items: center;
            gap: 35px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 25px;
        }

        .tab {
            display: inline-block;
            padding-bottom: 13px;
            font-weight: bold;
            color: #777;
            text-decoration: none;
            cursor: pointer;
            transition: color .25s ease;
        }

        .tab.active {
            color: #5b2810;
        }

        /* GARIS BAWAH BERGERAK */

        .tab-indicator {
            position: absolute;
            bottom: -1px;
            left: 0;
            height: 3px;
            width: 0;
            background: #5b2810;
            border-radius: 5px 5px 0 0;
            transition:
                transform .35s ease,
                width .35s ease;
            pointer-events: none;
        }

        /* KONTEN TAB */

        .tab-content {
            display: none;
            animation: fadeTab .3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeTab {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .detail-tabs p {
            color: #000;
            line-height: 1.7;
        }

        /* ULASAN */

        .ulasan-section {
            margin-top: 5px;
        }

        .ulasan-section h3 {
            color: #542D1C;
            margin-bottom: 15px;
        }

        .ringkasan-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .bintang-ringkasan {
            color: #e7a624;
            font-size: 20px;
        }

        /* FORM ULASAN */

        .form-ulasan {
            background: #fffaf3;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .form-ulasan label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            color: #542D1C;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
            margin-bottom: 18px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 32px;
            color: #ccc;
            cursor: pointer;
            margin: 0;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #e7a624;
        }

        .form-ulasan textarea {
            width: 100%;
            min-height: 110px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: vertical;
            font-family: Arial, sans-serif;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-ulasan button {
            margin-top: 12px;
            padding: 12px 22px;
            background: #542D1C;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .form-ulasan button:hover {
            background: #6a3214;
        }

        .login-ulasan {
            background: #fffaf3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            line-height: 1.7;
        }

        .login-ulasan a {
            color: #d47100;
            font-weight: bold;
            text-decoration: none;
        }

        .pesan-ulasan {
            color: #b33a20;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .daftar-ulasan {
            margin-top: 20px;
        }

        .item-ulasan {
            padding: 18px 0;
            border-bottom: 1px solid #eee;
        }

        .item-ulasan strong {
            color: #542D1C;
        }

        .item-ulasan .bintang {
            color: #e7a624;
            margin: 5px 0;
        }

        .item-ulasan p {
            color: #555;
            line-height: 1.6;
        }

        .item-ulasan small {
            color: #999;
        }

        /* NAVBAR */

        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: -35px;
            flex-shrink: 0;
        }

        .back-circle {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff98a;
            color: #542D1C;
            font-size: 17px;
            text-decoration: none;
            transition: .2s ease;
        }

        .back-circle:hover {
            background: #EBD3C0;
        }

        /* AKUN PROFIL */

        .nav-account {
            position: relative;
        }

        .account-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            font-size: 17px;
            color: inherit;
        }

        .account-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #a45b19;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: bold;
        }

        .account-arrow {
            font-size: 11px;
            transition: transform .25s ease;
        }

        .nav-account.open .account-arrow {
            transform: rotate(180deg);
        }

        .account-dropdown {
            position: absolute;
            top: calc(100% + 13px);
            right: 0;
            width: 255px;
            background: #F8EFE5;
            border: 1px solid #eadcc5;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(59,29,13,.16);
            padding: 10px;
            z-index: 99999;
            display: none;
            animation: dropdownShow .2s ease;
        }

        .nav-account.open .account-dropdown {
            display: block;
        }

        @keyframes dropdownShow {
            from {
                opacity: 0;
                transform: translateY(-7px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .account-user-info {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 8px;
        }

        .account-user-avatar {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #f4dfbd;
            color: #80420f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: bold;
        }

        .account-user-text {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .account-user-text strong {
            color: #542D1C;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-user-text small {
            color: #927d6c;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-divider {
            height: 1px;
            background: #eee2d1;
            margin: 7px 4px;
        }

        .account-menu-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 11px !important;
            width: auto !important;
            height: auto !important;
            padding: 12px !important;
            border-radius: 9px;
            color: #a45b19 !important;
            text-decoration: none !important;
            font-size: 13px !important;
            transition: .2s ease;
        }

        .account-menu-link:hover {
            background: #fff2dc;
            transform: none !important;
        }

        .account-menu-link .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .account-menu-link.logout {
            color: #b34435 !important;
        }

        .account-menu-link.logout:hover {
            background: #fff0ed;
        }

        .account-login-link {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            color: white !important;
            text-decoration: none;
            font-size: 23px;
        }

        /* RESPONSIVE */

        @media (max-width: 800px) {

            .detail-container {
                grid-template-columns: 1fr;
            }

            .detail-image {
                height: 400px;
            }

            .product-features {
                grid-template-columns: 1fr;
            }

            .detail-info h1 {
                font-size: 29px;
            }

            .brand-group {
                margin-left: 0;
            }

            .detail-page {
                padding: 30px 5%;
            }

            .detail-tabs {
                padding: 20px;
            }

            .tabs {
                gap: 25px;
            }
        }

        @media (max-width: 500px) {
            .account-dropdown {
                width: 245px;
            }

            .detail-image {
                height: 300px;
            }

            .tabs {
                gap: 18px;
            }

            .tab {
                font-size: 13px;
            }
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

<!-- NAVBAR -->

<header class="navbar">

    <div class="brand-group">

        <a href="javascript:history.back()"
           class="back-circle"
           title="Kembali ke halaman sebelumnya"
           aria-label="Kembali">
            &#8592;
        </a>

        <a class="brand" href="index.php">
            <span class="brand-mark">👩‍🍳</span>

            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>

    </div>

    <div class="nav-icons" aria-label="Menu cepat">

        <a href="cart.php" aria-label="Keranjang" title="Keranjang">🛒</a>

        <?php if ($isLogin): ?>

            <div class="nav-account" id="navAccount">

                <button type="button"
                        class="account-trigger"
                        id="accountTrigger"
                        aria-label="Menu akun"
                        aria-expanded="false">

                    <span class="account-avatar">
                        <?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <span class="account-arrow">▼</span>

                </button>

                <div class="account-dropdown">

                    <div class="account-user-info">

                        <div class="account-user-avatar">
                            <?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <div class="account-user-text">

                            <strong>
                                <?= htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?>
                            </strong>

                            <small>
                                <?= htmlspecialchars($email_user, ENT_QUOTES, 'UTF-8'); ?>
                            </small>

                        </div>

                    </div>

                    <div class="account-divider"></div>

                    <a href="profil.php" class="account-menu-link">
                        <span class="menu-icon">👤</span>
                        Profil Saya
                    </a>

                    <a href="orders.php" class="account-menu-link">
                        <span class="menu-icon">📦</span>
                        Pesanan Saya
                    </a>

                    <a href="cart.php#wishlist" class="account-menu-link">
                        <span class="menu-icon">♡</span>
                        Wishlist Saya
                    </a>

                    <div class="account-divider"></div>

                    <a href="logout.php"
                       class="account-menu-link logout"
                       onclick="return confirm('Apakah kamu yakin ingin keluar?')">

                        <span class="menu-icon">↪</span>
                        Logout

                    </a>

                </div>

            </div>

        <?php else: ?>

            <a href="login.php"
               class="account-login-link"
               aria-label="Login"
               title="Login">♙</a>

        <?php endif; ?>

    </div>

</header>


<!-- DETAIL PRODUK -->

<main class="detail-page">

    <div class="breadcrumb">

        <a href="index.php">Beranda</a>

        &nbsp; / &nbsp;

        <a href="produk.php">Menu</a>

        &nbsp; / &nbsp;

        <?= htmlspecialchars($produk['nama_product']); ?>

    </div>

    <div class="detail-container">

        <!-- FOTO PRODUK -->

        <div class="detail-image">

            <?php if (!empty($fileGambar)): ?>

                <img
                    src="<?= htmlspecialchars($urlGambar . $fileGambar, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?= htmlspecialchars($produk['nama_product'], ENT_QUOTES, 'UTF-8'); ?>"
                >

            <?php else: ?>

                <div class="detail-image-placeholder">
                    Gambar produk belum tersedia.
                </div>

            <?php endif; ?>

        </div>


        <!-- INFORMASI PRODUK -->

        <div class="detail-info">

            <div class="detail-category">
                <?= htmlspecialchars($produk['nama_category']); ?>
            </div>

            <h1>
                <?= htmlspecialchars($produk['nama_product']); ?>
            </h1>

            <div class="rating">

                <?php
                $intRating = (int) round((float) $rataRating);

                echo str_repeat("★", $intRating);
                echo str_repeat("☆", 5 - $intRating);
                ?>

                <span>
                    <?= $rataRating; ?> (<?= $jumlahUlasan; ?> Ulasan)
                </span>

            </div>

            <div class="detail-price">
                Rp <?= number_format($produk['harga'], 0, ',', '.'); ?>
            </div>

            <p class="detail-description">
                <?= htmlspecialchars($produk['deskripsi']); ?>
                Cocok untuk camilan kapan saja.
            </p>

            <div class="option-title">Varian</div>

            <div class="variant-options">
                <button class="variant active" type="button">
                    Original
                </button>
            </div>

            <div class="option-title">Jumlah</div>

            <div class="quantity-area">

                <div class="quantity">

                    <button type="button" onclick="kurang()">−</button>

                    <input
                        type="text"
                        id="jumlah"
                        value="0"
                        readonly
                    >

                    <button type="button" onclick="tambah()">+</button>

                </div>

                <span>
                    Stok tersedia:
                    <?= (int) $produk['stok']; ?>
                </span>

            </div>

            <form
                action="cart.php"
                method="POST"
                onsubmit="return validasiJumlah();"
            >

                <input type="hidden" name="action" value="add">

                <input
                    type="hidden"
                    name="id_product"
                    value="<?= (int) $produk['id_product']; ?>"
                >

                <input
                    type="hidden"
                    name="jumlah"
                    id="jumlah_hidden"
                    value="0"
                >

                <button
                    class="cart-button"
                    type="submit"
                    <?= (int) $produk['stok'] <= 0 ? 'disabled' : ''; ?>
                >
                    🛒 Tambah ke Keranjang
                </button>

            </form>

        </div>

    </div>


    <!-- FITUR PRODUK -->

    <div class="product-features">

        <div class="feature">
            <div class="feature-icon">🛡️</div>
            <h4>Bahan Pilihan</h4>
            <p>Bahan berkualitas</p>
        </div>

        <div class="feature">
            <div class="feature-icon">✦</div>
            <h4>Rasa Lezat</h4>
            <p>Dibuat dengan rasa terbaik</p>
        </div>

        <div class="feature">
            <div class="feature-icon">🚚</div>
            <h4>Pengiriman Cepat</h4>
            <p>Siap dikirim</p>
        </div>

    </div>


    <!-- DETAIL PRODUK DAN ULASAN -->

    <section class="detail-tabs" id="bagian-ulasan">

        <div class="tabs" id="productTabs">

            <a
                class="tab active"
                href="#detail-produk"
                data-tab="detail-produk"
            >
                Detail Produk
            </a>

            <a
                class="tab"
                href="#konten-ulasan"
                data-tab="konten-ulasan"
            >
                Ulasan (<?= $jumlahUlasan; ?>)
            </a>

            <span class="tab-indicator" id="tabIndicator"></span>

        </div>


        <!-- KONTEN DETAIL PRODUK -->

        <div class="tab-content active" id="detail-produk">

            <p>
                <strong>Berat Bersih:</strong>
                50 gram/pcs
            </p>

            <p>
                <strong>Komposisi:</strong>
                <?= htmlspecialchars($komposisi); ?>
            </p>

            <p>
                <strong>Cocok untuk:</strong>
                Camilan keluarga dan teman minum.
            </p>

        </div>


        <!-- KONTEN ULASAN -->

        <div class="tab-content" id="konten-ulasan">

            <div class="ulasan-section">

                <h3>Ulasan Pelanggan</h3>

                <p class="ringkasan-rating">

                    <strong><?= $rataRating; ?> / 5</strong>

                    <span class="bintang-ringkasan">

                        <?php
                        $intRatingRingkasan = (int) round((float) $rataRating);

                        echo str_repeat("★", $intRatingRingkasan);
                        echo str_repeat("☆", 5 - $intRatingRingkasan);
                        ?>

                    </span>

                    <span>(<?= $jumlahUlasan; ?> ulasan)</span>

                </p>


                <?php if ($isLogin): ?>

                    <form
                        method="POST"
                        action="detail.php?id=<?= (int) $id_product; ?>#konten-ulasan"
                        class="form-ulasan"
                    >

                        <label>Berikan Rating</label>

                        <div class="rating-input">

                            <?php for ($i = 5; $i >= 1; $i--): ?>

                                <input
                                    type="radio"
                                    name="rating"
                                    id="bintang<?= $i; ?>"
                                    value="<?= $i; ?>"
                                    required
                                >

                                <label for="bintang<?= $i; ?>">★</label>

                            <?php endfor; ?>

                        </div>

                        <label for="komentar">Tulis Komentar</label>

                        <textarea
                            name="komentar"
                            id="komentar"
                            placeholder="Bagaimana pendapat kamu tentang produk ini?"
                            required
                        ></textarea>

                        <?php if (!empty($pesanUlasan)): ?>

                            <p class="pesan-ulasan">
                                <?= htmlspecialchars($pesanUlasan); ?>
                            </p>

                        <?php endif; ?>

                        <button type="submit" name="kirim_ulasan">
                            Kirim Ulasan
                        </button>

                    </form>

                <?php else: ?>

                    <div class="login-ulasan">

                        <p>
                            Silakan login terlebih dahulu untuk memberikan
                            rating dan komentar terhadap produk ini.
                        </p>

                        <a href="login.php">
                            Login untuk Memberi Ulasan
                        </a>

                    </div>

                <?php endif; ?>


                <!-- DAFTAR ULASAN -->

                <div class="daftar-ulasan">

                    <h3>Ulasan Pengguna</h3>

                    <?php if ($queryUlasan && mysqli_num_rows($queryUlasan) > 0): ?>

                        <?php while ($ulasan = mysqli_fetch_assoc($queryUlasan)): ?>

                            <article class="item-ulasan">

                                <strong>
                                    <?= htmlspecialchars($ulasan['nama']); ?>
                                </strong>

                                <div class="bintang">

                                    <?php
                                    $intUlasanRating = (int) $ulasan['rating'];

                                    echo str_repeat("★", $intUlasanRating);
                                    echo str_repeat("☆", 5 - $intUlasanRating);
                                    ?>

                                </div>

                                <p>
                                    <?= nl2br(htmlspecialchars($ulasan['komentar'])); ?>
                                </p>

                                <small>
                                    <?= date("d-m-Y H:i", strtotime($ulasan['created_at'])); ?>
                                </small>

                            </article>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <p>
                            Belum ada ulasan untuk produk ini.
                            Jadilah yang pertama memberikan ulasan!
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

</main>


<!-- FOOTER -->

<footer class="footer">

    <div class="footer-sprig"></div>

    <h2>Budiarti Risol</h2>

    <p>Risol homemade dengan rasa yang dibuat sepenuh hati.</p>

    <div class="footer-links">
        <a href="syarat.php">Syarat dan Ketentuan</a>
        &nbsp; | &nbsp;
        <a href="privasi.php">Kebijakan Privasi</a>
    </div>

    <span class="footer-line"></span>

    <small>© 2026 Budiarti Risol</small>

</footer>

<script>

/* TAMBAH JUMLAH PRODUK */

function tambah() {

    let jumlah = document.getElementById("jumlah");
    let nilai = parseInt(jumlah.value);

    let stok = <?= (int) $produk['stok']; ?>;

    if (nilai < stok) {
        jumlah.value = nilai + 1;
    }

}

/* KURANGI JUMLAH PRODUK */

function kurang() {

    let jumlah = document.getElementById("jumlah");
    let nilai = parseInt(jumlah.value);

    if (nilai > 0) {
        jumlah.value = nilai - 1;
    }

}

/* VALIDASI KERANJANG */

function validasiJumlah() {

    let jumlah = parseInt(document.getElementById("jumlah").value);
    let stok = <?= (int) $produk['stok']; ?>;

    if (jumlah < 1) {
        alert("Pilih jumlah produk terlebih dahulu.");
        return false;
    }

    if (jumlah > stok) {
        alert("Jumlah melebihi stok yang tersedia.");
        return false;
    }

    document.getElementById("jumlah_hidden").value = jumlah;

    return true;
}


/* SISTEM TAB DETAIL PRODUK DAN ULASAN */

document.addEventListener("DOMContentLoaded", function () {

    const tabs = document.querySelectorAll("#productTabs .tab");
    const indicator = document.getElementById("tabIndicator");
    const contents = document.querySelectorAll(".tab-content");

    if (!tabs.length || !indicator) {
        return;
    }

    function aktifkanTab(tab, ubahHash = true) {

        if (!tab) return;

        const targetId = tab.dataset.tab;
        const targetContent = document.getElementById(targetId);

        if (!targetContent) return;

        tabs.forEach(function (item) {
            item.classList.remove("active");
        });

        tab.classList.add("active");

        contents.forEach(function (content) {
            content.classList.remove("active");
        });

        targetContent.classList.add("active");

        indicator.style.width = tab.offsetWidth + "px";

        indicator.style.transform =
            "translateX(" + tab.offsetLeft + "px)";

        if (ubahHash) {
            history.replaceState(null, "", "#" + targetId);
        }
    }

    tabs.forEach(function (tab) {

        tab.addEventListener("click", function (event) {

            event.preventDefault();

            aktifkanTab(tab);

        });

    });

    /* MENENTUKAN TAB AWAL */

    const hash = window.location.hash;

    let tabAwal = tabs[0];

    if (
        hash === "#konten-ulasan" ||
        hash === "#bagian-ulasan"
    ) {

        tabAwal = document.querySelector(
            '#productTabs .tab[data-tab="konten-ulasan"]'
        );

    }

    aktifkanTab(tabAwal, false);

    /* RESPONSIVE */

    window.addEventListener("resize", function () {

        const tabAktif = document.querySelector(
            "#productTabs .tab.active"
        );

        if (tabAktif) {

            indicator.style.width = tabAktif.offsetWidth + "px";

            indicator.style.transform =
                "translateX(" + tabAktif.offsetLeft + "px)";

        }

    });

});


/* DROPDOWN AKUN */

const account = document.getElementById('navAccount');
const trigger = document.getElementById('accountTrigger');

if (account && trigger) {

    trigger.addEventListener('click', function(event) {

        event.stopPropagation();

        account.classList.toggle('open');

        trigger.setAttribute(
            'aria-expanded',
            account.classList.contains('open') ? 'true' : 'false'
        );

    });

    document.addEventListener('click', function(event) {

        if (!account.contains(event.target)) {

            account.classList.remove('open');

            trigger.setAttribute('aria-expanded', 'false');

        }

    });

    document.addEventListener('keydown', function(event) {

        if (event.key === 'Escape') {

            account.classList.remove('open');

            trigger.setAttribute('aria-expanded', 'false');

        }

    });

}

</script>

</body>
</html>
