<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/pengunjung.php";

init_tabel_pengunjung($koneksi);

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai)) $tanggal_mulai = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_selesai)) $tanggal_selesai = date('Y-m-d');

function fetchAllRows($result) {
    $rows=[];
    while($result && ($row=mysqli_fetch_assoc($result))) $rows[]=$row;
    return $rows;
}

$stmt=mysqli_prepare($koneksi,"SELECT COUNT(*) total_kunjungan, COUNT(DISTINCT visitor_key) pengunjung FROM visitor_logs WHERE tanggal BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$ringkasan=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))?:[]; mysqli_stmt_close($stmt);
$total_kunjungan=(int)($ringkasan['total_kunjungan']??0); $pengunjung=(int)($ringkasan['pengunjung']??0);

// Pembeli: pengunjung teridentifikasi (id_user) yang melakukan pesanan terverifikasi/lunas pada periode yang dipilih.
$stmt=mysqli_prepare($koneksi,"SELECT COUNT(DISTINCT v.id_user) pembeli
 FROM visitor_logs v
 INNER JOIN orders o ON o.id_user=v.id_user
 WHERE v.id_user IS NOT NULL
   AND v.tanggal BETWEEN ? AND ?
   AND DATE(o.created_at) BETWEEN ? AND ?
   AND o.status <> 'Dibatalkan'
   AND EXISTS (SELECT 1 FROM pembayaran p WHERE p.id_order=o.id_order AND p.status_pembayaran IN ('Terverifikasi','Lunas'))");
mysqli_stmt_bind_param($stmt,'ssss',$tanggal_mulai,$tanggal_selesai,$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$ringkasan_pembeli=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))?:[]; mysqli_stmt_close($stmt);
$pembeli=(int)($ringkasan_pembeli['pembeli']??0);
$konversi=$pengunjung>0?($pembeli/$pengunjung)*100:0;

$stmt=mysqli_prepare($koneksi,"SELECT tanggal,COUNT(*) kunjungan,COUNT(DISTINCT visitor_key) pengunjung FROM visitor_logs WHERE tanggal BETWEEN ? AND ? GROUP BY tanggal ORDER BY tanggal ASC");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_harian=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);

