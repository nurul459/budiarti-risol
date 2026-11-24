
<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/pengunjung.php";

init_tabel_pengunjung($koneksi);

function dashboard_one($koneksi, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) return [];
    if ($types !== '' && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); return []; }
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? (mysqli_fetch_assoc($result) ?: []) : [];
    mysqli_stmt_close($stmt);
    return $row;
}

function dashboard_all($koneksi, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) return [];
    if ($types !== '' && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); return []; }
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

function dashboard_money($value) {
    return 'Rp' . number_format((float)$value, 0, ',', '.');
}

function dashboard_status_class($status) {
    $map = [
        'Menunggu Verifikasi' => 'warning',
        'Belum Dibayar' => 'warning',
        'Terverifikasi' => 'success',
        'Lunas' => 'success',
        'Diproses' => 'process',
        'Dikirim' => 'shipping',
        'Selesai' => 'success',
        'Dibatalkan' => 'danger',
        'Ditolak' => 'danger'
    ];
    return $map[$status] ?? 'neutral';
}

// =========================================================
// 1. KPI OPERASIONAL HARI INI
// =========================================================
$today = date('Y-m-d');

$pesanan_perlu_diproses_row = dashboard_one($koneksi,
    "SELECT COUNT(DISTINCT o.id_order) AS total
     FROM orders o
     LEFT JOIN pembayaran p ON p.id_order = o.id_order
     WHERE o.status NOT IN ('Selesai','Dibatalkan')
       AND (
           o.status = 'Diproses'
           OR COALESCE(p.status_pembayaran,
               CASE WHEN o.metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END
           ) IN ('Menunggu Verifikasi','Belum Dibayar')
       )"
);
$pesanan_perlu_diproses = (int)($pesanan_perlu_diproses_row['total'] ?? 0);

$pendapatan_hari_ini_row = dashboard_one($koneksi,
    "SELECT COALESCE(SUM(o.total_pembayaran),0) AS total
     FROM orders o
     WHERE DATE(o.created_at)=?
       AND o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )",
    's', [$today]
);
$pendapatan_hari_ini = (float)($pendapatan_hari_ini_row['total'] ?? 0);

$produk_aktif_row = dashboard_one($koneksi,
    "SELECT COUNT(*) AS total FROM products WHERE stok > 0"
);
$total_produk_aktif = (int)($produk_aktif_row['total'] ?? 0);

$awal_bulan = date('Y-m-01');
$pengunjung_bulan_ini_row = dashboard_one($koneksi,
    "SELECT COUNT(DISTINCT visitor_key) AS total
     FROM visitor_logs
     WHERE tanggal BETWEEN ? AND ?",
    'ss', [$awal_bulan, $today]
);
$pengunjung_bulan_ini = (int)($pengunjung_bulan_ini_row['total'] ?? 0);

$kemarin = date('Y-m-d', strtotime('-1 day'));
$pendapatan_kemarin_row = dashboard_one($koneksi,
    "SELECT COALESCE(SUM(o.total_pembayaran),0) AS total
     FROM orders o
     WHERE DATE(o.created_at)=?
       AND o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )",
    's', [$kemarin]
);
$pendapatan_kemarin = (float)($pendapatan_kemarin_row['total'] ?? 0);

if ($pendapatan_kemarin > 0) {
    $perubahan_pendapatan = (($pendapatan_hari_ini - $pendapatan_kemarin) / $pendapatan_kemarin) * 100;
} elseif ($pendapatan_hari_ini > 0) {
    $perubahan_pendapatan = 100;
} else {
    $perubahan_pendapatan = 0;
}

$tujuh_hari_lalu = date('Y-m-d', strtotime('-6 days'));

