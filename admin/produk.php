
<?php

include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
ensureCategoryImageColumn($koneksi);

/* =========================
   SEARCH & FILTER
========================= */

$keyword = isset($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';

$id_category_filter = isset($_GET['id_category'])
    ? (int) $_GET['id_category']
    : 0;

/* =========================
   DATA KATEGORI
========================= */

$query_kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM categories ORDER BY nama_category ASC"
);

if (!$query_kategori) {
    die("Gagal mengambil data kategori: " . mysqli_error($koneksi));
}

/* =========================
   DATA PRODUK
========================= */

$sql = "
    SELECT
        products.*,
        categories.nama_category
    FROM products
    LEFT JOIN categories
        ON products.id_category = categories.id_category
    WHERE 1=1
";

if ($keyword !== '') {
    $keyword_aman = mysqli_real_escape_string($koneksi, $keyword);

    $sql .= "
        AND products.nama_product LIKE '%$keyword_aman%'
    ";
}

if ($id_category_filter > 0) {
    $sql .= "
        AND products.id_category = $id_category_filter
    ";
}

/* URUTKAN STOK DARI TERENDAH KE TERTINGGI */

$sql .= " ORDER BY products.stok ASC, products.id_product DESC";

$query_produk = mysqli_query($koneksi, $sql);

$query_stok_habis = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM products WHERE stok <= 0"
);

$total_stok_habis = $query_stok_habis
    ? (int) (mysqli_fetch_assoc($query_stok_habis)['total'] ?? 0)
    : 0;

$query_stok_menipis = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM products WHERE stok BETWEEN 1 AND 5"
);

$total_stok_menipis = $query_stok_menipis
    ? (int) (mysqli_fetch_assoc($query_stok_menipis)['total'] ?? 0)
    : 0;

