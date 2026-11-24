
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

$MINIMAL_PROMO = 50000;

// Menerima data dari halaman checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_penerima'])) {

    $wajib = [
        'nama_penerima',
        'no_hp',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'kode_pos'
    ];

    foreach ($wajib as $field) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            die("Data checkout belum lengkap. Silakan kembali ke halaman checkout.");
        }
    }

    $_SESSION['checkout'] = [
        'nama_penerima' => trim($_POST['nama_penerima']),
        'no_hp' => trim($_POST['no_hp']),
        'alamat' => trim($_POST['alamat']),
        'provinsi' => trim($_POST['provinsi']),
        'kota' => trim($_POST['kota']),
        'kecamatan' => trim($_POST['kecamatan']),
        'kode_pos' => trim($_POST['kode_pos']),
        'kode_promo' => '',
        'diskon' => 0
    ];
}

if (empty($_SESSION['checkout'])) {
    header("Location: checkout.php");
    exit;
}

// Menghitung total harga produk dari database
$total_harga = 0;

foreach ($_SESSION['cart'] as $id_product => $jumlah) {

    $id_product = (int)$id_product;
    $jumlah = (int)$jumlah;

    if ($id_product <= 0 || $jumlah <= 0) {
        continue;
    }

    $q = mysqli_query(
        $koneksi,
        "SELECT harga FROM products WHERE id_product = $id_product"
    );

    $p = mysqli_fetch_assoc($q);

    if (!$p) {
        die("Produk dalam keranjang tidak ditemukan.");
    }

    $total_harga += (float)$p['harga'] * $jumlah;
}

// Proses kode promo
$pesanPromo = '';
$statusPromo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_promo'])) {

    $kode = strtoupper(
        preg_replace('/\s+/', '', trim($_POST['kode_promo'] ?? ''))
    );

    if ($kode === '') {

        $_SESSION['checkout']['kode_promo'] = '';
        $_SESSION['checkout']['diskon'] = 0;

        $pesanPromo = 'Masukkan kode promo terlebih dahulu.';
        $statusPromo = 'gagal';

    } elseif ($kode !== 'RISOL10') {

        $_SESSION['checkout']['kode_promo'] = '';
        $_SESSION['checkout']['diskon'] = 0;

        $pesanPromo = 'Kode promo tidak valid. Periksa kembali kode yang dimasukkan.';
        $statusPromo = 'gagal';

    } elseif ($total_harga < $MINIMAL_PROMO) {

        $_SESSION['checkout']['kode_promo'] = '';
        $_SESSION['checkout']['diskon'] = 0;

        $pesanPromo = 'Promo RISOL10 hanya berlaku untuk minimal pembelian Rp50.000.';
        $statusPromo = 'gagal';

    } else {

        $_SESSION['checkout']['kode_promo'] = 'RISOL10';

        $pesanPromo = 'Selamat! Promo RISOL10 berhasil digunakan. Kamu mendapatkan diskon 10%.';
        $statusPromo = 'berhasil';
    }
}

// Perhitungan diskon
$kode_promo = $_SESSION['checkout']['kode_promo'] ?? '';

if ($kode_promo === 'RISOL10' && $total_harga >= $MINIMAL_PROMO) {
    $diskon = (int)round($total_harga * 0.10);
} else {
    $kode_promo = '';
    $diskon = 0;
}

// Total pembayaran hanya harga produk dikurangi diskon
$total_pembayaran = max(0, $total_harga - $diskon);

// Simpan hasil perhitungan ke session
$_SESSION['checkout']['kode_promo'] = $kode_promo;
$_SESSION['checkout']['diskon'] = $diskon;
$_SESSION['checkout']['total_harga'] = $total_harga;
$_SESSION['checkout']['total_pembayaran'] = $total_pembayaran;

