<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
include "../config/pengunjung.php";
ensureCrmTable($koneksi);
init_tabel_pengunjung($koneksi);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function whatsappUrl($phone){$digits=preg_replace('/\D+/','',$phone);if($digits==='')return '';if(strpos($digits,'0')===0)$digits='62'.substr($digits,1);elseif(strpos($digits,'62')!==0)$digits='62'.$digits;return 'https://wa.me/'.$digits;}

$notice='';$error='';
$allowed=['Pesanan','Keluhan','Pertanyaan','Follow Up','Lainnya'];

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['simpan_interaksi'])){
    $id_user=(int)($_POST['id_user']??0);$jenis=trim($_POST['jenis_interaksi']??'');$catatan=trim($_POST['catatan']??'');
    if(!$id_user||!in_array($jenis,$allowed,true))$error='Data interaksi belum lengkap.';
    else{$stmt=mysqli_prepare($koneksi,"INSERT INTO crm_interaksi (id_user,jenis_interaksi,catatan,dibuat_oleh) VALUES (?,?,?,?)");$admin=$_SESSION['nama']??'Admin';mysqli_stmt_bind_param($stmt,'isss',$id_user,$jenis,$catatan,$admin);$notice=mysqli_stmt_execute($stmt)?'Interaksi pelanggan berhasil dicatat.':'Interaksi gagal disimpan.';mysqli_stmt_close($stmt);}
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['edit_interaksi'])){
    $id_interaksi=(int)($_POST['id_interaksi']??0);
    $id_user=(int)($_POST['id_user']??0);
    $jenis=trim($_POST['jenis_interaksi']??'');
    $catatan=trim($_POST['catatan']??'');
    if(!$id_interaksi||!$id_user||!in_array($jenis,$allowed,true)){
        $error='Data interaksi belum lengkap.';
    }else{
        $stmt=mysqli_prepare($koneksi,"UPDATE crm_interaksi SET jenis_interaksi=?, catatan=? WHERE id_interaksi=? AND id_user=?");
        mysqli_stmt_bind_param($stmt,'ssii',$jenis,$catatan,$id_interaksi,$id_user);
        $notice=mysqli_stmt_execute($stmt)?'Interaksi pelanggan berhasil diperbarui.':'Interaksi gagal diperbarui.';
        mysqli_stmt_close($stmt);
    }
}

$search=trim($_GET['q']??'');$where="u.role='user'";
if($search!==''){$safe=mysqli_real_escape_string($koneksi,$search);$where.=" AND (u.nama LIKE '%$safe%' OR u.email LIKE '%$safe%' OR u.no_hp LIKE '%$safe%')";}
$sql="SELECT u.id_user,u.nama,u.email,u.no_hp,
 (SELECT COUNT(*) FROM orders o WHERE o.id_user=u.id_user) jumlah_pesanan,
 (SELECT COALESCE(SUM(CASE WHEN o2.status <> 'Dibatalkan' THEN o2.total_pembayaran ELSE 0 END),0) FROM orders o2 WHERE o2.id_user=u.id_user) total_belanja,
 (SELECT MAX(o3.created_at) FROM orders o3 WHERE o3.id_user=u.id_user) pesanan_terakhir,
 (SELECT COUNT(*) FROM orders o4 WHERE o4.id_user=u.id_user AND o4.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY) AND o4.status <> 'Dibatalkan') pesanan_30_hari,
 (SELECT COUNT(*) FROM crm_interaksi ci WHERE ci.id_user=u.id_user) jumlah_interaksi,
 (SELECT MAX(v.waktu_kunjungan) FROM visitor_logs v WHERE v.id_user=u.id_user) terakhir_berkunjung
 FROM users u WHERE $where ORDER BY total_belanja DESC,jumlah_pesanan DESC,u.nama ASC";
$result=mysqli_query($koneksi,$sql);

