
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

if (empty($_SESSION['checkout']) || !isset($_POST['metode'])) {
    header("Location: payment.php");
    exit;
}

$metode = $_POST['metode'];

if (!in_array($metode, ['Qris', 'COD'], true)) {
    header("Location: payment.php");
    exit;
}

$_SESSION['checkout']['metode'] = $metode;

$minimal_promo = 50000;
$total_harga = 0;

// Hitung ulang harga produk dari database
foreach ($_SESSION['cart'] as $id_product => $jumlah) {
    $id_product = (int)$id_product;
    $jumlah = (int)$jumlah;

    if ($id_product <= 0 || $jumlah <= 0) {
        die("Produk dalam keranjang tidak valid.");
    }

    $query = mysqli_query(
        $koneksi,
        "SELECT harga FROM products WHERE id_product = $id_product"
    );

    $produk = mysqli_fetch_assoc($query);

    if (!$produk) {
        die("Produk tidak ditemukan.");
    }

    $total_harga += (float)$produk['harga'] * $jumlah;
}

// Perhitungan promo
$kode_promo = strtoupper(
    preg_replace(
        '/\s+/',
        '',
        trim($_SESSION['checkout']['kode_promo'] ?? '')
    )
);

if ($kode_promo === 'RISOL10' && $total_harga >= $minimal_promo) {
    $diskon = (int)round($total_harga * 0.10);
} else {
    $kode_promo = '';
    $diskon = 0;
}

// Total tanpa ongkir
$total_pembayaran = max(0, $total_harga - $diskon);

// Simpan data checkout
$_SESSION['checkout']['kode_promo'] = $kode_promo;
$_SESSION['checkout']['total_harga'] = $total_harga;
$_SESSION['checkout']['diskon'] = $diskon;
$_SESSION['checkout']['total_pembayaran'] = $total_pembayaran;
$_SESSION['checkout']['metode'] = $metode;

// Hapus data ongkir dan jarak lama dari session
unset(
    $_SESSION['checkout']['ongkir'],
    $_SESSION['checkout']['jarak_km']
);

$checkout = $_SESSION['checkout'];

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Pesanan - Budiarti Risol</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
.confirm-page {
    padding: 40px 7%;
}

.confirm-title {
    text-align: center;
    margin-bottom: 30px;
}

.confirm-title h1 {
    font-family: Georgia, serif;
    color: #542D1C;
}

.confirm-title p {
    color: #777;
}

.confirm-container {
    max-width: 850px;
    margin: auto;
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 18px rgba(0,0,0,.08);
}

.success-icon {
    width: 70px;
    height: 70px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #e9f5e8;
    color: #3c8a3c;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 35px;
}

.confirm-section {
    border-bottom: 1px solid #eee;
    padding: 20px 0;
}

.confirm-section h3 {
    color: #542D1C;
    margin-bottom: 12px;
}

.confirm-section p {
    color: #666;
    line-height: 1.7;
    font-size: 14px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
    color: #555;
}

.confirm-total {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 20px;
    font-weight: bold;
    color: #542D1C;
    border-top: 1px solid #e8dfd5;
    padding-top: 17px;
    margin-top: 17px;
}

.upload-box {
    background: #F8EFE5;
    border: 1px dashed #b97842;
    border-radius: 10px;
    padding: 20px;
    margin-top: 15px;
}

.upload-box label {
    display: block;
    font-weight: bold;
    color: #542D1C;
    margin-bottom: 12px;
}

.upload-box input {
    width: 100%;
    padding: 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 7px;
}

.upload-info {
    color: #777;
    font-size: 12px;
    margin-top: 10px;
}

.preview-image {
    display: none;
    max-width: 220px;
    max-height: 220px;
    object-fit: contain;
    margin-top: 15px;
    border-radius: 8px;
}

.order-button {
    display: block;
    width: 100%;
    margin-top: 25px;
    padding: 15px;
    border: 0;
    border-radius: 8px;
    background: #542D1C;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: .3s;
}