$checkout = $_SESSION['checkout'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .payment-page {
            padding: 35px 7%;
        }

        .payment-title {
            margin-bottom: 25px;
        }

        .payment-title h1 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 7px;
        }

        .payment-title p {
            color: #777;
        }

        /* INFORMASI PENGIRIMAN */
        .shipping-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 18px;
            margin: 0 7% 30px;
            background: #fff0ed;
            border: 1px solid #e8a39a;
            border-left: 4px solid #c0392b;
            border-radius: 9px;
            color: #a93226;
        }

        .notice-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .shipping-notice strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #a93226;
        }

        .shipping-notice p {
            margin: 0;
            font-size: 12px;
            line-height: 1.7;
            color: #87352d;
        }

        .payment-progress {
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

        .payment-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 25px;
            align-items: start;
            color: #000;
        }

        .payment-card,
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,.07);
            height: fit-content;
        }

        .payment-card h2,
        .summary-card h2 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 20px;
        }

        .payment-option {
            display: block;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .payment-option:hover {
            border-color: #8a5229;
        }

        .payment-option input {
            margin-right: 10px;
        }

        .payment-option-title {
            font-weight: bold;
            color: #542D1C;
        }

        .payment-option-desc {
            color: #777;
            font-size: 12px;
            margin-top: 6px;
            margin-left: 25px;
        }

        .qris-box-content {
            display: none;
            background: #fafafa;
            border: 1px dashed #d47100;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
        }

        .qris-box-content img {
            width: 180px;
            height: 180px;
            object-fit: contain;
            margin: 10px 0;
            border-radius: 8px;
            background: white;
            padding: 5px;
            border: 1px solid #eee;
        }

        .address-box {
            margin-top: 25px;
            padding: 18px;
            border-radius: 10px;
            background: #F8EFE5;
        }

        .address-box h3 {
            margin-bottom: 10px;
            color: #542D1C;
        }

        .address-box p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .summary-total {
            border-top: 1px solid #ddd;
            padding-top: 17px;
            margin-top: 17px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #542D1C;
        }

        .pay-button {
            width: 100%;
            margin-top: 22px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #542D1C;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .pay-button:hover {
            background: #6a3214;
        }

        .promo-form {
            display: flex;
            gap: 8px;
            margin: 10px 0 5px;
        }

        .promo-form input {
            min-width: 0;
            flex: 1;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 7px;
        }

        .promo-form button {
            border: 0;
            border-radius: 7px;
            padding: 0 14px;
            background: #d47100;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .promo-message {
            font-size: 12px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .promo-success {
            color: #247a35;
        }

        .promo-error {
            color: #c0392b;
        }

        .back-button {
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
            .payment-layout {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:600px) {
            .payment-page {
                padding: 25px 5%;
            }

            .step-line {
                width: 35px;
                margin: 0 7px;
            }

            .brand-group {
                margin-left: 0;
            }

            .account-dropdown {
                width: 245px;
            }

            .shipping-notice {
                margin: 0 5% 25px;
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

        <a href="checkout.php" class="back-circle" aria-label="Kembali">
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

<main class="payment-page">

    <div class="payment-title">
        <h1>Metode Pembayaran</h1>
        <p>Pilih metode pembayaran yang ingin digunakan.</p>
    </div>

    <div class="payment-progress">

        <div class="step">
            <div class="step-number">✓</div>
            <span>Alamat</span>
        </div>

        <div class="step-line"></div>

        <div class="step active">
            <div class="step-number">2</div>
            <span>Pembayaran</span>
        </div>

        <div class="step-line"></div>

        <div class="step">
            <div class="step-number">3</div>
            <span>Konfirmasi</span>
        </div>

    </div>

    <div class="payment-layout">

        <div class="payment-card">

            <h2>Pilih Metode Pembayaran</h2>

            <form action="payment.php" method="POST" class="promo-form">

                <input
                    type="text"
                    name="kode_promo"
                    placeholder="Masukkan kode promo"
                    value="<?= htmlspecialchars($kode_promo) ?>"
                    autocomplete="off"
                >

                <button type="submit" name="aksi_promo" value="1">
                    Pakai
                </button>

            </form>

            <?php if ($pesanPromo): ?>

                <p class="promo-message <?= $statusPromo === 'berhasil' ? 'promo-success' : 'promo-error' ?>">
                    <?= htmlspecialchars($pesanPromo) ?>
                </p>

            <?php elseif ($kode_promo): ?>

                <p class="promo-message promo-success">
                    Promo RISOL10 aktif. Diskon 10% berhasil diterapkan.
                </p>

            <?php else: ?>

                <p class="promo-message">
                    Gunakan kode RISOL10 untuk mendapatkan diskon 10% dengan minimal pembelian Rp50.000.
                </p>

            <?php endif; ?>

            <form action="confirm.php" method="POST">

                <label class="payment-option">

                    <input type="radio"
                           id="radio-qris"
                           name="metode"
                           value="Qris"
                           required>

                    <span class="payment-option-title">QRIS</span>

                    <div class="payment-option-desc">
                        BCA, BNI, BRI, Mandiri, GoPay, dan lainnya.
                    </div>

                </label>

                <div id="qris-container" class="qris-box-content">

                    <p style="font-weight:bold;color:#542D1C">
                        Scan QR Code QRIS
                    </p>

                    <p style="font-size:12px;color:#666">
                        Silakan scan QR Code berikut untuk melakukan pembayaran.
                    </p>

                    <img src="../assets/images/qr.jpg" alt="QRIS Budiarti Risol">

                    <div style="margin:10px">

                        <a href="../assets/images/qr.jpg"
                           download="QRIS_BudiartiRisol.jpg"
                           style="background:#542D1C;color:white;padding:9px 15px;border-radius:6px;text-decoration:none">

                            Simpan QR Code

                        </a>

                    </div>

                    <p style="font-weight:bold;color:#542D1C">
                        Total Bayar: Rp <?= number_format($total_pembayaran, 0, ',', '.') ?>
                    </p>

                    <p style="font-size:12px;color:#777;margin-top:8px">
                        Upload bukti pembayaran pada halaman berikutnya.
                    </p>

                </div>

                <label class="payment-option">

                    <input type="radio"
                           id="radio-cod"
                           name="metode"
                           value="COD">

                    <span class="payment-option-title">COD (Cash on Delivery)</span>

                    <div class="payment-option-desc">
                        Bayar di tempat saat pesanan tiba.
                    </div>

                </label>

                <div class="address-box">

                    <h3>Alamat Pengiriman</h3>

                    <p>
                        <strong><?= htmlspecialchars($checkout['nama_penerima']) ?></strong><br>

                        <?= htmlspecialchars($checkout['no_hp']) ?><br>

                        <?= htmlspecialchars($checkout['alamat']) ?>,
                        <?= htmlspecialchars($checkout['kecamatan']) ?>,
                        <?= htmlspecialchars($checkout['kota']) ?>,
                        <?= htmlspecialchars($checkout['provinsi']) ?>,
                        <?= htmlspecialchars($checkout['kode_pos']) ?>
                    </p>

                </div>

                <button type="submit" class="pay-button">
                    Lanjut ke Konfirmasi →
                </button>

            </form>

        </div>

        <aside class="summary-card">

            <h2>Ringkasan Pesanan</h2>

            <div class="summary-row">
                <span>Total Harga</span>
                <span>Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
            </div>

            <div class="summary-row">
                <span>Diskon <?= $kode_promo ? '(RISOL10)' : '' ?></span>

                <span style="color:<?= $diskon > 0 ? 'green' : 'inherit' ?>">
                    - Rp <?= number_format($diskon, 0, ',', '.') ?>
                </span>
            </div>

            <div class="summary-total">
                <span>Total Pembayaran</span>
                <span>Rp <?= number_format($total_pembayaran, 0, ',', '.') ?></span>
            </div>

        </aside>

    </div>

</main>

<!-- INFORMASI PENGIRIMAN DI BAGIAN PALING BAWAH -->
<div class="shipping-notice">
    <span class="notice-icon">⚠</span>
    <div>
        <strong>Informasi Pengiriman</strong>
        <p>
            Biaya pengiriman tidak termasuk dalam total pembayaran website.
            Pengaturan pengiriman dan biaya ongkir menjadi tanggung jawab pembeli.
            Budiarti Risol tidak mengatur maupun menanggung biaya pengiriman.
        </p>
    </div>
</div>

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
const radioQris = document.getElementById('radio-qris');
const radioCod = document.getElementById('radio-cod');
const qrisContainer = document.getElementById('qris-container');

function toggleQris() {
    qrisContainer.style.display = radioQris.checked ? 'block' : 'none';
}

radioQris.addEventListener('change', toggleQris);
radioCod.addEventListener('change', toggleQris);
toggleQris();

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