$stats=[];$r=mysqli_query($koneksi,"SELECT COUNT(*) total FROM users WHERE role='user'");$stats['pelanggan']=$r?(int)mysqli_fetch_assoc($r)['total']:0;
$r=mysqli_query($koneksi,"SELECT COUNT(DISTINCT id_user) total FROM visitor_logs WHERE id_user IS NOT NULL AND waktu_kunjungan>=DATE_SUB(NOW(),INTERVAL 30 DAY)");$stats['aktif']=$r?(int)mysqli_fetch_assoc($r)['total']:0;
$r=mysqli_query($koneksi,"SELECT COALESCE(AVG(x.total),0) avg_order FROM (SELECT id_user,SUM(CASE WHEN status<>'Dibatalkan' THEN total_pembayaran ELSE 0 END) total FROM orders GROUP BY id_user) x");$stats['rata_belanja']=$r?(float)mysqli_fetch_assoc($r)['avg_order']:0;

// Donut: aktif berarti akun pelanggan tercatat berkunjung dalam 6 bulan terakhir.
$cutoff=date('Y-m-d H:i:s',strtotime('-6 months'));
$stmt=mysqli_prepare($koneksi,"SELECT COUNT(*) total FROM users u LEFT JOIN (SELECT id_user,MAX(waktu_kunjungan) terakhir FROM visitor_logs WHERE id_user IS NOT NULL GROUP BY id_user) v ON v.id_user=u.id_user WHERE u.role='user' AND v.terakhir >= ?");
mysqli_stmt_bind_param($stmt,'s',$cutoff);mysqli_stmt_execute($stmt);$aktif6=(int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total']??0);mysqli_stmt_close($stmt);
$aktif6=min($aktif6,$stats['pelanggan']);$tidakAktif6=max(0,$stats['pelanggan']-$aktif6);$persenAktif=$stats['pelanggan']>0?round(($aktif6/$stats['pelanggan'])*100,1):0;$persenTidak=round(100-$persenAktif,1);

$stmt=mysqli_prepare($koneksi,"SELECT u.id_user,u.nama,u.email,u.no_hp,v.terakhir_berkunjung FROM users u LEFT JOIN (SELECT id_user,MAX(waktu_kunjungan) terakhir_berkunjung FROM visitor_logs WHERE id_user IS NOT NULL GROUP BY id_user) v ON v.id_user=u.id_user WHERE u.role='user' AND (v.terakhir_berkunjung IS NULL OR v.terakhir_berkunjung < ?) ORDER BY v.terakhir_berkunjung IS NULL DESC,v.terakhir_berkunjung ASC,u.nama ASC");
mysqli_stmt_bind_param($stmt,'s',$cutoff);mysqli_stmt_execute($stmt);$inactiveRows=[];$inactiveResult=mysqli_stmt_get_result($stmt);while($inactiveResult&&($row=mysqli_fetch_assoc($inactiveResult)))$inactiveRows[]=$row;mysqli_stmt_close($stmt);

// Analisis 14 hari berdasarkan pelanggan yang melakukan pesanan.
$analysisLabels=[];$analysisValues=[];$start14=date('Y-m-d',strtotime('-13 days'));$end14=date('Y-m-d');
$stmt=mysqli_prepare($koneksi,"SELECT DATE(created_at) tanggal,COUNT(DISTINCT id_user) jumlah FROM orders WHERE status<>'Dibatalkan' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY tanggal ASC");
mysqli_stmt_bind_param($stmt,'ss',$start14,$end14);mysqli_stmt_execute($stmt);$map=[];$analysisResult=mysqli_stmt_get_result($stmt);while($analysisResult&&($row=mysqli_fetch_assoc($analysisResult)))$map[$row['tanggal']]=(int)$row['jumlah'];mysqli_stmt_close($stmt);
for($i=0;$i<14;$i++){ $d=date('Y-m-d',strtotime("$start14 +$i day"));$analysisLabels[]=date('d M',strtotime($d));$analysisValues[]=$map[$d]??0; }

$selected=(int)($_GET['pelanggan']??0);$selectedUser=null;$interactions=[];$recipients=[];$customerOrders=[];$selectedLastVisit=null;
if($selected){
    $q=mysqli_query($koneksi,"SELECT u.id_user,u.nama,u.email,u.no_hp,(SELECT MAX(v.waktu_kunjungan) FROM visitor_logs v WHERE v.id_user=u.id_user) terakhir_berkunjung FROM users u WHERE u.id_user=$selected AND u.role='user' LIMIT 1");$selectedUser=$q?mysqli_fetch_assoc($q):null;
    if($selectedUser){$selectedLastVisit=$selectedUser['terakhir_berkunjung'];
        $q=mysqli_query($koneksi,"SELECT id_order,nama_penerima,total_pembayaran,metode_pembayaran,status,created_at FROM orders WHERE id_user=$selected ORDER BY created_at DESC,id_order DESC LIMIT 20");while($q&&$row=mysqli_fetch_assoc($q))$customerOrders[]=$row;
        $q=mysqli_query($koneksi,"SELECT * FROM crm_interaksi WHERE id_user=$selected ORDER BY tanggal_interaksi DESC LIMIT 20");while($q&&$row=mysqli_fetch_assoc($q))$interactions[]=$row;
        $q=mysqli_query($koneksi,"SELECT TRIM(o.nama_penerima) nama_penerima,COUNT(*) jumlah_pesanan,COALESCE(SUM(CASE WHEN o.status <> 'Dibatalkan' THEN o.total_pembayaran ELSE 0 END),0) total_uang,MAX(o.created_at) terakhir_dipakai FROM orders o WHERE o.id_user=$selected AND TRIM(COALESCE(o.nama_penerima,''))<>'' GROUP BY LOWER(TRIM(o.nama_penerima)) ORDER BY total_uang DESC,nama_penerima ASC");while($q&&$row=mysqli_fetch_assoc($q))$recipients[]=$row;
    }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CRM Pelanggan - Budiarti Risol</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;inset:0 auto 0 0;padding:25px 15px;overflow:auto}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.profile{display:flex;gap:10px;align-items:center;font-size:13px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.page{padding:30px}.title h1{font:700 27px Georgia,serif;margin:0 0 7px}.title p{color:#8a7669;font-size:13px;margin:0 0 20px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}.stat,.card,.analysis-card{background:#fff;border:1px solid #eee4db;border-radius:14px;box-shadow:0 3px 12px rgba(74,34,14,.05)}.stat{padding:18px}.stat small{color:#8d796b;font-size:11px}.stat strong{display:block;font-size:24px;color:#542D1C;margin-top:8px}.summary-grid{display:grid;grid-template-columns:1.15fr 1fr;gap:18px;margin-bottom:18px}.summary-card{padding:20px}.summary-head{display:flex;justify-content:space-between;align-items:start}.summary-head h2,.card h2{font:700 18px Georgia,serif;color:#542D1C;margin:0}.summary-head p{font-size:11px;color:#8d796b;margin:5px 0 0}.donut-area{display:flex;align-items:center;justify-content:flex-start;gap:34px;padding-left:22px;min-height:180px}.donut-button{width:132px;height:132px;border:0;border-radius:50%;background:conic-gradient(#C99B78 var(--active),#eadfd5 0);position:relative;cursor:pointer;padding:0}.donut-button:after{content:"";position:absolute;inset:19px;background:#fff;border-radius:50%}.donut-center{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;z-index:2;color:#542D1C}.donut-center strong{font-size:25px}.donut-center span{font-size:9px;color:#8d796b;margin-top:2px}.donut-legend{display:grid;gap:16px}.legend-row{display:flex;align-items:center;gap:10px;font-size:13px;color:#5b4a40}.dot{width:10px;height:10px;border-radius:50%}.dot.active{background:#C99B78}.dot.inactive{background:#eadfd5}.legend-row strong{margin-left:7px;color:#542D1C;font-size:13px}.analysis-card{padding:20px}.analysis-card .head{display:flex;justify-content:space-between;align-items:start}.analysis-card .head p{font-size:11px;color:#8d796b;margin:5px 0 0}.chart-wrap{height:180px;margin-top:12px}.toolbar{display:flex;gap:10px;margin-bottom:15px}.toolbar input{flex:1;padding:11px 13px;border:1px solid #e4d8ce;border-radius:8px}.btn{background:#542D1C;color:#fff;border:0;border-radius:8px;padding:10px 15px;font-size:12px;font-weight:bold;text-decoration:none;cursor:pointer}.card{padding:20px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:980px}th{background:#fcf8f3;color:#8d796b;text-align:left;font-size:10px;padding:12px;border-bottom:1px solid #eee4db}td{padding:12px;border-bottom:1px solid #f1ebe5;font-size:12px;color:#5b4a40}.badge{display:inline-block;background:#f7e4c5;color:#855016;border-radius:20px;padding:5px 8px;font-size:10px}.action{color:#8b4a16;text-decoration:none;font-weight:bold;font-size:11px}.notice{padding:12px 15px;border-radius:8px;font-size:12px;margin-bottom:15px}.ok{background:#dcefdc;color:#286b32}.err{background:#fde3e5;color:#a52c38}.crm-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:18px;margin-top:18px}.field{margin:12px 0}.field label{display:block;font-size:11px;font-weight:bold;margin-bottom:6px}.field select,.field textarea{width:100%;padding:10px;border:1px solid #e4d8ce;border-radius:8px}.field textarea{min-height:90px;resize:vertical}.history{max-height:330px;overflow:auto}.history-item{padding:12px 0;border-bottom:1px solid #eee4db}.history-item:last-child{border:0}.history-item small{color:#8d796b}.history-item p{font-size:12px;margin:6px 0 0;color:#5b4a40}.recipient-list{border:1px solid #eee4db;border-radius:10px;overflow:hidden;background:#fcfaf7}.recipient-item{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:12px 14px;border-bottom:1px solid #eee4db}.recipient-item:last-child{border-bottom:0}.recipient-item strong{font-size:12px;color:#542D1C}.recipient-item span{font-size:10px;color:#8d796b}.modal{display:none;position:fixed;inset:0;background:rgba(59,29,13,.45);z-index:9999;align-items:center;justify-content:center;padding:20px}.modal.open{display:flex}.modal-box{background:#fff;border-radius:16px;width:min(720px,100%);max-height:80vh;overflow:auto;padding:22px}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}.modal-head h2{font:700 19px Georgia,serif;margin:0;color:#542D1C}.close{border:0;background:#f5eee7;border-radius:8px;padding:7px 10px;cursor:pointer;color:#6d3519}.inactive-item{display:flex;justify-content:space-between;gap:15px;align-items:center;padding:12px 0;border-bottom:1px solid #eee4db}.inactive-item:last-child{border-bottom:0}.inactive-item strong{font-size:12px;color:#542D1C}.inactive-item span{font-size:10px;color:#8d796b}.small-action{color:#8b4a16;font-weight:bold;text-decoration:none;font-size:11px;white-space:nowrap}.detail-top{margin-bottom:2px}.back-link{font-size:12px;color:#8b4a16;text-decoration:none;font-weight:bold}.detail-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:stretch}.detail-grid>.card,.detail-side>.card{min-width:0}.detail-profile{height:620px;overflow:auto}.detail-profile{display:flex;flex-direction:column}.detail-side{height:620px;display:flex;flex-direction:column;gap:18px;min-width:0}.detail-orders{height:340px;overflow:hidden}.detail-profile-head{display:flex;align-items:center;gap:13px;padding-bottom:16px;border-bottom:1px solid #eee4db}.detail-avatar{width:46px;height:46px;border-radius:50%;background:#f7e4c5;display:flex;align-items:center;justify-content:center;font-size:20px;flex:0 0 auto}.detail-profile-head h2{margin:0;font:700 19px Georgia,serif;color:#542D1C}.detail-profile-head p{margin:5px 0 0;font-size:11px;color:#8d796b}.last-visit{display:flex;justify-content:space-between;align-items:center;padding:12px 0 15px;border-bottom:1px solid #eee4db}.last-visit span{font-size:10px;color:#8d796b}.last-visit strong{font-size:11px;color:#542D1C}.detail-section-title{font-size:13px;font-weight:bold;color:#542D1C;margin:17px 0 7px}.detail-muted{font-size:10px;color:#8d796b;line-height:1.5;margin:0 0 10px}.recipient-list{max-height:205px;overflow:auto}.detail-card-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}.detail-card-head h2{margin:0;font:700 18px Georgia,serif;color:#542D1C}.detail-card-head span{font-size:10px;color:#8d796b;background:#f5eee7;padding:6px 8px;border-radius:7px}.detail-history{max-height:530px;overflow:auto}.detail-history .history-item{padding:13px 0}.detail-history .history-item small{display:block;margin-top:5px}.order-recipient{font-size:11px;color:#5b4a40;margin-top:4px}.empty-text{font-size:12px;color:#8d796b;padding:12px 0;margin:0}.detail-interactions{height:262px;margin-top:0;overflow:hidden}.interaction-layout{display:block}.interaction-log{max-height:202px;overflow:auto;padding-right:4px}.interaction-actions{display:flex;gap:7px;margin-top:10px;flex-wrap:wrap}
.interaction-edit{display:inline-flex;align-items:center;padding:6px 10px;border-radius:7px;background:#EBD3C0;color:#542D1C;text-decoration:none;font-size:10px;font-weight:700;border:0;cursor:pointer}
.interaction-edit:hover{background:#C99B78}
.interaction-edit-form{display:none;margin-top:10px;padding:12px;border:1px solid #E5D3C3;border-radius:10px;background:#FCF8F3}
.interaction-edit-form.open{display:block}
.interaction-edit-form select,.interaction-edit-form textarea{width:100%;padding:9px;border:1px solid #E5D3C3;border-radius:7px;background:#fff;color:#542D1C;font:12px Arial,sans-serif;margin-bottom:8px}
.interaction-edit-form textarea{min-height:70px;resize:vertical}
.interaction-edit-form .save{background:#542D1C;color:#fff;border:0;border-radius:7px;padding:7px 11px;font-size:10px;font-weight:700;cursor:pointer}
.interaction-edit-form .cancel{background:#EBD3C0;color:#542D1C;border:0;border-radius:7px;padding:7px 11px;font-size:10px;font-weight:700;cursor:pointer}.interaction-form{padding-top:16px}.interaction-form-title{font-size:13px;font-weight:bold;color:#542D1C;margin:0 0 10px}.profile-interaction-form{border-top:1px solid #eee4db;margin-top:16px}.interaction-form .field{margin:0 0 12px}.interaction-form textarea{min-height:90px}
@media(max-width:950px){.stats{grid-template-columns:repeat(2,1fr)}.summary-grid,.crm-grid,.detail-grid{grid-template-columns:1fr}.detail-profile,.detail-orders,.detail-side,.detail-interactions{height:auto}.detail-history{max-height:420px}.interaction-log{max-height:420px}.profile-interaction-form{margin-top:16px}}@media(max-width:750px){.main-content{margin-left:0;width:100%}.sidebar{position:relative;width:100%;bottom:auto}.page{padding:20px}.stats{grid-template-columns:1fr}.donut-area{gap:18px;flex-direction:column}}
</style><link rel="stylesheet" href="../assets/css/admin-sidebar.css"><link rel="stylesheet" href="../assets/css/admin-topbar.css">

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
</head><body><div class="admin-wrapper">
<?php include __DIR__.'/sidebar.php'; ?><main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header><section class="page">
<?php if($selectedUser): ?>
<div class="detail-top">
    <div>
        <a class="back-link" href="crm.php">← Kembali ke Data Pelanggan</a>
        <div class="title" style="margin-top:18px"><h1>Detail Pelanggan</h1><p>Profil CRM, riwayat pesanan, dan riwayat interaksi pelanggan.</p></div>
    </div>
</div>
<?php if($notice):?><div class="notice ok">✓ <?=h($notice)?></div><?php endif;?><?php if($error):?><div class="notice err">⚠ <?=h($error)?></div><?php endif;?>
<div class="detail-grid">
    <div class="card detail-profile">
        <div class="detail-profile-head">
            <div class="detail-avatar">👤</div>
            <div><h2>Profil CRM: <?=h($selectedUser['nama'])?></h2><p><?=h($selectedUser['email'])?> · <?=h($selectedUser['no_hp'])?></p></div>
        </div>
        <div class="last-visit"><span>Terakhir Berkunjung</span><strong><?= $selectedLastVisit?date('d/m/Y H:i',strtotime($selectedLastVisit)):'Belum tercatat' ?></strong></div>
        <div class="detail-section-title">Nama Penerima yang Pernah Dipakai</div>
        <p class="detail-muted">Nama penerima yang sama otomatis digabung dan total transaksinya ditampilkan.</p>
        <div class="recipient-list">
        <?php if($recipients): foreach($recipients as $recipient): ?>
            <div class="recipient-item"><div><strong><?=h($recipient['nama_penerima'])?></strong><br><span><?=number_format((int)$recipient['jumlah_pesanan'])?> pesanan · terakhir <?=date('d/m/Y',strtotime($recipient['terakhir_dipakai']))?></span></div><strong>Rp <?=number_format((float)$recipient['total_uang'],0,',','.')?></strong></div>
        <?php endforeach; else: ?><p class="empty-text">Belum ada riwayat nama penerima.</p><?php endif; ?>
        </div>

        <div class="interaction-form profile-interaction-form">
            <div class="interaction-form-title">Catat Interaksi</div>
            <form method="post">
                <input type="hidden" name="id_user" value="<?=$selectedUser['id_user']?>">
                <div class="field"><label>Jenis Interaksi</label><select name="jenis_interaksi"><option>Pesanan</option><option>Keluhan</option><option>Pertanyaan</option><option>Follow Up</option><option>Lainnya</option></select></div>
                <div class="field"><label>Catatan</label><textarea name="catatan" placeholder="Catat komunikasi atau kebutuhan pelanggan..."></textarea></div>
                <button class="btn" name="simpan_interaksi">Simpan Interaksi</button>
            </form>
        </div>
    </div>

    <div class="detail-side">
        <div class="card detail-orders">
            <div class="detail-card-head"><h2>Riwayat Pesanan</h2><span><?=number_format(count($customerOrders))?> pesanan</span></div>
            <div class="history detail-history">
            <?php if($customerOrders): foreach($customerOrders as $o): ?>
                <div class="history-item"><strong>#ORD<?=str_pad((int)$o['id_order'],3,'0',STR_PAD_LEFT)?></strong><div class="order-recipient"><?=h($o['nama_penerima'])?> · Rp <?=number_format((float)$o['total_pembayaran'],0,',','.')?></div><small><?=date('d/m/Y H:i',strtotime($o['created_at']))?> · <?=h($o['metode_pembayaran'])?> · <?=h($o['status'])?></small></div>
            <?php endforeach; else: ?><p class="empty-text">Belum ada pesanan.</p><?php endif; ?>
            </div>
        </div>

        <div class="card detail-interactions">
            <div class="detail-card-head"><h2>Riwayat Interaksi</h2><span><?=number_format(count($interactions))?> interaksi</span></div>
            <div class="interaction-log">
            <?php if($interactions): foreach($interactions as $i): ?>
                <div class="history-item">
                    <strong><?=h($i['jenis_interaksi'])?></strong>
                    <small><?=date('d/m/Y H:i',strtotime($i['tanggal_interaksi']))?></small>
                    <p><?=nl2br(h($i['catatan']?:'Tidak ada catatan.'))?></p>
                    <div class="interaction-actions">
                        <button type="button" class="interaction-edit" onclick="toggleInteractionEdit(<?= (int)$i['id_interaksi'] ?>)">Edit Interaksi</button>
                    </div>
                    <form method="post" class="interaction-edit-form" id="edit-interaksi-<?= (int)$i['id_interaksi'] ?>">
                        <input type="hidden" name="id_interaksi" value="<?= (int)$i['id_interaksi'] ?>">
                        <input type="hidden" name="id_user" value="<?= (int)$selectedUser['id_user'] ?>">
                        <select name="jenis_interaksi">
                            <?php foreach($allowed as $jenisOption): ?>
                                <option value="<?=h($jenisOption)?>" <?= $i['jenis_interaksi']===$jenisOption?'selected':'' ?>><?=h($jenisOption)?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="catatan"><?=h($i['catatan']??'')?></textarea>
                        <button type="submit" class="save" name="edit_interaksi">Simpan Perubahan</button>
                        <button type="button" class="cancel" onclick="toggleInteractionEdit(<?= (int)$i['id_interaksi'] ?>)">Batal</button>
                    </form>
                </div>
            <?php endforeach; else: ?><p class="empty-text">Belum ada interaksi yang dicatat.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="title"><h1>Pelanggan</h1><p>Kelola data pelanggan, analisis aktivitas, dan hubungan pelanggan dalam satu halaman.</p></div>
<?php if($notice):?><div class="notice ok">✓ <?=h($notice)?></div><?php endif;?><?php if($error):?><div class="notice err">⚠ <?=h($error)?></div><?php endif;?>
<div class="stats"><div class="stat"><small>Total Pelanggan</small><strong><?=number_format($stats['pelanggan'])?></strong></div><div class="stat"><small>Aktif 30 Hari</small><strong><?=number_format($stats['aktif'])?></strong></div><div class="stat"><small>Tidak Aktif 6+ Bulan</small><strong><?=number_format($tidakAktif6)?></strong></div><div class="stat"><small>Rata-rata Belanja/Pelanggan</small><strong>Rp <?=number_format($stats['rata_belanja'],0,',','.')?></strong></div></div>
<div class="summary-grid">
<div class="card summary-card"><div class="summary-head"><div><h2>Ringkasan Pelanggan</h2><p>Persentase pelanggan yang masih aktif berkunjung ke website.</p></div></div><div class="donut-area"><button class="donut-button" style="--active:<?=$persenAktif?>%" type="button" id="donutAktif" aria-label="Lihat pelanggan tidak aktif 6 bulan"><span class="donut-center"><strong><?=$persenAktif?>%</strong><span>Aktif</span></span></button><div class="donut-legend"><div class="legend-row"><span class="dot active"></span>Aktif 6 bulan terakhir <strong><?=$aktif6?></strong></div><div class="legend-row"><span class="dot inactive"></span>Tidak aktif 6+ bulan <strong><?=$tidakAktif6?></strong></div><small style="font-size:10px;color:#927b69">Klik grafik untuk melihat pelanggan yang tidak aktif.</small></div></div></div>
<div class="analysis-card"><div class="head"><div><h2>Analisis Pelanggan</h2><p>Jumlah pelanggan yang melakukan pesanan per hari.</p></div><span style="font-size:10px;color:#8d796b;background:#f5eee7;padding:7px 9px;border-radius:7px">14 Hari</span></div><div class="chart-wrap"><canvas id="grafikPelanggan"></canvas></div></div>
</div>
<form class="toolbar" method="get"><input name="q" value="<?=h($search)?>" placeholder="Cari nama, email, atau nomor HP pelanggan..."><button class="btn">Cari</button><a class="btn" href="crm.php">Reset</a></form>
<div class="card"><h2 style="font:700 18px Georgia,serif;margin:0 0 15px;color:#542D1C">Data Pelanggan</h2><div class="table-wrap"><table><thead><tr><th>Pelanggan</th><th>Pesanan</th><th>Total Belanja</th><th>Pesanan Terakhir</th><th>Terakhir Berkunjung</th><th>Segmen</th><th>Interaksi</th></tr></thead><tbody>
<?php if($result&&mysqli_num_rows($result)):while($r=mysqli_fetch_assoc($result)):$last=$r['pesanan_terakhir'];$days=$last?floor((time()-strtotime($last))/86400):9999;$segment=$r['jumlah_pesanan']==0?'Lead':($days<=30?'Aktif':($days<=60?'Perlu Follow Up':'Tidak Aktif'));?><tr><td><strong><?=h($r['nama'])?></strong><br><span style="font-size:10px;color:#8d796b"><?=h($r['email'])?> · <?=h($r['no_hp'])?></span></td><td><?=number_format($r['jumlah_pesanan'])?></td><td>Rp <?=number_format($r['total_belanja'],0,',','.')?></td><td><?=$last?date('d/m/Y',strtotime($last)):'Belum ada'?></td><td><?=$r['terakhir_berkunjung']?date('d/m/Y H:i',strtotime($r['terakhir_berkunjung'])):'Belum tercatat'?></td><td><span class="badge"><?=h($segment)?></span></td><td><a class="action" href="crm.php?pelanggan=<?=(int)$r['id_user']?>">Lihat Detail</a><?php $wa=whatsappUrl($r['no_hp']);if($segment==='Perlu Follow Up'&&$wa):?> <a class="action" target="_blank" rel="noopener" href="<?=h($wa)?>" style="margin-left:8px">WhatsApp</a><?php endif;?></td></tr><?php endwhile;else:?><tr><td colspan="7" style="text-align:center;padding:30px">Belum ada pelanggan.</td></tr><?php endif;?></tbody></table></div></div>
<?php endif; ?>
</section></main></div>
<div class="modal" id="inactiveModal"><div class="modal-box"><div class="modal-head"><h2>Pelanggan Tidak Aktif 6+ Bulan</h2><button class="close" id="closeModal">Tutup</button></div><?php if($inactiveRows):foreach($inactiveRows as $r):?><div class="inactive-item"><div><strong><?=h($r['nama'])?></strong><br><span><?=h($r['email'])?> · terakhir berkunjung: <?=$r['terakhir_berkunjung']?date('d/m/Y H:i',strtotime($r['terakhir_berkunjung'])):'Belum tercatat'?></span></div><a class="small-action" href="crm.php?pelanggan=<?=(int)$r['id_user']?>">Lihat Detail →</a></div><?php endforeach;else:?><p style="font-size:12px;color:#8d796b">Belum ada pelanggan yang tidak aktif lebih dari 6 bulan.</p><?php endif;?></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>
<?php if(!$selectedUser): ?>
new Chart(document.getElementById('grafikPelanggan'),{type:'line',data:{labels:<?=json_encode($analysisLabels)?>,datasets:[{label:'Pelanggan',data:<?=json_encode($analysisValues)?>,borderColor:'#C99B78',backgroundColor:'rgba(173,118,54,.10)',fill:true,tension:.35,borderWidth:3,pointRadius:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,minRotation:0,padding:6,font:{size:10}}}}}});
const modal=document.getElementById('inactiveModal');document.getElementById('donutAktif').onclick=()=>modal.classList.add('open');document.getElementById('closeModal').onclick=()=>modal.classList.remove('open');modal.addEventListener('click',e=>{if(e.target===modal)modal.classList.remove('open')});
<?php endif; ?>
</script><script>
function toggleInteractionEdit(id){
    const form=document.getElementById('edit-interaksi-'+id);
    if(form) form.classList.toggle('open');
}
</script>
</body></html>