$pembeli_7hari_row = dashboard_one($koneksi,
    "SELECT COUNT(DISTINCT v.id_user) AS total
     FROM visitor_logs v
     INNER JOIN orders o ON o.id_user=v.id_user
     WHERE v.id_user IS NOT NULL
       AND v.tanggal BETWEEN ? AND ?
       AND DATE(o.created_at) BETWEEN ? AND ?
       AND o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )",
    'ssss', [$tujuh_hari_lalu, $today, $tujuh_hari_lalu, $today]
);
$pembeli_7hari = (int)($pembeli_7hari_row['total'] ?? 0);

$pengunjung_7hari_row = dashboard_one($koneksi,
    "SELECT COUNT(DISTINCT visitor_key) AS total
     FROM visitor_logs
     WHERE tanggal BETWEEN ? AND ?",
    'ss', [$tujuh_hari_lalu, $today]
);
$pengunjung_7hari = (int)($pengunjung_7hari_row['total'] ?? 0);

$data_pembeli_harian = dashboard_all($koneksi,
    "SELECT DATE(o.created_at) AS tanggal, COUNT(DISTINCT o.id_user) AS pembeli
     FROM orders o
     WHERE DATE(o.created_at) BETWEEN ? AND ?
       AND o.id_user IS NOT NULL
       AND o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )
       AND EXISTS (
           SELECT 1 FROM visitor_logs v
           WHERE v.id_user=o.id_user AND v.tanggal BETWEEN ? AND ?
       )
     GROUP BY DATE(o.created_at)
     ORDER BY tanggal ASC",
    'ssss', [$tujuh_hari_lalu, $today, $tujuh_hari_lalu, $today]
);

$pembeli_map = [];
foreach ($data_pembeli_harian as $row) {
    $pembeli_map[$row['tanggal']] = (int)$row['pembeli'];
}

// =========================================================
// 2. PESANAN TERBARU
// =========================================================
$pesanan_terbaru = dashboard_all($koneksi,
    "SELECT o.id_order, o.nama_penerima, o.total_pembayaran, o.metode_pembayaran,
            o.status, o.created_at,
            COALESCE(p.status_pembayaran,
                CASE WHEN o.metode_pembayaran='COD' THEN 'Belum Dibayar' ELSE 'Menunggu Verifikasi' END
            ) AS status_pembayaran
     FROM orders o
     LEFT JOIN pembayaran p ON p.id_order=o.id_order
     ORDER BY o.created_at DESC, o.id_order DESC
     LIMIT 7"
);

// =========================================================
// 3. TREN PENJUALAN
// =========================================================
$data_penjualan = dashboard_all($koneksi,
    "SELECT DATE(o.created_at) AS tanggal,
            COUNT(*) AS jumlah_pesanan,
            COALESCE(SUM(o.total_pembayaran),0) AS pendapatan
     FROM orders o
     WHERE DATE(o.created_at) BETWEEN ? AND ?
       AND o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )
     GROUP BY DATE(o.created_at)
     ORDER BY tanggal ASC",
    'ss', [$tujuh_hari_lalu, $today]
);

$penjualan_map = [];
foreach ($data_penjualan as $row) {
    $penjualan_map[$row['tanggal']] = [
        'pesanan' => (int)$row['jumlah_pesanan'],
        'pendapatan' => (float)$row['pendapatan']
    ];
}

$labels_7hari = [];
$nilai_pendapatan = [];
$nilai_pesanan = [];
$hari_id = [
    'Sun'=>'Min',
    'Mon'=>'Sen',
    'Tue'=>'Sel',
    'Wed'=>'Rab',
    'Thu'=>'Kam',
    'Fri'=>'Jum',
    'Sat'=>'Sab'
];

$data_kunjungan = dashboard_all($koneksi,
    "SELECT tanggal,
            COUNT(DISTINCT visitor_key) AS pengunjung,
            COUNT(*) AS kunjungan
     FROM visitor_logs
     WHERE tanggal BETWEEN ? AND ?
     GROUP BY tanggal
     ORDER BY tanggal ASC",
    'ss', [$tujuh_hari_lalu, $today]
);