.order-button:hover {
    background: #8a4b20;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 15px;
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

/* ACCOUNT DROPDOWN */
.navbar .nav-icons .nav-account {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.navbar .nav-icons .nav-account .account-trigger {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 8px !important;
    border: none !important;
    background: transparent !important;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}

.navbar .nav-icons .nav-account .account-avatar {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 50%;
    background: #a45b19;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: bold;
}

/* PANAH SEGITIGA */
.navbar .nav-icons .nav-account .account-arrow {
    display: inline-block !important;
    width: 0 !important;
    height: 0 !important;
    min-width: 0 !important;
    margin-left: 3px;
    padding: 0 !important;

    border-top: 6px solid #ffffff !important;
    border-right: 5px solid transparent !important;
    border-bottom: 0 !important;
    border-left: 5px solid transparent !important;

    background: transparent !important;
    color: transparent !important;
    font-size: 0 !important;
    line-height: 0 !important;

    transform: rotate(0deg);
    transform-origin: center;
    transition: transform .3s ease;
}

.navbar .nav-icons .nav-account.open .account-arrow {
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
    box-shadow: 0 8px 25px rgba(59,29,13,.16);
    padding: 10px;
    z-index: 10010;
}

.navbar .nav-icons .nav-account.open .account-dropdown {
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
    padding: 10px;
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
    font-weight: bold;
}

.account-user-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.account-user-text strong {
    color: #542D1C;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.account-user-text small {
    color: #888;
    font-size: 11px;
    overflow-wrap: anywhere;
}

.account-divider {
    height: 1px;
    background: #eadcc5;
    margin: 7px 4px;
}

.navbar .nav-icons .account-dropdown .account-menu-link {
    display: flex !important;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 9px;
    color: #a45b19 !important;
    font-size: 13px !important;
    text-decoration: none;
    transition: background .2s;
}

.navbar .nav-icons .account-dropdown .account-menu-link:hover {
    background: #fff2dc;
    transform: none;
}

.account-menu-link .menu-icon {
    width: 20px;
    text-align: center;
}

.navbar .nav-icons .account-dropdown .account-menu-link.logout {
    color: #b34435 !important;
}

.account-login-link {
    font-size: 25px;
    color: #542D1C;
    text-decoration: none;
}

@media(max-width:700px) {
    .confirm-page {
        padding: 25px 5%;
    }

    .confirm-container {
        padding: 20px;
    }

    .confirm-total {
        font-size: 16px;
    }

    .brand-group {
        margin-left: 0;
        gap: 8px;
    }

    .account-dropdown {
        right: -5px;
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
        <a href="payment.php" class="back-circle">&#8592;</a>

        <a class="brand" href="index.php">
            <span class="brand-mark">👩‍🍳</span>
            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>
    </div>

    <div class="nav-icons">

        <?php if ($isLogin): ?>
        <div class="nav-account" id="navAccount">
            <button
                type="button"
                class="account-trigger"
                id="accountTrigger"
                aria-label="Menu akun"
                aria-expanded="false"
            >
                <span class="account-avatar"><?= e($inisial) ?></span>
                <span class="account-arrow" aria-hidden="true"></span>
            </button>

            <div class="account-dropdown">
                <div class="account-user-info">
                    <div class="account-user-avatar"><?= e($inisial) ?></div>

                    <div class="account-user-text">
                        <strong><?= e($nama_user) ?></strong>
                        <small><?= e($email_user) ?></small>
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

                <a
                    href="logout.php"
                    class="account-menu-link logout"
                    onclick="return confirm('Apakah kamu yakin ingin keluar?')"
                >
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

<main class="confirm-page">

    <div class="confirm-title">
        <div class="success-icon">✓</div>
        <h1>Konfirmasi Pesanan</h1>
        <p>Periksa kembali data pesanan kamu sebelum melakukan pemesanan.</p>
    </div>

    <div class="confirm-container">

        <div class="confirm-section">
            <h3>Data Pengiriman</h3>

            <p>
                <strong><?= e($checkout['nama_penerima']) ?></strong><br>
                <?= e($checkout['no_hp']) ?><br>
                <?= e($checkout['alamat']) ?>,
                <?= e($checkout['kecamatan']) ?>,
                <?= e($checkout['kota']) ?>,
                <?= e($checkout['provinsi']) ?>
                <?= e($checkout['kode_pos']) ?>
            </p>
        </div>

        <div class="confirm-section">
            <h3>Metode Pembayaran</h3>
            <p><?= e($metode) ?></p>
        </div>

        <?php if ($metode === 'Qris'): ?>

        <div class="confirm-section">
            <h3>Upload Bukti Pembayaran</h3>

            <p>
                Silakan upload bukti transfer setelah melakukan pembayaran melalui QRIS.
            </p>

            <div class="upload-box">
                <label for="bukti_pembayaran">Pilih Foto Bukti Transfer</label>

                <input
                    type="file"
                    id="bukti_pembayaran"
                    accept="image/jpeg,image/png,image/webp"
                    form="form-pesanan"
                    name="bukti_pembayaran"
                    required
                >

                <p class="upload-info">
                    Format JPG, JPEG, PNG, atau WEBP. Maksimal ukuran 2 MB.
                </p>

                <img
                    id="preview"
                    class="preview-image"
                    alt="Preview bukti pembayaran"
                >
            </div>
        </div>

        <?php else: ?>

        <div class="confirm-section">
            <h3>Pembayaran COD</h3>

            <p>
                Pembayaran dilakukan secara tunai ketika pesanan diterima.
                Kamu tidak perlu mengunggah bukti pembayaran.
            </p>
        </div>

        <?php endif; ?>

        <div class="confirm-section">

            <div class="total-row">
                <span>Total Harga Produk</span>
                <strong>Rp <?= number_format($total_harga, 0, ',', '.') ?></strong>
            </div>

            <div class="total-row">
                <span>
                    Diskon <?= $kode_promo ? '(' . e($kode_promo) . ')' : '' ?>
                </span>

                <strong style="color:green">
                    - Rp <?= number_format($diskon, 0, ',', '.') ?>
                </strong>
            </div>

            <div class="confirm-total">
                <span>Total Pembayaran</span>
                <span>Rp <?= number_format($total_pembayaran, 0, ',', '.') ?></span>
            </div>

        </div>

        <form
            action="process_order.php"
            method="POST"
            enctype="multipart/form-data"
            id="form-pesanan"
        >
            <button type="submit" class="order-button">
                Buat Pesanan
            </button>
        </form>

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
const inputBukti = document.getElementById('bukti_pembayaran');
const preview = document.getElementById('preview');

if (inputBukti) {
    inputBukti.addEventListener('change', function() {
        const file = this.files[0];

        if (!file) {
            preview.style.display = 'none';
            preview.removeAttribute('src');
            return;
        }

        const tipeValid = ['image/jpeg', 'image/png', 'image/webp'];

        if (!tipeValid.includes(file.type)) {
            alert('Format gambar harus JPG, PNG, atau WEBP.');
            this.value = '';
            preview.style.display = 'none';
            preview.removeAttribute('src');
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran gambar maksimal 2 MB.');
            this.value = '';
            preview.style.display = 'none';
            preview.removeAttribute('src');
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    });
}

// Dropdown akun
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
