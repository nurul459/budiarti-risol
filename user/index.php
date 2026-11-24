
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

$isLogin = isset($_SESSION['id_user']);
$nama_user = $isLogin ? ($_SESSION['nama'] ?? 'Pengguna') : '';
$email_user = $isLogin ? ($_SESSION['email'] ?? '') : '';
$inisial = $isLogin ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM products
    WHERE
        nama_product LIKE '%Matcha%'
        OR nama_product LIKE '%Keju%'
        OR nama_product LIKE '%Pisang%'
        OR (
            nama_product LIKE '%Cok%'
            AND nama_product NOT LIKE '%Pisang%'
        )
    ORDER BY
        CASE
            WHEN nama_product LIKE '%Matcha%' THEN 1
            WHEN nama_product LIKE '%Keju%' THEN 2
            WHEN nama_product LIKE '%Pisang%' THEN 3
            ELSE 4
        END
    LIMIT 4"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budiarti Risol</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* MENU AKUN */
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

        .account-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 9px;
            text-decoration: none;
            color: #60432e;
            font-size: 13px;
            transition: .2s;
        }

        .account-menu-link:hover {
            background: #fff2dc;
            color: #a45b19;
        }

        .account-menu-link .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .account-menu-link.logout {
            color: #b34435;
        }

        .account-menu-link.logout:hover {
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

        .footer-links {
            margin: 15px 0;
        }

        .footer-links a {
            color: inherit;
            text-decoration: none;
            font-size: 13px;
            transition: .3s;
        }

        .footer-links a:hover {
            text-decoration: underline;
            opacity: .8;
        }

        @media(max-width: 600px) {
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

<header class="navbar">

    <a class="brand" href="index.php">
        <span class="brand-mark">👩‍🍳</span>
        <span class="brand-copy">
            <strong>Budiarti Risol</strong>
            <small>HOMEMADE • FRESHLY MADE</small>
        </span>
    </a>

    <nav class="nav-menu">
        <a class="active" href="index.php">Beranda</a>
        <a href="produk.php">Menu</a>
        <a href="tentang.php">Tentang Kami</a>
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
                            <strong>
                                <?= htmlspecialchars($nama_user) ?>
                            </strong>
                            <small>
                                <?= htmlspecialchars($email_user) ?>
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

                    <a href="logout.php" class="account-menu-link logout"
                       onclick="return confirm('Apakah kamu yakin ingin keluar?')">
                        <span class="menu-icon">↪</span>
                        Logout
                    </a>

                </div>
            </div>

        <?php else: ?>

            <a href="login.php" class="account-login-link"
               aria-label="Login">♙</a>

        <?php endif; ?>

    </div>

</header>

<main>

<section class="hero">

    <div class="hero-copy">
        <p class="eyebrow">✦ UMKM BUDIARTI RISOL</p>

        <h1>
            Risol Homemade<br>
            <em>dengan Rasa Istimewa</em>
        </h1>

        <p class="hero-description">
            Dibuat dengan bahan pilihan dan penuh rasa.
            Nikmati risol homemade yang fresh, lezat,
            dan dibuat khusus untuk setiap pesanan.
        </p>

        <a class="button button-primary" href="produk.php">
            Lihat Menu <span>→</span>
        </a>

        <span class="leaf-doodle" aria-hidden="true"></span>
    </div>

    <div class="hero-visual">
        <div
            class="hero-photo"
            role="img"
            aria-label="Risol homemade keemasan"
        ></div>
    </div>

</section>

<section class="favorite-section">

    <div class="section-header">
        <div>
            <p class="eyebrow">MENU FAVORIT</p>
            <h2>Pilihan yang Paling Disukai Pelanggan</h2>
        </div>

        <a class="see-all" href="produk.php">
            Lihat Semua <span>→</span>
        </a>
    </div>

    <div class="product-grid">

        <?php while ($produk = mysqli_fetch_assoc($query)) : ?>

        <article class="product-card">

            <a class="product-image"
               href="detail.php?id=<?= (int)$produk['id_product']; ?>"
               aria-label="Lihat <?= htmlspecialchars($produk['nama_product']); ?>">

                <span class="food-photo food-photo-<?= (int)$produk['id_product'] % 4 ?>"></span>

            </a>

            <div class="product-info">

                <h3><?= htmlspecialchars($produk['nama_product']); ?></h3>

                <p><?= htmlspecialchars($produk['deskripsi']); ?></p>

                <div class="product-bottom">

                    <span class="price">
                        Rp <?= number_format((float)$produk['harga'], 0, ',', '.'); ?>
                    </span>

                    <a class="add-cart"
                       href="detail.php?id=<?= (int)$produk['id_product']; ?>"
                       aria-label="Lihat detail produk">+</a>

                </div>
            </div>

        </article>

        <?php endwhile; ?>

        <?php if (mysqli_num_rows($query) === 0) : ?>
            <p class="empty-state">
                Menu favorit akan segera hadir. Silakan cek kembali nanti.
            </p>
        <?php endif; ?>

    </div>

</section>

<section class="benefits">

    <div class="benefit-item">
        <span class="benefit-icon">◇</span>
        <div>
            <h3>Produk Berkualitas</h3>
            <p>Bahan pilihan terbaik</p>
        </div>
    </div>

    <div class="benefit-item">
        <span class="benefit-icon">◇</span>
        <div>
            <h3>Freshly Made</h3>
            <p>Dibuat fresh setiap pesanan</p>
        </div>
    </div>

    <div class="benefit-item">
        <span class="benefit-icon">◇</span>
        <div>
            <h3>Pembayaran Aman</h3>
            <p>Transaksi mudah dan aman</p>
        </div>
    </div>

</section>

<section class="promo-banner">

    <div class="promo-copy">
        <p class="eyebrow">HOMEMADE • FRESHLY MADE</p>

        <h2>
            Dibuat dengan rasa,<br>
            <em>disajikan dengan cinta.</em>
        </h2>

        <p>Homemade &nbsp;•&nbsp; Freshly Made &nbsp;•&nbsp; Pre-Order</p>

        <a class="button button-light" href="produk.php">
            Pesan Sekarang <span>→</span>
        </a>
    </div>

    <div class="promo-photo"
         role="img"
         aria-label="Risol renyah siap disajikan"></div>

</section>

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
