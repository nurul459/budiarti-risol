<?php
include "auth.php";
include "../config/koneksi.php";

function ensureShippingTable($koneksi) {
    $sql = "CREATE TABLE IF NOT EXISTS pengiriman (
        id_pengiriman INT AUTO_INCREMENT PRIMARY KEY,
        id_order INT NOT NULL,
        kurir VARCHAR(100) NOT NULL DEFAULT '',
        nomor_resi VARCHAR(100) NOT NULL DEFAULT '',
        status_pengiriman ENUM('Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai','Gagal') NOT NULL DEFAULT 'Belum Diproses',
        tanggal_kirim DATETIME NULL,
        estimasi_sampai DATE NULL,
        catatan VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_pengiriman_order (id_order),
        KEY idx_resi (nomor_resi),
        KEY idx_status_pengiriman (status_pengiriman)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return mysqli_query($koneksi, $sql);
}
ensureShippingTable($koneksi);

$allowed_status = ['Belum Diproses','Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai','Gagal'];
$allowed_kurir = ['GoSend (Gojek)', 'GrabExpress (Grab)'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_pengiriman_inline'])) {
    $id_order = (int)($_POST['id_order'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');
    $allowed_fields = ['kurir', 'status_pengiriman'];

    if ($id_order <= 0 || !in_array($field, $allowed_fields, true)) {
        $error = 'Data pengiriman tidak valid.';
    } elseif ($field === 'kurir' && !in_array($value, $allowed_kurir, true) && $value !== '') {
        $error = 'Layanan ojol tidak valid.';
    } elseif ($field === 'status_pengiriman' && !in_array($value, $allowed_status, true)) {
        $error = 'Status pengiriman tidak valid.';
    } else {
        $cek = mysqli_query($koneksi, "SELECT id_order, status FROM orders WHERE id_order=$id_order LIMIT 1");
        $order = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$order) {
            $error = 'Pesanan tidak ditemukan.';
        } else {
            $cek_kirim = mysqli_query($koneksi, "SELECT id_pengiriman, kurir, status_pengiriman FROM pengiriman WHERE id_order=$id_order LIMIT 1");
            $pengiriman_lama = $cek_kirim ? mysqli_fetch_assoc($cek_kirim) : null;
            $kurir_lama = $pengiriman_lama['kurir'] ?? '';
            $status_lama = $pengiriman_lama['status_pengiriman'] ?? 'Belum Diproses';
            $kurir_baru = $field === 'kurir' ? $value : $kurir_lama;
            $status_baru = $field === 'status_pengiriman' ? $value : $status_lama;

            if ($status_baru !== 'Belum Diproses' && $kurir_baru === '') {
                $error = 'Pilih layanan ojol terlebih dahulu sebelum mengubah status pengiriman.';
            } else {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO pengiriman (id_order, kurir, nomor_resi, status_pengiriman) VALUES (?, ?, '', ?) ON DUPLICATE KEY UPDATE kurir=VALUES(kurir), status_pengiriman=VALUES(status_pengiriman)");
                mysqli_stmt_bind_param($stmt, 'iss', $id_order, $kurir_baru, $status_baru);
                if (mysqli_stmt_execute($stmt)) {
                    $map_status = ['Selesai'=>'Selesai','Sampai Tujuan'=>'Dikirim','Dalam Perjalanan'=>'Dikirim','Dikirim'=>'Dikirim','Diproses'=>'Diproses','Belum Diproses'=>'Diproses'];
                    if (isset($map_status[$status_baru])) {
                        $new_order_status = $map_status[$status_baru];
                        mysqli_query($koneksi, "UPDATE orders SET status='" . mysqli_real_escape_string($koneksi, $new_order_status) . "' WHERE id_order=$id_order AND status <> 'Dibatalkan'");
                    }
                    $success = 'Pengiriman berhasil diperbarui.';
                } else {
                    $error = 'Pengiriman gagal diperbarui: ' . mysqli_error($koneksi);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengiriman'])) {
    $id_order = (int)($_POST['id_order'] ?? 0);
    $kurir = trim($_POST['kurir'] ?? '');
    $nomor_resi = trim($_POST['nomor_resi'] ?? '');
    $status = trim($_POST['status_pengiriman'] ?? 'Belum Diproses');
    $tanggal_kirim = trim($_POST['tanggal_kirim'] ?? '');
    $estimasi_sampai = trim($_POST['estimasi_sampai'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($id_order <= 0) $error = 'Pesanan tidak valid.';
    elseif (!in_array($status, $allowed_status, true)) $error = 'Status pengiriman tidak valid.';
    elseif ($status !== 'Belum Diproses' && $kurir === '') $error = 'Nama kurir wajib diisi untuk pengiriman yang sudah diproses.';
    else {
        $cek = mysqli_query($koneksi, "SELECT id_order, status FROM orders WHERE id_order = $id_order LIMIT 1");
        $order = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$order) {
            $error = 'Pesanan tidak ditemukan.';
        } else {
            $tanggal_db = $tanggal_kirim !== '' ? $tanggal_kirim . ':00' : null;
            $estimasi_db = $estimasi_sampai !== '' ? $estimasi_sampai : null;
            $stmt = mysqli_prepare($koneksi, "INSERT INTO pengiriman (id_order, kurir, nomor_resi, status_pengiriman, tanggal_kirim, estimasi_sampai, catatan) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE kurir=VALUES(kurir), nomor_resi=VALUES(nomor_resi), status_pengiriman=VALUES(status_pengiriman), tanggal_kirim=VALUES(tanggal_kirim), estimasi_sampai=VALUES(estimasi_sampai), catatan=VALUES(catatan)");
            mysqli_stmt_bind_param($stmt, 'issssss', $id_order, $kurir, $nomor_resi, $status, $tanggal_db, $estimasi_db, $catatan);
            if (mysqli_stmt_execute($stmt)) {
                // Sinkronkan status pesanan utama agar alur order dan logistik konsisten.
                $map_status = ['Selesai'=>'Selesai','Sampai Tujuan'=>'Dikirim','Dalam Perjalanan'=>'Dikirim','Dikirim'=>'Dikirim','Diproses'=>'Diproses','Belum Diproses'=>'Diproses'];
                if (isset($map_status[$status])) {
                    $new_order_status = $map_status[$status];
                    mysqli_query($koneksi, "UPDATE orders SET status='" . mysqli_real_escape_string($koneksi, $new_order_status) . "' WHERE id_order=$id_order AND status <> 'Dibatalkan'");
                }
                $success = 'Data pengiriman berhasil disimpan.';
            } else $error = 'Data pengiriman gagal disimpan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}

$keyword = trim($_GET['keyword'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$periode = trim($_GET['periode'] ?? 'semua');
$allowed_periode = ['hari_ini', 'minggu_ini', 'semua'];
if (!in_array($periode, $allowed_periode, true)) { $periode = 'semua'; }
$where = '';
if ($keyword !== '') {
    $k = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (CAST(o.id_order AS CHAR) LIKE '%$k%' OR o.nama_penerima LIKE '%$k%' OR p.kurir LIKE '%$k%')";
}
if ($status_filter !== '' && in_array($status_filter, $allowed_status, true)) {
    $s = mysqli_real_escape_string($koneksi, $status_filter);
    $where .= " AND COALESCE(p.status_pengiriman, 'Belum Diproses')='$s'";
}
if ($periode === 'hari_ini') {
    $where .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($periode === 'minggu_ini') {
    $where .= " AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
}

$sql = "SELECT o.id_order, o.nama_penerima, o.no_hp, o.alamat, o.kecamatan, o.kota, o.provinsi, o.kode_pos, o.ongkir, o.status AS status_order, o.created_at,
               p.id_pengiriman, p.kurir, p.nomor_resi, p.status_pengiriman, p.tanggal_kirim, p.estimasi_sampai, p.catatan
        FROM orders o LEFT JOIN pengiriman p ON p.id_order=o.id_order
        WHERE o.status <> 'Dibatalkan' $where ORDER BY o.id_order DESC";
$query = mysqli_query($koneksi, $sql);

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah($value) { return 'Rp' . number_format((float)$value, 0, ',', '.'); }
function shippingClass($status) {
    $map = ['Belum Diproses'=>'waiting','Diproses'=>'process','Dikirim'=>'sent','Dalam Perjalanan'=>'travel','Sampai Tujuan'=>'arrived','Selesai'=>'done','Gagal'=>'failed'];
    return $map[$status] ?? 'waiting';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Pengiriman - Budiarti Risol</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}a{text-decoration:none}.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;inset:0 auto 0 0;padding:25px 15px;z-index:10}.sidebar-logo{text-align:center;padding-bottom:30px}.logo-icon{font-size:32px}.sidebar-logo h2{font-family:Georgia,serif;font-size:22px;font-style:italic}.sidebar-logo p{font-size:9px;letter-spacing:1px;color:#EBD3C0;margin-top:3px}.sidebar-menu{display:flex;flex-direction:column;gap:7px}.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;color:#f6e8dc;font-size:13px;white-space:nowrap;overflow:hidden}.sidebar-menu a:hover,.sidebar-menu a.active{background:#75401f;color:#fff}.menu-icon{flex:0 0 20px;width:20px;text-align:center;line-height:1;font-size:15px}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;border-bottom:1px solid #eee3d8;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.page-content{padding:30px 35px 50px}.page-title-row{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:25px}.page-title h1{font-family:Georgia,serif;font-size:27px;margin-bottom:7px}.page-title p{color:#8a7669;font-size:12px}.btn{display:inline-block;border:0;border-radius:9px;padding:11px 15px;font-weight:bold;font-size:12px;cursor:pointer}.btn-primary{background:#542D1C;color:#fff}.btn-light{background:#fff;border:1px solid #e7d8cb;color:#5a3b29}.alert{padding:12px 15px;border-radius:10px;margin-bottom:18px;font-size:12px}.alert.success{background:#e1f3df;color:#39713d}.alert.error{background:#fde2df;color:#9a342d}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px}.stat{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:18px}.stat small{color:#8a7669;font-size:11px}.stat strong{display:block;font-size:24px;color:#542D1C;margin-top:6px}.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px;box-shadow:0 3px 12px rgba(80,40,10,.04)}.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.filters input,.filters select{border:1px solid #e2d4c8;border-radius:8px;padding:10px 12px;background:#fff;font-size:12px}.table-wrap{overflow-x:auto}.data-table{width:100%;border-collapse:collapse;min-width:920px}.data-table th,.data-table td{padding:13px 10px;border-bottom:1px solid #eee8e2;text-align:left;font-size:11px;vertical-align:top}.data-table th{color:#765e4c;font-size:10px;text-transform:uppercase;letter-spacing:.4px}.order-no{font-weight:bold;color:#542D1C}.muted{color:#8a7669}.badge{display:inline-block;padding:6px 9px;border-radius:20px;font-size:10px;font-weight:bold}.waiting{background:#f1eee9;color:#777}.process{background:#fff0c9;color:#bd7a13}.sent,.travel{background:#dceefa;color:#397fa9}.arrived,.done{background:#dcefdc;color:#47884d}.failed{background:#fde1df;color:#a43c34}.action{color:#8a421b;font-weight:bold;font-size:11px}.address{line-height:1.55;max-width:240px}.inline-edit-form{margin:0}.inline-select{width:100%;min-width:145px;padding:8px 28px 8px 9px;border:1px solid #e2d4c8;border-radius:8px;background:#fff;color:#542D1C;font-size:11px;font-weight:600;cursor:pointer}.inline-select:focus{outline:2px solid #e8c58b;outline-offset:1px}.status-select{font-weight:700}.status-select.waiting{background:#f1eee9;color:#777}.status-select.process{background:#fff0c9;color:#bd7a13}.status-select.sent,.status-select.travel{background:#dceefa;color:#397fa9}.status-select.arrived,.status-select.done{background:#dcefdc;color:#47884d}.status-select.failed{background:#fde1df;color:#a43c34}.inline-edit-form + .muted,.inline-edit-form + br + .muted{display:block;margin-top:5px}.table-wrap .data-table td:nth-child(4),.table-wrap .data-table td:nth-child(5){min-width:165px}
.modal-backdrop{position:fixed;inset:0;background:rgba(40,20,5,.45);display:flex;align-items:center;justify-content:center;padding:20px;z-index:30}.modal{width:min(620px,100%);background:#fff;border-radius:16px;padding:24px;max-height:90vh;overflow:auto}.modal h2{font-family:Georgia,serif;margin-bottom:6px}.modal p{font-size:11px;color:#8a7669;margin-bottom:18px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.form-group{display:flex;flex-direction:column;gap:6px}.form-group.full{grid-column:1/-1}.form-group label{font-size:11px;font-weight:bold;color:#5a3b29}.form-group input,.form-group select,.form-group textarea{padding:10px;border:1px solid #ded0c4;border-radius:8px;font-size:12px}.form-group textarea{min-height:80px;resize:vertical}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}@media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:700px){.sidebar{width:190px}.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:20px}.top-header{padding:0 20px}.page-title-row{align-items:flex-start;flex-direction:column}.form-grid{grid-template-columns:1fr}.form-group.full{grid-column:auto}}
</style><link rel="stylesheet" href="../assets/css/admin-sidebar.css">
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
<main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>
<section class="page-content">
<?php include __DIR__ . '/tabs_pesanan.php'; ?>
<div class="page-title-row"><div class="page-title"><h1>Manajemen Pengiriman</h1><p>Kelola layanan ojol, status, dan informasi pengiriman pesanan.</p></div></div>
<?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?><?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<div class="stats">
<?php
$period_sql = '';
if ($periode === 'hari_ini') {
    $period_sql = " AND DATE(o.created_at) = CURDATE()";
} elseif ($periode === 'minggu_ini') {
    $period_sql = " AND YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
}
$stats = [];
$stats_q = mysqli_query($koneksi, "SELECT
    COUNT(*) AS total_pesanan,
    SUM(CASE WHEN COALESCE(p.status_pengiriman, 'Belum Diproses') = 'Belum Diproses' THEN 1 ELSE 0 END) AS belum_diproses,
    SUM(CASE WHEN COALESCE(p.status_pengiriman, 'Belum Diproses') IN ('Dikirim','Dalam Perjalanan') THEN 1 ELSE 0 END) AS sedang_dikirim,
    SUM(CASE WHEN COALESCE(p.status_pengiriman, 'Belum Diproses') = 'Selesai' THEN 1 ELSE 0 END) AS selesai
    FROM orders o LEFT JOIN pengiriman p ON p.id_order=o.id_order
    WHERE o.status <> 'Dibatalkan' $period_sql");
$stat_row = $stats_q ? mysqli_fetch_assoc($stats_q) : [];
$stats[] = ['Total Pesanan', (int)($stat_row['total_pesanan'] ?? 0)];
$stats[] = ['Belum Diproses', (int)($stat_row['belum_diproses'] ?? 0)];
$stats[] = ['Sedang Dikirim', (int)($stat_row['sedang_dikirim'] ?? 0)];
$stats[] = ['Selesai', (int)($stat_row['selesai'] ?? 0)];
foreach($stats as $st): ?><div class="stat"><small><?=e($st[0])?></small><strong><?=e($st[1])?></strong></div><?php endforeach; ?>
</div>
<div class="card"><form class="filters" method="get"><input type="text" name="keyword" value="<?=e($keyword)?>" placeholder="Cari order, penerima, kurir..."><select name="periode" aria-label="Pilih periode" onchange="this.form.submit()"><option value="semua" <?=$periode==='semua'?'selected':''?>>Semua Waktu</option><option value="hari_ini" <?=$periode==='hari_ini'?'selected':''?>>Hari Ini</option><option value="minggu_ini" <?=$periode==='minggu_ini'?'selected':''?>>Minggu Ini</option></select><select name="status" aria-label="Filter status pengiriman" onchange="this.form.submit()"><option value="">Semua status</option><?php foreach($allowed_status as $s): ?><option value="<?=e($s)?>" <?= $status_filter===$s?'selected':'' ?>><?=e($s)?></option><?php endforeach; ?></select></form>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Pesanan</th><th>Penerima</th><th>Alamat Pengiriman</th><th>Layanan Ojol</th><th>Status Pengiriman</th></tr></thead><tbody>
<?php if(mysqli_num_rows($query)===0): ?><tr><td colspan="5" class="muted">Belum ada data pesanan.</td></tr><?php else: while($row=mysqli_fetch_assoc($query)): ?><tr>
<td><span class="order-no">#ORD<?=str_pad($row['id_order'],3,'0',STR_PAD_LEFT)?></span><br><span class="muted"><?=e(date('d/m/Y H:i',strtotime($row['created_at'])))?></span></td>
<td><strong><?=e($row['nama_penerima'])?></strong><br><span class="muted"><?=e($row['no_hp'])?></span></td>
<td class="address"><?=e($row['alamat'])?><br><?=e($row['kecamatan'])?>, <?=e($row['kota'])?><br><?=e($row['provinsi'])?> - <?=e($row['kode_pos'])?></td>
<td>
<form method="post" class="inline-edit-form">
<input type="hidden" name="ubah_pengiriman_inline" value="1"><input type="hidden" name="id_order" value="<?=e($row['id_order'])?>"><input type="hidden" name="field" value="kurir"><select name="value" class="inline-select" onchange="this.form.submit()" aria-label="Pilih layanan ojol">
<option value="">Belum ditentukan</option><?php foreach($allowed_kurir as $k): ?><option value="<?=e($k)?>" <?=($row['kurir'] ?? '')===$k?'selected':''?>><?=e($k)?></option><?php endforeach; ?>
</select></form>
</td>
<td>
<?php $ss=$row['status_pengiriman'] ?: 'Belum Diproses'; ?>
<form method="post" class="inline-edit-form">
<input type="hidden" name="ubah_pengiriman_inline" value="1"><input type="hidden" name="id_order" value="<?=e($row['id_order'])?>"><input type="hidden" name="field" value="status_pengiriman"><select name="value" class="inline-select status-select <?=shippingClass($ss)?>" onchange="this.form.submit()" aria-label="Pilih status pengiriman"><?php foreach($allowed_status as $s): ?><option value="<?=e($s)?>" <?=$ss===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select>
</form>
<?php if($row['estimasi_sampai']): ?><span class="muted">Estimasi: <?=e(date('d/m/Y',strtotime($row['estimasi_sampai'])))?></span><?php endif; ?>
</td>
</tr><?php endwhile; endif; ?></tbody></table></div></div>
</section></main></div>
<?php /* Form pengiriman sekarang diedit langsung dari tabel. */ ?>
</body></html>