$kunjungan_map = [];
foreach ($data_kunjungan as $row) {
    $kunjungan_map[$row['tanggal']] = [
        'pengunjung' => (int)$row['pengunjung'],
        'kunjungan' => (int)$row['kunjungan']
    ];
}

$nilai_pengunjung = [];
$nilai_kunjungan = [];
$nilai_pembeli = [];

$cursor_kunjungan = new DateTime($tujuh_hari_lalu);
$akhir_kunjungan = new DateTime($today);
$akhir_kunjungan->modify('+1 day');

while ($cursor_kunjungan < $akhir_kunjungan) {
    $tgl = $cursor_kunjungan->format('Y-m-d');
    $nilai_pengunjung[] = $kunjungan_map[$tgl]['pengunjung'] ?? 0;
    $nilai_kunjungan[] = $kunjungan_map[$tgl]['kunjungan'] ?? 0;
    $nilai_pembeli[] = $pembeli_map[$tgl] ?? 0;
    $cursor_kunjungan->modify('+1 day');
}

$cursor_penjualan = new DateTime($tujuh_hari_lalu);
$akhir_penjualan = new DateTime($today);
$akhir_penjualan->modify('+1 day');

while ($cursor_penjualan < $akhir_penjualan) {
    $tgl = $cursor_penjualan->format('Y-m-d');
    $labels_7hari[] = ($hari_id[$cursor_penjualan->format('D')] ?? $cursor_penjualan->format('D')) . ' ' . $cursor_penjualan->format('d/m');
    $nilai_pendapatan[] = $penjualan_map[$tgl]['pendapatan'] ?? 0;
    $nilai_pesanan[] = $penjualan_map[$tgl]['pesanan'] ?? 0;
    $cursor_penjualan->modify('+1 day');
}

// =========================================================
// 4. PRODUK TERLARIS
// =========================================================
$produk_terlaris = dashboard_all($koneksi,
    "SELECT products.nama_product,
            SUM(od.jumlah) AS jumlah_terjual,
            SUM(od.subtotal) AS total_penjualan
     FROM order_details od
     JOIN orders o ON od.id_order=o.id_order
     JOIN products ON od.id_product=products.id_product
     WHERE o.status <> 'Dibatalkan'
       AND (
           LOWER(o.status) = 'selesai'
           OR EXISTS (
               SELECT 1 FROM pembayaran p
               WHERE p.id_order=o.id_order
                 AND p.status_pembayaran IN ('Terverifikasi','Lunas')
           )
       )
     GROUP BY products.id_product, products.nama_product
     ORDER BY jumlah_terjual DESC, total_penjualan DESC
     LIMIT 5"
);

// =========================================================
// 5. PERINGATAN STOK
// =========================================================
$stok_perlu_diperhatikan = dashboard_all($koneksi,
    "SELECT nama_product, stok
     FROM products
     WHERE stok <= 5
     ORDER BY stok ASC, nama_product ASC
     LIMIT 5"
);

$jumlah_stok_habis_row = dashboard_one($koneksi,
    "SELECT COUNT(*) AS total FROM products WHERE stok <= 0"
);
$jumlah_stok_habis = (int)($jumlah_stok_habis_row['total'] ?? 0);

// =========================================================
// 6. RATING TERBARU
// =========================================================
$rating_terbaru = [];
$cek_ulasan = mysqli_query($koneksi, "SHOW TABLES LIKE 'ulasan'");

