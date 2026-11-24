<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";

ensurePaymentTable($koneksi);
ensureShippingTable($koneksi);

$allowed = ['Menunggu Verifikasi','Terverifikasi','Ditolak','Belum Dibayar','Lunas'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pembayaran'])) {
    $id_order = (int)($_POST['id_order'] ?? 0);
    $status = trim($_POST['status_pembayaran'] ?? '');
    if ($id_order <= 0 || !in_array($status, $allowed, true)) {
        $error = 'Data pembayaran tidak valid.';
    } else {
        $cek = mysqli_query($koneksi, "SELECT * FROM orders WHERE id_order=$id_order LIMIT 1");
        $order = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$order) {
            $error = 'Pesanan tidak ditemukan.';
        } else {
            $tanggal_bayar = in_array($status, ['Terverifikasi','Lunas'], true) ? date('Y-m-d H:i:s') : null;
            $stmt = mysqli_prepare($koneksi, "INSERT INTO pembayaran (id_order, metode_pembayaran, jumlah, status_pembayaran, bukti_pembayaran, tanggal_bayar)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE metode_pembayaran=VALUES(metode_pembayaran), jumlah=VALUES(jumlah), status_pembayaran=VALUES(status_pembayaran), bukti_pembayaran=VALUES(bukti_pembayaran), tanggal_bayar=VALUES(tanggal_bayar)");
            $metode = $order['metode_pembayaran'];
            $jumlah = (float)$order['total_pembayaran'];
            $bukti = $order['bukti_pembayaran'];
            mysqli_stmt_bind_param($stmt, 'isdsss', $id_order, $metode, $jumlah, $status, $bukti, $tanggal_bayar);
            if (mysqli_stmt_execute($stmt)) {
                // Semua metode pembayaran (termasuk COD) mengikuti verifikasi admin.
                if (in_array($status, ['Terverifikasi','Lunas'], true) && $order['status'] !== 'Dibatalkan') {
                    mysqli_query($koneksi, "UPDATE orders SET status='Diproses' WHERE id_order=$id_order");
                    // Begitu pembayaran disetujui, pesanan otomatis masuk ke antrian pengiriman.
                    mysqli_query($koneksi, "INSERT IGNORE INTO pengiriman (id_order, kurir, nomor_resi, status_pengiriman) VALUES ($id_order, '', '', 'Belum Diproses')");
                }
                if ($status === 'Ditolak' && $order['status'] !== 'Dibatalkan') {
                    mysqli_query($koneksi, "UPDATE orders SET status='Menunggu Verifikasi' WHERE id_order=$id_order");
                }
                $success = 'Status pembayaran berhasil diperbarui.';
            } else {
                $error = 'Pembayaran gagal diperbarui: ' . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$keyword = trim($_GET['keyword'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$where = '';
if ($keyword !== '') {
    $k = mysqli_real_escape_string($koneksi, $keyword);
    $where .= " AND (CAST(o.id_order AS CHAR) LIKE '%$k%' OR o.nama_penerima LIKE '%$k%' OR o.metode_pembayaran LIKE '%$k%')";
}
if ($status_filter !== '' && in_array($status_filter, $allowed, true)) {
    $s = mysqli_real_escape_string($koneksi, $status_filter);
    $where .= " AND p.status_pembayaran='$s'";
}

$sql = "SELECT o.id_order,o.nama_penerima,o.total_pembayaran,o.metode_pembayaran,
               COALESCE(NULLIF(o.bukti_pembayaran,''), p.bukti_pembayaran) AS bukti_pembayaran,
               o.status AS status_order,o.created_at,
               p.id_pembayaran,p.status_pembayaran,p.tanggal_bayar,p.catatan
        FROM orders o LEFT JOIN pembayaran p ON p.id_order=o.id_order
        WHERE 1=1 $where ORDER BY o.id_order DESC";
$query = mysqli_query($koneksi, $sql);

$counts = [];
foreach ($allowed as $s) {
    $esc = mysqli_real_escape_string($koneksi, $s);
    $q = mysqli_query($koneksi, "SELECT COUNT(*) total FROM pembayaran WHERE status_pembayaran='$esc'");
    $counts[$s] = $q ? (int)mysqli_fetch_assoc($q)['total'] : 0;
}
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function paymentClass($s){
    return match($s){ 'Terverifikasi','Lunas'=>'paid', 'Ditolak'=>'rejected', 'Belum Dibayar'=>'unpaid', default=>'waiting' };
}

function proofUrl($filename, $id_order = 0){
    $filename = trim((string)$filename);
    if ($filename === '') return '';
    return 'lihat_bukti.php?id_order=' . (int)$id_order;
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manajemen Pembayaran - Budiarti Risol</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}a{text-decoration:none}.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;inset:0 auto 0 0;padding:25px 15px;z-index:10}.sidebar-logo{text-align:center;padding-bottom:30px}.logo-icon{font-size:32px}.sidebar-logo h2{font-family:Georgia,serif;font-size:22px;font-style:italic}.sidebar-logo p{font-size:9px;letter-spacing:1px;color:#EBD3C0;margin-top:3px}.sidebar-menu{display:flex;flex-direction:column;gap:7px}.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;color:#f6e8dc;font-size:13px;white-space:nowrap;overflow:hidden}.sidebar-menu a:hover,.sidebar-menu a.active{background:#75401f;color:#fff}.menu-icon{flex:0 0 20px;width:20px;text-align:center;line-height:1;font-size:15px}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.page-content{padding:30px 35px 50px}.page-title-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px}.page-title h1{font-family:Georgia,serif;font-size:27px;margin-bottom:7px}.page-title p{color:#8a7669;font-size:12px}.alert{padding:12px 15px;border-radius:10px;margin-bottom:18px;font-size:12px}.success{background:#e1f3df;color:#39713d}.error{background:#fde2df;color:#9a342d}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px}.stat{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:18px}.stat small{color:#8a7669;font-size:11px}.stat strong{display:block;font-size:23px;color:#542D1C;margin-top:6px}.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px}.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.filters input,.filters select,.modal input,.modal select,.modal textarea{border:1px solid #e2d4c8;border-radius:8px;padding:10px 12px;background:#fff;font-size:12px}.btn{display:inline-block;border:0;border-radius:9px;padding:11px 15px;font-weight:bold;font-size:12px;cursor:pointer}.btn-primary{background:#542D1C;color:#fff}.btn-light{background:#fff;border:1px solid #e7d8cb;color:#5a3b29}.table-wrap{overflow-x:auto}.data-table{width:100%;border-collapse:collapse;min-width:980px}.data-table th,.data-table td{padding:13px 10px;border-bottom:1px solid #eee8e2;text-align:left;font-size:11px;vertical-align:top}.data-table th{color:#765e4c;font-size:10px;text-transform:uppercase}.order-no{font-weight:bold;color:#542D1C}.muted{color:#8a7669}.badge{display:inline-block;padding:6px 9px;border-radius:20px;font-size:10px;font-weight:bold}.waiting{background:#fff0c9;color:#bd7a13}.paid{background:#dcefdc;color:#47884d}.rejected{background:#fde1df;color:#a43c34}.unpaid{background:#eeeeee;color:#777}.proof{color:#8a421b;font-weight:bold}.action{color:#8a421b;font-weight:bold;font-size:11px}.modal-backdrop{position:fixed;inset:0;background:rgba(40,20,5,.45);display:flex;align-items:center;justify-content:center;padding:20px;z-index:30}.modal{width:min(520px,100%);background:#fff;border-radius:16px;padding:24px}.modal h2{font-family:Georgia,serif;margin-bottom:6px}.modal p{font-size:11px;color:#8a7669;margin-bottom:18px}.form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}.form-group label{font-size:11px;font-weight:bold;color:#5a3b29}.form-group textarea{min-height:85px;resize:vertical}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.status-action{display:flex;flex-direction:column;align-items:flex-start;gap:5px}.status-action-link{font-size:11px;color:#8a421b;font-weight:700;background:none;border:0;padding:0;cursor:pointer;text-decoration:none}.status-action-link:hover{text-decoration:underline}.proof-img{max-width:100%;max-height:260px;border-radius:10px;border:1px solid #eadfd5;margin-top:8px}@media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:700px){.sidebar{width:190px}.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:20px}.top-header{padding:0 20px}.page-title-row{align-items:flex-start;flex-direction:column}}
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
</head><body><div class="admin-wrapper"><?php include __DIR__ . '/sidebar.php'; ?><main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header><section class="page-content"><?php include __DIR__ . '/tabs_pesanan.php'; ?>
<div class="page-title-row"><div class="page-title"><h1>Manajemen Pembayaran</h1><p>Verifikasi bukti pembayaran dan kelola status pembayaran pesanan.</p></div></div>
<?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif;?><?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif;?>
<div class="stats"><div class="stat"><small>Menunggu Verifikasi</small><strong><?=$counts['Menunggu Verifikasi']?></strong></div><div class="stat"><small>Terverifikasi</small><strong><?=$counts['Terverifikasi']?></strong></div><div class="stat"><small>Belum Dibayar</small><strong><?=$counts['Belum Dibayar']?></strong></div><div class="stat"><small>Ditolak</small><strong><?=$counts['Ditolak']?></strong></div></div>
<div class="card"><form class="filters" method="get"><input name="keyword" value="<?=e($keyword)?>" placeholder="Cari order, nama, metode..."><select name="status"><option value="">Semua status</option><?php foreach($allowed as $s):?><option value="<?=e($s)?>" <?=$status_filter===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select><button class="btn btn-primary">Filter</button><a class="btn btn-light" href="pembayaran.php">Reset</a></form><div class="table-wrap"><table class="data-table"><thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th><th>Bukti</th><th>Status</th><th>Tanggal Bayar</th></tr></thead><tbody>
<?php if(!$query || mysqli_num_rows($query)===0):?><tr><td colspan="7" class="muted">Belum ada data pembayaran.</td></tr><?php else:while($row=mysqli_fetch_assoc($query)):?><tr><td><span class="order-no">#ORD<?=str_pad($row['id_order'],3,'0',STR_PAD_LEFT)?></span><br><span class="muted"><?=e(date('d/m/Y H:i',strtotime($row['created_at'])))?></span></td><td><strong><?=e($row['nama_penerima'])?></strong></td><td><?=e($row['metode_pembayaran'])?></td><td>Rp<?=number_format($row['total_pembayaran'],0,',','.')?></td><td><?php if($row['bukti_pembayaran']):?><a class="proof" href="pembayaran.php?edit=<?=$row['id_order']?>">Lihat Bukti</a><?php else:?><span class="muted">Tidak ada</span><?php endif;?></td><td><div class="status-action"><span class="badge <?=paymentClass($row['status_pembayaran'] ?: ($row['metode_pembayaran']==='COD'?'Belum Dibayar':'Menunggu Verifikasi'))?>"><?=e($row['status_pembayaran'] ?: ($row['metode_pembayaran']==='COD'?'Belum Dibayar':'Menunggu Verifikasi'))?></span><?php $row_status = $row['status_pembayaran'] ?: ($row['metode_pembayaran']==='COD'?'Belum Dibayar':'Menunggu Verifikasi'); ?><?php if($row_status === 'Menunggu Verifikasi'): ?><form method="post" onsubmit="return confirm('Verifikasi pembayaran #ORD<?=str_pad($row['id_order'],3,'0',STR_PAD_LEFT)?>?');"><input type="hidden" name="id_order" value="<?=$row['id_order']?>"><input type="hidden" name="status_pembayaran" value="Terverifikasi"><button class="status-action-link" type="submit" name="update_pembayaran" value="1">Verifikasi</button></form><?php elseif($row_status === 'Belum Dibayar'): ?><a class="status-action-link" href="pembayaran.php?edit=<?=$row['id_order']?>">Kelola</a><?php else: ?><a class="status-action-link" href="pembayaran.php?edit=<?=$row['id_order']?>">Kelola</a><?php endif; ?></div></td><td><?=!empty($row['tanggal_bayar'])?e(date('d/m/Y H:i',strtotime($row['tanggal_bayar']))):'<span class="muted">-</span>'?></td></tr><?php endwhile;endif;?></tbody></table></div></div></section></main></div>
<?php if(isset($_GET['edit']) && (int)$_GET['edit']>0):$eid=(int)$_GET['edit'];$eq=mysqli_query($koneksi,"SELECT o.*, COALESCE(NULLIF(o.bukti_pembayaran,''), p.bukti_pembayaran) AS bukti_pembayaran, p.status_pembayaran FROM orders o LEFT JOIN pembayaran p ON p.id_order=o.id_order WHERE o.id_order=$eid LIMIT 1");$edit=$eq?mysqli_fetch_assoc($eq):null;if($edit):$cur=$edit['status_pembayaran'] ?: ($edit['metode_pembayaran']==='COD'?'Belum Dibayar':'Menunggu Verifikasi');?><div class="modal-backdrop"><div class="modal"><h2>Kelola Pembayaran #ORD<?=str_pad($edit['id_order'],3,'0',STR_PAD_LEFT)?></h2><p><?=e($edit['nama_penerima'])?> · <?=e($edit['metode_pembayaran'])?> · Rp<?=number_format($edit['total_pembayaran'],0,',','.')?></p><?php if($edit['bukti_pembayaran']):?><div class="form-group"><label>Bukti Pembayaran</label><a href="<?=e(proofUrl($edit['bukti_pembayaran'], $edit['id_order']))?>" target="_blank" rel="noopener"><img class="proof-img" src="<?=e(proofUrl($edit['bukti_pembayaran'], $edit['id_order']))?>" alt="Bukti pembayaran" onerror="this.style.display='none';this.nextElementSibling.style.display='block';"><span class="muted" style="display:none">File bukti tidak ditemukan di server.</span></a></div><?php endif;?><form method="post"><input type="hidden" name="id_order" value="<?=$edit['id_order']?>"><div class="form-group"><label>Status Pembayaran</label><select name="status_pembayaran" required><?php foreach($allowed as $s):?><option value="<?=e($s)?>" <?=$cur===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select></div><div class="modal-actions"><a class="btn btn-light" href="pembayaran.php">Batal</a><button class="btn btn-primary" name="update_pembayaran" value="1">Simpan Status</button></div></form></div></div><?php endif;endif;?><script>document.addEventListener('keydown',function(e){if(e.key==='Escape')window.location='pembayaran.php';});</script></body></html>
