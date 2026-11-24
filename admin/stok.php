<?php
include "auth.php";
include "../config/koneksi.php";
$q=mysqli_query($koneksi,"SELECT id_product,nama_product,stok,harga FROM products ORDER BY stok ASC,nama_product ASC");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Stok Produk - Budiarti Risol</title>
<style>*{box-sizing:border-box}body{margin:0;background:#F8EFE5;color:#542D1C;font-family:Arial,sans-serif}.admin-wrapper{display:flex;min-height:100vh}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.admin-profile{display:flex;gap:10px;align-items:center}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.page-content{padding:30px}.page-title h1{font:700 27px Georgia;margin:0 0 6px}.page-title p{font-size:12px;color:#8a7669;margin:0 0 20px}.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px;box-shadow:0 3px 12px rgba(74,34,14,.05);overflow:auto}table{width:100%;border-collapse:collapse;min-width:700px}th,td{padding:12px;border-bottom:1px solid #eee4db;text-align:left;font-size:12px}th{color:#8d796b;background:#fcf8f3}.badge{display:inline-block;padding:5px 9px;border-radius:20px;font-size:10px;font-weight:bold}.aman{background:#dff1df;color:#286b32}.tipis{background:#fff0cf;color:#855016}.habis{background:#fde1df;color:#a52c38}.action{color:#8b4a16;text-decoration:none;font-weight:bold;font-size:11px}@media(max-width:900px){.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:20px}}</style><link rel="stylesheet" href="../assets/css/admin-sidebar.css"><style>.admin-tabs{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 20px;padding:5px;background:#fff;border:1px solid #eadfd5;border-radius:12px;box-shadow:0 2px 8px rgba(74,34,14,.05)}.admin-tabs a{display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;color:#765a46;text-decoration:none;font-size:12px;font-weight:600}.admin-tabs a:hover{background:#f8f0e8}.admin-tabs a.active{background:#6d3519;color:#fff}</style><link rel="stylesheet" href="../assets/css/admin-topbar.css">

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
</head><body><div class="admin-wrapper"><?php include __DIR__.'/sidebar.php'; ?><main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header><section class="page-content"><?php include __DIR__.'/tabs_produk.php'; ?><div class="page-title"><h1>Stok Produk</h1><p>Pantau ketersediaan stok dan buka halaman produk untuk melakukan penyesuaian.</p></div><div class="card"><table><thead><tr><th>Produk</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php if($q&&mysqli_num_rows($q)):while($r=mysqli_fetch_assoc($q)):$stok=(int)$r['stok'];$cls=$stok<=0?'habis':($stok<=5?'tipis':'aman');$label=$stok<=0?'Stok Habis':($stok<=5?'Menipis':'Aman');?><tr><td><strong><?=htmlspecialchars($r['nama_product'])?></strong></td><td>Rp<?=number_format((float)$r['harga'],0,',','.')?></td><td><?=number_format($stok)?></td><td><span class="badge <?=$cls?>"><?=$label?></span></td><td><a class="action" href="stok_produk.php?id=<?=intval($r['id_product'])?>">Atur Stok</a></td></tr><?php endwhile;else:?><tr><td colspan="5" style="text-align:center;padding:30px;color:#8d796b">Belum ada produk.</td></tr><?php endif;?></tbody></table></div></section></main></div></body></html>
