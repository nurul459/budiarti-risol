
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

/* AMBIL DATA ULASAN PELANGGAN */
$query_testimoni = mysqli_query($koneksi, "
    SELECT
        ulasan.rating,
        ulasan.komentar,
        users.nama,
        products.nama_product
    FROM ulasan
    LEFT JOIN users
        ON ulasan.id_user = users.id_user
    LEFT JOIN products
        ON ulasan.id_product = products.id_product
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Testimoni Pelanggan - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* RESET KHUSUS HALAMAN TESTIMONI */
        body {
            margin: 0;
            background: #F8EFE5;
            font-family: Arial, Helvetica, sans-serif;
            color: #542D1C;
        }

        /* NAVBAR */
        .testimoni-navbar {
            width: 100%;
            min-height: 85px;
            padding: 0 7%;
            box-sizing: border-box;
            background: #fffdf0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 10;
            box-shadow: 0 3px 15px rgba(59, 29, 13, 0.07);
        }

        .testimoni-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #542D1C;
        }

        .testimoni-brand-icon {
            width: 45px;
            height: 45px;
            background: #f5d75e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .testimoni-brand-copy strong {
            display: block;
            font-family: Georgia, serif;
            font-size: 21px;
        }

        .testimoni-brand-copy small {
            display: block;
            font-size: 9px;
            letter-spacing: 2px;
            margin-top: 5px;
            color: #a07842;
        }

        .testimoni-nav-menu {
            display: flex;
            align-items: center;
            gap: 35px;
        }

        .testimoni-nav-menu a {
            color: #5b3924;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: .3s;
            position: relative;
        }

        .testimoni-nav-menu a:hover,
        .testimoni-nav-menu a.active {
            color: #c47716;
        }

        .testimoni-nav-menu a.active::after {
            content: "";
            position: absolute;
            bottom: -9px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #c47716;
        }

        /* BAGIAN TESTIMONI */
        .testimoni-container {
            width: 88%;
            max-width: 1150px;
            margin: 65px auto 90px;
        }

        .testimoni-heading {
            text-align: center;
            margin-bottom: 50px;
        }

        .testimoni-heading .eyebrow {
            color: #b47a27;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 14px;
        }

        .testimoni-heading h1 {
            font-family: Georgia, serif;
            font-size: 42px;
            color: #542D1C;
            margin: 0 0 15px;
        }

        .testimoni-heading h1 span {
            color: #c47716;
        }

        .testimoni-heading p {
            color: #806d5e;
            font-size: 14px;
            line-height: 1.8;
            max-width: 520px;
            margin: auto;
        }

        .heading-line {
            width: 55px;
            height: 3px;
            background: #d99a32;
            border-radius: 10px;
            margin: 22px auto 0;
        }

        /* GRID KARTU */
        .testimoni-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .testimoni-card {
            background: #fffefa;
            padding: 30px;
            border-radius: 18px;
            border: 1px solid #f0e5d3;
            box-shadow: 0 8px 25px rgba(59, 29, 13, .07);
            display: flex;
            flex-direction: column;
            min-height: 210px;
            transition: .3s ease;
        }

        .testimoni-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 13px 30px rgba(59, 29, 13, .12);
        }

        .testimoni-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stars {
            color: #e4a52e;
            font-size: 20px;
            letter-spacing: 2px;
        }

        .quote-icon {
            font-family: Georgia, serif;
            font-size: 48px;
            line-height: 30px;
            color: #f1dca9;
        }

        .testimoni-card h3 {
            font-family: Georgia, serif;
            font-size: 19px;
            margin: 0 0 12px;
            color: #542D1C;
        }

        .testimoni-card .komentar {
            font-size: 14px;
            color: #766354;
            line-height: 1.9;
            margin: 0 0 25px;
            overflow-wrap: anywhere;
            flex-grow: 1;
        }

        .produk-label {
            margin-top: auto;
            padding-top: 17px;
            border-top: 1px solid #eee8df;
            color: #a17b5b;
            font-size: 12px;
        }

        .produk-label span {
            color: #9b601b;
            font-weight: bold;
        }

        /* KETIKA BELUM ADA TESTIMONI */
        .empty-testimoni {
            background: #fffefa;
            padding: 55px 25px;
            border-radius: 18px;
            text-align: center;
            color: #806d5e;
            grid-column: 1 / -1;
            box-shadow: 0 8px 25px rgba(59, 29, 13, .06);
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 15px;
        }

        .empty-testimoni h3 {
            font-family: Georgia, serif;
            color: #542D1C;
            font-size: 23px;
        }

        .empty-testimoni p {
            font-size: 14px;
            line-height: 1.8;
        }

        /* FOOTER */
        .testimoni-footer {
            text-align: center;
            padding: 45px 20px 30px;
            background: #542D1C;
            color: #e0d0b8;
        }

        .testimoni-footer h2 {
            font-family: Georgia, serif;
            font-size: 27px;
            color: #fff;
            margin: 10px 0;
        }

        .testimoni-footer p {
            font-size: 13px;
            margin: 10px 0 20px;
        }

        .testimoni-footer-line {
            display: block;
            width: 50px;
            height: 2px;
            background: #e4b75e;
            margin: 22px auto;
        }

        .testimoni-footer small {
            font-size: 11px;
            color: #bfae99;
        }

        /* RESPONSIVE */
        @media (max-width: 850px) {
            .testimoni-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .testimoni-navbar {
                padding: 18px 5%;
                flex-wrap: wrap;
                gap: 18px;
            }

            .testimoni-nav-menu {
                gap: 20px;
            }
        }

        @media (max-width: 600px) {
            .testimoni-navbar {
                justify-content: center;
                text-align: center;
            }

            .testimoni-nav-menu {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 17px;
            }

            .testimoni-nav-menu a {
                font-size: 12px;
            }

            .testimoni-container {
                width: 90%;
                margin: 45px auto 65px;
            }

            .testimoni-heading h1 {
                font-size: 32px;
            }

            .testimoni-heading p {
                font-size: 13px;
            }

            .testimoni-grid {
                grid-template-columns: 1fr;
            }

            .testimoni-card {
                padding: 25px;
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
<header class="testimoni-navbar">

    <a href="index.php" class="testimoni-brand">
        <span class="testimoni-brand-icon">👩‍🍳</span>

        <span class="testimoni-brand-copy">
            <strong>Budiarti Risol</strong>
            <small>HOMEMADE • FRESHLY MADE</small>
        </span>
    </a>

    <nav class="testimoni-nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Menu</a>
        <a href="tentang.php">Tentang Kami</a>
        <a href="kontak.php">Kontak</a>
    </nav>

</header>

<!-- KONTEN TESTIMONI -->
<main class="testimoni-container">

    <div class="testimoni-heading">

        <div class="eyebrow">WHAT THEY SAY</div>

        <h1>Cerita <span>Pelanggan</span> Kami</h1>

        <p>
            Setiap ulasan memiliki cerita tersendiri.
            Simak pengalaman pelanggan setelah menikmati
            risol homemade dari Budiarti Risol.
        </p>

        <div class="heading-line"></div>

    </div>

    <div class="testimoni-grid">

        <?php if ($query_testimoni && mysqli_num_rows($query_testimoni) > 0): ?>

            <?php while ($testimoni = mysqli_fetch_assoc($query_testimoni)): ?>

                <?php
                $rating = max(0, min(5, (int) $testimoni['rating']));
                ?>

                <div class="testimoni-card">

                    <div class="testimoni-card-top">

                        <div class="stars">
                            <?= str_repeat("★", $rating); ?><?= str_repeat("☆", 5 - $rating); ?>
                        </div>

                        <div class="quote-icon">“</div>

                    </div>

                    <h3>
                        <?= htmlspecialchars($testimoni['nama'] ?? 'Pelanggan'); ?>
                    </h3>

                    <p class="komentar">
                        "<?= htmlspecialchars($testimoni['komentar'] ?? ''); ?>"
                    </p>

                    <div class="produk-label">
                        Produk: 
                        <span>
                            <?= htmlspecialchars($testimoni['nama_product'] ?? '-'); ?>
                        </span>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-testimoni">

                <div class="empty-icon">💬</div>

                <h3>Belum Ada Testimoni</h3>

                <p>
                    Belum ada pelanggan yang memberikan ulasan.
                    Jadilah pelanggan pertama yang membagikan pengalamanmu!
                </p>

            </div>

        <?php endif; ?>

    </div>

</main>

<!-- FOOTER -->
<<footer class="footer">

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

</body>
</html>
