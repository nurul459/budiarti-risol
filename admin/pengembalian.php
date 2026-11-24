<?php
include "auth.php"; include "../config/koneksi.php";
function ensureReturnTable($k){mysqli_query($k,"CREATE TABLE IF NOT EXISTS pengembalian_penukaran (id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,id_order INT NOT NULL,id_user INT NOT NULL,jenis ENUM('Pengembalian','Penukaran') NOT NULL,alasan VARCHAR(100) NOT NULL,detail_alasan TEXT NULL,status ENUM('Diajukan','Diproses','Disetujui','Ditolak','Selesai') NOT NULL DEFAULT 'Diajukan',catatan_admin TEXT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_pengajuan_order (id_order),KEY idx_pengajuan_user (id_user),KEY idx_pengajuan_status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");} 
$cekKolomFoto = mysqli_query($koneksi, "SHOW COLUMNS FROM pengembalian_penukaran LIKE 'bukti_foto'");
if ($cekKolomFoto && mysqli_num_rows($cekKolomFoto) === 0) {
    mysqli_query($koneksi, "ALTER TABLE pengembalian_penukaran ADD COLUMN bukti_foto VARCHAR(255) NULL AFTER detail_alasan");
}
ensureReturnTable($koneksi);
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
$allowed=['Diajukan','Diproses','Disetujui','Ditolak','Selesai'];$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_pengajuan'])){
$id=(int)$_POST['id_pengajuan'];$status=$_POST['status']??'';$catatan=trim($_POST['catatan_admin']??'');
if(!$id||!in_array($status,$allowed,true))$err='Data tidak valid.';else{$st=mysqli_prepare($koneksi,"UPDATE pengembalian_penukaran SET status=?,catatan_admin=? WHERE id_pengajuan=?");mysqli_stmt_bind_param($st,'ssi',$status,$catatan,$id);$msg=mysqli_stmt_execute($st)?'Status pengajuan berhasil diperbarui.':'Gagal memperbarui pengajuan.';mysqli_stmt_close($st);}}
$filter=$_GET['status']??'';$where='';if(in_array($filter,$allowed,true))$where=" WHERE p.status='".mysqli_real_escape_string($koneksi,$filter)."'";
$q=mysqli_query($koneksi,"SELECT p.*,o.nama_penerima,o.total_pembayaran,o.created_at AS order_date,u.nama,u.email FROM pengembalian_penukaran p JOIN orders o ON o.id_order=p.id_order JOIN users u ON u.id_user=p.id_user $where ORDER BY p.created_at DESC");
$counts=[];foreach($allowed as $s){$r=mysqli_query($koneksi,"SELECT COUNT(*) total FROM pengembalian_penukaran WHERE status='".mysqli_real_escape_string($koneksi,$s)."'");$counts[$s]=(int)mysqli_fetch_assoc($r)['total'];}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pengembalian & Penukaran - Budiarti Risol</title><style>
*{box-sizing:border-box}body{margin:0;background:#F8EFE5;color:#542D1C;font-family:Arial,sans-serif}.main{margin:0;padding:30px 35px}.head{margin-bottom:22px}.head h1{font-family:Georgia,serif;font-size:27px;font-weight:700;line-height:1.2;color:#542D1C;margin:0 0 6px}.head p{font-size:12px;color:#8a7669}.alert{padding:12px 15px;border-radius:9px;margin-bottom:15px;font-size:12px}.ok{background:#e1f3df;color:#39713d}.bad{background:#fde2df;color:#9a342d}.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px}.stat,.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:16px}.stat small{color:#8a7669;font-size:10px}.stat strong{display:block;font-size:22px;margin-top:5px;color:#542D1C}.filters{display:flex;gap:8px;margin-bottom:15px}.filters select,.btn{padding:10px 12px;border-radius:8px;border:1px solid #dfd0c3;background:#fff;font-size:11px}.btn{background:#542D1C;color:#fff;border:0;font-weight:bold;cursor:pointer}.table{overflow:auto}table{width:100%;border-collapse:collapse;min-width:1000px}th,td{padding:12px 9px;border-bottom:1px solid #eee8e2;text-align:left;font-size:11px;vertical-align:top}th{font-size:10px;color:#765e4c;text-transform:uppercase}.badge{padding:5px 8px;border-radius:20px;background:#f5e5ce;color:#855016;font-weight:bold;font-size:10px}.action{color:#8a421b;text-decoration:none;font-weight:bold}.modal-bg{position:fixed;inset:0;background:#0006;display:flex;align-items:center;justify-content:center;padding:20px}.modal{background:#fff;width:min(500px,100%);border-radius:15px;padding:22px}.modal h2{font:21px Georgia;color:#542D1C}.field{display:flex;flex-direction:column;gap:6px;margin:12px 0}.field label{font-size:11px;font-weight:bold}.field select,.field textarea{padding:10px;border:1px solid #ddd0c5;border-radius:8px}.field textarea{min-height:90px}.actions{display:flex;justify-content:flex-end;gap:8px}@media(max-width:900px){.main{padding:20px}.stats{grid-template-columns:repeat(2,1fr)}}

        .proof-link {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 7px;
            background: #EBD3C0;
            color: #542D1C !important;
            text-decoration: none;
            font-size: 10px;
            font-weight: 700;
        }
        .proof-link:hover { background: #C99B78; }
        .proof-preview {
            display: block;
            width: 100%;
            max-width: 420px;
            max-height: 280px;
            object-fit: contain;
            border: 1px solid #E5D3C3;
            border-radius: 10px;
            background: #F8EFE5;
            padding: 5px;
        }
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
</header>
<section class="page-content"><main class="main"><?php include __DIR__ . '/tabs_pesanan.php'; ?><div class="head"><h1>Pengembalian & Penukaran</h1><p>Kelola pengajuan pengembalian atau penukaran dari pelanggan.</p></div><?php if($msg):?><div class="alert ok"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="alert bad"><?=e($err)?></div><?php endif;?><div class="stats"><?php foreach($allowed as $s):?><div class="stat"><small><?=e($s)?></small><strong><?=$counts[$s]?></strong></div><?php endforeach;?></div><div class="card"><form class="filters"><select name="status"><option value="">Semua Status</option><?php foreach($allowed as $s):?><option value="<?=e($s)?>" <?=$filter===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select><button class="btn">Filter</button><a href="pengembalian.php" style="padding:10px 12px;font-size:11px;color:#6b3518">Reset</a></form><div class="table"><table><thead><tr><th>Pengajuan</th><th>Pelanggan</th><th>Pesanan</th><th>Jenis</th><th>Alasan</th><th>Bukti Foto</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody><?php if(!$q||mysqli_num_rows($q)===0):?><tr><td colspan="9">Belum ada pengajuan.</td></tr><?php else:while($r=mysqli_fetch_assoc($q)):?><tr><td>#REQ<?=str_pad($r['id_pengajuan'],3,'0',STR_PAD_LEFT)?><br><span style="color:#8a7669"><?=e($r['detail_alasan']?:'-')?></span></td><td><strong><?=e($r['nama'])?></strong><br><?=e($r['email'])?></td><td>#ORD<?=str_pad($r['id_order'],3,'0',STR_PAD_LEFT)?><br>Rp<?=number_format((float)$r['total_pembayaran'],0,',','.')?></td><td><?=e($r['jenis'])?></td><td><?=e($r['alasan'])?></td><td><?php if (!empty($r['bukti_foto'])): ?><a class="proof-link" href="../<?= e(ltrim($r['bukti_foto'], '/')) ?>" target="_blank" rel="noopener">Lihat Foto</a><?php else: ?><span style="color:#9b8b7f">Tidak ada</span><?php endif; ?></td><td><span class="badge"><?=e($r['status'])?></span></td><td><?=e(date('d/m/Y H:i',strtotime($r['created_at'])))?></td><td><a class="action" href="pengembalian.php?edit=<?=$r['id_pengajuan']?>">Kelola</a></td></tr><?php endwhile;endif;?></tbody></table></div></div></main></section></main></div><?php if(isset($_GET['edit'])){$eid=(int)$_GET['edit'];$er=mysqli_query($koneksi,"SELECT * FROM pengembalian_penukaran WHERE id_pengajuan=$eid LIMIT 1");$edit=$er?mysqli_fetch_assoc($er):null;if($edit):?><div class="modal-bg"><div class="modal"><h2>Kelola Pengajuan #REQ<?=str_pad($edit['id_pengajuan'],3,'0',STR_PAD_LEFT)?></h2><p style="font-size:12px;color:#806d5e">#ORD<?=str_pad($edit['id_order'],3,'0',STR_PAD_LEFT)?> · <?=e($edit['jenis'])?> · <?=e($edit['alasan'])?></p><div style="font-size:12px;background:#fff8f1;padding:12px;border-radius:9px;margin:12px 0"><?=nl2br(e($edit['detail_alasan']?:'Tidak ada detail.'))?></div><?php if (!empty($edit['bukti_foto'])): ?><div class="field"><label>Pratinjau Bukti Foto</label><a href="../<?= e(ltrim($edit['bukti_foto'], '/')) ?>" target="_blank" rel="noopener"><img src="../<?= e(ltrim($edit['bukti_foto'], '/')) ?>" alt="Bukti foto pengembalian pelanggan" class="proof-preview"></a><small>Klik foto untuk melihat ukuran penuh.</small></div><?php else: ?><div class="field"><label>Bukti Foto</label><small>Pelanggan belum mengunggah foto.</small></div><?php endif; ?><form method="post"><input type="hidden" name="id_pengajuan" value="<?=$edit['id_pengajuan']?>"><div class="field"><label>Status</label><select name="status"><?php foreach($allowed as $s):?><option value="<?=e($s)?>" <?=$edit['status']===$s?'selected':''?>><?=e($s)?></option><?php endforeach;?></select></div><div class="field"><label>Catatan Admin</label><textarea name="catatan_admin" placeholder="Contoh: pengajuan disetujui setelah pemeriksaan."><?=e($edit['catatan_admin'])?></textarea></div><div class="actions"><a href="pengembalian.php" style="padding:10px 12px;font-size:11px">Batal</a><button class="btn" name="update_pengajuan" value="1">Simpan</button></div></form></div></div><?php endif;}?></body></html>