
<?php
session_start();

/* DATA AKUN */
$login = isset($_SESSION['id_user']);

$nama_user = $_SESSION['nama'] ?? '';
$email_user = $_SESSION['email'] ?? '';
$inisial = $nama_user !== '' ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

$artikel = [
    [
        "id" => 1,
        "judul" => "Rahasia Membuat Risol yang Renyah dan Lezat",
        "tanggal" => "10 November 2026",
        "kategori" => "Tips Kuliner",
        "gambar" => "../assets/images/blog-risol-renyah.webp",
        "ringkasan" => "Risol yang renyah dan lezat berawal dari pemilihan bahan berkualitas serta proses pembuatan yang tepat.",
        "isi" => "Untuk menghasilkan risol yang renyah dan lezat, pemilihan bahan menjadi hal yang penting. Mulai dari kulit risol yang tipis, isian yang berkualitas, hingga proses penggorengan dengan suhu minyak yang sesuai. Dengan bahan segar dan proses pembuatan yang tepat, risol dapat memiliki tekstur renyah di luar dan lembut di dalam."
    ],
    [
        "id" => 2,
        "judul" => "Kenapa Risol Homemade Lebih Istimewa?",
        "tanggal" => "8 November 2026",
        "kategori" => "Cerita Kuliner",
        "gambar" => "../assets/images/blog-risol-homemade.webp",
        "ringkasan" => "Dibuat dengan bahan pilihan dan proses yang lebih diperhatikan, risol homemade memiliki cita rasa tersendiri.",
        "isi" => "Risol homemade dibuat dengan perhatian pada setiap prosesnya. Mulai dari menyiapkan bahan, membuat kulit, mengolah isian, hingga menggoreng risol. Proses tersebut membuat setiap risol memiliki cita rasa khas dan kualitas yang lebih terjaga."
    ],
    [
        "id" => 3,
        "judul" => "Mengenal Berbagai Varian Rasa Risol",
        "tanggal" => "5 November 2026",
        "kategori" => "Menu Kami",
        "gambar" => "../assets/images/blog-varian-risol.webp",
        "ringkasan" => "Dari rasa gurih hingga manis, risol memiliki banyak pilihan rasa yang bisa dinikmati.",
        "isi" => "Risol tidak hanya memiliki varian rasa gurih. Kini, risol juga hadir dengan berbagai pilihan rasa manis yang menarik. Budiarti Risol menyediakan beberapa pilihan seperti risol keju, matcha, pisang cokelat, dan cokelat. Setiap varian memiliki cita rasa yang berbeda dan cocok dinikmati dalam berbagai suasana."
    ],
    [
        "id" => 4,
        "judul" => "Risol, Camilan Favorit untuk Berbagai Suasana",
        "tanggal" => "2 November 2026",
        "kategori" => "Inspirasi",
        "gambar" => "../assets/images/blog-risol-mayo.webp",
        "ringkasan" => "Risol bisa menjadi teman santai, camilan keluarga, maupun sajian untuk berbagai acara.",
        "isi" => "Risol merupakan salah satu camilan yang cocok dinikmati kapan saja. Rasanya yang lezat dan bentuknya yang praktis membuat risol cocok dijadikan teman bersantai bersama keluarga, teman, maupun sebagai sajian dalam berbagai acara."
    ]
];

$id_artikel = isset($_GET['artikel']) ? (int) $_GET['artikel'] : 0;
$artikel_detail = null;