$stmt=mysqli_prepare($koneksi,"SELECT DATE(o.created_at) tanggal,COUNT(DISTINCT o.id_user) pembeli
 FROM orders o
 WHERE DATE(o.created_at) BETWEEN ? AND ?
   AND o.id_user IS NOT NULL
   AND o.status <> 'Dibatalkan'
   AND EXISTS (SELECT 1 FROM pembayaran p WHERE p.id_order=o.id_order AND p.status_pembayaran IN ('Terverifikasi','Lunas'))
   AND EXISTS (SELECT 1 FROM visitor_logs v WHERE v.id_user=o.id_user AND v.tanggal BETWEEN ? AND ?)
 GROUP BY DATE(o.created_at) ORDER BY tanggal ASC");
mysqli_stmt_bind_param($stmt,'ssss',$tanggal_mulai,$tanggal_selesai,$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_pembeli_harian=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
$pembeli_map=[]; foreach($data_pembeli_harian as $r){$pembeli_map[$r['tanggal']]=(int)$r['pembeli'];}

// Penjualan per hari: hanya pesanan yang pembayarannya sudah terverifikasi/lunas.
$stmt=mysqli_prepare($koneksi,"SELECT DATE(o.created_at) tanggal, COALESCE(SUM(o.total_pembayaran),0) pendapatan, COUNT(*) pesanan
 FROM orders o
 WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan'
 AND EXISTS (SELECT 1 FROM pembayaran p WHERE p.id_order=o.id_order AND p.status_pembayaran IN ('Terverifikasi','Lunas'))
 GROUP BY DATE(o.created_at) ORDER BY tanggal ASC");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_penjualan=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);

// Produk terlaris untuk analitik.
$stmt=mysqli_prepare($koneksi,"SELECT p.id_product,p.nama_product,COALESCE(SUM(od.jumlah),0) jumlah_terjual,COALESCE(SUM(od.subtotal),0) total_penjualan
 FROM order_details od JOIN orders o ON od.id_order=o.id_order JOIN products p ON od.id_product=p.id_product
 WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan'
 AND EXISTS (SELECT 1 FROM pembayaran pb WHERE pb.id_order=o.id_order AND pb.status_pembayaran IN ('Terverifikasi','Lunas'))
 GROUP BY p.id_product,p.nama_product ORDER BY jumlah_terjual DESC,total_penjualan DESC ");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_produk=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);


// Kategori terlaris berdasarkan jumlah produk terjual.
$stmt=mysqli_prepare($koneksi,"SELECT COALESCE(c.nama_category,'Tanpa Kategori') nama_category,
 SUM(od.jumlah) jumlah_terjual
 FROM order_details od
 JOIN orders o ON o.id_order=od.id_order
 JOIN products p ON p.id_product=od.id_product
 LEFT JOIN categories c ON c.id_category=p.id_category
 WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan'
 AND EXISTS (SELECT 1 FROM pembayaran pb WHERE pb.id_order=o.id_order AND pb.status_pembayaran IN ('Terverifikasi','Lunas'))
 GROUP BY c.id_category,c.nama_category
 ORDER BY jumlah_terjual DESC
 LIMIT 6");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_kategori=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
$total_kategori_terjual=array_sum(array_map(fn($r)=>(int)$r['jumlah_terjual'],$data_kategori));

// Pelanggan baru vs lama dihitung dari pembelian terverifikasi pertama setiap pelanggan.
$stmt=mysqli_prepare($koneksi,"SELECT
 SUM(CASE WHEN first_buy.first_date BETWEEN ? AND ? THEN 1 ELSE 0 END) pelanggan_baru,
 SUM(CASE WHEN first_buy.first_date < ? THEN 1 ELSE 0 END) pelanggan_lama
 FROM (
   SELECT o.id_user, MIN(DATE(o.created_at)) first_date
   FROM orders o
   WHERE o.id_user IS NOT NULL AND o.status <> 'Dibatalkan'
     AND EXISTS (SELECT 1 FROM pembayaran pb WHERE pb.id_order=o.id_order AND pb.status_pembayaran IN ('Terverifikasi','Lunas'))
   GROUP BY o.id_user
 ) first_buy
 WHERE EXISTS (
   SELECT 1 FROM orders active_o
   WHERE active_o.id_user=first_buy.id_user
     AND DATE(active_o.created_at) BETWEEN ? AND ?
     AND active_o.status <> 'Dibatalkan'
     AND EXISTS (SELECT 1 FROM pembayaran active_pb WHERE active_pb.id_order=active_o.id_order AND active_pb.status_pembayaran IN ('Terverifikasi','Lunas'))
 )");
mysqli_stmt_bind_param($stmt,'sssss',$tanggal_mulai,$tanggal_selesai,$tanggal_mulai,$tanggal_mulai,$tanggal_selesai); mysqli_stmt_execute($stmt);
$data_pelanggan=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))?:[]; mysqli_stmt_close($stmt);
$pelanggan_baru=(int)($data_pelanggan['pelanggan_baru']??0);
$pelanggan_lama=(int)($data_pelanggan['pelanggan_lama']??0);
$total_pelanggan_analitik=$pelanggan_baru+$pelanggan_lama;

// Bandingkan pertumbuhan produk dengan periode sebelumnya yang durasinya sama.
$jumlah_hari=max(1,(int)((strtotime($tanggal_selesai)-strtotime($tanggal_mulai))/86400)+1);
$tanggal_sebelumnya_selesai=date('Y-m-d',strtotime($tanggal_mulai.' -1 day'));
$tanggal_sebelumnya_mulai=date('Y-m-d',strtotime($tanggal_sebelumnya_selesai.' -'.($jumlah_hari-1).' day'));
$stmt=mysqli_prepare($koneksi,"SELECT p.id_product,p.nama_product,COALESCE(SUM(od.jumlah),0) jumlah
 FROM order_details od JOIN orders o ON o.id_order=od.id_order JOIN products p ON p.id_product=od.id_product
 WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status <> 'Dibatalkan'
 AND EXISTS (SELECT 1 FROM pembayaran pb WHERE pb.id_order=o.id_order AND pb.status_pembayaran IN ('Terverifikasi','Lunas'))
 GROUP BY p.id_product,p.nama_product");
mysqli_stmt_bind_param($stmt,'ss',$tanggal_sebelumnya_mulai,$tanggal_sebelumnya_selesai); mysqli_stmt_execute($stmt);
$previous_rows=fetchAllRows(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
$previous_product=[]; foreach($previous_rows as $r){$previous_product[(int)$r['id_product']]=(int)$r['jumlah'];}
$growth_rows=[];
foreach($data_produk as $r){
    $id=(int)$r['id_product']; $current=(int)$r['jumlah_terjual']; $previous=$previous_product[$id]??0;
    if($previous>0){$growth=(($current-$previous)/$previous)*100;}
    elseif($current>0){$growth=null;}
    else{$growth=0;}
    $growth_rows[]=['nama_product'=>$r['nama_product'],'current'=>$current,'previous'=>$previous,'growth'=>$growth];
}
usort($growth_rows,function($a,$b){
    if($a['growth']===null && $b['growth']!==null) return -1;
    if($b['growth']===null && $a['growth']!==null) return 1;
    return ($b['growth']??-INF)<=>($a['growth']??-INF);
});
$growth_rows=array_slice($growth_rows,0,3);

$label_harian=[];$nilai_pengunjung=[];$nilai_pembeli=[];
foreach($data_harian as $r){$label_harian[]=date('d M',strtotime($r['tanggal']));$nilai_pengunjung[]=(int)$r['pengunjung'];$nilai_pembeli[]=$pembeli_map[$r['tanggal']]??0;}
$label_penjualan=[];$nilai_pendapatan=[];$nilai_pesanan=[];
foreach($data_penjualan as $r){$label_penjualan[]=date('d M',strtotime($r['tanggal']));$nilai_pendapatan[]=(float)$r['pendapatan'];$nilai_pesanan[]=(int)$r['pesanan'];}
$label_produk=[];$nilai_produk=[];
foreach(array_reverse($data_produk) as $r){$label_produk[]=$r['nama_product'];$nilai_produk[]=(int)$r['jumlah_terjual'];}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Analitik - Budiarti Risol</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;inset:0 auto 0 0;padding:25px 15px;overflow:auto}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.content{padding:30px 35px;max-width:1500px}.title h1{font:700 28px Georgia,serif;margin:0 0 7px;color:#542D1C}.title p{font-size:13px;color:#8a7669;margin:0}.filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:20px 0 22px}.filter label{display:block;font-size:10px;color:#765a46;margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}.filter input{height:38px;padding:0 11px;border:1px solid #dfd0c2;border-radius:8px;background:#fff;color:#542D1C}.btn{height:38px;border:0;border-radius:8px;padding:0 17px;background:#6d3519;color:#fff;font-weight:bold;cursor:pointer}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:17px 19px;box-shadow:0 3px 12px rgba(74,34,14,.045);min-height:102px}.card small{font-size:10px;color:#8d796b;text-transform:uppercase;letter-spacing:.5px;font-weight:700}.card strong{display:block;font-size:23px;margin-top:9px;color:#542D1C;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.card .sub{font-size:10px;color:#927b69;margin-top:4px}.card.highlight{background:#fffaf0}.card.highlight strong{color:#6d3519}.card.success strong{color:#687743}.chart-grid{display:grid;grid-template-columns:1.35fr .9fr;gap:18px;margin-bottom:18px}.chart-card{background:#fff;border:1px solid #eee4db;border-radius:14px;box-shadow:0 3px 12px rgba(74,34,14,.045);padding:20px}.chart-head{display:flex;justify-content:space-between;gap:15px;align-items:start}.chart-head h2,.section-title{font:700 18px Georgia,serif;margin:0;color:#542D1C}.chart-head p{font-size:11px;color:#8d796b;margin:5px 0 0;line-height:1.5}.chart-wrap{height:280px;margin-top:14px}.insight{display:flex;align-items:center;gap:16px;margin-top:18px;padding:13px 14px;background:#F8EFE5;border:1px solid #f0e3d7;border-radius:10px}.insight-number{font-size:23px;font-weight:800;color:#6d3519}.insight-text{font-size:11px;color:#705d50;line-height:1.45}.product-card{background:#fff;border:1px solid #eee4db;border-radius:14px;box-shadow:0 3px 12px rgba(74,34,14,.045);padding:20px;margin-bottom:18px}.product-card .chart-wrap{height:310px}.switch{display:inline-flex;padding:3px;background:#f5eee7;border-radius:8px;gap:3px}.switch button{border:0;background:transparent;color:#7c634f;padding:7px 11px;border-radius:6px;font-size:11px;font-weight:bold;cursor:pointer}.switch button.active{background:#C99B78;color:#fff}.product-view{margin-top:14px}.product-view.hidden{display:none}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px}.table-card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:18px;overflow:auto;box-shadow:0 3px 12px rgba(74,34,14,.045)}.table-card h2{font:700 17px Georgia,serif;margin:0 0 14px}.table{width:100%;border-collapse:collapse;font-size:12px}.table th,.table td{text-align:left;padding:10px 8px;border-bottom:1px solid #eee4db}.table th{color:#8d796b;font-size:10px;text-transform:uppercase;letter-spacing:.3px}.empty{text-align:center;color:#927b69;padding:25px}.rank{font-weight:bold;color:#C99B78;width:38px}.money{font-weight:bold;color:#542D1C}.note{font-size:10px;color:#927b69;margin-top:10px}@media(max-width:1050px){.chart-grid{grid-template-columns:1fr}}@media(max-width:850px){.sidebar{position:relative;width:100%;bottom:auto}.admin-wrapper{display:block}.main-content{margin-left:0;width:100%}.content{padding:20px}.stats,.two-col{grid-template-columns:1fr}.chart-grid{grid-template-columns:1fr}}

/* Penyamaan tab Keuangan dengan halaman Pengeluaran dan Laporan */
.admin-tabs{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 25px;padding:5px;background:#fff;border:1px solid #eadfd5;border-radius:12px;box-shadow:0 2px 8px rgba(74,34,14,.05)}
.admin-tabs a{display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;color:#765a46;text-decoration:none;font-size:12px;font-weight:600}
.admin-tabs a:hover{background:#f8f0e8}
.admin-tabs a.active{background:#6d3519;color:#fff}
.insight-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;margin-bottom:18px;align-items:stretch}
.product-card{margin:0;min-width:0}
.category-card{min-width:0}
.panel-heading h2{font:700 18px Georgia,serif;margin:0;color:#542D1C}
.panel-heading p{font-size:11px;color:#8d796b;margin:5px 0 18px;line-height:1.5}
.category-item{margin:0 0 18px}
.category-label{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#542D1C;margin-bottom:7px}
.category-label span{font-weight:700;color:#6d3519}
.category-track{height:9px;border-radius:20px;background:#f3eee8;overflow:hidden}
.category-track span{display:block;height:100%;border-radius:20px;background:#d6a64f}
.category-item small{display:block;color:#927b69;font-size:10px;margin-top:5px}
.insight-bottom{margin-bottom:10px}
.customer-content{display:flex;align-items:center;gap:24px;min-height:150px}
.donut{width:138px;height:138px;flex:0 0 138px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#C99B78 0 var(--new-pct),#e8d9c7 var(--new-pct) 100%);position:relative}
.donut:before{content:"";position:absolute;inset:15px;background:#fff;border-radius:50%}
.donut>div{position:relative;display:flex;flex-direction:column;align-items:center}
.donut strong{font-size:23px;color:#542D1C}
.donut small{font-size:10px;color:#927b69}
.customer-legend{display:grid;gap:15px;flex:1;min-width:0}
.customer-legend>div{display:flex;align-items:center;gap:9px}
.customer-legend>div>div{display:flex;flex-direction:column;flex:1}
.customer-legend strong{font-size:15px;color:#542D1C}
.customer-legend small{font-size:10px;color:#927b69}
.customer-legend b{font-size:12px;color:#6d3519}
.legend-dot{width:10px;height:10px;border-radius:50%;flex:0 0 10px}
.legend-dot.new{background:#C99B78}.legend-dot.old{background:#e8d9c7}
.growth-item{display:flex;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid #f0e7df}
.growth-item:last-child{border-bottom:0}
.growth-rank{width:27px;height:27px;border-radius:8px;background:#fbf2e2;color:#8a5b20;display:grid;place-items:center;font-size:11px;font-weight:800;flex:0 0 27px}
.growth-info{display:flex;flex-direction:column;gap:4px;min-width:0;flex:1}
.growth-info strong{font-size:12px;color:#542D1C;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.growth-info small{font-size:10px;color:#927b69}
.growth-value{font-size:12px;font-weight:800;white-space:nowrap}
.growth-value.up{color:#687743}.growth-value.down{color:#a65d48}
@media(max-width:1050px){.insight-grid{grid-template-columns:1fr}.customer-content{justify-content:flex-start}}
@media(max-width:850px){.insight-bottom{grid-template-columns:1fr}.admin-tabs{margin-bottom:18px}}
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
<?php include __DIR__.'/sidebar.php'; ?>
<main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header><section class="content">
<div class="title"><h1>Analitik</h1><p>Pantau pengunjung, penjualan, dan produk terlaris dalam satu halaman.</p></div>
<form class="filter" method="get"><div><label>Dari tanggal</label><input type="date" name="tanggal_mulai" value="<?=htmlspecialchars($tanggal_mulai)?>"></div><div><label>Sampai tanggal</label><input type="date" name="tanggal_selesai" value="<?=htmlspecialchars($tanggal_selesai)?>"></div><button class="btn">Tampilkan</button></form>
<div class="stats"><div class="card"><small>Pengunjung</small><strong><?=number_format($pengunjung)?></strong><div class="sub">Pengunjung unik pada periode ini</div></div><div class="card success"><small>Pembeli</small><strong><?=number_format($pembeli)?></strong><div class="sub">Pengunjung teridentifikasi yang melakukan pembelian</div></div><div class="card highlight"><small>Konversi Pengunjung</small><strong><?=number_format($konversi,1,',','.')?>%</strong><div class="sub">Perbandingan pembeli terhadap pengunjung</div></div></div>
<div class="chart-grid"><div class="chart-card"><div class="chart-head"><div><h2>Pengunjung & Pembeli</h2><p>Perbandingan pengunjung dan pengunjung yang akhirnya melakukan pembelian.</p></div></div><div class="chart-wrap"><canvas id="grafikPengunjung"></canvas></div></div><div class="chart-card"><div class="chart-head"><div><h2>Pendapatan</h2><p>Pendapatan per hari dari pesanan terverifikasi/lunas.</p></div></div><div class="chart-wrap"><canvas id="grafikPenjualan"></canvas></div><div class="insight"><div class="insight-number">Rp<?=number_format(array_sum($nilai_pendapatan),0,',','.')?></div><div class="insight-text">Total pendapatan pada periode yang dipilih.</div></div></div></div>

<div class="insight-grid">
 <section class="product-card">
  <div class="chart-head"><div><h2>Produk Terlaris</h2><p>Produk dengan jumlah penjualan tertinggi pada periode ini.</p></div><div class="switch" role="tablist"><button type="button" id="btnGrafik" class="active">Grafik</button><button type="button" id="btnTulisan">Tulisan</button></div></div>
  <div id="produkGrafik" class="product-view"><div class="chart-wrap" style="height:235px"><canvas id="grafikProduk"></canvas></div></div>
  <div id="produkTulisan" class="product-view hidden"><table class="table"><thead><tr><th>NO</th><th>PRODUK</th><th>TERJUAL</th></tr></thead><tbody><?php if(!$data_produk):?><tr><td colspan="3" class="empty">Belum ada penjualan pada periode ini.</td></tr><?php else: foreach(array_slice($data_produk,0,5) as $i=>$r):?><tr><td class="rank">#<?=($i+1)?></td><td><strong><?=htmlspecialchars($r['nama_product'])?></strong></td><td><?=number_format($r['jumlah_terjual'])?> pcs</td></tr><?php endforeach; endif;?></tbody></table></div>
 </section>
 <section class="table-card category-card">
  <div class="panel-heading"><h2>Kategori Terlaris</h2><p>Kontribusi kategori berdasarkan jumlah produk terjual.</p></div>
  <?php if(!$data_kategori):?><div class="empty">Belum ada penjualan pada periode ini.</div><?php else: foreach($data_kategori as $r): $pct=$total_kategori_terjual>0?($r['jumlah_terjual']/$total_kategori_terjual)*100:0;?>
   <div class="category-item"><div class="category-label"><strong><?=htmlspecialchars($r['nama_category'])?></strong><span><?=number_format($pct,1,',','.')?>%</span></div><div class="category-track"><span style="width:<?=min(100,$pct)?>%"></span></div><small><?=number_format($r['jumlah_terjual'])?> pcs terjual</small></div>
  <?php endforeach; endif;?>
 </section>
</div>
<div class="two-col insight-bottom">
 <section class="table-card customer-card">
  <div class="panel-heading"><h2>Pelanggan Baru vs Lama</h2><p>Berdasarkan tanggal pembelian terverifikasi pertama pelanggan.</p></div>
  <div class="customer-content">
   <?php $baru_pct=$total_pelanggan_analitik>0?($pelanggan_baru/$total_pelanggan_analitik)*100:0; ?>
   <div class="donut" style="--new-pct:<?=number_format($baru_pct,2,'.','')?>%"><div><strong><?=number_format($total_pelanggan_analitik)?></strong><small>Total pelanggan</small></div></div>
   <div class="customer-legend"><div><span class="legend-dot new"></span><div><strong><?=number_format($pelanggan_baru)?></strong><small>Pelanggan Baru</small></div><b><?=number_format($baru_pct,1,',','.')?>%</b></div><div><span class="legend-dot old"></span><div><strong><?=number_format($pelanggan_lama)?></strong><small>Pelanggan Lama</small></div><b><?=number_format(100-$baru_pct,1,',','.')?>%</b></div></div>
  </div>
 </section>
 <section class="table-card growth-card">
  <div class="panel-heading"><h2>Produk dengan Pertumbuhan Tertinggi</h2><p>Dibandingkan periode sebelumnya dengan durasi yang sama.</p></div>
  <?php if(!$growth_rows):?><div class="empty">Belum ada data penjualan untuk dibandingkan.</div><?php else: foreach($growth_rows as $i=>$r):?>
   <div class="growth-item"><div class="growth-rank"><?=($i+1)?></div><div class="growth-info"><strong><?=htmlspecialchars($r['nama_product'])?></strong><small><?=number_format($r['current'])?> pcs pada periode ini · <?=number_format($r['previous'])?> pcs sebelumnya</small></div><div class="growth-value <?=($r['growth']===null||$r['growth']>=0)?'up':'down'?>"><?php if($r['growth']===null):?>Baru<?php else:?><?=($r['growth']>0?'↑':($r['growth']<0?'↓':'—'))?> <?=number_format(abs($r['growth']),1,',','.')?>%<?php endif;?></div></div>
  <?php endforeach; endif;?>
 </section>
</div>
</section></main></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>
const brown='#C99B78', dark='#6d3519', olive='#7b8750';
new Chart(document.getElementById('grafikPengunjung'),{type:'line',data:{labels:<?=json_encode($label_harian)?>,datasets:[{label:'Pengunjung',data:<?=json_encode($nilai_pengunjung)?>,borderColor:brown,backgroundColor:'rgba(173,118,54,.10)',fill:true,tension:.35,pointRadius:2,pointHoverRadius:4},{label:'Pembeli',data:<?=json_encode($nilai_pembeli)?>,borderColor:olive,backgroundColor:'rgba(123,135,80,.08)',fill:false,tension:.35,pointRadius:2,pointHoverRadius:4}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'top',labels:{boxWidth:10,font:{size:11}}}},scales:{x:{grid:{display:false},ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:10}},y:{beginAtZero:true,ticks:{precision:0}}}}});
new Chart(document.getElementById('grafikPenjualan'),{type:'bar',data:{labels:<?=json_encode($label_penjualan)?>,datasets:[{label:'Pendapatan',data:<?=json_encode($nilai_pendapatan)?>,backgroundColor:brown,borderRadius:5,maxBarThickness:28}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,ticks:{callback:v=>'Rp'+Number(v).toLocaleString('id-ID'),maxTicksLimit:5}}}}});
new Chart(document.getElementById('grafikProduk'),{type:'bar',data:{labels:<?=json_encode(array_slice(array_reverse($label_produk),0,5))?>,datasets:[{label:'Terjual',data:<?=json_encode(array_slice(array_reverse($nilai_produk),0,5))?>,backgroundColor:brown,borderRadius:5,barThickness:18}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{grid:{display:false}}}}});
const bg=document.getElementById('btnGrafik'),bt=document.getElementById('btnTulisan'),vg=document.getElementById('produkGrafik'),vt=document.getElementById('produkTulisan');
bg.onclick=()=>{bg.classList.add('active');bt.classList.remove('active');vg.classList.remove('hidden');vt.classList.add('hidden')};bt.onclick=()=>{bt.classList.add('active');bg.classList.remove('active');vt.classList.remove('hidden');vg.classList.add('hidden')};
</script></body></html>
