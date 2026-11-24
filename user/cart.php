
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);
include "../config/ensure_erp_tables.php";

ensureWishlistTable($koneksi);

/* DATA AKUN */
$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

/* SESSION KERANJANG */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* FUNGSI GAMBAR */
function gambarProduk($nama_file)
{
    $nama_file = basename(trim($nama_file ?? ''));
    $folder = __DIR__ . "/../assets/images/";

    if (!empty($nama_file) && is_file($folder . $nama_file)) {
        return $nama_file;
    }

    return '';
}

/* TAMBAH PRODUK — WAJIB LOGIN */
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    if (!$isLogin) {
        header("Location: login.php");
        exit;
    }

    $id_product = (int) $_POST['id_product'];
    $jumlah = (int) $_POST['jumlah'];

    if ($jumlah < 1) {
        $jumlah = 1;
    }

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM products WHERE id_product = $id_product"
    );

    $produk = mysqli_fetch_assoc($query);

    if ($produk) {
        if (isset($_SESSION['cart'][$id_product])) {
            $_SESSION['cart'][$id_product] += $jumlah;
        } else {
            $_SESSION['cart'][$id_product] = $jumlah;
        }
    }

    header("Location: cart.php");
    exit;
}

/* HAPUS PRODUK */
if (isset($_GET['hapus'])) {
    $id_product = (int) $_GET['hapus'];
    unset($_SESSION['cart'][$id_product]);

    header("Location: cart.php");
    exit;
}

/* UPDATE JUMLAH */
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    foreach ($_POST['jumlah'] as $id_product => $jumlah) {
        $id_product = (int) $id_product;
        $jumlah = (int) $jumlah;

        if ($jumlah <= 0) {
            unset($_SESSION['cart'][$id_product]);
        } else {
            $_SESSION['cart'][$id_product] = $jumlah;
        }
    }

    header("Location: cart.php");
    exit;
}

/* MENGAMBIL PRODUK DALAM KERANJANG */
$cart_items = [];
$total_harga = 0;
$total_produk = 0;

foreach ($_SESSION['cart'] as $id_product => $jumlah) {
    $id_product = (int) $id_product;

    $query = mysqli_query(
        $koneksi,
        "SELECT * FROM products WHERE id_product = $id_product"
    );

    $produk = mysqli_fetch_assoc($query);

    if ($produk) {
        $produk['jumlah_beli'] = $jumlah;
        $produk['subtotal'] = $produk['harga'] * $jumlah;
        $produk['gambar_cart'] = gambarProduk($produk['gambar']);

        $cart_items[] = $produk;
        $total_harga += $produk['subtotal'];
        $total_produk += $jumlah;
    }
}

$total_bayar = $total_harga;

