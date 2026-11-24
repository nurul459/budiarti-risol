<?php

include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
ensurePaymentTable($koneksi);
ensureShippingTable($koneksi);

/* =========================
   AKSI PESANAN TERPUSAT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_pesanan'])) {
    $id_order = (int)($_POST['id_order'] ?? 0);
    $aksi = trim($_POST['aksi_pesanan']);
    if ($id_order <= 0) {
        header('Location: pesanan.php?pesan=aksi_gagal'); exit;
    }
    $cek = mysqli_query($koneksi, "SELECT * FROM orders WHERE id_order=$id_order LIMIT 1");
    $order = $cek ? mysqli_fetch_assoc($cek) : null;
    if (!$order) { header('Location: pesanan.php?pesan=aksi_gagal'); exit; }

    if ($aksi === 'verifikasi') {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pembayaran (id_order, metode_pembayaran, jumlah, status_pembayaran, bukti_pembayaran, tanggal_bayar) VALUES (?, ?, ?, 'Terverifikasi', ?, NOW()) ON DUPLICATE KEY UPDATE status_pembayaran='Terverifikasi', tanggal_bayar=NOW(), bukti_pembayaran=VALUES(bukti_pembayaran)");
        $metode=$order['metode_pembayaran']; $jumlah=(float)$order['total_pembayaran']; $bukti=$order['bukti_pembayaran'] ?? '';
        mysqli_stmt_bind_param($stmt,'isds',$id_order,$metode,$jumlah,$bukti);
        $ok=mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        if($ok && $order['status']!=='Dibatalkan') {
            mysqli_query($koneksi,"UPDATE orders SET status='Diproses' WHERE id_order=$id_order");
            mysqli_query($koneksi,"INSERT IGNORE INTO pengiriman (id_order,kurir,nomor_resi,status_pengiriman) VALUES ($id_order,'','','Belum Diproses')");
            header('Location: pesanan.php?status=Diproses&pesan=verifikasi_berhasil'); exit;
        }
    }

    if ($aksi === 'tolak') {
        mysqli_query($koneksi,"UPDATE pembayaran SET status_pembayaran='Ditolak' WHERE id_order=$id_order");
        mysqli_query($koneksi,"UPDATE orders SET status='Menunggu Verifikasi' WHERE id_order=$id_order AND status<>'Dibatalkan'");
        header('Location: pesanan.php?status=Menunggu Verifikasi&pesan=tolak_berhasil'); exit;
    }

    if ($aksi === 'selesaikan') {
        mysqli_query($koneksi,"UPDATE pengiriman SET status_pengiriman='Selesai' WHERE id_order=$id_order");
        mysqli_query($koneksi,"UPDATE orders SET status='Selesai' WHERE id_order=$id_order AND status<>'Dibatalkan'");
        header('Location: pesanan.php?status=Selesai&pesan=selesai_berhasil'); exit;
    }

    if ($aksi === 'atur_pengiriman') {
        $kurir=trim($_POST['kurir'] ?? '');
        $status_kirim=trim($_POST['status_pengiriman'] ?? 'Belum Diproses');
        $kurir_valid=['GoSend (Gojek)','GrabExpress (Grab)'];
        $status_valid=['Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai'];
        if(!in_array($kurir,$kurir_valid,true) || !in_array($status_kirim,$status_valid,true)) {
            header('Location: pesanan.php?status=Diproses&pesan=aksi_gagal'); exit;
        }
        $stmt=mysqli_prepare($koneksi,"INSERT INTO pengiriman (id_order,kurir,nomor_resi,status_pengiriman) VALUES (?,?,'',?) ON DUPLICATE KEY UPDATE kurir=VALUES(kurir),status_pengiriman=VALUES(status_pengiriman)");
        mysqli_stmt_bind_param($stmt,'iss',$id_order,$kurir,$status_kirim);
        $ok=mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        $map=['Belum Diproses'=>'Diproses','Diproses'=>'Diproses','Dikirim'=>'Dikirim','Dalam Perjalanan'=>'Dikirim','Sampai Tujuan'=>'Dikirim','Selesai'=>'Selesai'];
        if($ok && isset($map[$status_kirim])) mysqli_query($koneksi,"UPDATE orders SET status='".mysqli_real_escape_string($koneksi,$map[$status_kirim])."' WHERE id_order=$id_order AND status<>'Dibatalkan'");
        header('Location: pesanan.php?status='.urlencode($map[$status_kirim] ?? 'Diproses').'&pesan='.($ok?'pengiriman_berhasil':'aksi_gagal')); exit;
    }

    header('Location: pesanan.php?pesan=aksi_gagal'); exit;
}

/* =========================
   BATALKAN / PULIHKAN PESANAN
========================= */

