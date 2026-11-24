
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);
include "../config/ensure_erp_tables.php";

ensureWishlistTable($koneksi);

/* DATA AKUN */
$login = isset($_SESSION['id_user']);

$nama_user = $_SESSION['nama'] ?? '';
$email_user = $_SESSION['email'] ?? '';
$inisial = $nama_user !== '' ? strtoupper(substr(trim($nama_user), 0, 1)) : '';

/* WISHLIST USER */
$wishlist_ids = [];

if ($login) {
    $id_user_login = (int) $_SESSION['id_user'];

    $query_wishlist = mysqli_query($koneksi, "
        SELECT id_product
        FROM wishlist
        WHERE id_user = $id_user_login
    ");

    if ($query_wishlist) {
        while ($item = mysqli_fetch_assoc($query_wishlist)) {
            $wishlist_ids[] = (int) $item['id_product'];
        }
    }
}

/* KATEGORI */
$kategori_query = mysqli_query(
    $koneksi,
    "SELECT * FROM categories ORDER BY id_category ASC"
);

if (!$kategori_query) {
    die("Gagal mengambil kategori: " . mysqli_error($koneksi));
}

$kategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

/* SORTING */
$sort = $_GET['sort'] ?? 'terbaru';

if ($sort === 'termurah') {
    $order_by = "products.harga ASC";
} elseif ($sort === 'termahal') {
    $order_by = "products.harga DESC";
} else {
    $sort = 'terbaru';
    $order_by = "products.id_product DESC";
}

/* PRODUK */
$sql_produk = "
    SELECT products.*, categories.nama_category
    FROM products
    JOIN categories
    ON products.id_category = categories.id_category
";

if ($kategori > 0) {
    $sql_produk .= " WHERE products.id_category = $kategori";
}

$sql_produk .= " ORDER BY $order_by";

$produk_query = mysqli_query($koneksi, $sql_produk);

if (!$produk_query) {
    die("Gagal mengambil data produk: " . mysqli_error($koneksi));
}

$jumlah_produk = mysqli_num_rows($produk_query);

/* GAMBAR PRODUK */
$gambar_produk = [
    'Risol Matcha' => 'matchaa.jpeg',
    'Risol Keju' => 'kejuu.jpeg',
    'Risol Pisang Coklat' => 'piscok.jpeg',
    'Risol Coklat' => 'chocooo.jpg',
    'Risol Sayur' => 'sayur.jpg',
    'Risol Beef' => 'beef.jpg',
    'Risol Ayam' => 'ayam.jpg',
    'Risol Mayo' => 'mayo.jpg',
    'Paket Hemat 1' => 'pakethemat1.jpeg',
    'Paket Hemat 2' => 'pakethemat2.jpeg',
    'Paket Hemat 3' => 'pakethemat2.jpeg',
    'Paket Ramai' => 'paketramai.jpeg'
];

$folder_gambar = __DIR__ . '/../assets/images/';
$url_gambar = '../assets/images/';

/* JUDUL KATEGORI */
$judul_kategori = 'Semua Produk';

if ($kategori > 0) {
    $judul_query = mysqli_query($koneksi, "
        SELECT nama_category
        FROM categories
        WHERE id_category = $kategori
        LIMIT 1
    ");

    if ($judul_query && mysqli_num_rows($judul_query) > 0) {
        $judul_data = mysqli_fetch_assoc($judul_query);
        $judul_kategori = $judul_data['nama_category'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Produk - Budiarti Risol</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    background: #F8EFE5;
}

.product-page {
    padding: 35px 5%;
}

.product-page-header {
    margin-bottom: 30px;
}

.product-page-header h1 {
    font-family: Georgia, serif;
    font-size: 34px;
    color: #542D1C;
    margin-bottom: 8px;
}

.product-page-header p {
    color: #777;
}

/* HEADER */
.navbar {
    position: relative;
    z-index: 1000;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-left: -35px;
}

.navbar .brand {
    text-decoration: none;
}

.navbar .brand .brand-copy strong,
.navbar .brand .brand-copy small {
    color: #ffffff !important;
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

.navbar .nav-menu a {
    color: #ffffff !important;
}

.navbar .nav-menu a:hover {
    color: #ffe2a8 !important;
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

/* LAYOUT PRODUK */
.product-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 30px;
}

.category-sidebar {
    background: white;
    border-radius: 14px;
    padding: 20px;
    height: fit-content;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
}

.category-sidebar h3 {
    margin-bottom: 15px;
    color: #542D1C;
}

.category-link {
    display: block;
    padding: 13px 12px;
    margin-bottom: 7px;
    border-radius: 8px;
    text-decoration: none;
    color: #555;
    transition: .2s;
}

.category-link:hover {
    background: #d47100;
    color: #542D1C;
}

.category-link.active {
    background: #542D1C;
    color: white;
}

/* KONTEN PRODUK */
.product-content {
    min-width: 0;
}

.product-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.product-top h2 {
    font-size: 23px;
    color: #542D1C;
}

.product-count {
    color: #777;
    font-size: 13px;
    margin-top: 4px;
}

.sort-box {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

.sort-form {
    margin: 0;
}

/* DAFTAR PRODUK */
.product-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.menu-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(0,0,0,.07);
    transition: .2s;
}

.menu-card:hover {
    transform: translateY(-4px);
}

/* GAMBAR PRODUK */
.menu-image {
    height: 165px;
    overflow: hidden;
    background: #fff2e5;
    position: relative;
}

.menu-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    pointer-events: none;
}

/* LABEL PROMO */
.promo-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 5;
    background: #b83c26;
    color: white;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: bold;
    box-shadow: 0 2px 7px rgba(0,0,0,.15);
}

/* WISHLIST */
.wishlist-heart {
    position: absolute !important;
    top: 10px;
    right: 10px;
    z-index: 20 !important;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: white;
    color: #777;
    font-size: 25px;
    text-decoration: none !important;
    cursor: pointer;
    pointer-events: auto !important;
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
    transition: .2s;
}

.wishlist-heart:hover {
    transform: scale(1.1);
    color: #e53935;
}

.wishlist-heart.active {
    color: #e53935;
}

/* INFORMASI PRODUK */
.menu-info {
    padding: 13px;
}

.menu-category {
    color: #9b6a27;
    font-size: 11px;
    margin-bottom: 5px;
}

.menu-info h3 {
    font-size: 16px;
    margin-bottom: 6px;
    color: #000;
}

.menu-info p {
    font-size: 12px;
    color: #777;
    min-height: 30px;
    margin-bottom: 10px;
    line-height: 1.5;
}

/* HARGA */
.menu-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.price-area {
    min-width: 0;
}

.menu-price {
    font-weight: bold;
    color: #542D1C;
    font-size: 16px;
}

.old-price {
    color: #999;
    font-size: 11px;
    text-decoration: line-through;
    margin-bottom: 3px;
}

.discount-text {
    display: inline-block;
    color: #b83c26;
    font-size: 10px;
    font-weight: bold;
    margin-top: 3px;
}

.stock {
    font-size: 10px;
    color: #777;
    margin-top: 5px;
}

.detail-button {
    display: inline-block;
    flex-shrink: 0;
    background: #542D1C;
    color: white;
    padding: 8px 12px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 11px;
}

.detail-button:hover {
    background: #6a3214;
}

/* PRODUK KOSONG */
.empty-product {
    background: white;
    padding: 40px;
    text-align: center;
    border-radius: 12px;
    grid-column: 1 / -1;
}

/* FOOTER */
.footer {
    text-align: center;
}

/* RESPONSIVE */
@media (max-width: 1000px) {
    .product-list {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 800px) {
    .product-layout {
        grid-template-columns: 1fr;
    }

    .product-list {
        grid-template-columns: repeat(2, 1fr);
    }

    .brand-group {
        margin-left: 0;
    }
}

@media (max-width: 500px) {
    .product-list {
        grid-template-columns: 1fr;
    }

    .product-top {
        align-items: flex-start;
        gap: 15px;
        flex-direction: column;
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

<!-- HEADER -->
<header class="navbar">

    <div class="brand-group">

        <a href="javascript:history.back()"
           class="back-circle"
           title="Kembali"
           aria-label="Kembali">&#8592;</a>

        <a class="brand" href="index.php">
            <span class="brand-mark">👩‍🍳</span>

            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>

    </div>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a class="active" href="produk.php">Menu</a>
        <a href="tentang.php">Tentang Kami</a>
        <a href="blog.php">Blog</a>
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
               aria-label="Login"
               title="Login">♙</a>

        <?php endif; ?>

    </div>

</header>

<!-- HALAMAN PRODUK -->
<main class="product-page">

    <div class="product-page-header">
        <h1>Semua Produk</h1>
        <p>Pilihan risol lezat dari Budiarti Risol</p>
    </div>

    <div class="product-layout">

        <!-- SIDEBAR KATEGORI -->
        <aside class="category-sidebar">

            <h3>Kategori</h3>

            <a href="produk.php<?= $sort !== 'terbaru' ? '?sort=' . urlencode($sort) : ''; ?>"
               class="category-link <?= $kategori == 0 ? 'active' : ''; ?>">
                Semua Produk
            </a>

            <?php while ($cat = mysqli_fetch_assoc($kategori_query)) : ?>

                <a href="produk.php?kategori=<?= (int) $cat['id_category']; ?><?= $sort !== 'terbaru' ? '&sort=' . urlencode($sort) : ''; ?>"
                   class="category-link <?= $kategori == $cat['id_category'] ? 'active' : ''; ?>">

                    <?= htmlspecialchars($cat['nama_category'], ENT_QUOTES, 'UTF-8'); ?>

                </a>

            <?php endwhile; ?>

        </aside>

        <!-- KONTEN PRODUK -->
        <section class="product-content">

            <div class="product-top">

                <div>
                    <h2><?= htmlspecialchars($judul_kategori, ENT_QUOTES, 'UTF-8'); ?></h2>

                    <div class="product-count">
                        <?= $jumlah_produk; ?> produk tersedia
                    </div>
                </div>

                <form method="GET" action="produk.php" class="sort-form">

                    <?php if ($kategori > 0) : ?>
                        <input type="hidden" name="kategori" value="<?= $kategori; ?>">
                    <?php endif; ?>

                    <select name="sort"
                            class="sort-box"
                            onchange="this.form.submit()"
                            aria-label="Urutkan produk">

                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : ''; ?>>
                            Terbaru
                        </option>

                        <option value="termurah" <?= $sort === 'termurah' ? 'selected' : ''; ?>>
                            Harga Terendah
                        </option>

                        <option value="termahal" <?= $sort === 'termahal' ? 'selected' : ''; ?>>
                            Harga Tertinggi
                        </option>

                    </select>

                </form>

            </div>

            <div class="product-list">

                <?php if ($jumlah_produk > 0) : ?>

                    <?php while ($produk = mysqli_fetch_assoc($produk_query)) : ?>

                        <?php
                        $nama_produk = trim($produk['nama_product']);
                        $nama_file = $gambar_produk[$nama_produk] ?? '';
                        $gambar = 'default.jpg';

                        if ($nama_file !== '' && is_file($folder_gambar . $nama_file)) {
                            $gambar = $nama_file;
                        }

                        $gambar_src = $url_gambar . $gambar;
                        $id_produk = (int) $produk['id_product'];
                        $sudah_favorit = in_array($id_produk, $wishlist_ids, true);

                        $harga_jual = (float) $produk['harga'];

                        // Mengambil harga normal jika kolom tersedia
                        $harga_normal = isset($produk['harga_normal'])
                            ? (float) $produk['harga_normal']
                            : $harga_jual;

                        // Promo hanya muncul jika harga normal lebih besar
                        $ada_diskon = $harga_normal > $harga_jual;

                        $persen_diskon = $ada_diskon
                            ? round((($harga_normal - $harga_jual) / $harga_normal) * 100)
                            : 0;

                        $stok_produk = (int) ($produk['stok'] ?? 0);
                        ?>

                        <div class="menu-card">

                            <div class="menu-image">

                                <?php if ($ada_diskon): ?>
                                    <span class="promo-badge">
                                        -<?= $persen_diskon; ?>%
                                    </span>
                                <?php endif; ?>

                                <a href="tambah_wishlist.php?id=<?= $id_produk; ?>"
                                   class="wishlist-heart <?= $sudah_favorit ? 'active' : ''; ?>"
                                   title="<?= $sudah_favorit ? 'Hapus dari Wishlist' : 'Tambah ke Wishlist'; ?>"
                                   aria-label="Wishlist">

                                    <?= $sudah_favorit ? '♥' : '♡'; ?>

                                </a>

                                <img
                                    src="<?= htmlspecialchars($gambar_src, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?= htmlspecialchars($nama_produk, ENT_QUOTES, 'UTF-8'); ?>"
                                    onerror="this.onerror=null; this.src='../assets/images/default.jpg';"
                                >

                            </div>

                            <div class="menu-info">

                                <div class="menu-category">
                                    <?= htmlspecialchars($produk['nama_category'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>

                                <h3>
                                    <?= htmlspecialchars($nama_produk, ENT_QUOTES, 'UTF-8'); ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars($produk['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </p>

                                <div class="menu-bottom">

                                    <div class="price-area">

                                        <?php if ($ada_diskon): ?>
                                            <div class="old-price">
                                                Rp<?= number_format($harga_normal, 0, ',', '.'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="menu-price">
                                            Rp<?= number_format($harga_jual, 0, ',', '.'); ?>
                                        </div>

                                        <?php if ($ada_diskon): ?>
                                            <div class="discount-text">
                                                Hemat Rp<?= number_format($harga_normal - $harga_jual, 0, ',', '.'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="stock">
                                            Stok: <?= $stok_produk; ?>
                                        </div>

                                    </div>

                                    <a href="detail.php?id=<?= $id_produk; ?>" class="detail-button">
                                        Detail
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else : ?>

                    <div class="empty-product">
                        <h3>Produk belum tersedia</h3>
                        <p>Belum ada produk pada kategori ini.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

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
