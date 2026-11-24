
<?php
session_start();

/* DATA AKUN */
$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Budiarti Risol</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #F8EFE5;
        }

        .about-page {
            padding: 50px 6% 80px;
        }

        .about-container {
            max-width: 1150px;
            margin: auto;
        }

        .about-heading {
            margin-bottom: 30px;
        }

        .about-heading span {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #b4772d;
        }

        .about-heading h1 {
            font-family: Georgia, serif;
            font-size: 38px;
            color: #542D1C;
            margin: 8px 0;
        }

        .about-heading p {
            color: #000000;
        }

        .about-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,.07);
        }

        .about-banner {
            height: 280px;
            background: radial-gradient(
                ellipse at center,
                #f6d58e 0%,
                #d8893d 45%,
                #71330f 100%
            );
            display: flex;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .about-banner-text {
            position: relative;
            z-index: 2;
        }

        .about-banner h2 {
            font-family: Georgia, serif;
            font-size: 42px;
            color: #542D1C;
            margin-bottom: 8px;
        }

        .about-banner p {
            color: #5b321d;
            font-size: 16px;
        }

        .about-food {
            position: absolute;
            right: 90px;
            bottom: 35px;
            width: 250px;
            height: 115px;
            border-radius: 60%;
            background: linear-gradient(135deg, #f8ca70, #c66b20);
            transform: rotate(-12deg);
            box-shadow: 0 18px 20px rgba(0,0,0,.25);
        }

        .about-food::before {
            content: "";
            position: absolute;
            width: 100px;
            height: 60px;
            border-radius: 50%;
            background: #8d4215;
            left: 75px;
            top: 28px;
            opacity: .7;
        }

        .about-body {
            padding: 40px;
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 50px;
        }

        .about-story h2 {
            font-family: Georgia, serif;
            color: #542D1C;
            font-size: 28px;
            margin-bottom: 18px;
        }

        .about-story p {
            color: #000000;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .about-reasons {
            background: #F8EFE5;
            padding: 28px;
            border-radius: 15px;
        }

        .about-reasons h3 {
            color: #542D1C;
            margin-bottom: 22px;
            font-size: 21px;
        }

        .reason {
            display: flex;
            gap: 14px;
            margin-bottom: 20px;
        }

        .reason:last-child {
            margin-bottom: 0;
        }

        .reason-icon {
            width: 35px;
            height: 35px;
            min-width: 35px;
            border-radius: 50%;
            background: #EBD3C0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #542D1C;
            font-weight: bold;
        }

        .reason h4 {
            color: #000000;
            margin-bottom: 4px;
        }

        .reason p {
            color: #000000;
            font-size: 15px;
            line-height: 1.5;
        }

        /* VISI DAN MISI */

        .about-vision-mission {
            padding: 0 40px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .vision-card,
        .mission-card {
            background: #F8EFE5;
            border-radius: 15px;
            padding: 28px;
            border: 1px solid #f3e5d3;
        }

        .vision-mission-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: #EBD3C0;
            color: #542D1C;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 16px;
        }

        .vision-card h3,
        .mission-card h3 {
            font-family: Georgia, serif;
            font-size: 23px;
            color: #000000;
            margin-bottom: 12px;
        }

        .vision-card p {
            color: #000000;
            font-size: 15px;
            line-height: 1.8;
        }

        .mission-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .mission-list li {
            display: flex;
            gap: 10px;
            color: #000000;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .mission-list li:last-child {
            margin-bottom: 0;
        }

        .mission-check {
            color: #a45b19;
            font-weight: bold;
            flex-shrink: 0;
        }

        .about-button {
            display: inline-block;
            margin-top: 10px;
            padding: 12px 22px;
            background: #542D1C;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
        }

        .about-button:hover {
            background: #6a3214;
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

        /* FAQ */
        .about-faq {
            padding: 0 40px 40px;
        }

        .about-faq-card {
            background: #fff;
            border: 1px solid #f3e5d3;
            border-radius: 15px;
            padding: 28px 34px;
        }

        .about-faq-heading {
            margin-bottom: 20px;
        }

        .about-faq-heading h3 {
            font-family: Georgia, serif;
            color: #542D1C;
            font-size: 25px;
            margin: 0 0 7px;
        }

        .about-faq-heading p {
            color: #000000;
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
        }

        .about-faq-item {
            border-bottom: 1px solid #eee2d7;
        }

        .about-faq-item:last-child {
            border-bottom: 0;
        }

        .about-faq-question {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 19px 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            text-align: left;
            color: #542D1C;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }

        .about-faq-question span:last-child {
            color: #a65c20;
            font-size: 24px;
            font-weight: 400;
            transition: transform .2s;
        }

        .about-faq-answer {
            display: none;
            color: #71645c;
            font-size: 14px;
            line-height: 1.8;
            padding: 0 38px 18px 4px;
        }

        .about-faq-item.active .about-faq-answer {
            display: block;
        }

        .about-faq-item.active .about-faq-question span:last-child {
            transform: rotate(45deg);
        }

        /* RESPONSIVE */

        @media (max-width: 800px) {
            .about-body {
                grid-template-columns: 1fr;
            }

            .about-vision-mission {
                grid-template-columns: 1fr;
            }

            .about-faq {
                padding: 0 25px 25px;
            }

            .about-food {
                right: -30px;
                opacity: .7;
            }
        }

        @media (max-width: 500px) {
            .about-page {
                padding: 35px 5% 60px;
            }

            .about-heading h1 {
                font-size: 30px;
            }

            .about-banner {
                height: 230px;
                padding: 25px;
            }

            .about-banner h2 {
                font-size: 32px;
            }

            .about-food {
                width: 170px;
                height: 80px;
                right: -45px;
            }

            .about-body {
                padding: 25px;
            }

            .about-vision-mission {
                padding: 0 25px 25px;
            }

            .about-faq {
                padding: 0 25px 25px;
            }

            .about-faq-card {
                padding: 23px 19px;
            }

            .about-faq-question {
                font-size: 14px;
            }

            .about-faq-answer {
                font-size: 13px;
                padding-right: 12px;
            }

            .vision-card,
            .mission-card {
                padding: 23px;
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
            <a class="active" href="tentang.php">Tentang Kami</a>
            <a href="blog.php">Blog</a>
        <a href="kontak.php">Kontak</a>
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
                                <strong><?= htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($email_user, ENT_QUOTES, 'UTF-8'); ?></small>
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

    <!-- TENTANG KAMI -->
    <main class="about-page">
        <div class="about-container">

            <div class="about-heading">
                <span>TENTANG KAMI</span>
                <h1>Kenalan dengan Budiarti Risol</h1>
                <p>Mengenal lebih dekat Budiarti Risol dan produk yang kami sajikan.</p>
            </div>

            <div class="about-card">

                <!-- BANNER -->
                <div class="about-banner">
                    <div class="about-banner-text">
                        <h2>Tentang Kami</h2>
                        <p>Risol lezat untuk setiap saat.</p>
                    </div>
                </div>

                <!-- CERITA USAHA -->
                <div class="about-body">
                    <div class="about-story">
                        <h2>Budiarti Risol</h2>

                        <p>
                            Budiarti Risol merupakan UMKM yang menyediakan
                            berbagai pilihan risol dengan cita rasa yang lezat
                            dan bahan berkualitas.
                        </p>

                        <p>
                            Kami menyediakan berbagai varian risol asin dan
                            manis yang cocok untuk camilan sehari-hari,
                            acara keluarga, maupun berbagai kegiatan lainnya.
                        </p>

                        <p>
                            Dengan mengutamakan rasa, kualitas, dan pelayanan,
                            Budiarti Risol berusaha memberikan pengalaman
                            terbaik untuk setiap pelanggan.
                        </p>

                        <a href="produk.php" class="about-button">Lihat Menu</a>
                    </div>

                    <!-- ALASAN -->
                    <div class="about-reasons">
                        <h3>Kenapa Budiarti Risol?</h3>

                        <div class="reason">
                            <div class="reason-icon">✓</div>
                            <div>
                                <h4>Bahan Berkualitas</h4>
                                <p>
                                    Menggunakan bahan pilihan untuk
                                    menghasilkan produk yang berkualitas.
                                </p>
                            </div>
                        </div>

                        <div class="reason">
                            <div class="reason-icon">✓</div>
                            <div>
                                <h4>Rasa Lezat</h4>
                                <p>
                                    Dibuat dengan cita rasa yang nikmat
                                    dan cocok untuk berbagai kesempatan.
                                </p>
                            </div>
                        </div>

                        <div class="reason">
                            <div class="reason-icon">✓</div>
                            <div>
                                <h4>Harga Terjangkau</h4>
                                <p>
                                    Menyediakan pilihan risol dengan harga
                                    yang terjangkau.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISI DAN MISI -->
                <section class="about-vision-mission">

                    <div class="vision-card">
                        <div class="vision-mission-icon">✦</div>
                        <h3>Visi Kami</h3>
                        <p>
                            Menjadi usaha risol homemade yang dikenal
                            karena kualitas rasa, bahan pilihan, dan
                            pelayanan terbaik sehingga dapat memberikan
                            kepuasan kepada setiap pelanggan.
                        </p>
                    </div>

                    <div class="mission-card">
                        <div class="vision-mission-icon">✦</div>
                        <h3>Misi Kami</h3>

                        <ul class="mission-list">
                            <li>
                                <span class="mission-check">✓</span>
                                <span>Menyediakan risol homemade dengan cita rasa lezat dan kualitas yang terjaga.</span>
                            </li>
                            <li>
                                <span class="mission-check">✓</span>
                                <span>Menggunakan bahan pilihan yang berkualitas dan aman untuk dikonsumsi.</span>
                            </li>
                            <li>
                                <span class="mission-check">✓</span>
                                <span>Memberikan pelayanan yang ramah dan memudahkan pelanggan dalam melakukan pemesanan.</span>
                            </li>
                            <li>
                                <span class="mission-check">✓</span>
                                <span>Mengembangkan usaha melalui pemanfaatan teknologi digital.</span>
                            </li>
                        </ul>
                    </div>

                </section>

                <!-- FAQ -->
                <section class="about-faq">
                    <div class="about-faq-card">
                        <div class="about-faq-heading">
                            <h3>Pertanyaan yang Sering Ditanyakan</h3>
                            <p>Beberapa informasi penting seputar pemesanan, pembayaran, pengiriman, dan pesanan Budiarti Risol.</p>
                        </div>

                        <div class="about-faq-item active">
                            <button class="about-faq-question" type="button" aria-expanded="true">
                                <span>Bagaimana cara memesan produk Budiarti Risol?</span>
                                <span>+</span>
                            </button>
                            <div class="about-faq-answer">
                                Pilih produk yang diinginkan melalui halaman Menu, tambahkan ke keranjang,
                                lalu lanjutkan ke checkout untuk mengisi data penerima dan menyelesaikan pesanan.
                            </div>
                        </div>

                        <div class="about-faq-item">
                            <button class="about-faq-question" type="button" aria-expanded="false">
                                <span>Apakah saya harus memiliki akun untuk memesan?</span>
                                <span>+</span>
                            </button>
                            <div class="about-faq-answer">
                                Ya. Silakan masuk ke akun sebelum melakukan pemesanan. Jika belum memiliki akun,
                                kamu dapat melakukan registrasi terlebih dahulu.
                            </div>
                        </div>

                        <div class="about-faq-item">
                            <button class="about-faq-question" type="button" aria-expanded="false">
                                <span>Metode pembayaran apa yang tersedia?</span>
                                <span>+</span>
                            </button>
                            <div class="about-faq-answer">
                                Tersedia QRIS, Transfer Bank, dan COD (Cash on Delivery).
                                Untuk QRIS dan Transfer Bank, pelanggan perlu mengunggah bukti pembayaran sesuai instruksi pada halaman pembayaran.
                            </div>
                        </div>

                        <div class="about-faq-item">
                            <button class="about-faq-question" type="button" aria-expanded="false">
                                <span>Bagaimana ketentuan biaya dan pengiriman pesanan?</span>
                                <span>+</span>
                            </button>
                            <div class="about-faq-answer">
                                Biaya pengiriman menjadi tanggung jawab pembeli dan dipesan secara mandiri melalui layanan kurir yang tersedia,
                                seperti GoSend atau GrabExpress. Budiarti Risol menyiapkan pesanan hingga siap diambil oleh kurir.
                            </div>
                        </div>

                        <div class="about-faq-item">
                            <button class="about-faq-question" type="button" aria-expanded="false">
                                <span>Bagaimana cara melihat status pesanan?</span>
                                <span>+</span>
                            </button>
                            <div class="about-faq-answer">
                                Setelah masuk ke akun, buka Riwayat Pesanan dan pilih pesanan yang ingin dilihat untuk mengetahui detail
                                serta status pesanan dan pengirimannya.
                            </div>
                        </div>
                    </div>
                </section>

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

        // ACCORDION FAQ
        document.querySelectorAll('.about-faq-question').forEach(function(button) {
            button.addEventListener('click', function() {
                const item = this.closest('.about-faq-item');
                const isOpen = item.classList.contains('active');

                document.querySelectorAll('.about-faq-item').forEach(function(otherItem) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.about-faq-question').setAttribute('aria-expanded', 'false');
                });

                if (!isOpen) {
                    item.classList.add('active');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });
    </script>

</body>
</html>