// Action 1: Ubah status ke 'Dibatalkan'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pesanan'])) {

    $id_order = isset($_POST['id_order']) ? (int) $_POST['id_order'] : 0;

    if ($id_order > 0) {
        $update_status = mysqli_query(
            $koneksi,
            "UPDATE orders SET status = 'Dibatalkan' WHERE id_order = $id_order"
        );

        if ($update_status && mysqli_affected_rows($koneksi) > 0) {
            header("Location: pesanan.php?status=Dibatalkan&pesan=batal_berhasil");
            exit;
        } else {
            header("Location: pesanan.php?pesan=batal_gagal");
            exit;
        }
    }

    header("Location: pesanan.php?pesan=batal_gagal");
    exit;
}

// Action 2: Pulihkan status pesanan dari 'Dibatalkan' kembali ke 'Diproses'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pulihkan_pesanan'])) {

    $id_order = isset($_POST['id_order']) ? (int) $_POST['id_order'] : 0;

    if ($id_order > 0) {
        $update_status = mysqli_query(
            $koneksi,
            "UPDATE orders SET status = 'Diproses' WHERE id_order = $id_order"
        );

        if ($update_status && mysqli_affected_rows($koneksi) > 0) {
            header("Location: pesanan.php?status=Diproses&pesan=pulih_berhasil");
            exit;
        } else {
            header("Location: pesanan.php?pesan=pulih_gagal");
            exit;
        }
    }

    header("Location: pesanan.php?pesan=pulih_gagal");
    exit;
}


/* =========================
   SEARCH & FILTER
========================= */

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

/* =========================
   QUERY PESANAN
========================= */

$sql = "
    SELECT o.id_order, o.nama_penerima, o.total_pembayaran, o.status, o.created_at,
           o.metode_pembayaran, o.bukti_pembayaran,
           COALESCE(p.status_pembayaran, CASE WHEN o.metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END) AS status_pembayaran,
           p.bukti_pembayaran AS bukti_pembayaran_modul,
           COALESCE(pg.status_pengiriman, 'Belum Diproses') AS status_pengiriman, pg.kurir
    FROM orders o
    LEFT JOIN pembayaran p ON p.id_order=o.id_order
    LEFT JOIN pengiriman pg ON pg.id_order=o.id_order
    WHERE 1=1
";

/* Filter pencarian */

if ($keyword !== '') {

    $keyword_aman = mysqli_real_escape_string(
        $koneksi,
        $keyword
    );

    $sql .= "
        AND (
            CAST(id_order AS CHAR) LIKE '%$keyword_aman%'
            OR nama_penerima LIKE '%$keyword_aman%'
        )
    ";
}

/* Filter status */

if ($status_filter !== '') {
    $status_aman = mysqli_real_escape_string($koneksi, $status_filter);
    if ($status_filter === 'Menunggu Verifikasi') {
        $sql .= " AND COALESCE(p.status_pembayaran, CASE WHEN o.metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END) IN ('Menunggu Verifikasi','Belum Dibayar') AND o.status NOT IN ('Selesai','Dibatalkan')";
    } else {
        $sql .= " AND o.status = '$status_aman'";
    }
}

$sql .= "
    ORDER BY id_order DESC
";

$query_pesanan = mysqli_query(
    $koneksi,
    $sql
);

/* =========================
   JUMLAH PESANAN PER STATUS
========================= */

$query_semua = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM orders"
);

$total_semua = mysqli_fetch_assoc($query_semua)['total'];

$query_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM orders o LEFT JOIN pembayaran p ON p.id_order=o.id_order WHERE COALESCE(p.status_pembayaran, CASE WHEN o.metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END) IN ('Menunggu Verifikasi','Belum Dibayar') AND o.status NOT IN ('Selesai','Dibatalkan')");
$total_menunggu = $query_menunggu ? mysqli_fetch_assoc($query_menunggu)['total'] : 0;