if ($cek_ulasan && mysqli_num_rows($cek_ulasan) > 0) {
    $rating_terbaru = dashboard_all($koneksi,
        "SELECT u.rating, u.komentar, u.created_at,
                p.nama_product,
                usr.nama AS nama_pelanggan
         FROM ulasan u
         LEFT JOIN products p ON p.id_product=u.id_product
         LEFT JOIN users usr ON usr.id_user=u.id_user
         ORDER BY u.created_at DESC, u.id_ulasan DESC
         LIMIT 3"
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Budiarti Risol</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', Arial, sans-serif;
            background: #F8EFE5;
            color: #542D1C;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
            transition: margin-left .2s ease, width .2s ease;
        }

        .top-header {
            height: 70px;
            background: #C99B78;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }

        .hamburger {
            font-size: 24px;
            color: #542D1C;
            line-height: 1;
            cursor: pointer;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #542D1C;
        }

        .notification {
            font-size: 18px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #EBD3C0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .dashboard-content {
            padding: 30px 35px 45px;
        }

        .welcome h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 34px;
            margin-bottom: 9px;
        }

        .welcome p {
            font-size: 15px;
            color: #765a46;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 22px 0 25px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #eadbce;
            border-radius: 12px;
            padding: 19px 18px;
            box-shadow: 0 3px 12px rgba(74,34,14,.05);
            min-height: 132px;
        }

        .summary-card.urgent {
            border-top: 3px solid #C99B78;
        }

        .summary-icon {
            font-size: 21px;
            margin-bottom: 10px;
        }

        .summary-label {
            font-size: 14px;
            color: #765a46;
            margin-bottom: 8px;
        }

        .summary-number {
            font-size: 28px;
            font-weight: 700;
        }

        .summary-sub {
            font-size: 12px;
            color: #927b69;
            margin-top: 6px;
            line-height: 1.5;
        }

        .section {
            margin-top: 24px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin-bottom: 13px;
        }

        .section-head h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 23px;
        }

        .section-head p {
            font-size: 13px;
            color: #8a6b58;
            margin-top: 5px;
        }

        .section-link {
            font-size: 13px;
            color: #7a3c1d;
            font-weight: 600;
        }

        .table-panel {
            background: #fff;
            border: 1px solid #eadbce;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(74,34,14,.05);
        }

        .table-wrap {
            overflow: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 720px;
        }

        .table th,
        .table td {
            text-align: left;
            padding: 14px 15px;
            border-bottom: 1px solid #f0e5dd;
        }

        .table th {
            font-size: 12px;
            text-transform: uppercase;
            color: #765a46;
            letter-spacing: .3px;
            background: #fffaf6;
        }

        .table tr:last-child td {
            border-bottom: 0;
        }

        .order-no {
            font-weight: 700;
            color: #6d3519;
        }

        .muted {
            font-size: 12px;
            color: #927b69;
        }

        .money {
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.warning {
            background: #fff2d8;
            color: #8a5a10;
        }

        .status-badge.success {
            background: #e8f3e8;
            color: #3c6d42;
        }

        .status-badge.process {
            background: #f6eadf;
            color: #7a3c1d;
        }

        .status-badge.shipping {
            background: #eee5dc;
            color: #68472f;
        }

        .status-badge.danger {
            background: #f9e2df;
            color: #9a4138;
        }

        .status-badge.neutral {
            background: #f0ece8;
            color: #695a50;
        }

        .action-link {
            display: inline-block;
            padding: 7px 11px;
            border: 1px solid #d8bda8;
            border-radius: 7px;
            color: #7a3c1d;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .action-link:hover {
            background: #f8eee7;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.4fr .9fr;
            gap: 18px;
            align-items: stretch;
        }

        .panel {
            background: #fff;
            border: 1px solid #eadbce;
            border-radius: 12px;
            padding: 21px;
            box-shadow: 0 3px 12px rgba(74,34,14,.05);
        }

        .panel h3 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 21px;
            margin-bottom: 6px;
        }

        .panel-desc {
            font-size: 13px;
            color: #8a6b58;
            margin-bottom: 14px;
            line-height: 1.5;
        }

        .chart-wrap {
            height: 240px;
        }

        .small-chart {
            height: 210px;
        }

        .mini-list {
            display: flex;
            flex-direction: column;
        }

        .mini-row {
            display: grid;
            grid-template-columns: 30px 1fr auto;
            gap: 10px;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #f0e5dd;
        }

        .mini-row:last-child {
            border-bottom: 0;
        }

        .rank {
            font-size: 14px;
            font-weight: 700;
            color: #7a3c1d;
        }

        .mini-info strong {
            display: block;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mini-info span {
            display: block;
            font-size: 12px;
            color: #927b69;
            margin-top: 4px;
        }

        .mini-money {
            font-size: 13px;
            font-weight: 600;
            color: #5f321b;
        }

        .stock-box {
            margin-top: 16px;
            padding-top: 15px;
            border-top: 1px solid #f0e5dd;
        }

        .stock-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 9px;
        }

        .stock-head strong {
            font-size: 14px;
        }

        .stock-head a {
            font-size: 12px;
            color: #7a3c1d;
            font-weight: 600;
        }

        .stock-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            font-size: 13px;
        }

        .stock-row span:first-child {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stock-low {
            font-weight: 700;
            color: #a06417;
        }

        .stock-empty {
            font-weight: 700;
            color: #a43e34;
        }

        .empty {
            text-align: center;
            color: #927b69;
            padding: 25px;
            font-size: 13px;
        }

        .notice {
            background: #fff8e8;
            border: 1px solid #edd7a9;
            border-radius: 9px;
            padding: 12px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #765a46;
            line-height: 1.5;
        }

        .notice strong {
            color: #8a5a10;
        }

        .rating-panel {
            margin-top: 18px;
        }

        .rating-list {
            display: flex;
            flex-direction: column;
        }

        .rating-item {
            padding: 15px 0;
            border-bottom: 1px solid #f0e5dd;
        }

        .rating-item:last-child {
            border-bottom: 0;
        }

        .rating-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
        }

        .stars {
            color: #b4772d;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .rating-date {
            font-size: 12px;
            color: #927b69;
        }

        .rating-name {
            font-size: 14px;
            font-weight: 700;
            color: #542D1C;
            margin-top: 6px;
        }

        .rating-product {
            font-size: 12px;
            color: #927b69;
            margin-top: 3px;
        }

        .rating-comment {
            font-size: 13px;
            color: #5f4b3f;
            margin-top: 7px;
            line-height: 1.6;
        }

        .visitor-chart {
            height: 235px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.35fr .9fr;
            gap: 18px;
        }

        .rating-empty {
            padding: 20px 0;
            text-align: center;
            color: #927b69;
            font-size: 13px;
        }

        .footer-note {
            font-size: 12px;
            color: #927b69;
            margin-top: 10px;
        }

        .visitor-summary {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: stretch;
            margin: 4px 0 12px;
        }

        .visitor-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fffaf6;
            border: 1px solid #f0e5dd;
            border-radius: 9px;
            padding: 11px;
        }

        .visitor-icon {
            font-size: 19px;
        }

        .visitor-stat strong {
            display: block;
            font-size: 19px;
        }

        .visitor-stat span {
            display: block;
            font-size: 12px;
            color: #927b69;
            margin-top: 3px;
        }

        .visitor-conversion {
            min-width: 82px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #f6eadf;
            border-radius: 9px;
            padding: 8px;
        }

        .visitor-conversion strong {
            font-size: 19px;
            color: #6d3519;
        }

        .visitor-conversion span {
            font-size: 11px;
            color: #927b69;
            margin-top: 3px;
        }

        .revenue-panel {
            display: flex;
            flex-direction: column;
        }

        .revenue-main {
            padding: 17px 0 13px;
            border-bottom: 1px solid #f0e5dd;
        }

        .revenue-value {
            font-size: 30px;
            font-weight: 700;
            color: #542D1C;
        }

        .revenue-change {
            font-size: 13px;
            font-weight: 700;
            margin-top: 7px;
        }

        .revenue-change.up {
            color: #4d7a52;
        }

        .revenue-change.down {
            color: #a43e34;
        }

        .revenue-compare {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 14px;
        }

        .revenue-compare div {
            background: #fffaf6;
            border: 1px solid #f0e5dd;
            border-radius: 9px;
            padding: 12px;
        }

        .revenue-compare span {
            display: block;
            font-size: 12px;
            color: #927b69;
            margin-bottom: 6px;
        }

        .revenue-compare strong {
            font-size: 15px;
            color: #5f321b;
        }

        /* Ukuran font sidebar diperbesar tanpa mengubah layout */
        .sidebar,
        .sidebar * {
            font-family: 'DM Sans', Arial, sans-serif !important;
        }

        .sidebar-logo h2 {
            font-family: 'Playfair Display', Georgia, serif !important;
            font-size: 24px !important;
        }

        .sidebar-main-link,
        .sidebar-group > summary {
            font-family: 'DM Sans', Arial, sans-serif !important;
            font-size: 15px !important;
        }

        .sidebar-sub-link {
            font-family: 'DM Sans', Arial, sans-serif !important;
            font-size: 14px !important;
        }

        .sidebar-logo p {
            font-size: 10px !important;
        }

        @media(max-width:600px) {
            .visitor-summary {
                grid-template-columns: 1fr 1fr;
            }

            .visitor-conversion {
                grid-column: 1 / -1;
            }
        }

        @media(max-width:1100px) {
            .summary-grid {
                grid-template-columns: repeat(2,1fr);
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:900px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .dashboard-content {
                padding: 22px;
            }

            .top-header {
                padding: 0 22px;
            }
        }

        @media(max-width:600px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 27px;
            }

            .summary-card {
                min-height: 115px;
            }

            .panel {
                padding: 16px;
            }

            .revenue-value {
                font-size: 25px;
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

        <section class="dashboard-content">
            <div class="welcome">
                <h1>Selamat datang kembali, Admin!</h1>
                <p>Berikut ringkasan aktivitas Budiarti Risol hari ini.</p>
            </div>

            <div class="summary-grid">
                <div class="summary-card urgent">
                    <div class="summary-icon">📦</div>
                    <div class="summary-label">Pesanan Perlu Diproses</div>
                    <div class="summary-number"><?= number_format($pesanan_perlu_diproses,0,',','.'); ?></div>
                    <div class="summary-sub">pesanan yang masih membutuhkan tindakan</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">💰</div>
                    <div class="summary-label">Pendapatan Hari Ini</div>
                    <div class="summary-number"><?= dashboard_money($pendapatan_hari_ini); ?></div>
                    <div class="summary-sub">pesanan terverifikasi/lunas</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">🛍️</div>
                    <div class="summary-label">Total Produk Aktif</div>
                    <div class="summary-number"><?= number_format($total_produk_aktif,0,',','.'); ?></div>
                    <div class="summary-sub">produk dengan stok tersedia</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">👥</div>
                    <div class="summary-label">Pengunjung Bulan Ini</div>
                    <div class="summary-number"><?= number_format($pengunjung_7hari,0,',','.'); ?></div>
                    <div class="summary-sub">pengunjung unik bulan ini</div>
                </div>
            </div>

            <section class="section">
                <div class="dashboard-grid">
                    <div class="panel">
                        <h3>Statistik Kunjungan</h3>
                        <div class="panel-desc">Perbandingan pengunjung dan pembeli selama 7 hari terakhir.</div>

                        <div class="visitor-summary">
                            <div class="visitor-stat">
                                <span class="visitor-icon">👤</span>
                                <div>
                                    <strong><?= number_format($pengunjung_bulan_ini,0,',','.'); ?></strong>
                                    <span>Pengunjung 7 hari</span>
                                </div>
                            </div>

                            <div class="visitor-stat">
                                <span class="visitor-icon">🛒</span>
                                <div>
                                    <strong><?= number_format($pembeli_7hari,0,',','.'); ?></strong>
                                    <span>Pembeli 7 hari</span>
                                </div>
                            </div>

                            <div class="visitor-conversion">
                                <strong><?= $pengunjung_7hari > 0 ? number_format(($pembeli_7hari / $pengunjung_7hari) * 100, 1, ',', '.') : '0,0'; ?>%</strong>
                                <span>konversi</span>
                            </div>
                        </div>

                        <div class="chart-wrap visitor-chart">
                            <canvas id="grafikKunjungan"></canvas>
                        </div>
                    </div>

                    <div class="panel revenue-panel">
                        <h3>Pendapatan Hari Ini vs Kemarin</h3>
                        <div class="panel-desc">Perbandingan pendapatan dari pesanan terverifikasi/lunas.</div>

                        <div class="revenue-main">
                            <div class="revenue-value"><?= dashboard_money($pendapatan_hari_ini); ?></div>
                            <div class="revenue-change <?= $perubahan_pendapatan >= 0 ? 'up' : 'down'; ?>">
                                <?= $perubahan_pendapatan >= 0 ? '↑' : '↓'; ?>
                                <?= number_format(abs($perubahan_pendapatan),1,',','.'); ?>% dari kemarin
                            </div>
                        </div>

                        <div class="revenue-compare">
                            <div>
                                <span>Hari ini</span>
                                <strong><?= dashboard_money($pendapatan_hari_ini); ?></strong>
                            </div>
                            <div>
                                <span>Kemarin</span>
                                <strong><?= dashboard_money($pendapatan_kemarin); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="bottom-grid">
                    <div class="panel">
                        <h3>Produk Terlaris</h3>
                        <div class="panel-desc">5 produk dengan jumlah pcs paling banyak dibeli.</div>

                        <div class="mini-list">
                            <?php if (!$produk_terlaris): ?>
                                <div class="empty">Belum ada data penjualan.</div>
                            <?php else: foreach ($produk_terlaris as $i => $produk): ?>
                                <div class="mini-row">
                                    <div class="rank"><?= $i+1; ?></div>
                                    <div class="mini-info">
                                        <strong><?= htmlspecialchars($produk['nama_product'],ENT_QUOTES,'UTF-8'); ?></strong>
                                        <span><?= number_format((int)$produk['jumlah_terjual'],0,',','.'); ?> pcs terjual</span>
                                    </div>
                                    <div class="mini-money"><?= dashboard_money($produk['total_penjualan']); ?></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Stok Perlu Diperhatikan</h3>
                        <div class="panel-desc">Produk dengan stok 5 pcs atau kurang.</div>

                        <div class="stock-box" style="margin-top:0;padding-top:0;border-top:0">
                            <?php if ($stok_perlu_diperhatikan): foreach ($stok_perlu_diperhatikan as $stok): ?>
                                <div class="stock-row">
                                    <span><?= htmlspecialchars($stok['nama_product'],ENT_QUOTES,'UTF-8'); ?></span>
                                    <span class="<?= (int)$stok['stok'] <= 0 ? 'stock-empty' : 'stock-low'; ?>">
                                        <?= (int)$stok['stok'] <= 0 ? 'Habis' : (int)$stok['stok'].' pcs'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notice">
                                    <strong>✓ Stok aman.</strong>
                                    Tidak ada produk dengan stok 5 pcs atau kurang.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="footer-note">
                            <a href="stok.php" class="section-link">Cek stok →</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="panel rating-panel">
                    <div class="section-head">
                        <div>
                            <h2>⭐ Rating Terbaru</h2>
                            <p>Feedback terbaru dari pelanggan yang sudah memberikan rating.</p>
                        </div>
                        <a href="produk.php" class="section-link">Lihat produk →</a>
                    </div>

                    <div class="rating-list">
                        <?php if (!$rating_terbaru): ?>
                            <div class="rating-empty">Belum ada rating yang masuk.</div>
                        <?php else: foreach ($rating_terbaru as $rating): ?>
                            <div class="rating-item">
                                <div class="rating-top">
                                    <span class="stars">
                                        <?= str_repeat('★', max(0,min(5,(int)$rating['rating']))); ?><?= str_repeat('☆', 5-max(0,min(5,(int)$rating['rating']))); ?>
                                    </span>
                                    <span class="rating-date">
                                        <?= date('d/m/Y H:i', strtotime($rating['created_at'])); ?>
                                    </span>
                                </div>

                                <div class="rating-name">
                                    <?= htmlspecialchars($rating['nama_pelanggan'] ?: 'Pelanggan',ENT_QUOTES,'UTF-8'); ?>
                                </div>

                                <div class="rating-product">
                                    <?= htmlspecialchars($rating['nama_product'] ?: 'Produk',ENT_QUOTES,'UTF-8'); ?>
                                </div>

                                <div class="rating-comment">
                                    <?= htmlspecialchars($rating['komentar'] ?: 'Tidak ada komentar.',ENT_QUOTES,'UTF-8'); ?>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </section>

            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Pesanan Terbaru</h2>
                        <p>Transaksi terbaru yang perlu dipantau atau diproses.</p>
                    </div>
                    <a href="pesanan.php" class="section-link">Lihat semua pesanan →</a>
                </div>

                <div class="table-panel">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Pembayaran</th>
                                    <th>Status Pesanan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!$pesanan_terbaru): ?>
                                    <tr>
                                        <td colspan="6" class="empty">Belum ada pesanan.</td>
                                    </tr>
                                <?php else: foreach ($pesanan_terbaru as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="order-no">#ORD<?= str_pad((int)$order['id_order'],3,'0',STR_PAD_LEFT); ?></span>
                                            <br>
                                            <span class="muted"><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                                        </td>

                                        <td>
                                            <strong><?= htmlspecialchars($order['nama_penerima'],ENT_QUOTES,'UTF-8'); ?></strong>
                                        </td>

                                        <td class="money">
                                            <?= dashboard_money($order['total_pembayaran']); ?>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= dashboard_status_class($order['status_pembayaran']); ?>">
                                                <?= htmlspecialchars($order['metode_pembayaran'],ENT_QUOTES,'UTF-8'); ?>
                                                ·
                                                <?= htmlspecialchars($order['status_pembayaran'],ENT_QUOTES,'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="status-badge <?= dashboard_status_class($order['status']); ?>">
                                                <?= htmlspecialchars($order['status'],ENT_QUOTES,'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a class="action-link" href="detail_pesanan.php?id=<?= (int)$order['id_order']; ?>">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($labels_7hari); ?>;
const pengunjung = <?= json_encode($nilai_pengunjung); ?>;
const pembeli = <?= json_encode($nilai_pembeli); ?>;

Chart.defaults.font.family = "'DM Sans', Arial, sans-serif";

new Chart(document.getElementById('grafikKunjungan'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Pengunjung',
                data: pengunjung,
                borderColor: '#C99B78',
                backgroundColor: 'rgba(173,118,54,.12)',
                pointBackgroundColor: '#C99B78',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: .35,
                fill: true
            },
            {
                label: 'Pembeli',
                data: pembeli,
                borderColor: '#6d3519',
                backgroundColor: '#6d3519',
                pointBackgroundColor: '#6d3519',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: .35,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 12,
                    font: {
                        family: "'DM Sans', Arial, sans-serif",
                        size: 12
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    font: {
                        family: "'DM Sans', Arial, sans-serif",
                        size: 12
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: "'DM Sans', Arial, sans-serif",
                        size: 12
                    }
                }
            }
        }
    }
});
</script>
</body>
</html>
