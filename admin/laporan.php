<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
ensurePaymentTable($koneksi);

// Periode laporan. Semua angka di halaman ini mengikuti periode yang dipilih.
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

function valid_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
if (!valid_date($tanggal_mulai)) $tanggal_mulai = date('Y-m-01');
if (!valid_date($tanggal_selesai)) $tanggal_selesai = date('Y-m-d');
if ($tanggal_mulai > $tanggal_selesai) {
    [$tanggal_mulai, $tanggal_selesai] = [$tanggal_selesai, $tanggal_mulai];
}

// Tabel pengeluaran dibuat otomatis agar laporan tetap kompatibel dengan database lama.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengeluaran (
    id_pengeluaran INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    keterangan VARCHAR(255) NOT NULL,
    nominal DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Hanya pesanan yang tidak dibatalkan dan pembayarannya terverifikasi/lunas yang masuk laporan penjualan.
$paid_join = "EXISTS (
    SELECT 1 FROM pembayaran p
    WHERE p.id_order = o.id_order
      AND p.status_pembayaran IN ('Terverifikasi','Lunas')
)";

// Total pesanan
$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total FROM orders o WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan' AND $paid_join");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$total_pesanan = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

// Total pendapatan
$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(o.total_pembayaran),0) AS total FROM orders o WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan' AND $paid_join");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$total_pendapatan = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

// Total produk terjual
$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(od.jumlah),0) AS total
    FROM order_details od
    JOIN orders o ON od.id_order = o.id_order
    WHERE DATE(o.created_at) BETWEEN ? AND ?
      AND o.status <> 'Dibatalkan'
      AND $paid_join");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$total_produk_terjual = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

// Total pengeluaran pada periode yang sama
$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total FROM pengeluaran WHERE tanggal BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$total_pengeluaran = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$total_laba_bersih = $total_pendapatan - $total_pengeluaran;
$rata_rata_pesanan = $total_pesanan > 0 ? $total_pendapatan / $total_pesanan : 0;

