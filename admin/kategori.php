<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
ensureCategoryImageColumn($koneksi);

$query = mysqli_query($koneksi, "SELECT c.*, COUNT(p.id_product) AS total_produk FROM categories c LEFT JOIN products p ON p.id_category = c.id_category GROUP BY c.id_category ORDER BY c.id_category DESC");
$total = $query ? mysqli_num_rows($query) : 0;
?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Kelola Kategori - Budiarti Risol</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;left:0;top:0;bottom:0;padding:25px 15px;overflow-y:auto}.sidebar-logo{text-align:center;padding-bottom:30px}.sidebar-logo .logo-icon{font-size:32px;margin-bottom:5px}.sidebar-logo h2{font-family:Georgia,serif;font-size:22px;font-style:italic}.sidebar-logo p{font-size:9px;letter-spacing:1px;color:#EBD3C0;margin-top:3px}.sidebar-menu{display:flex;flex-direction:column;gap:7px}.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;color:#f6e8dc;font-size:13px;text-decoration:none}.sidebar-menu a:hover,.sidebar-menu a.active{background:#75401f}.menu-icon{width:20px;min-width:20px;text-align:center;font-size:15px}.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center}.page-content{padding:35px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.page-title h1{font-family:Georgia,serif;font-size:27px;margin-bottom:7px}.page-title p{color:#8a7669;font-size:13px}.btn{display:inline-flex;align-items:center;background:#542D1C;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:bold}.card{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:22px;box-shadow:0 3px 12px rgba(80,40,10,.04)}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:650px}th{background:#fcf8f3;text-align:left;padding:13px 12px;font-size:11px;color:#8d796b;border-bottom:1px solid #eee4db}td{padding:13px 12px;border-bottom:1px solid #f1ebe5;font-size:12px;color:#5b4a40;vertical-align:middle}.action a{font-size:11px;font-weight:bold;margin-right:10px;text-decoration:none}.edit{color:#bd7a13}.del{color:#bd303d}.alert{padding:12px 16px;border-radius:8px;font-size:12px;margin-bottom:20px;font-weight:bold}.ok{background:#dcefdc;color:#286b32}.err{background:#fde3e5;color:#a52c38}@media(max-width:750px){.sidebar{width:190px}.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:20px}.page-header{flex-direction:column;align-items:flex-start;gap:15px}}
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
</head><body><div class="admin-wrapper">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="main-content"><header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header><section class="page-content">

<div class="page-header"><div class="page-title"><h1>Kelola Kategori</h1><p>Atur kategori produk yang digunakan pada daftar produk. Total: <?= $total ?> kategori.</p></div><a class="btn" href="tambah_kategori.php">+ Tambah Kategori</a></div>
<?php if(($_GET['pesan']??'')==='tambah_berhasil'): ?><div class="alert ok">✓ Kategori berhasil ditambahkan.</div><?php elseif(($_GET['pesan']??'')==='edit_berhasil'): ?><div class="alert ok">✓ Kategori berhasil diperbarui.</div><?php elseif(($_GET['pesan']??'')==='hapus_berhasil'): ?><div class="alert ok">✓ Kategori berhasil dihapus.</div><?php elseif(($_GET['pesan']??'')==='digunakan'): ?><div class="alert err">Kategori belum bisa dihapus karena masih digunakan oleh produk.</div><?php elseif(($_GET['pesan']??'')==='hapus_gagal'): ?><div class="alert err">Kategori gagal dihapus.</div><?php endif; ?>
<div class="card"><div class="table-wrap"><table><thead><tr><th>ID</th><th>Nama Kategori</th><th>Total Produk</th><th>Aksi</th></tr></thead><tbody>
<?php if($query && mysqli_num_rows($query)): while($r=mysqli_fetch_assoc($query)): ?><tr><td>#<?= (int)$r['id_category'] ?></td><td><strong><?= htmlspecialchars($r['nama_category']) ?></strong></td><td><?= (int)$r['total_produk'] ?> produk</td><td class="action"><a class="edit" href="edit_kategori.php?id=<?= (int)$r['id_category'] ?>">Edit</a><a class="del" href="hapus_kategori.php?id=<?= (int)$r['id_category'] ?>" onclick="return confirm('Hapus kategori ini?')">Hapus</a></td></tr><?php endwhile; else: ?><tr><td colspan="4" style="text-align:center;padding:30px">Belum ada data kategori.</td></tr><?php endif; ?></tbody></table></div></div>
</section></main></div></body></html>