$query_diproses = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Diproses'"
);

$total_diproses = mysqli_fetch_assoc($query_diproses)['total'];

$query_dikirim = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Dikirim'"
);

$total_dikirim = mysqli_fetch_assoc($query_dikirim)['total'];

$query_selesai = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Selesai'"
);

$total_selesai = mysqli_fetch_assoc($query_selesai)['total'];

$query_dibatalkan = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE status = 'Dibatalkan'"
);

$total_dibatalkan = mysqli_fetch_assoc($query_dibatalkan)['total'];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Kelola Pesanan - Budiarti Risol</title>

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

        /* =========================
           LAYOUT
        ========================= */

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

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

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        /* =========================
           MAIN
        ========================= */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
        }

        /* =========================
           HEADER
        ========================= */

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

        /* =========================
           CONTENT
        ========================= */

        .page-content {
            padding: 35px;
        }

        .page-header {
            margin-bottom: 25px;
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

        /* =========================
           NOTIFIKASI
        ========================= */

        .alert {
            padding: 12px 16px;
            margin-bottom: 18px;
            border-radius: 8px;
            font-size: 13px;
        }

        .alert-success {
            background: #dcefdc;
            color: #286b32;
        }

        .alert-error {
            background: #fde3e5;
            color: #a52c38;
        }

        /* =========================
           STATUS FILTER
        ========================= */

        .status-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .status-tab {
            padding: 9px 15px;
            border-radius: 20px;
            background: white;
            border: 1px solid #e7ddd4;
            color: #705e52;
            font-size: 11px;
            font-weight: bold;
            transition: 0.2s;
        }

        .status-tab:hover {
            background: #fff3e4;
        }

        .status-tab.active {
            background: #542D1C;
            color: white;
            border-color: #542D1C;
        }

        /* =========================
           ORDER CARD
        ========================= */

        .order-card {
            background: white;
            border: 1px solid #eee4db;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(80, 40, 10, 0.04);
        }

        /* =========================
           SEARCH
        ========================= */

        .search-area {
            display: flex;
            gap: 10px;
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

        .search-button {
            height: 40px;
            border: none;
            border-radius: 8px;
            padding: 0 18px;
            background: #EBD3C0;
            color: #542D1C;
            font-weight: bold;
            cursor: pointer;
            font-size: 12px;
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

        /* =========================
           TABLE
        ========================= */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
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
            padding: 13px 10px;
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

        .order-number {
            color: #5b2c1f;
            font-weight: bold;
        }

        .customer-name {
            font-weight: bold;
            color: #542D1C;
        }

        .total-price {
            font-weight: bold;
            color: #542D1C;
        }

        .date {
            color: #8b786b;
        }

        /* =========================
           STATUS BADGE
        ========================= */

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-diproses {
            background: #fff0c9;
            color: #bd7a13;
        }

        .status-dikirim {
            background: #dceefa;
            color: #397fa9;
        }

        .status-selesai {
            background: #dcefdc;
            color: #47884d;
        }

        .status-dibatalkan {
            background: #eeeeee;
            color: #777777;
        }

        /* =========================
           DETAIL, BATAL, & PULIHKAN BUTTON
        ========================= */

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-button {
            display: inline-block;
            padding: 7px 12px;
            background: #f7eadc;
            color: #80502e;
            border-radius: 7px;
            font-size: 9px;
            font-weight: bold;
            transition: 0.2s;
        }

        .detail-button:hover {
            background: #efdcc8;
        }

        .delete-button {
            display: inline-block;
            padding: 7px 12px;
            background: #fde3e5;
            color: #bd303d;
            border: none;
            border-radius: 7px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .delete-button:hover {
            background: #f8cbd0;
        }

        .restore-button {
            display: inline-block;
            padding: 7px 12px;
            background: #dcefdc;
            color: #286b32;
            border: none;
            border-radius: 7px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .restore-button:hover {
            background: #c3e6c3;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty-data {
            text-align: center;
            padding: 50px 20px;
            color: #927f72;
        }

        .empty-data .empty-icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .empty-data p {
            font-size: 13px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 800px) {

            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }

            .page-content {
                padding: 25px;
            }

        }

        @media (max-width: 600px) {

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

            .search-area {
                flex-direction: column;
            }

            .search-button,
            .reset-button {
                width: 100%;
            }

        }

    </style>

<link rel="stylesheet" href="../assets/css/admin-sidebar.css">
<style>.admin-tabs{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 20px;padding:5px;background:#fff;border:1px solid #eadfd5;border-radius:12px;box-shadow:0 2px 8px rgba(74,34,14,.05)}.admin-tabs a{display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;color:#765a46;text-decoration:none;font-size:12px;font-weight:600}.admin-tabs a:hover{background:#f8f0e8}.admin-tabs a.active{background:#6d3519;color:#fff}</style>
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
</head>

<body>

<div class="admin-wrapper">

    <!-- =========================
         SIDEBAR
    ========================= -->

    <?php include __DIR__ . '/sidebar.php'; ?>


    <!-- =========================
         MAIN CONTENT
    ========================= -->

    <main class="main-content">

        <!-- HEADER -->

        <header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>


        <!-- CONTENT -->

        <section class="page-content">

            <!-- PAGE TITLE -->

            <?php include __DIR__ . '/tabs_pesanan.php'; ?>

            <div class="page-header">

                <div class="page-title">

                    <h1>Kelola Pesanan</h1>

                    <p>
                        Pantau dan kelola semua pesanan pelanggan.
                    </p>

                </div>

            </div>


            <!-- NOTIFIKASI -->

            <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'batal_berhasil') : ?>

                <div class="alert alert-success">
                    Pesanan berhasil dibatalkan dan dipindahkan ke daftar Dibatalkan.
                </div>

            <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] === 'pulih_berhasil') : ?>

                <div class="alert alert-success">
                    Pesanan berhasil dipulihkan dan dikembalikan ke status Diproses!
                </div>

            <?php elseif (isset($_GET['pesan']) && ($_GET['pesan'] === 'batal_gagal' || $_GET['pesan'] === 'pulih_gagal')) : ?>

                <div class="alert alert-error">
                    Gagal memperbarui status pesanan. Silakan coba lagi.
                </div>

            <?php endif; ?>


            <!-- STATUS TABS -->

            <div class="status-tabs">

                <a
                    href="pesanan.php"
                    class="status-tab <?= $status_filter === '' ? 'active' : ''; ?>"
                >
                    Semua (<?= $total_semua; ?>)
                </a>

                <a
                    href="pesanan.php?status=Menunggu%20Verifikasi"
                    class="status-tab <?= $status_filter === 'Menunggu Verifikasi' ? 'active' : ''; ?>"
                >
                    Menunggu Verifikasi (<?= $total_menunggu; ?>)
                </a>

                <a
                    href="pesanan.php?status=Diproses"
                    class="status-tab <?= $status_filter === 'Diproses' ? 'active' : ''; ?>"
                >
                    Diproses (<?= $total_diproses; ?>)
                </a>

                <a
                    href="pesanan.php?status=Dikirim"
                    class="status-tab <?= $status_filter === 'Dikirim' ? 'active' : ''; ?>"
                >
                    Dikirim (<?= $total_dikirim; ?>)
                </a>

                <a
                    href="pesanan.php?status=Selesai"
                    class="status-tab <?= $status_filter === 'Selesai' ? 'active' : ''; ?>"
                >
                    Selesai (<?= $total_selesai; ?>)
                </a>

                <a
                    href="pesanan.php?status=Dibatalkan"
                    class="status-tab <?= $status_filter === 'Dibatalkan' ? 'active' : ''; ?>"
                >
                    Dibatalkan (<?= $total_dibatalkan; ?>)
                </a>

            </div>


            <!-- ORDER CARD -->

            <div class="order-card">

                <!-- SEARCH -->

                <form
                    method="GET"
                    action="pesanan.php"
                    class="search-area"
                >

                    <?php if ($status_filter !== '') : ?>

                        <input
                            type="hidden"
                            name="status"
                            value="<?= htmlspecialchars($status_filter); ?>"
                        >

                    <?php endif; ?>

                    <div class="search-box">

                        <span>⌕</span>

                        <input
                            type="text"
                            name="keyword"
                            placeholder="Cari no. pesanan / nama pelanggan..."
                            value="<?= htmlspecialchars($keyword); ?>"
                        >

                    </div>

                    <button
                        type="submit"
                        class="search-button"
                    >
                        Cari
                    </button>

                    <?php if ($keyword !== '') : ?>

                        <a
                            href="pesanan.php<?= $status_filter !== '' ? '?status=' . urlencode($status_filter) : ''; ?>"
                            class="reset-button"
                        >
                            Reset
                        </a>

                    <?php endif; ?>

                </form>


                <!-- TABLE -->

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if ($query_pesanan && mysqli_num_rows($query_pesanan) > 0) : ?>

                                <?php while ($pesanan = mysqli_fetch_assoc($query_pesanan)) : ?>

                                    <tr>

                                        <!-- NOMOR -->

                                        <td class="order-number">

                                            #ORD<?= str_pad(
                                                $pesanan['id_order'],
                                                3,
                                                '0',
                                                STR_PAD_LEFT
                                            ); ?>

                                        </td>


                                        <!-- PELANGGAN -->

                                        <td class="customer-name">

                                            <?= htmlspecialchars(
                                                $pesanan['nama_penerima']
                                            ); ?>

                                        </td>


                                        <!-- TANGGAL -->

                                        <td class="date">

                                            <?= date(
                                                'd/m/Y',
                                                strtotime($pesanan['created_at'])
                                            ); ?>

                                        </td>


                                        <!-- TOTAL -->

                                        <td class="total-price">

                                            Rp<?= number_format(
                                                $pesanan['total_pembayaran'],
                                                0,
                                                ',',
                                                '.'
                                            ); ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php

                                            $status_class = '';

                                            switch ($pesanan['status']) {

                                                case 'Menunggu Verifikasi':
                                                    $status_class = 'status-menunggu';
                                                    break;
                                                case 'Diproses':
                                                    $status_class = 'status-diproses';
                                                    break;

                                                case 'Dikirim':
                                                    $status_class = 'status-dikirim';
                                                    break;

                                                case 'Selesai':
                                                    $status_class = 'status-selesai';
                                                    break;

                                                case 'Dibatalkan':
                                                    $status_class = 'status-dibatalkan';
                                                    break;

                                                default:
                                                    $status_class = 'status-dibatalkan';

                                            }

                                            ?>

                                            <span class="status <?= $status_class; ?>">

                                                <?= $status_filter === 'Menunggu Verifikasi' ? 'Menunggu Verifikasi' : htmlspecialchars($pesanan['status']); ?>

                                            </span>

                                        </td>


                                        <!-- AKSI -->

                                        <td>

                                            <div class="action-buttons">
                                                <a href="detail_pesanan.php?id=<?= (int)$pesanan['id_order']; ?>" class="detail-button">Detail</a>
                                                <?php $payment_status = $pesanan['status_pembayaran']; $proof = $pesanan['bukti_pembayaran_modul'] ?: $pesanan['bukti_pembayaran']; ?>
                                                <?php if ($pesanan['status'] === 'Dibatalkan'): ?>
                                                    <form method="POST" onsubmit="return confirm('Pulihkan pesanan ini ke Diproses?');"><input type="hidden" name="id_order" value="<?= (int)$pesanan['id_order']; ?>"><button type="submit" name="pulihkan_pesanan" value="1" class="restore-button">Pulihkan</button></form>
                                                <?php elseif (in_array($payment_status, ['Menunggu Verifikasi','Belum Dibayar'], true) && !in_array($pesanan['status'], ['Selesai','Dikirim'], true)): ?>
                                                    <?php if ($proof): ?><a href="pembayaran.php?edit=<?= (int)$pesanan['id_order']; ?>" class="detail-button">Lihat Bukti</a><?php endif; ?>
                                                    <form method="POST" onsubmit="return confirm('Verifikasi pembayaran pesanan ini?');"><input type="hidden" name="id_order" value="<?= (int)$pesanan['id_order']; ?>"><input type="hidden" name="aksi_pesanan" value="verifikasi"><button type="submit" class="restore-button">Verifikasi</button></form>
                                                    <form method="POST" onsubmit="return confirm('Tolak pembayaran pesanan ini?');"><input type="hidden" name="id_order" value="<?= (int)$pesanan['id_order']; ?>"><input type="hidden" name="aksi_pesanan" value="tolak"><button type="submit" class="delete-button">Tolak</button></form>
                                                <?php elseif ($pesanan['status'] === 'Diproses'): ?>
                                                    <button type="button" class="detail-button" onclick="openShippingModal(<?= (int)$pesanan['id_order']; ?>,'<?= htmlspecialchars($pesanan['kurir'] ?? '', ENT_QUOTES); ?>','<?= htmlspecialchars($pesanan['status_pengiriman'] ?? 'Belum Diproses', ENT_QUOTES); ?>')">Atur Pengiriman</button>
                                                <?php elseif ($pesanan['status'] === 'Dikirim'): ?>
                                                    <form method="POST" onsubmit="return confirm('Tandai pesanan ini sebagai selesai?');"><input type="hidden" name="id_order" value="<?= (int)$pesanan['id_order']; ?>"><input type="hidden" name="aksi_pesanan" value="selesaikan"><button type="submit" class="restore-button">Tandai Selesai</button></form>
                                                <?php endif; ?>
                                                <?php if ($pesanan['status'] !== 'Selesai' && $pesanan['status'] !== 'Dibatalkan'): ?>
                                                    <form method="POST" onsubmit="return confirm('Batalkan pesanan ini?');"><input type="hidden" name="id_order" value="<?= (int)$pesanan['id_order']; ?>"><button type="submit" name="hapus_pesanan" value="1" class="delete-button">Batalkan</button></form>
                                                <?php endif; ?>
                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else : ?>

                                <tr>

                                    <td colspan="6">

                                        <div class="empty-data">

                                            <div class="empty-icon">
                                                🧾
                                            </div>

                                            <p>
                                                Pesanan tidak ditemukan.
                                            </p>

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


<div id="shippingModal" class="modal-overlay" aria-hidden="true">
  <div class="shipping-modal">
    <button type="button" class="modal-close" onclick="closeShippingModal()">×</button>
    <h2>Atur Pengiriman</h2>
    <p id="shippingOrderLabel"></p>
    <form method="POST">
      <input type="hidden" name="aksi_pesanan" value="atur_pengiriman">
      <input type="hidden" name="id_order" id="shippingOrderId">
      <label>Layanan Ojol</label>
      <select name="kurir" id="shippingKurir" required><option value="">Pilih layanan</option><option>GoSend (Gojek)</option><option>GrabExpress (Grab)</option></select>
      <label>Status Pengiriman</label>
      <select name="status_pengiriman" id="shippingStatus" required><option>Belum Diproses</option><option>Diproses</option><option>Dikirim</option><option>Dalam Perjalanan</option><option>Sampai Tujuan</option><option>Selesai</option></select>
      <button type="submit" class="modal-save">Simpan Pengiriman</button>
    </form>
  </div>
</div>
<style>
.status-menunggu{background:#fff0d0!important;color:#9a6200!important}.modal-overlay{display:none;position:fixed;inset:0;background:rgba(40,20,10,.45);align-items:center;justify-content:center;z-index:9999}.modal-overlay.show{display:flex}.shipping-modal{width:min(430px,92vw);background:#fff;border-radius:14px;padding:24px;position:relative;box-shadow:0 15px 50px rgba(0,0,0,.2)}.shipping-modal h2{margin-bottom:6px}.shipping-modal p{font-size:12px;color:#8a7669;margin-bottom:18px}.shipping-modal label{display:block;font-size:12px;font-weight:bold;margin:12px 0 6px}.shipping-modal select{width:100%;height:40px;border:1px solid #ddd0c5;border-radius:8px;padding:0 10px}.modal-close{position:absolute;right:14px;top:10px;border:0;background:none;font-size:24px;cursor:pointer}.modal-save{margin-top:18px;width:100%;border:0;border-radius:8px;background:#542D1C;color:#fff;padding:11px;font-weight:bold;cursor:pointer}
</style>
<script>
function openShippingModal(id,kurir,status){document.getElementById('shippingOrderId').value=id;document.getElementById('shippingOrderLabel').textContent='#ORD'+String(id).padStart(3,'0');document.getElementById('shippingKurir').value=kurir||'';document.getElementById('shippingStatus').value=status||'Belum Diproses';document.getElementById('shippingModal').classList.add('show');}
function closeShippingModal(){document.getElementById('shippingModal').classList.remove('show')}
document.getElementById('shippingModal')?.addEventListener('click',e=>{if(e.target.id==='shippingModal')closeShippingModal()});
</script>
</body>

</html>