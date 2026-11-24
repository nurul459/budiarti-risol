<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pesanan.php");
    exit;
}

$id_order = (int) $_GET['id'];

$query_order = mysqli_query($koneksi, "SELECT * FROM orders WHERE id_order = $id_order");

if (mysqli_num_rows($query_order) === 0) {
    echo "Pesanan tidak ditemukan.";
    exit;
}

$order = mysqli_fetch_assoc($query_order);

$query_detail = mysqli_query($koneksi, "SELECT order_details.*, products.nama_product FROM order_details INNER JOIN products ON order_details.id_product = products.id_product WHERE order_details.id_order = $id_order ORDER BY order_details.id_detail ASC");

// Data pengiriman (akan dibuat otomatis jika tabel belum ada)
ensureShippingTable($koneksi);
$query_pengiriman = mysqli_query($koneksi, "SELECT * FROM pengiriman WHERE id_order = $id_order LIMIT 1");
$pengiriman = $query_pengiriman ? mysqli_fetch_assoc($query_pengiriman) : null;

$nomor_order = '#ORD' . str_pad($order['id_order'], 3, '0', STR_PAD_LEFT);

$status_class = '';
switch ($order['status']) {
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
        $status_class = 'status-diproses';
}

$tanggal_order = date('d/m/Y H:i', strtotime($order['created_at']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail <?= $nomor_order; ?> - Budiarti Risol</title>
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
.main-content {
    margin-left: 230px;
    width: calc(100% - 230px);
    min-height: 100vh;
}
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
.page-content {
    padding: 30px 35px 50px;
}
.back-link {
    display: inline-block;
    color: #7c624f;
    font-size: 12px;
    margin-bottom: 15px;
}
.back-link:hover {
    color: #542D1C;
}
.page-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
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
    font-size: 12px;
}
.status {
    display: inline-block;
    padding: 8px 13px;
    border-radius: 20px;
    font-size: 10px;
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
.detail-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(270px, 0.8fr);
    gap: 20px;
}
.left-column,
.right-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.card {
    background: white;
    border: 1px solid #eee4db;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(80, 40, 10, 0.04);
}
.card-title {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: bold;
    color: #542D1C;
    margin-bottom: 20px;
}
.card-title-icon {
    width: 29px;
    height: 29px;
    border-radius: 8px;
    background: #fff0df;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.customer-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
.info-item label {
    display: block;
    font-size: 9px;
    color: #9b887a;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.info-item p {
    font-size: 12px;
    color: #4b392f;
    line-height: 1.5;
}
.customer-name {
    font-weight: bold;
    color: #542D1C !important;
}
.address-box {
    background: #F8EFE5;
    border: 1px solid #f0e2d4;
    border-radius: 10px;
    padding: 16px;
}
.address-box p {
    color: #5c4a3e;
    font-size: 11px;
    line-height: 1.7;
}
.table-wrapper {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 500px;
}
th {
    text-align: left;
    background: #fcf8f3;
    color: #8d796b;
    font-size: 9px;
    padding: 11px 9px;
    border-bottom: 1px solid #eee4db;
}
td {
    padding: 12px 9px;
    font-size: 11px;
    color: #5b4a40;
    border-bottom: 1px solid #f1ebe5;
}
tr:last-child td {
    border-bottom: none;
}
.product-name {
    color: #542D1C;
    font-weight: bold;
}
.product-price,
.product-subtotal {
    font-weight: bold;
    color: #542D1C;
}
.payment-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 10px 0;
    font-size: 11px;
    color: #766459;
}
.payment-row.total {
    border-top: 1px solid #eee4db;
    margin-top: 8px;
    padding-top: 15px;
    font-size: 15px;
    font-weight: bold;
    color: #542D1C;
}
.payment-method {
    background: #F8EFE5;
    border-radius: 8px;
    padding: 9px 11px;
    color: #5c4a3e;
    font-size: 11px;
    margin-top: 5px;
}
.status-history {
    position: relative;
    padding-left: 30px;
}
.status-history::before {
    content: "";
    position: absolute;
    left: 7px;
    top: 7px;
    bottom: 7px;
    width: 2px;
    background: #e7ddd4;
}
.history-item {
    position: relative;
    padding-bottom: 20px;
}
.history-item:last-child {
    padding-bottom: 0;
}
.history-dot {
    position: absolute;
    left: -30px;
    top: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #ddd3cb;
    border: 3px solid white;
    box-shadow: 0 0 0 1px #ddd3cb;
}
.history-item.active .history-dot {
    background: #f3a928;
    box-shadow: 0 0 0 1px #f3a928;
}
.history-item.done .history-dot {
    background: #5aa660;
    box-shadow: 0 0 0 1px #5aa660;
}
.history-title {
    font-size: 11px;
    font-weight: bold;
    color: #5a493e;
    margin-bottom: 4px;
}
.history-item.active .history-title {
    color: #a9660f;
}
.history-date {
    font-size: 9px;
    color: #a18e81;
}
.status-form {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #eee4db;
}
.status-form label {
    display: block;
    font-size: 10px;
    font-weight: bold;
    color: #695548;
    margin-bottom: 8px;
}
.status-form-row {
    display: flex;
    gap: 8px;
}
.status-select {
    flex: 1;
    height: 38px;
    border: 1px solid #e4d8ce;
    border-radius: 7px;
    padding: 0 10px;
    font-size: 11px;
    color: #5c4a3e;
    background: white;
    outline: none;
}
.update-button {
    height: 38px;
    border: none;
    border-radius: 7px;
    padding: 0 15px;
    background: #542D1C;
    color: white;
    font-size: 10px;
    font-weight: bold;
    cursor: pointer;
}
.update-button:hover {
    background: #6a3418;
}
.note-box {
    background: #F8EFE5;
    border: 1px solid #f0e2d4;
    border-radius: 9px;
    padding: 14px;
    margin-top: 18px;
}
.note-box h4 {
    font-size: 10px;
    color: #7b6251;
    margin-bottom: 7px;
}
.note-box p {
    font-size: 11px;
    color: #6e5c50;
    line-height: 1.6;
}
@media (max-width: 950px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 700px) {
    .sidebar {
        width: 190px;
    }
    .main-content {
        margin-left: 190px;
        width: calc(100% - 190px);
    }
    .page-content {
        padding: 22px;
    }
    .page-title-row {
        align-items: flex-start;
        flex-direction: column;
    }
    .customer-info {
        grid-template-columns: 1fr;
    }
}
</style>
<link rel="stylesheet" href="../assets/css/admin-sidebar.css"><link rel="stylesheet" href="../assets/css/admin-topbar.css">

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
<?php include __DIR__ . '/sidebar.php'; ?>
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
<a href="pesanan.php" class="back-link">← Kembali ke Daftar Pesanan</a>
<!-- TITLE -->
<div class="page-title-row">
<div class="page-title">
<h1>Detail Pesanan <?= $nomor_order; ?></h1>
<p>Pesanan dibuat pada <?= $tanggal_order; ?></p>
</div>
<span class="status <?= $status_class; ?>"><?= htmlspecialchars($order['status']); ?></span>
</div>
<!-- DETAIL GRID -->
<div class="detail-grid">
<div class="left-column">
<!-- CUSTOMER -->
<div class="card">
<div class="card-title">
<div class="card-title-icon">👤</div>
Informasi Pelanggan
</div>
<div class="customer-info">
<div class="info-item">
<label>Nama Penerima</label>
<p class="customer-name"><?= htmlspecialchars($order['nama_penerima']); ?></p>
</div>
<div class="info-item">
<label>No. HP</label>
<p><?= htmlspecialchars($order['no_hp']); ?></p>
</div>
<div class="info-item">
<label>Provinsi</label>
<p><?= htmlspecialchars($order['provinsi']); ?></p>
</div>
<div class="info-item">
<label>Kota</label>
<p><?= htmlspecialchars($order['kota']); ?></p>
</div>
<div class="info-item">
<label>Kecamatan</label>
<p><?= htmlspecialchars($order['kecamatan']); ?></p>
</div>
<div class="info-item">
<label>Kode Pos</label>
<p><?= htmlspecialchars($order['kode_pos']); ?></p>
</div>
</div>
<!-- ALAMAT -->
<div class="note-box">
<h4>📍 Alamat Pengiriman</h4>
<p><?= htmlspecialchars($order['alamat']); ?><br><?= htmlspecialchars($order['kecamatan']); ?>, <?= htmlspecialchars($order['kota']); ?>, <?= htmlspecialchars($order['provinsi']); ?><br>Kode Pos: <?= htmlspecialchars($order['kode_pos']); ?></p>
</div>
</div>
<!-- PRODUCTS -->
<div class="card">
<div class="card-title">
<div class="card-title-icon">🛒</div>
Produk yang Dipesan
</div>
<div class="table-wrapper">
<table>
<thead>
<tr>
<th>No.</th>
<th>Nama Produk</th>
<th>Jumlah</th>
<th>Harga</th>
<th>Subtotal</th>
</tr>
</thead>
<tbody>
<?php
$no = 1;
while ($detail = mysqli_fetch_assoc($query_detail)) :
?>
<tr>
<td><?= $no++; ?></td>
<td class="product-name"><?= htmlspecialchars($detail['nama_product']); ?></td>
<td><?= htmlspecialchars($detail['jumlah']); ?></td>
<td class="product-price">Rp<?= number_format($detail['harga'], 0, ',', '.'); ?></td>
<td class="product-subtotal">Rp<?= number_format($detail['subtotal'], 0, ',', '.'); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>
<div class="right-column">
<!-- PAYMENT -->
<div class="card">
<div class="card-title">
<div class="card-title-icon">💳</div>
Ringkasan Pembayaran
</div>
<div class="payment-row">
<span>Subtotal</span>
<strong>Rp<?= number_format($order['total_harga'], 0, ',', '.'); ?></strong>
</div>
<div class="payment-row">
<span>Ongkir</span>
<strong>Rp<?= number_format($order['ongkir'], 0, ',', '.'); ?></strong>
</div>
<div class="payment-row total">
<span>Total</span>
<span>Rp<?= number_format($order['total_pembayaran'], 0, ',', '.'); ?></span>
</div>
<div style="margin-top:18px;">
<div style="font-size:10px;color:#9b887a;margin-bottom:7px;">METODE PEMBAYARAN</div>
<div class="payment-method"><?= htmlspecialchars($order['metode_pembayaran']); ?></div>
<?php
ensurePaymentTable($koneksi);
mysqli_query($koneksi, "INSERT IGNORE INTO pembayaran (id_order, metode_pembayaran, jumlah, status_pembayaran, bukti_pembayaran) SELECT id_order, metode_pembayaran, total_pembayaran, CASE WHEN metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END, bukti_pembayaran FROM orders WHERE id_order=".(int)$order['id_order']);
$qp = mysqli_query($koneksi, "SELECT status_pembayaran, tanggal_bayar FROM pembayaran WHERE id_order=".(int)$order['id_order']." LIMIT 1");
$payment_info = $qp ? mysqli_fetch_assoc($qp) : null;
?>
<div style="margin-top:15px;font-size:10px;color:#9b887a;margin-bottom:7px;">STATUS PEMBAYARAN</div>
<div class="payment-method"><?= htmlspecialchars($payment_info['status_pembayaran'] ?? 'Menunggu Verifikasi'); ?></div>
<?php if (!empty($order['bukti_pembayaran'])): ?>
<div style="margin-top:12px;"><a href="lihat_bukti.php?id_order=<?= (int)$order['id_order']; ?>" target="_blank" style="font-size:11px;color:#8a421b;font-weight:bold;">Lihat Bukti Pembayaran ↗</a></div>
<?php endif; ?>
<div style="margin-top:12px;"><a href="pembayaran.php?edit=<?= (int)$order['id_order']; ?>" style="font-size:11px;color:#8a421b;font-weight:bold;">Kelola Pembayaran →</a></div>
</div>
</div>
<!-- SHIPPING -->
<div class="card">
<div class="card-title">
<div class="card-title-icon">🚚</div>
Informasi Pengiriman
</div>
<div class="payment-row"><span>Layanan Pengiriman</span><strong><?= !empty($pengiriman['kurir']) ? 'Dikirim menggunakan ' . htmlspecialchars($pengiriman['kurir']) : 'Belum ditentukan'; ?></strong></div>
<div class="payment-row"><span>Status Pengiriman</span><strong><?= htmlspecialchars($pengiriman['status_pengiriman'] ?? 'Belum Diproses'); ?></strong></div>
<div style="margin-top:12px;"><a href="pengiriman.php?periode=semua&keyword=<?= (int)$order['id_order']; ?>" style="font-size:11px;color:#8a421b;font-weight:bold;">Pantau di Pengiriman →</a></div>
</div>
<!-- STATUS HISTORY -->
<div class="card">
<div class="card-title">
<div class="card-title-icon">⏱</div>
Status Pesanan
</div>
<div class="status-history">
<!-- DIPROSES -->
<div class="history-item <?= in_array($order['status'], ['Diproses', 'Dikirim', 'Selesai']) ? 'done' : ''; ?>">
<div class="history-dot"></div>
<div class="history-title">Diproses</div>
<div class="history-date"><?= in_array($order['status'], ['Diproses', 'Dikirim', 'Selesai']) ? $tanggal_order : '-'; ?></div>
</div>
<!-- DIKIRIM -->
<div class="history-item <?= $order['status'] === 'Dikirim' ? 'active' : ($order['status'] === 'Selesai' ? 'done' : ''); ?>">
<div class="history-dot"></div>
<div class="history-title">Dikirim</div>
<div class="history-date"><?= in_array($order['status'], ['Dikirim', 'Selesai']) ? $tanggal_order : '-'; ?></div>
</div>
<!-- SELESAI -->
<div class="history-item <?= $order['status'] === 'Selesai' ? 'active' : ''; ?>">
<div class="history-dot"></div>
<div class="history-title">Selesai</div>
<div class="history-date"><?= $order['status'] === 'Selesai' ? $tanggal_order : '-'; ?></div>
</div>
</div>
<!-- UPDATE STATUS -->
<form action="update_status.php" method="POST" class="status-form">
<input type="hidden" name="id_order" value="<?= $order['id_order']; ?>">
<label>Ubah Status Pesanan</label>
<div class="status-form-row">
<select name="status" class="status-select" id="statusPesananSelect" required>
<option value="Menunggu Verifikasi" <?= $order['status'] === 'Menunggu Verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
<option value="Diproses" <?= $order['status'] === 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
<option value="Dikirim" <?= $order['status'] === 'Dikirim' ? 'selected' : ''; ?>>Dikirim</option>
<option value="Selesai" <?= $order['status'] === 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
<option value="Dibatalkan" <?= $order['status'] === 'Dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
</select>
<button type="submit" class="update-button">Ubah</button>
<span class="shipping-hint" id="shippingHint">Pantau pesanan di Pengiriman</span>
</div>
</form>
</div>
</div>
</div>
</section>
</main>
</div>

<style>
.shipping-hint{display:none;align-items:center;padding:7px 10px;border-radius:8px;background:#fff4d8;color:#8a5a16;font-size:10px;font-weight:700;white-space:nowrap;}
.shipping-hint.show{display:inline-flex;}
</style>
<script>
(function(){const select=document.getElementById('statusPesananSelect');const hint=document.getElementById('shippingHint');if(!select||!hint)return;function syncHint(){hint.classList.toggle('show',select.value==='Dikirim');}select.addEventListener('change',syncHint);syncHint();})();
</script>
</body>
</html>