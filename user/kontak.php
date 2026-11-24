
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

/* DATA AKUN */
$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

/* AMBIL DATA TOKO */
$query_toko = mysqli_query($koneksi, "SELECT * FROM settings LIMIT 1");
$data_toko = $query_toko ? mysqli_fetch_assoc($query_toko) : null;

/* NOMOR WHATSAPP BUDIARTI RISOL */
$no_hp = '0856-7806-654';

$alamat = !empty($data_toko['alamat'])
    ? $data_toko['alamat']
    : 'Alamat UMKM Budiarti Risol';

$email = 'budiartirisol@gmail.com';

/* FORMAT NOMOR WHATSAPP */
$wa_number = preg_replace('/[^0-9]/', '', $no_hp);

if (substr($wa_number, 0, 1) === '0') {
    $wa_number = '62' . substr($wa_number, 1);
}

$wa_link = "https://wa.me/" . $wa_number;

/* LINK GMAIL */
$gmail_link = "https://mail.google.com/mail/?view=cm&fs=1&to="
    . urlencode($email)
    . "&su=" . urlencode("Pertanyaan Budiarti Risol");

/* LINK GOOGLE MAPS */
$maps_link = "https://www.google.com/maps/search/?api=1&query="
    . urlencode($alamat);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #F8EFE5;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .contact-page {
            padding: 50px 6% 80px;
        }

        .contact-container {
            max-width: 1150px;
            margin: auto;
        }

        .contact-heading {
            margin-bottom: 35px;
        }

        .contact-heading span {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #b4772d;
        }

        .contact-heading h1 {
            font-family: Georgia, serif;
            font-size: 38px;
            color: #542D1C;
            margin: 8px 0;
        }

        .contact-heading p {
            color: #000000;
        }

        .contact-card-wrapper {
            background: white;
            border-radius: 18px;
            padding: 45px 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,.07);
        }

        .contact-intro {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 45px;
        }

        .contact-intro h2 {
            font-family: Georgia, serif;
            font-size: 30px;
            color: #542D1C;
            margin-bottom: 12px;
        }

        .contact-intro p {
            color: #000000;
            line-height: 1.7;
        }

        .contact-grid-page {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            align-items: stretch;
            margin-bottom: 40px;
        }

        .contact-item {
            background: #F8EFE5;
            border: 1px solid #f0e2d4;
            border-radius: 15px;
            padding: 35px 25px;
            text-align: center;
            transition: .3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 230px;
        }

        .contact-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0,0,0,.07);
        }

        .contact-item-content {
            margin-bottom: 20px;
        }

        .contact-item-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #EBD3C0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .contact-item h3 {
            color: #542D1C;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .contact-item p {
            color: #777;
            line-height: 1.6;
            font-size: 14px;
            margin: 0;
            word-break: break-word;
        }

        .contact-action {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #68300f, #a95120);
            color: white;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: .3s;
        }

        .contact-action:hover {
            background: #68300f;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(104,48,15,.2);
        }

        .email-address {
            color: #777;
            text-decoration: none;
            transition: .2s;
        }

        .email-address:hover {
            color: #a95120;
            text-decoration: underline;
        }

        .contact-message {
            padding: 35px 30px;
            border-radius: 15px;
            background: #542D1C;
            color: white;
            text-align: center;
        }

        .contact-message h3 {
            font-family: Georgia, serif;
            font-size: 25px;
            margin-bottom: 10px;
        }

        .contact-message p {
            color: #f5e9df;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .contact-button {
            display: inline-block;
            padding: 12px 24px;
            background: #EBD3C0;
            color: #542D1C;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            transition: .2s;
        }

        .contact-button:hover {
            background: #e9b957;
            transform: translateY(-2px);
        }

        /* DROPDOWN AKUN */

        .nav-account {
            position: relative;
        }

        .account-trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-family: inherit;
            font-size: 17px;
            padding: 8px;
        }

        .account-trigger:hover {
            opacity: .75;
        }

        .account-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #a45b19;
            color: white;
            font-size: 15px;
            font-weight: bold;
        }

        .account-arrow {
            font-size: 11px;
            transition: transform .2s;
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
            box-shadow: 0 12px 35px rgba(59, 29, 13, .16);
            padding: 10px;
            z-index: 9999;
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
            padding: 13px 12px;
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .account-user-avatar {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            background: #f4dfbd;
            color: #80420f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            font-weight: bold;
        }

        .account-user-text {
            min-width: 0;
        }

        .account-user-text strong {
            display: block;
            color: #542D1C;
            font-size: 14px;
            margin-bottom: 4px;
            overflow-wrap: anywhere;
        }

        .account-user-text small {
            display: block;
            color: #92765e;
            font-size: 11px;
            overflow-wrap: anywhere;
        }

        .account-divider {
            height: 1px;
            background: #eee3d2;
            margin: 5px 3px;
        }

        .nav-icons .account-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 9px;
            text-decoration: none;
            background: transparent;
            color: #60432e;
            font-size: 13px;
            transition: .2s;
        }

        .nav-icons .account-menu-link:hover {
            background: #fff2dc;
            color: #a45b19;
        }

        .account-menu-link .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .nav-icons .account-menu-link.logout {
            color: #b34435;
        }

        .nav-icons .account-menu-link.logout:hover {
            background: #fff0ed;
        }

        .account-login-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: inherit;
            font-size: 21px;
        }

        .account-login-link:hover {
            background: rgba(164, 91, 25, .1);
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {
            .contact-grid-page {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .contact-card-wrapper {
                padding: 30px 20px;
            }

            .contact-item {
                min-height: auto;
            }
        }

        @media (max-width: 500px) {
            .contact-page {
                padding: 30px 4% 50px;
            }

            .contact-heading h1 {
                font-size: 30px;
            }

            .contact-intro h2 {
                font-size: 25px;
            }

            .account-dropdown {
                right: -5px;
                width: 245px;
            }

            .account-trigger {
                padding: 5px;
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

        <a class="brand" href="index.php">
            <span class="brand-mark">👩‍🍳</span>

            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>

        <nav class="nav-menu">
            <a href="index.php">Beranda</a>
            <a href="produk.php">Menu</a>
            <a href="tentang.php">Tentang Kami</a>
        <a href="blog.php">Blog</a>
            <a class="active" href="kontak.php">Kontak</a>
        </nav>

        <div class="nav-icons" aria-label="Menu cepat">
        <a href="cart.php" aria-label="Keranjang" title="Keranjang">🛒</a>

            <?php if ($isLogin): ?>

                <div class="nav-account" id="navAccount">

                    <button
                        type="button"
                        class="account-trigger"
                        id="accountTrigger"
                        aria-label="Menu akun"
                        aria-expanded="false"
                    >
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

                <a
                    href="login.php"
                    class="account-login-link"
                    aria-label="Login"
                >♙</a>

            <?php endif; ?>

        </div>
    </header>

    <!-- HALAMAN KONTAK -->

    <main class="contact-page">

        <div class="contact-container">

            <div class="contact-heading">
                <span>KONTAK KAMI</span>

                <h1>Hubungi Budiarti Risol</h1>

                <p>
                    Kami siap membantu dan menerima pesanan dari kamu.
                </p>
            </div>

            <div class="contact-card-wrapper">

                <div class="contact-intro">

                    <h2>Yuk, Hubungi Kami!</h2>

                    <p>
                        Punya pertanyaan mengenai produk atau ingin melakukan pemesanan?
                        Kamu bisa menghubungi Budiarti Risol melalui informasi berikut.
                    </p>

                </div>

                <div class="contact-grid-page">

                    <!-- WHATSAPP -->

                    <div class="contact-item">

                        <div class="contact-item-content">

                            <div class="contact-item-icon">☎</div>

                            <h3>WhatsApp</h3>

                            <p>
                                <?= htmlspecialchars($no_hp, ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                        </div>

                        <div>
                            <a
                                href="<?= htmlspecialchars($wa_link, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="contact-action"
                            >
                                Chat WhatsApp
                            </a>
                        </div>

                    </div>

                    <!-- EMAIL -->

                    <div class="contact-item">

                        <div class="contact-item-content">

                            <div class="contact-item-icon">✉</div>

                            <h3>Email</h3>

                            <p>
                                <a
                                    href="<?= htmlspecialchars($gmail_link, ENT_QUOTES, 'UTF-8'); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="email-address"
                                >
                                    <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </p>

                        </div>

                        <div>
                            <a
                                href="<?= htmlspecialchars($gmail_link, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="contact-action"
                            >
                                Kirim Email
                            </a>
                        </div>

                    </div>

                    <!-- ALAMAT -->

                    <div class="contact-item">

                        <div class="contact-item-content">

                            <div class="contact-item-icon">📍</div>

                            <h3>Alamat</h3>

                            <p>
                                <?= nl2br(htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8')); ?>
                            </p>

                        </div>

                        <div>
                            <a
                                href="<?= htmlspecialchars($maps_link, ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="contact-action"
                            >
                                Lihat Lokasi
                            </a>
                        </div>

                    </div>

                </div>

                <!-- PESAN BAGIAN BAWAH -->

                <div class="contact-message">

                    <h3>Ingin Pesan Risol?</h3>

                    <p>
                        Lihat berbagai pilihan risol yang tersedia
                        dan pilih menu favorit kamu.
                    </p>

                    <a href="produk.php" class="contact-button">
                        Lihat Menu →
                    </a>

                </div>

            </div>
        </div>
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
