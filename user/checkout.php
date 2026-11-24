<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$cart_items = [];
$total_harga = 0;

foreach ($_SESSION['cart'] as $id_product => $jumlah) {
    $id_product = (int) $id_product;
    $jumlah = (int) $jumlah;

    if ($id_product <= 0 || $jumlah <= 0) {
        continue;
    }

    $query = mysqli_query($koneksi, "
        SELECT id_product, nama_product, harga, stok
        FROM products
        WHERE id_product = $id_product
    ");

    $produk = mysqli_fetch_assoc($query);

    if ($produk) {
        if ($jumlah > (int) $produk['stok']) {
            $jumlah = (int) $produk['stok'];
            $_SESSION['cart'][$id_product] = $jumlah;
        }

        if ($jumlah <= 0) {
            continue;
        }

        $produk['jumlah_beli'] = $jumlah;
        $produk['subtotal'] = (float) $produk['harga'] * $jumlah;

        $cart_items[] = $produk;
        $total_harga += $produk['subtotal'];
    }
}

if (!$cart_items) {
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .checkout-page {
            padding: 35px 7%;
        }

        .checkout-title {
            margin-bottom: 25px;
        }

        .checkout-title h1 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 8px;
        }

        .checkout-title p {
            color: #777;
        }

        .checkout-progress {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 35px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #aaa;
            font-size: 13px;
        }

        .step.active {
            color: #542D1C;
            font-weight: bold;
        }

        .step-number {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            background: #d47100;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .step.active .step-number {
            background: #542D1C;
        }

        .step-line {
            width: 70px;
            height: 2px;
            background: #ddd;
            margin: 0 15px;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
            align-items: start;
            color: #000;
        }

        .checkout-card,
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
            margin-bottom: 20px;
        }

        .checkout-card h2,
        .order-summary h2 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 20px;
        }

        .order-summary {
            height: fit-content;
            align-self: start;
        }

        .order-summary h2 {
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-family: Arial, sans-serif;
            outline: none;
            box-sizing: border-box;
        }

        .form-group textarea {
            height: 90px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #7a421d;
        }

        .summary-product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 9px 0;
            margin: 0;
            border: none;
            font-size: 13px;
        }

        .summary-product-name {
            font-weight: bold;
        }

        .summary-product-qty {
            color: #777;
            margin-top: 4px;
        }

        .summary-product-price {
            font-weight: bold;
            color: #542D1C;
            white-space: nowrap;
        }

        .summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e8dfd5;
    font-size: 16px;
    font-weight: bold;
    color: #542D1C;
}

        .continue-button {
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #542D1C;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .continue-button:hover {
            background: #6a3214;
        }

        .back-cart {
            display: block;
            text-align: center;
            margin-top: 13px;
            color: #6a3214;
            text-decoration: none;
            font-size: 13px;
        }

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
        }

        .save-address {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #555;
        }

        /* MENU AKUN */
        .nav-account {
            position: relative;
        }

        .account-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: 0;
            padding: 8px;
            cursor: pointer;
            color: white;
            font-size: 17px;
        }

        .account-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #a45b19;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
        }

        .account-arrow {
            color: white;
            font-size: 11px;
            transition: transform .25s;
        }

        .nav-account.open .account-arrow {
            transform: rotate(180deg);
        }

        .account-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 13px);
            right: 0;
            width: 255px;
            background: #F8EFE5;
            border: 1px solid #eadcc5;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(59,29,13,.16);
            padding: 10px;
            z-index: 10010;
        }

        .nav-account.open .account-dropdown {
            display: block;
            animation: dropdownShow .2s ease;
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
            padding: 10px 8px 13px;
        }

        .account-user-avatar {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #f4dfbd;
            color: #80420f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 17px;
            flex-shrink: 0;
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
            color: #8a7767;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-divider {
            height: 1px;
            background: #eee3d4;
            margin: 5px 2px;
        }

        .account-menu-link {
            display: flex !important;
            align-items: center;
            gap: 11px;
            padding: 12px;
            border-radius: 9px;
            text-decoration: none;
            color: #a45b19 !important;
            font-size: 13px !important;
            transition: .2s;
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
            color: #b34435 !important;
        }

        .account-menu-link.logout:hover {
            background: #fff0ed;
        }

        .account-login-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 20px;
            padding: 8px;
        }

        .nav-icons .account-dropdown .account-menu-link:hover {
            transform: none;
        }

        @media(max-width:850px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .checkout-progress {
                justify-content: flex-start;
                overflow-x: auto;
            }

            .step-line {
                width: 35px;
                margin: 0 7px;
            }

            .checkout-page {
                padding: 25px 5%;
            }

            .brand-group {
                margin-left: 0;
            }

            .account-dropdown {
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

<header class="navbar">

    <div class="brand-group">

        <a href="javascript:history.back()" class="back-circle" aria-label="Kembali">
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

    <div class="nav-icons">

        <a href="cart.php" aria-label="Keranjang">🛒</a>

        <?php if ($isLogin): ?>

            <div class="nav-account" id="navAccount">

                <button type="button"
                        class="account-trigger"
                        id="accountTrigger"
                        aria-label="Menu akun"
                        aria-expanded="false">

                    <span class="account-avatar">
                        <?= htmlspecialchars($inisial) ?>
                    </span>

                    <span class="account-arrow">▼</span>

                </button>

                <div class="account-dropdown">

                    <div class="account-user-info">

                        <div class="account-user-avatar">
                            <?= htmlspecialchars($inisial) ?>
                        </div>

                        <div class="account-user-text">
                            <strong><?= htmlspecialchars($nama_user) ?></strong>
                            <small><?= htmlspecialchars($email_user) ?></small>
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

            <a href="login.php" class="account-login-link" aria-label="Login">♙</a>

        <?php endif; ?>

    </div>

</header>

<main class="checkout-page">

    <div class="checkout-title">
        <h1>Checkout</h1>
        <p>Lengkapi informasi penerima untuk menyelesaikan pesanan kamu.</p>
    </div>

    <div class="checkout-progress">

        <div class="step active">
            <div class="step-number">1</div>
            <span>Alamat</span>
        </div>

        <div class="step-line"></div>

        <div class="step">
            <div class="step-number">2</div>
            <span>Pembayaran</span>
        </div>

        <div class="step-line"></div>

        <div class="step">
            <div class="step-number">3</div>
            <span>Konfirmasi</span>
        </div>

    </div>

    <div class="checkout-layout">

        <div class="checkout-card">

            <h2>Alamat Pengiriman</h2>

            <form action="payment.php" method="POST" id="checkout-form">

                <div class="form-row">

                    <div class="form-group">
                        <label>Nama Penerima *</label>

                        <input type="text"
                               name="nama_penerima"
                               required
                               value="<?= htmlspecialchars($_SESSION['checkout']['nama_penerima'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>No. HP *</label>

                        <input type="tel"
                               name="no_hp"
                               required
                               value="<?= htmlspecialchars($_SESSION['checkout']['no_hp'] ?? '') ?>"
                               placeholder="08xxxxxxxxxx">
                    </div>

                </div>

                <div class="form-group">
                    <label>Alamat Lengkap *</label>

                    <textarea name="alamat"
                              id="alamat"
                              required
                              placeholder="Nama jalan, nomor rumah, RT/RW, patokan"><?= htmlspecialchars($_SESSION['checkout']['alamat'] ?? '') ?></textarea>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>Provinsi *</label>

                        <select name="provinsi" id="provinsi" required>

                            <option value="">Pilih Provinsi</option>

                            <?php
                            $provinsiTerpilih = $_SESSION['checkout']['provinsi'] ?? '';

                            foreach (['Jawa Barat', 'DKI Jakarta'] as $prov):
                            ?>

                                <option value="<?= $prov ?>"
                                    <?= $provinsiTerpilih === $prov ? 'selected' : '' ?>>
                                    <?= $prov ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kota / Kabupaten *</label>

                        <input type="text"
                               name="kota"
                               id="kota"
                               required
                               value="<?= htmlspecialchars($_SESSION['checkout']['kota'] ?? '') ?>"
                               placeholder="Contoh: Depok">
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>Kecamatan *</label>

                        <input type="text"
                               name="kecamatan"
                               id="kecamatan"
                               required
                               value="<?= htmlspecialchars($_SESSION['checkout']['kecamatan'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Kode Pos *</label>

                        <input type="text"
                               name="kode_pos"
                               id="kode_pos"
                               required
                               value="<?= htmlspecialchars($_SESSION['checkout']['kode_pos'] ?? '') ?>">
                    </div>

                </div>

                <label class="save-address">
                    <input type="checkbox" name="simpan_alamat" value="1">
                    Simpan alamat untuk pemesanan berikutnya
                </label>

                <button type="submit" class="continue-button">
                    Lanjut ke Pembayaran →
                </button>

                <a href="cart.php" class="back-cart">
                    Kembali ke keranjang
                </a>

            </form>

        </div>

        <aside class="order-summary">

            <h2>Ringkasan Pesanan</h2>

            <?php foreach ($cart_items as $item): ?>

                <div class="summary-product">

                    <div>
                        <div class="summary-product-name">
                            <?= htmlspecialchars($item['nama_product']) ?>
                        </div>

                        <div class="summary-product-qty">
                            <?= (int) $item['jumlah_beli'] ?> pcs
                        </div>
                    </div>

                    <div class="summary-product-price">
                        Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                    </div>

                </div>

            <?php endforeach; ?>

            <div class="summary-total">
                <span>Total Harga</span>
                <span>Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
            </div>

        </aside>

    </div>

</main>

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