/* DATA WISHLIST UNTUK DITAMPILKAN DI HALAMAN KERANJANG */
$wishlist_items = [];
if ($isLogin) {
    $id_user = (int) $_SESSION['id_user'];

    $wishlist_query = mysqli_query($koneksi, "
        SELECT products.*, categories.nama_category
        FROM wishlist
        INNER JOIN products ON wishlist.id_product = products.id_product
        LEFT JOIN categories ON products.id_category = categories.id_category
        WHERE wishlist.id_user = $id_user
        ORDER BY wishlist.id_wishlist DESC
    ");

    if ($wishlist_query) {
        while ($wishlist_product = mysqli_fetch_assoc($wishlist_query)) {
            $wishlist_items[] = $wishlist_product;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Budiarti Risol</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .cart-page {
            padding: 40px 7%;
        }

        .cart-title {
            margin-bottom: 25px;
        }

        .cart-title h1 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 7px;
        }

        .cart-title p {
            color: #777;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 330px;
            gap: 25px;
        }

        /* DAFTAR PRODUK */
        .cart-products {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .cart-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 40px;
            gap: 15px;
            padding: 0 10px 15px;
            border-bottom: 1px solid #eee;
            color: #000;
            font-size: 13px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 40px;
            gap: 15px;
            align-items: center;
            padding: 18px 10px;
            border-bottom: 1px solid #ddd;
        }

        .cart-product {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-image {
            width: 75px;
            height: 65px;
            border-radius: 10px;
            overflow: hidden;
            background: #f3e4d5;
            flex-shrink: 0;
        }

        .cart-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
        }

        .cart-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 5px;
            color: #8a6b58;
            font-size: 10px;
        }

        .cart-product h3 {
            font-size: 15px;
            margin-bottom: 5px;
            color: #000;
        }

        .cart-product p {
            color: #000;
            font-size: 11px;
        }

        .cart-price {
            font-size: 13px;
        }

        .cart-quantity {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-quantity input {
            width: 45px;
            height: 32px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .cart-subtotal {
            font-weight: bold;
            color: #542D1C;
            font-size: 13px;
        }

        .delete-button {
            color: #b13b2e;
            text-decoration: none;
            font-size: 18px;
        }

        .update-button {
            margin-top: 20px;
            padding: 10px 20px;
            border: none;
            background: #eee;
            border-radius: 7px;
            cursor: pointer;
        }

        /* RINGKASAN */
        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            height: fit-content;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .cart-summary h2 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: #000;
            font-size: 14px;
        }

        .summary-total {
            border-top: 1px solid #ddd;
            padding-top: 18px;
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 18px;
            color: #542D1C;
        }

        .checkout-button {
            display: block;
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            background: #542D1C;
            color: white;
            border: none;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .continue-shopping {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #6a3214;
            text-decoration: none;
            font-size: 13px;
        }

        /* KERANJANG KOSONG */
        .empty-cart {
            background: white;
            border-radius: 15px;
            padding: 70px 30px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .empty-cart-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty-cart h2 {
            font-family: Georgia, serif;
            margin-bottom: 10px;
        }

        .empty-cart p {
            color: #777;
            margin-bottom: 20px;
        }

        /* NAVBAR */
        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: -35px;
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
            z-index: 9999;
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
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border-radius: 9px;
            color: #a45b19;
            text-decoration: none;
            font-size: 13px;
            transition: .2s ease;
        }

        .account-menu-link:hover {
            background: #fff2dc;
        }

        .account-menu-link .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .account-menu-link.logout {
            color: #b34435;
        }

        .account-menu-link.logout:hover {
            background: #fff0ed;
        }

        .account-login-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            color: inherit;
            text-decoration: none;
            font-size: 23px;
        }

        /* Menjaga dropdown tidak terkena style ikon navbar global */
        .nav-icons .account-dropdown .account-menu-link {
            width: auto;
            height: auto;
            justify-content: flex-start;
            font-size: 13px;
        }


        /* WISHLIST DI DALAM CART */
        .wishlist-section {
            margin-top: 35px;
            background: white;
            border-radius: 15px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
        }

        .wishlist-section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .wishlist-section-header h2 {
            margin: 0 0 5px;
            color: #542D1C;
            font-family: Georgia, serif;
        }

        .wishlist-section-header p {
            margin: 0;
            color: #777;
            font-size: 13px;
        }

        .wishlist-see-all {
            color: #8a4b18;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .wishlist-see-all:hover { text-decoration: underline; }

        .wishlist-cart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .wishlist-cart-card {
            border: 1px solid #eee2d1;
            border-radius: 13px;
            overflow: hidden;
            background: #fffdf8;
        }

        .wishlist-cart-image {
            height: 155px;
            background: #f3e4d5;
            overflow: hidden;
        }

        .wishlist-cart-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .wishlist-cart-info { padding: 15px; }

        .wishlist-cart-category {
            font-size: 11px;
            color: #9a6c4a;
            margin-bottom: 5px;
        }

        .wishlist-cart-info h3 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #542D1C;
        }

        .wishlist-cart-price {
            font-weight: bold;
            color: #542D1C;
            margin-bottom: 12px;
        }

        .wishlist-cart-actions {
            display: flex;
            gap: 8px;
        }

        .wishlist-cart-actions a,
        .wishlist-cart-actions button {
            flex: 1;
            min-height: 34px;
            border-radius: 8px;
            border: 1px solid #d9c3aa;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 11px;
            cursor: pointer;
            font-family: inherit;
        }

        .wishlist-cart-detail {
            color: #6f4326;
            background: #fff;
        }

        .wishlist-cart-add {
            background: #a45b19;
            color: #fff;
            border-color: #a45b19 !important;
        }

        .wishlist-cart-add:disabled {
            background: #ddd;
            border-color: #ddd !important;
            color: #888;
            cursor: not-allowed;
        }

        .wishlist-cart-remove {
            display: block;
            margin-top: 8px;
            text-align: center;
            color: #a94c3d;
            font-size: 11px;
            text-decoration: none;
        }

        .wishlist-cart-stock {
            display: inline-block;
            margin-bottom: 10px;
            padding: 4px 8px;
            border-radius: 20px;
            background: #fff1d7;
            color: #94601c;
            font-size: 10px;
        }

        .wishlist-cart-stock.empty {
            background: #fdeaea;
            color: #b83232;
        }

        .wishlist-login-note,
        .wishlist-cart-empty {
            padding: 25px;
            border: 1px dashed #dbc7ad;
            border-radius: 12px;
            text-align: center;
            color: #777;
            background: #fffdf8;
        }

        .wishlist-login-note a {
            color: #8a4b18;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .wishlist-cart-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .wishlist-section-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .wishlist-cart-grid {
                grid-template-columns: 1fr;
            }
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .cart-header {
                display: none;
            }

            .cart-item {
                grid-template-columns: 1fr;
            }

            .brand-group {
                margin-left: 0;
            }

            .account-dropdown {
                right: 0;
                width: 245px;
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

        <a href="cart.php" aria-label="Keranjang">🛒</a>

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
               aria-label="Login">♙</a>

        <?php endif; ?>

    </div>

</header>


<!-- KERANJANG BELANJA -->
<main class="cart-page">

    <div class="cart-title">
        <h1>Keranjang Belanja</h1>
        <p>Periksa kembali produk yang ingin kamu pesan.</p>
    </div>

    <?php if (count($cart_items) > 0): ?>

        <form method="POST">
            <input type="hidden" name="action" value="update">

            <div class="cart-layout">

                <!-- DAFTAR PRODUK -->
                <div class="cart-products">

                    <div class="cart-header">
                        <span>Produk</span>
                        <span>Harga</span>
                        <span>Jumlah</span>
                        <span>Subtotal</span>
                        <span></span>
                    </div>

                    <?php foreach ($cart_items as $item): ?>

                        <div class="cart-item">

                            <div class="cart-product">

                                <div class="cart-image">

                                    <?php if (!empty($item['gambar_cart'])): ?>

                                        <img
                                            src="../assets/images/<?= htmlspecialchars($item['gambar_cart'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?= htmlspecialchars($item['nama_product'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >

                                    <?php else: ?>

                                        <div class="cart-image-placeholder">
                                            Gambar belum tersedia
                                        </div>

                                    <?php endif; ?>

                                </div>

                                <div>
                                    <h3>
                                        <?= htmlspecialchars($item['nama_product'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars($item['deskripsi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                </div>

                            </div>

                            <div class="cart-price">
                                Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
                            </div>

                            <div class="cart-quantity">
                                <input
                                    type="number"
                                    name="jumlah[<?= $item['id_product']; ?>]"
                                    value="<?= $item['jumlah_beli']; ?>"
                                    min="1"
                                    max="<?= $item['stok']; ?>"
                                >
                            </div>

                            <div class="cart-subtotal">
                                Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?>
                            </div>

                            <a
                                href="cart.php?hapus=<?= $item['id_product']; ?>"
                                class="delete-button"
                                title="Hapus"
                            >×</a>

                        </div>

                    <?php endforeach; ?>

                    <button type="submit" class="update-button">
                        Update Keranjang
                    </button>

                </div>


                <!-- RINGKASAN BELANJA -->
                <aside class="cart-summary">

                    <h2>Ringkasan Belanja</h2>

                    <div class="summary-row">
                        <span>Total Produk</span>
                        <span><?= $total_produk; ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Harga Produk</span>
                        <span>
                            Rp <?= number_format($total_harga, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span>
                            Rp <?= number_format($total_bayar, 0, ',', '.'); ?>
                        </span>
                    </div>

                    <a href="checkout.php" class="checkout-button">
                        Checkout →
                    </a>

                    <a href="produk.php" class="continue-shopping">
                        ← Lanjut Belanja
                    </a>

                </aside>

            </div>
        </form>

    <?php else: ?>

        <!-- KERANJANG KOSONG -->
        <div class="empty-cart">

            <div class="empty-cart-icon">🛒</div>

            <h2>Keranjang Masih Kosong</h2>

            <p>
                Yuk pilih risol favorit kamu terlebih dahulu.
            </p>

            <a href="produk.php" class="btn-primary">
                Lihat Menu →
            </a>

        </div>

    <?php endif; ?>

    <!-- WISHLIST SEKALIGUS DI HALAMAN KERANJANG -->
    <section class="wishlist-section" id="wishlist">
        <div class="wishlist-section-header">
            <div>
                <h2>Wishlist Kamu </h2>
                <p>Produk yang kamu simpan, sekarang bisa langsung dilihat dari keranjang.</p>
            </div>
            <a href="produk.php" class="wishlist-see-all">Cari Produk Lain →</a>
        </div>

        <?php if (!$isLogin): ?>
            <div class="wishlist-login-note">
                Login terlebih dahulu untuk melihat dan menyimpan wishlist kamu.
                <a href="login.php">Login →</a>
            </div>
        <?php elseif (count($wishlist_items) > 0): ?>
            <div class="wishlist-cart-grid">
                <?php foreach ($wishlist_items as $wish): ?>
                    <?php
                    $wish_image = gambarProduk($wish['gambar'] ?? '');
                    $wish_stock = (int) ($wish['stok'] ?? 0);
                    ?>
                    <article class="wishlist-cart-card">
                        <div class="wishlist-cart-image">
                            <?php if ($wish_image !== ''): ?>
                                <img src="../assets/images/<?= htmlspecialchars($wish_image, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?= htmlspecialchars($wish['nama_product'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <div class="cart-image-placeholder">Gambar belum tersedia</div>
                            <?php endif; ?>
                        </div>

                        <div class="wishlist-cart-info">
                            <div class="wishlist-cart-category">
                                <?= htmlspecialchars($wish['nama_category'] ?? 'Produk', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <h3><?= htmlspecialchars($wish['nama_product'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="wishlist-cart-price">
                                Rp<?= number_format((float) $wish['harga'], 0, ',', '.'); ?>
                            </div>

                            <?php if ($wish_stock > 0): ?>
                                <span class="wishlist-cart-stock">Stok tersedia: <?= $wish_stock; ?></span>
                            <?php else: ?>
                                <span class="wishlist-cart-stock empty">Stok habis</span>
                            <?php endif; ?>

                            <div class="wishlist-cart-actions">
                                <a href="detail.php?id=<?= (int) $wish['id_product']; ?>" class="wishlist-cart-detail">Detail</a>
                                <form method="POST" style="flex:1;display:flex;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id_product" value="<?= (int) $wish['id_product']; ?>">
                                    <input type="hidden" name="jumlah" value="1">
                                    <button type="submit" class="wishlist-cart-add" <?= $wish_stock <= 0 ? 'disabled' : ''; ?>>
                                        <?= $wish_stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis'; ?>
                                    </button>
                                </form>
                            </div>

                            <a href="wishlist.php?hapus=<?= (int) $wish['id_product']; ?>"
                               class="wishlist-cart-remove"
                               onclick="return confirm('Hapus produk ini dari wishlist?')">
                                Hapus dari Wishlist
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="wishlist-cart-empty">
                <strong>Wishlist kamu masih kosong ♡</strong><br>
                <span>Simpan produk favorit dari halaman produk, nanti akan muncul di sini.</span>
            </div>
        <?php endif; ?>
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


<!-- JAVASCRIPT DROPDOWN AKUN -->
<script>
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