// Produk terlaris pada periode terpilih
$stmt = mysqli_prepare($koneksi, "SELECT
        pr.nama_product,
        SUM(od.jumlah) AS jumlah_terjual,
        SUM(od.subtotal) AS total_penjualan
    FROM order_details od
    JOIN orders o ON od.id_order = o.id_order
    JOIN products pr ON od.id_product = pr.id_product
    WHERE DATE(o.created_at) BETWEEN ? AND ?
      AND o.status <> 'Dibatalkan'
      AND $paid_join
    GROUP BY pr.id_product, pr.nama_product
    ORDER BY jumlah_terjual DESC, total_penjualan DESC");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result_produk = mysqli_stmt_get_result($stmt);
$produk_rows = $result_produk ? mysqli_fetch_all($result_produk, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

// Detail pengeluaran pada periode terpilih
$stmt = mysqli_prepare($koneksi, "SELECT tanggal, kategori, keterangan, nominal
    FROM pengeluaran
    WHERE tanggal BETWEEN ? AND ?
    ORDER BY tanggal DESC, id_pengeluaran DESC");
mysqli_stmt_bind_param($stmt, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($stmt);
$result_pengeluaran = mysqli_stmt_get_result($stmt);
$pengeluaran_rows = $result_pengeluaran ? mysqli_fetch_all($result_pengeluaran, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

// Format periode untuk tampilan
$periode_mulai = date('d/m/Y', strtotime($tanggal_mulai));
$periode_selesai = date('d/m/Y', strtotime($tanggal_selesai));
$periode_teks = $periode_mulai . ' – ' . $periode_selesai;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Budiarti Risol</title>
    <style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}
a{text-decoration:none}
.admin-wrapper{display:flex;min-height:100vh}
.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;left:0;top:0;bottom:0;padding:25px 15px}
.sidebar-logo{text-align:center;padding-bottom:30px}.sidebar-logo .logo-icon{font-size:32px;margin-bottom:5px}
.sidebar-logo h2{font-family:Georgia,serif;font-size:22px;font-style:italic;color:#fff}
.sidebar-logo p{font-size:9px;letter-spacing:1px;color:#EBD3C0;margin-top:3px}
.sidebar-menu{display:flex;flex-direction:column;gap:7px}
.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;color:#f6e8dc;font-size:13px;transition:.2s}
.sidebar-menu a:hover{background:#633419}.sidebar-menu a.active{background:#75401f;color:#fff}
.menu-icon{width:20px;text-align:center;font-size:15px}
.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}
.top-header{height:70px;background:#C99B78;border-bottom:1px solid #eee3d8;display:flex;align-items:center;justify-content:space-between;padding:0 35px}
.hamburger{font-size:20px;color:#5b2c1f}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px;color:#542D1C}
.notification{font-size:18px;margin-right:12px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center;font-size:17px}.admin-name{font-weight:bold}
.page-content{padding:35px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:15px}
.page-title h1{font-family:Georgia,serif;font-size:27px;color:#542D1C;margin-bottom:7px}.page-title p{color:#8a7669;font-size:13px}
.print-button{background:transparent;color:#6d3519;padding:9px 13px;border:1px solid #d9c7b8;border-radius:8px;font-size:12px;font-weight:bold;cursor:pointer}
.print-button:hover{background:#fff8f2}
.filter-strip{display:flex;align-items:end;gap:12px;flex-wrap:wrap;padding:0 0 22px;border-bottom:1px solid #e7dcd2;margin-bottom:24px}
.date-group{display:flex;flex-direction:column;gap:6px}.date-group label{font-size:11px;color:#8d796b;font-weight:bold}
.date-group input{height:40px;border:1px solid #dfd2c7;border-radius:8px;padding:0 12px;outline:none;font-size:12px;color:#4b392f;background:#fffdfb}
.date-group input:focus{border-color:#b4772d}
.filter-button{height:40px;border:0;border-radius:8px;padding:0 18px;background:#EBD3C0;color:#542D1C;font-weight:bold;cursor:pointer;font-size:12px}
.filter-button:hover{background:#eab958}.reset-button{height:40px;padding:0 12px;display:flex;align-items:center;color:#745d4d;font-size:12px}
.finance-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.finance-stat{background:#fff;border:1px solid #eee4db;border-radius:12px;padding:19px 20px}
.finance-stat .label{font-size:11px;color:#8d796b;margin-bottom:8px}.finance-stat .value{font-size:21px;font-weight:bold;color:#542D1C}
.finance-stat.profit{background:#fffdf8}.finance-stat.profit .value{color:#5e6f3c}
.report-section{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px;margin-bottom:18px}
.section-heading{display:flex;justify-content:space-between;align-items:end;gap:10px;margin:0 0 14px}
.section-heading h2{font-family:Georgia,serif;font-size:19px;color:#542D1C}.section-heading p{font-size:11px;color:#8a7669;margin-top:4px}
.table-wrap{overflow-x:auto;border-top:1px solid #e7dcd2}
table{width:100%;border-collapse:collapse;min-width:620px}
th{text-align:left;padding:12px 10px;font-size:10px;color:#8d796b;font-weight:bold;border-bottom:1px solid #e7dcd2}
td{padding:12px 10px;border-bottom:1px solid #f1ebe5;font-size:11px;color:#5b4a40;vertical-align:middle}
tr:last-child td{border-bottom:none}.number{color:#927e70;width:45px}.product-name{font-weight:600;color:#542D1C}.quantity{color:#66554a}.price{font-weight:bold;color:#542D1C}
.transaction-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.summary-item{background:#F8EFE5;border:1px solid #eee4db;border-radius:10px;padding:15px}
.summary-item .summary-label{font-size:10px;color:#8d796b;margin-bottom:6px}.summary-item .summary-value{font-size:17px;font-weight:bold;color:#542D1C}
.empty-data{text-align:center;padding:32px;color:#927e70;font-size:12px}
.expense-table td{padding:11px 10px}
.expense-date{white-space:nowrap;color:#6d5a4c}
.expense-category{font-weight:600;color:#542D1C}
.expense-description{color:#5b4a40}
.expense-amount{font-weight:bold;color:#542D1C;white-space:nowrap}
.print-header{display:none}

.print-meta{display:none}
.admin-tabs{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 25px;padding:5px;background:#fff;border:1px solid #eadfd5;border-radius:12px;box-shadow:0 2px 8px rgba(74,34,14,.05)}
.admin-tabs a{display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;color:#765a46;text-decoration:none;font-size:12px;font-weight:600}.admin-tabs a:hover{background:#f8f0e8}.admin-tabs a.active{background:#6d3519;color:#fff}
@media(max-width:800px){.finance-stats,.transaction-summary{grid-template-columns:1fr}.sidebar{width:190px}.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:24px}.top-header{padding:0 24px}}
@media print{
    /* Cetak satu lembar A4 landscape dengan struktur yang sama seperti laporan di layar. */
    @page{size:A4 landscape;margin:7mm}
    html,body{background:#F8EFE5!important;color:#542D1C!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    body{font-size:10px!important}
    .sidebar,.top-header,.hamburger,.admin-tabs,.print-button,.print-header{display:none!important}
    .main-content{margin-left:0!important;width:100%!important;min-height:0!important}
    .page-content{padding:8px!important;background:#F8EFE5!important}
    .page-header{display:flex!important;margin-bottom:8px!important;gap:8px!important}
    .page-title h1{font-size:23px!important;margin-bottom:3px!important}
    .page-title p{font-size:10px!important}
    .filter-strip{display:flex!important;gap:7px!important;padding-bottom:8px!important;margin-bottom:10px!important}
    .date-group{gap:3px!important}.date-group label{font-size:9px!important}.date-group input,.filter-button{height:30px!important;font-size:10px!important}
    .reset-button{height:30px!important;font-size:10px!important}
    .finance-stats{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:9px!important;margin-bottom:10px!important}
    .finance-stat{padding:11px 13px!important;border-radius:9px!important}
    .finance-stat .label{font-size:9px!important;margin-bottom:4px!important}.finance-stat .value{font-size:17px!important}
    .finance-stat,.report-section{box-shadow:none!important;border:1px solid #eee4db!important;break-inside:avoid;page-break-inside:avoid}
    .report-section{margin-bottom:9px!important;padding:11px 13px!important;border-radius:10px!important}
    .section-heading{margin-bottom:7px!important}.section-heading h2{font-size:16px!important}.section-heading p{font-size:9px!important;margin-top:2px!important}
    .table-wrap{border-top:1px solid #e7dcd2!important;overflow:visible!important}
    table{min-width:0!important}
    th{padding:6px 7px!important;font-size:8px!important}
    td{padding:6px 7px!important;font-size:9px!important}
    .expense-table td{padding:5px 7px!important}
    .empty-data{padding:12px!important;font-size:9px!important}
    .transaction-summary{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:8px!important}
    .summary-item{padding:9px!important;border-radius:8px!important}
    .summary-item .summary-label{font-size:8px!important;margin-bottom:3px!important}.summary-item .summary-value{font-size:13px!important}
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
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>

        <section class="page-content">
            <?php include __DIR__ . '/tabs_keuangan.php'; ?>

            <div class="print-header">
                <div class="print-logo">👩‍🍳</div>
                <div class="print-brand">Budiarti Risol</div>
                <div class="print-subtitle">HOMEMADE • FRESHLY MADE</div>
                <h1>Laporan Keuangan</h1>
                <p>Periode laporan: <?= htmlspecialchars($periode_teks); ?></p>
            </div>

            <div class="page-header">
                <div class="page-title">
                    <h1>Laporan Keuangan</h1>
                    <p>Pantau kondisi keuangan toko pada periode tertentu.</p>
                    <div class="print-meta">Periode laporan: <?= htmlspecialchars($periode_teks); ?></div>
                </div>
                <button onclick="window.print()" class="print-button">🖨 Cetak</button>
            </div>

            <form method="GET" action="laporan.php" class="filter-strip">
                <div class="date-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai); ?>" required>
                </div>
                <div class="date-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai); ?>" required>
                </div>
                <button type="submit" class="filter-button">Tampilkan</button>
                <a href="laporan.php" class="reset-button">Reset</a>
            </form>

            <div class="finance-stats">
                <div class="finance-stat">
                    <div class="label">Pendapatan</div>
                    <div class="value">Rp<?= number_format($total_pendapatan,0,',','.'); ?></div>
                </div>
                <div class="finance-stat">
                    <div class="label">Pengeluaran</div>
                    <div class="value">Rp<?= number_format($total_pengeluaran,0,',','.'); ?></div>
                </div>
                <div class="finance-stat profit">
                    <div class="label">Laba Bersih</div>
                    <div class="value">Rp<?= number_format($total_laba_bersih,0,',','.'); ?></div>
                </div>
            </div>
<section class="report-section">
                <div class="section-heading">
                    <div>
                        <h2>Produk Terlaris</h2>
                        <p>Penjualan pada periode <?= htmlspecialchars($periode_teks); ?> berdasarkan pembayaran terverifikasi/lunas.</p>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>PRODUK</th>
                                <th>TERJUAL</th>
                                <th>PENJUALAN</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($produk_rows) > 0): ?>
                            <?php $no=1; foreach ($produk_rows as $produk): ?>
                            <tr>
                                <td class="number"><?= $no++; ?></td>
                                <td class="product-name"><?= htmlspecialchars($produk['nama_product']); ?></td>
                                <td class="quantity"><?= number_format($produk['jumlah_terjual']); ?> pcs</td>
                                <td class="price">Rp<?= number_format($produk['total_penjualan'],0,',','.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4"><div class="empty-data">Belum ada data penjualan pada periode tersebut.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="report-section">
                <div class="section-heading">
                    <div>
                        <h2>Detail Pengeluaran</h2>
                        <p>Rincian pengeluaran selama periode <?= htmlspecialchars($periode_teks); ?>.</p>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="expense-table">
                        <thead>
                            <tr>
                                <th>TANGGAL</th>
                                <th>KATEGORI</th>
                                <th>KETERANGAN</th>
                                <th>NOMINAL</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($pengeluaran_rows) > 0): ?>
                            <?php foreach ($pengeluaran_rows as $pengeluaran): ?>
                            <tr>
                                <td class="expense-date"><?= date('d/m/Y', strtotime($pengeluaran['tanggal'])); ?></td>
                                <td class="expense-category"><?= htmlspecialchars($pengeluaran['kategori']); ?></td>
                                <td class="expense-description"><?= htmlspecialchars($pengeluaran['keterangan']); ?></td>
                                <td class="expense-amount">Rp<?= number_format($pengeluaran['nominal'],0,',','.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4"><div class="empty-data">Belum ada pengeluaran pada periode tersebut.</div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="report-section">
                <div class="section-heading">
                    <div>
                        <h2>Ringkasan Transaksi</h2>
                        <p>Ringkasan aktivitas penjualan selama periode yang dipilih.</p>
                    </div>
                </div>
                <div class="transaction-summary">
                    <div class="summary-item">
                        <div class="summary-label">Total Pesanan</div>
                        <div class="summary-value"><?= number_format($total_pesanan); ?> pesanan</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Produk Terjual</div>
                        <div class="summary-value"><?= number_format($total_produk_terjual); ?> pcs</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Rata-rata Nilai Pesanan</div>
                        <div class="summary-value">Rp<?= number_format($rata_rata_pesanan,0,',','.'); ?></div>
                    </div>
                </div>
            </section>
        </section>
    </main>
</div>
</body>
</html>