if ($id_artikel > 0) {
    foreach ($artikel as $item) {
        if ($item['id'] === $id_artikel) {
            $artikel_detail = $item;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Blog - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #F8EFE5;
            font-family: var(--sans, 'DM Sans', Arial, sans-serif);
            font-size: var(--text-base, 15px);
        }

        .blog-heading h1,
        .article-detail h1,
        .article-detail h2 {
            font-family: var(--serif, 'Playfair Display', Georgia, serif);
        }

        .blog-heading p,
        .blog-card p,
        .article-detail p {
            font-family: var(--sans, 'DM Sans', Arial, sans-serif);
        }

        /* NAVBAR */
        .navbar {
            position: relative;
            z-index: 1000;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-icons > a {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            color: white;
            border: 1px solid rgba(255,255,255,.4);
            border-radius: 50%;
            text-decoration: none;
        }

        /* DROPDOWN AKUN - SAMA SEPERTI BERANDA */
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
            min-width: 36px;
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
            color: white;
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
            box-sizing: border-box;
            background: #F8EFE5;
            border: 1px solid #eadcc5;
            border-radius: 15px;
            box-shadow: 0 12px 35px rgba(59, 29, 13, .16);
            padding: 10px;
            z-index: 99999;
        }

        .nav-account.open .account-dropdown {
            display: block;
            animation: dropdownShow .2s ease;
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

        .nav-icons .account-dropdown .account-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            height: auto;
            padding: 12px;
            border: none;
            border-radius: 9px;
            background: transparent;
            color: #60432e;
            font-size: 13px;
            text-decoration: none;
            transition: .2s;
        }

        .nav-icons .account-dropdown .account-menu-link:hover {
            background: #fff2dc;
            color: #a45b19;
        }

        .account-menu-link .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .nav-icons .account-dropdown .account-menu-link.logout {
            color: #b34435;
        }

        .nav-icons .account-dropdown .account-menu-link.logout:hover {
            background: #fff0ed;
        }

        .nav-icons > .account-login-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: white;
            font-size: 21px;
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

        /* BAGIAN BLOG */
        .blog-container {
            width: 90%;
            max-width: 1200px;
            margin: 60px auto 85px;
        }

        .blog-heading {
            text-align: center;
            margin-bottom: 45px;
        }

        .blog-heading .eyebrow {
            color: #a45b19;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 3px;
        }

        .blog-heading h1 {
            font-size: 38px;
            color: #542D1C;
            margin: 12px 0;
        }

        .blog-heading p {
            color: #76543b;
            font-size: 15px;
            line-height: 1.7;
        }

        /* KARTU ARTIKEL */
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }

        .blog-card {
            background: #fffdf0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(70, 39, 15, 0.09);
            transition: 0.3s;
        }

        .blog-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(70, 39, 15, 0.15);
        }

        .blog-image {
            height: 230px;
            background: #f5d88b;
            overflow: hidden;
        }

        .blog-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .blog-content {
            padding: 25px;
        }

        .blog-category {
            display: inline-block;
            background: #a45b19;
            color: #ffffff !important;
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .blog-date {
            color: #9b8067;
            font-size: 12px;
            margin: 15px 0 10px;
        }

        .blog-content h2 {
            font-size: 21px;
            line-height: 1.4;
            margin: 0 0 12px;
            color: #542D1C;
        }

        .blog-content p {
            font-size: 14px;
            line-height: 1.7;
            color: #765f4c;
            margin-bottom: 22px;
        }

        .read-more {
            display: inline-block;
            background: #a45b19 !important;
            color: #ffffff !important;
            padding: 11px 19px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none !important;
            transition: 0.3s;
        }

        .read-more:hover {
            background: #542D1C !important;
            color: #ffffff !important;
        }

        /* DETAIL ARTIKEL */
        .article-detail {
            background: #fffdf0;
            max-width: 850px;
            margin: auto;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(70, 39, 15, 0.09);
        }

        .article-image {
            height: 360px;
            background: #f5d88b;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 25px;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .article-detail h1 {
            font-size: 32px;
            line-height: 1.4;
            margin: 18px 0;
            color: #542D1C;
        }

        .article-detail p {
            font-size: 15px;
            line-height: 1.9;
            color: #654b35;
        }

        .back-blog {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            background: #a45b19 !important;
            color: #ffffff !important;
            border-radius: 25px;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: bold;
            transition: 0.3s;
        }

        .back-blog:hover {
            background: #542D1C !important;
        }

        /* RESPONSIVE */
        @media (max-width: 700px) {
            .blog-container {
                margin-top: 35px;
            }

            .blog-heading h1 {
                font-size: 28px;
            }

            .blog-grid {
                grid-template-columns: 1fr;
            }

            .article-detail {
                padding: 22px;
            }

            .article-detail h1 {
                font-size: 25px;
            }
        }

        @media (max-width: 500px) {
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

<!-- HEADER -->
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
        <a href="blog.php" class="active">Blog</a>
        <a href="kontak.php">Kontak</a>
    </nav>

    <div class="nav-icons">

        <a href="cart.php" aria-label="Keranjang" title="Keranjang">🛒</a>

        <?php if ($login): ?>

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

                    <a href="logout.php"
                       class="account-menu-link logout"
                       onclick="return confirm('Apakah kamu yakin ingin keluar?')">
                        <span class="menu-icon">↪</span>
                        Keluar
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

<main class="blog-container">

    <?php if ($artikel_detail): ?>

        <div class="article-detail">

            <div class="article-image">
                <img src="<?= htmlspecialchars($artikel_detail['gambar'], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?= htmlspecialchars($artikel_detail['judul'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <span class="blog-category">
                <?= htmlspecialchars($artikel_detail['kategori'], ENT_QUOTES, 'UTF-8'); ?>
            </span>

            <div class="blog-date">
                <?= htmlspecialchars($artikel_detail['tanggal'], ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <h1>
                <?= htmlspecialchars($artikel_detail['judul'], ENT_QUOTES, 'UTF-8'); ?>
            </h1>

            <p>
                <?= htmlspecialchars($artikel_detail['isi'], ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <a href="blog.php" class="back-blog">
                ← Kembali ke Blog
            </a>

        </div>

    <?php else: ?>

        <div class="blog-heading">
            <p class="eyebrow">CERITA DARI BUDIARTI RISOL</p>

            <h1>Blog & Berita</h1>

            <p>
                Temukan cerita, informasi, dan inspirasi menarik
                seputar dunia kuliner dan risol homemade.
            </p>
        </div>

        <div class="blog-grid">

            <?php foreach ($artikel as $item): ?>

                <article class="blog-card">

                    <div class="blog-image">
                        <img src="<?= htmlspecialchars($item['gambar'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8'); ?>"
                             loading="lazy">
                    </div>

                    <div class="blog-content">

                        <span class="blog-category">
                            <?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <div class="blog-date">
                            <?= htmlspecialchars($item['tanggal'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <h2>
                            <?= htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8'); ?>
                        </h2>

                        <p>
                            <?= htmlspecialchars($item['ringkasan'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>

                        <a
                            href="blog.php?artikel=<?= (int) $item['id']; ?>"
                            class="read-more"
                        >
                            Baca Selengkapnya →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

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
// DROPDOWN AKUN
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