if (!$query_produk) {
    die("Gagal mengambil data produk: " . mysqli_error($koneksi));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Budiarti Risol</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #F8EFE5;
            color: #542D1C;
        }

        a {
            text-decoration: none;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 230px;
            background: #542D1C;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 25px 15px;
        }

        .sidebar-logo {
            text-align: center;
            padding-bottom: 30px;
        }

        .sidebar-logo .logo-icon {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .sidebar-logo h2 {
            font-family: Georgia, serif;
            font-size: 22px;
            font-style: italic;
            color: white;
        }

        .sidebar-logo p {
            font-size: 9px;
            letter-spacing: 1px;
            color: #EBD3C0;
            margin-top: 3px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            color: #f6e8dc;
            font-size: 13px;
            transition: 0.2s;
        }

        .sidebar-menu a:hover {
            background: #633419;
        }

        .sidebar-menu a.active {
            background: #75401f;
            color: white;
        }

        .sidebar-menu .submenu {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: -2px 0 4px 32px;
            padding-left: 10px;
            border-left: 1px solid #8b5a3c;
        }

        .sidebar-menu .submenu a {
            padding: 9px 10px;
            font-size: 12px;
            color: #e8d4c4;
        }

        .sidebar-menu .submenu a:hover,
        .sidebar-menu .submenu a.active {
            background: #75401f;
            color: white;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        /* MAIN */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
        }

        /* HEADER */

        .top-header {
            height: 70px;
            background: #C99B78;
            border-bottom: 1px solid #eee3d8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }

        .hamburger {
            font-size: 20px;
            color: #5b2c1f;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #542D1C;
        }

        .notification {
            font-size: 18px;
            margin-right: 12px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #EBD3C0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .admin-name {
            font-weight: bold;
        }

        /* CONTENT */

        .page-content {
            padding: 35px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            gap: 20px;
        }

        .page-title h1 {
            font-family: Georgia, serif;
            font-size: 27px;
            color: #542D1C;
            margin-bottom: 7px;
        }

        .page-title p {
            color: #8a7669;
            font-size: 13px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .category-button,
        .add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            transition: 0.2s;
            white-space: nowrap;
        }

        .category-button {
            background: #EBD3C0;
            color: #542D1C;
        }

        .category-button:hover {
            background: #eab957;
        }

        .add-button {
            background: #542D1C;
            color: white;
        }

        .add-button:hover {
            background: #6a3418;
        }

        /* PRODUCT CARD */

        .product-card {
            background: white;
            border: 1px solid #eee4db;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(80, 40, 10, 0.04);
        }

        /* FILTER */

        .filter-area {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box span {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9b897d;
            font-size: 14px;
        }

        .search-box input {
            width: 100%;
            height: 40px;
            border: 1px solid #e5dbd2;
            border-radius: 8px;
            padding: 0 14px 0 37px;
            outline: none;
            font-size: 12px;
            color: #4b392f;
            background: #fffdfb;
        }

        .search-box input:focus {
            border-color: #b4772d;
        }

        .category-filter {
            min-width: 190px;
            height: 40px;
            padding: 0 38px 0 13px;
            border: 1px solid #E5D3C3;
            border-radius: 8px;
            background: #fff;
            color: #542D1C;
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
            cursor: pointer;
            outline: none;
        }

        .category-filter:focus {
            border-color: #C99B78;
        }


        .category-alert {
            padding: 11px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 11px;
            font-weight: bold;
        }

        .category-alert.ok {
            background: #dcefdc;
            color: #286b32;
        }

        .category-alert.err {
            background: #fde3e5;
            color: #a52c38;
        }

        .reset-button {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            padding: 0 15px;
            border-radius: 8px;
            border: 1px solid #e5dbd2;
            color: #745d4d;
            font-size: 12px;
            background: white;
        }

        .reset-button:hover {
            background: #fcf8f3;
        }

        /* TABLE */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #fcf8f3;
            text-align: left;
            padding: 13px 10px;
            font-size: 10px;
            color: #8d796b;
            font-weight: bold;
            border-bottom: 1px solid #eee4db;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1ebe5;
            font-size: 11px;
            color: #5b4a40;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #fffdfa;
        }

        .product-number {
            color: #927e70;
        }

        .product-name {
            font-weight: bold;
            color: #542D1C;
        }

        .category-name {
            display: inline-block;
            padding: 5px 9px;
            background: #fff0df;
            color: #9a5d27;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
        }

        .price {
            font-weight: bold;
            color: #542D1C;
        }

        .stock {
            font-weight: bold;
        }

        /* PRODUCT IMAGE */

        .product-image {
            width: 45px;
            height: 45px;
            border-radius: 9px;
            object-fit: cover;
            background: linear-gradient(135deg, #d77b24, #f0b34c);
            display: block;
        }

        .stock-warning {
            margin-bottom: 18px;
            padding: 12px 15px;
            background: #fff7f0;
            border: 1px solid #f0c9a8;
            border-left: 4px solid #c65d28;
            border-radius: 9px;
            color: #7a3518;
            font-size: 11px;
        }

        .stock-empty {
            color: #47884d;
            font-weight: bold;
        }

        /* STATUS */

        .status-active {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #dcefdc;
            color: #47884d;
            font-size: 9px;
            font-weight: bold;
        }

        .status-empty {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #fde2e2;
            color: #c44750;
            font-size: 9px;
            font-weight: bold;
        }

        .status-low {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #fff0c9;
            color: #b77912;
            font-size: 9px;
            font-weight: bold;
        }

        /* ACTION */

        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .edit-button,
        .stock-button,
        .delete-button {
            padding: 6px 9px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: bold;
        }

        .edit-button {
            background: #f7eadc;
            color: #80502e;
        }

        .edit-button:hover {
            background: #efdcc8;
        }

        .stock-button {
            background: #dcefdc;
            color: #47884d;
        }

        .stock-button:hover {
            background: #c9e5c9;
        }

        .delete-button {
            background: #fde2e2;
            color: #c44750;
        }

        .delete-button:hover {
            background: #f8cdcd;
        }

        /* EMPTY */

        .empty-data {
            text-align: center;
            padding: 45px 20px;
            color: #927f72;
        }

        .empty-data .empty-icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .empty-data p {
            font-size: 13px;
        }


        /* RESPONSIVE */

        @media (max-width: 900px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }

            .filter-area {
                flex-wrap: wrap;
            }

            .search-box {
                flex-basis: 100%;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                width: 180px;
            }

            .main-content {
                margin-left: 180px;
                width: calc(100% - 180px);
            }

            .page-content {
                padding: 20px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .category-button,
            .add-button {
                text-align: center;
            }
        }
    </style>

    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">


<link rel="stylesheet" href="../assets/css/admin-topbar.css">

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

<style id="produk-modal-style">
.add-button{border:0;cursor:pointer;font-family:inherit}.add-category-button{background:#C99B78!important;color:#542D1C!important;border-color:#C99B78!important}.add-category-button:hover{background:#B98967!important}.manage-category-button{background:#fff!important;color:#542D1C!important;border:1px solid #D9C7B8!important}.manage-category-button:hover{background:#F8EFE5!important}
.modal-produk-overlay{position:fixed;inset:0;background:rgba(35,20,13,.58);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:24px;z-index:9999;opacity:0;transition:opacity .2s ease}
.modal-produk-overlay.active{display:flex;opacity:1}
.modal-produk-dialog{width:min(980px,96vw);height:min(900px,92vh);background:#F8EFE5;border:1px solid #E5D3C3;border-radius:18px;box-shadow:0 24px 80px rgba(35,20,13,.32);overflow:hidden;display:flex;flex-direction:column;transform:translateY(12px) scale(.985);transition:transform .22s ease}
.modal-produk-overlay.active .modal-produk-dialog{transform:translateY(0) scale(1)}
.modal-produk-top{height:52px;flex:0 0 52px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 18px 0 22px;border-bottom:1px solid #E5D3C3;color:#542D1C;font-weight:700;font-size:14px}
.modal-produk-close{width:34px;height:34px;border:0;border-radius:50%;background:#F8EFE5;color:#542D1C;font-size:22px;line-height:1;cursor:pointer}
.modal-produk-close:hover{background:#EBD3C0}
.modal-produk-frame{width:100%;height:calc(100% - 52px);border:0;background:#F8EFE5}
body.modal-open{overflow:hidden}
@media(max-width:650px){.modal-produk-overlay{padding:8px}.modal-produk-dialog{width:100%;height:96vh;border-radius:13px}.modal-produk-top{height:48px;flex-basis:48px}}
</style>

</head>

<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->

    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN -->

    <main class="main-content">

        <header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>

        <section class="page-content">

            <div class="page-header">

                <div class="page-title">
                    <h1>Kelola Produk</h1>
                    <p>Tambah dan kelola produk Budiarti Risol.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="add-button" onclick="bukaModalProduk('tambah_produk.php?modal=1', 'Tambah Produk')">
                        + Tambah Produk
                    </button>
                    <button type="button" class="add-button add-category-button" onclick="bukaModalProduk('tambah_kategori.php?modal=1', 'Tambah Kategori')">
                        + Kategori
                    </button>
                    <a href="kategori.php" class="add-button manage-category-button">
                        ⚙ Kelola Kategori
                    </a>
                </div>

            </div>

            <?php if (($_GET['pesan'] ?? '') === 'tambah_kategori_berhasil') : ?>
                <div class="category-alert ok" style="margin-bottom:15px;">✓ Kategori berhasil ditambahkan.</div>
            <?php endif; ?>

            <div class="product-card">

                <!-- PERINGATAN STOK -->

                <?php if ($total_stok_habis > 0 || $total_stok_menipis > 0) : ?>
                    <div class="stock-warning">
                        ⚠️

                        <?php if ($total_stok_habis > 0) : ?>
                            <strong><?= $total_stok_habis; ?> produk</strong> stoknya habis.
                        <?php endif; ?>

                        <?php if ($total_stok_habis > 0 && $total_stok_menipis > 0) : ?>
                            &nbsp;|&nbsp;
                        <?php endif; ?>

                        <?php if ($total_stok_menipis > 0) : ?>
                            <strong><?= $total_stok_menipis; ?> produk</strong> stoknya menipis (1–5).
                        <?php endif; ?>

                        Silakan cek stok pada daftar produk.
                    </div>
                <?php else : ?>
                    <div class="stock-warning stock-empty">
                        ✓ Semua produk memiliki stok yang aman.
                    </div>
                <?php endif; ?>

                <!-- SEARCH & FILTER -->

                <form method="GET" action="produk.php" class="filter-area" id="filterForm">

                    <div class="search-box">
                        <span>⌕</span>

                        <input
                            type="text"
                            name="keyword"
                            placeholder="Cari nama produk..."
                            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>

                    <select
                        name="id_category"
                        class="category-filter"
                        aria-label="Filter kategori"
                        onchange="this.form.submit()"
                    >
                        <option value="0">Semua Kategori</option>
                        <?php
                        mysqli_data_seek($query_kategori, 0);
                        while ($kategori_filter = mysqli_fetch_assoc($query_kategori)) :
                        ?>
                            <option
                                value="<?= (int) $kategori_filter['id_category']; ?>"
                                <?= $id_category_filter === (int) $kategori_filter['id_category'] ? 'selected' : ''; ?>
                            >
                                <?= htmlspecialchars($kategori_filter['nama_category'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <?php if ($keyword !== '' || $id_category_filter > 0) : ?>

                        <a href="produk.php" class="reset-button">
                            Reset
                        </a>

                    <?php endif; ?>

                </form>

                <!-- TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($query_produk) > 0) : ?>

                                <?php $no = 1; ?>

                                <?php while ($produk = mysqli_fetch_assoc($query_produk)) : ?>

                                    <?php
                                    /*
                                     * Mengambil nama gambar dari database.
                                     * Mendukung kolom gambar maupun foto.
                                     */

                                    $nama_file_gambar = '';

                                    if (!empty($produk['gambar'])) {
                                        $nama_file_gambar = $produk['gambar'];
                                    } elseif (!empty($produk['foto'])) {
                                        $nama_file_gambar = $produk['foto'];
                                    }

                                    $nama_file_gambar = basename($nama_file_gambar);

                                    $lokasi_gambar = "../assets/images/" . $nama_file_gambar;
                                    $path_gambar = __DIR__ . "/../assets/images/" . $nama_file_gambar;

                                    $gambar_tersedia = (
                                        $nama_file_gambar !== '' &&
                                        is_file($path_gambar)
                                    );
                                    ?>

                                    <tr>

                                        <td class="product-number">
                                            <?= $no++; ?>
                                        </td>

                                        <!-- GAMBAR PRODUK -->

                                        <td>

                                            <?php if ($gambar_tersedia) : ?>

                                                <img
                                                    src="<?= htmlspecialchars($lokasi_gambar, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="product-image"
                                                    alt="<?= htmlspecialchars($produk['nama_product'], ENT_QUOTES, 'UTF-8'); ?>"
                                                >

                                            <?php else : ?>

                                                <div class="product-image"></div>

                                            <?php endif; ?>

                                        </td>

                                        <!-- NAMA PRODUK -->

                                        <td>
                                            <span class="product-name">
                                                <?= htmlspecialchars(
                                                    $produk['nama_product'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </span>
                                        </td>

                                        <!-- KATEGORI -->

                                        <td>
                                            <span class="category-name">
                                                <?= htmlspecialchars(
                                                    $produk['nama_category'] ?? 'Tanpa Kategori',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </span>
                                        </td>

                                        <!-- HARGA -->

                                        <td class="price">
                                            Rp<?= number_format(
                                                (float) $produk['harga'],
                                                0,
                                                ',',
                                                '.'
                                            ); ?>
                                        </td>

                                        <!-- STOK -->

                                        <td class="stock">
                                            <?= htmlspecialchars(
                                                (string) $produk['stok'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </td>

                                        <!-- STATUS -->

                                        <td>
                                            <?php if ((int) $produk['stok'] <= 0) : ?>
                                                <span class="status-empty">
                                                    Stok Habis
                                                </span>
                                            <?php elseif ((int) $produk['stok'] <= 5) : ?>
                                                <span class="status-low">
                                                    Menipis
                                                </span>
                                            <?php else : ?>
                                                <span class="status-active">
                                                    Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- AKSI -->

                                        <td>

                                            <div class="action-buttons">

                                                <a
                                                    href="edit_produk.php?id=<?= (int) $produk['id_product']; ?>&modal=1"
                                                    class="edit-button" onclick="event.preventDefault(); bukaModalProduk(this.href, 'Edit Produk')"
                                                >
                                                    Edit
                                                </a>

                                                <a
                                                    href="hapus_produk.php?id=<?= (int) $produk['id_product']; ?>"
                                                    class="delete-button"
                                                    onclick="return confirm('Yakin ingin menghapus produk ini?');"
                                                >
                                                    Hapus
                                                </a>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else : ?>

                                <tr>
                                    <td colspan="8">

                                        <div class="empty-data">
                                            <div class="empty-icon">📦</div>
                                            <p>Produk tidak ditemukan.</p>
                                        </div>

                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>


<div class="modal-produk-overlay" id="modalProduk" aria-hidden="true">
  <div class="modal-produk-dialog" role="dialog" aria-modal="true" aria-labelledby="modalProdukTitle">
    <div class="modal-produk-top"><span id="modalProdukTitle">Edit Produk</span><button type="button" class="modal-produk-close" onclick="tutupModalProduk()" aria-label="Tutup">&times;</button></div>
    <iframe class="modal-produk-frame" id="frameProduk" title="Form produk" src="about:blank"></iframe>
  </div>
</div>
<script>
function bukaModalProduk(url, judul){const overlay=document.getElementById('modalProduk');document.getElementById('modalProdukTitle').textContent=judul;document.getElementById('frameProduk').src=url;overlay.style.display='flex';document.body.classList.add('modal-open');requestAnimationFrame(()=>overlay.classList.add('active'));overlay.setAttribute('aria-hidden','false');}
function tutupModalProduk(){const overlay=document.getElementById('modalProduk');overlay.classList.remove('active');overlay.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');setTimeout(()=>{overlay.style.display='none';document.getElementById('frameProduk').src='about:blank';},220);}
document.getElementById('modalProduk').addEventListener('click',function(e){if(e.target===this)tutupModalProduk();});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&document.getElementById('modalProduk').classList.contains('active'))tutupModalProduk();});
</script>

</body>
</html>
