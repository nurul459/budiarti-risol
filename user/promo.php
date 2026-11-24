
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

$promos = [
    [
        "judul" => "Diskon 10%",
        "deskripsi" => "Nikmati potongan harga 10% untuk pembelian risol pilihan.",
        "kode" => "RISOL10",
        "label" => "DISKON SPESIAL",
        "warna" => "orange"
    ],
    [
        "judul" => "Beli 5 Gratis 1",
        "deskripsi" => "Beli 5 risol satuan dan dapatkan 1 risol gratis.",
        "kode" => "BELI5GRATIS1",
        "label" => "PROMO FAVORIT",
        "warna" => "pink"
    ],
    [
        "judul" => "Gratis Ongkir",
        "deskripsi" => "Nikmati gratis ongkir untuk pesanan dengan ketentuan tertentu.",
        "kode" => "ONGKIRGRATIS",
        "label" => "PROMO SPESIAL",
        "warna" => "green"
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo - Budiarti Risol</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #F8EFE5;
            color: #542D1C;
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .brand-copy {
            display: flex !important;
            flex-direction: column;
        }

        .brand-copy strong {
            color: white !important;
            font-size: 20px;
            white-space: nowrap;
        }

        .brand-copy small {
            color: white !important;
            font-size: 9px;
            letter-spacing: 1.5px;
        }

        .nav-icons {
            display: flex !important;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }

        .promo-page {
            padding: 45px 6%;
            min-height: 70vh;
        }

        .promo-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .promo-heading h1 {
            font-family: Georgia, serif;
            font-size: 36px;
            margin-bottom: 10px;
            color: #542D1C;
        }

        .promo-heading p {
            color: #765b49;
        }

        .promo-banner {
            background: #542D1C;
            color: white;
            padding: 35px;
            border-radius: 18px;
            margin-bottom: 35px;
            text-align: center;
        }

        .promo-banner h2 {
            font-family: Georgia, serif;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .promo-banner p {
            color: #fff2d8;
        }

        .promo-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .promo-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 14px rgba(0,0,0,.07);
            transition: .2s;
        }

        .promo-card:hover {
            transform: translateY(-5px);
        }

        .promo-label {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .orange {
            background: #fff0d9;
            color: #b55a00;
        }

        .pink {
            background: #ffe8ec;
            color: #c43d5b;
        }

        .green {
            background: #e5f5e5;
            color: #398047;
        }

        .promo-card h3 {
            font-family: Georgia, serif;
            font-size: 23px;
            margin-bottom: 10px;
            color: #542D1C;
        }

        .promo-card p {
            color: #777;
            font-size: 13px;
            line-height: 1.7;
            min-height: 45px;
        }

        .promo-code {
            background: #fff9e9;
            border: 1px dashed #d47100;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .promo-code strong {
            color: #542D1C;
            font-size: 13px;
        }

        .copy-button {
            border: none;
            background: #542D1C;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
        }

        .promo-note {
            text-align: center;
            color: #765b49;
            font-size: 12px;
            margin-top: 30px;
        }

        @media(max-width: 850px) {
            .promo-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 550px) {
            .promo-list {
                grid-template-columns: 1fr;
            }

            .promo-heading h1 {
                font-size: 29px;
            }

            .promo-banner {
                padding: 25px 15px;
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
        <a href="index.php" class="brand">
            <span class="brand-mark">👩‍🍳</span>
            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>
    </div>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Menu</a>
        <a href="tentang.php">Tentang Kami</a>
        <a href="blog.php">Blog</a>
        <a href="kontak.php">Kontak</a>
    </nav>

    <div class="nav-icons">
        <a href="cart.php#wishlist" title="Wishlist">♡</a>
        <a href="cart.php" title="Keranjang">🛒</a>
        <a href="login.php" title="Login">♙</a>
    </div>

</header>

<main class="promo-page">

    <div class="promo-heading">
        <h1>Promo Spesial ♡</h1>
        <p>Penawaran menarik untuk menemani risol favoritmu.</p>
    </div>

    <section class="promo-banner">
        <h2>Lebih Hemat, Lebih Nikmat!</h2>
        <p>Temukan penawaran spesial dari Budiarti Risol.</p>
    </section>

    <section class="promo-list">

        <?php foreach ($promos as $promo) : ?>

            <div class="promo-card">

                <span class="promo-label <?= $promo['warna']; ?>">
                    <?= htmlspecialchars($promo['label']); ?>
                </span>

                <h3>
                    <?= htmlspecialchars($promo['judul']); ?>
                </h3>

                <p>
                    <?= htmlspecialchars($promo['deskripsi']); ?>
                </p>

                <div class="promo-code">
                    <strong><?= htmlspecialchars($promo['kode']); ?></strong>

                    <button
                        type="button"
                        class="copy-button"
                        onclick="salinKode('<?= htmlspecialchars($promo['kode'], ENT_QUOTES); ?>')">
                        Salin Kode
                    </button>
                </div>

            </div>

        <?php endforeach; ?>

    </section>

    <p class="promo-note">
        *Promo di atas merupakan contoh penawaran dan belum otomatis diterapkan saat checkout.
    </p>

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
function salinKode(kode) {
    navigator.clipboard.writeText(kode)
        .then(() => alert("Kode promo berhasil disalin: " + kode))
        .catch(() => alert("Gagal menyalin kode promo."));
}
</script>

</body>
</html>
