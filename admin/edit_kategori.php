<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";
ensureCategoryImageColumn($koneksi);
$id=(int)($_GET['id']??0); $q=mysqli_query($koneksi,"SELECT * FROM categories WHERE id_category=$id LIMIT 1"); $kategori=$q?mysqli_fetch_assoc($q):null; if(!$kategori){header('Location:produk.php#kelola-kategori');exit;} $pesan_error='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])){
 $nama=trim($_POST['nama_category']??''); if($nama==='')$pesan_error='Nama kategori tidak boleh kosong.';
 if($pesan_error===''){ $stmt=mysqli_prepare($koneksi,"SELECT id_category FROM categories WHERE nama_category=? AND id_category<>? LIMIT 1"); mysqli_stmt_bind_param($stmt,'si',$nama,$id); mysqli_stmt_execute($stmt); if(mysqli_stmt_get_result($stmt)->num_rows)$pesan_error='Kategori tersebut sudah ada.'; mysqli_stmt_close($stmt);}
 if($pesan_error===''){ $stmt=mysqli_prepare($koneksi,"UPDATE categories SET nama_category=? WHERE id_category=?"); mysqli_stmt_bind_param($stmt,'si',$nama,$id); if(mysqli_stmt_execute($stmt)){ header('Location:produk.php?pesan=edit_kategori_berhasil#kelola-kategori');exit;} $pesan_error='Gagal memperbarui kategori.'; }
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Kategori - Budiarti Risol</title>
<style>
*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C;margin:0}.wrap{max-width:900px;margin:36px auto;padding:20px}.back{display:inline-block;margin-bottom:14px;color:#6b3518;text-decoration:none;font-size:13px;font-weight:600}.head{margin-bottom:22px}.head h1{font-family:Georgia,serif;margin:0;color:#542D1C;font-size:30px}.head p{margin:7px 0 0;color:#8a7669;font-size:13px}.layout{display:grid;grid-template-columns:1.15fr .85fr;gap:18px}.card{background:#fff;padding:28px;border-radius:14px;box-shadow:0 4px 15px rgba(80,40,10,.06);border:1px solid #eee4db}.section-title{font-family:Georgia,serif;margin:0 0 18px;color:#5b2c1f;font-size:19px}.field{margin:0 0 20px}.field:last-child{margin-bottom:0}label{display:block;font-size:13px;font-weight:bold;margin-bottom:8px}input[type=text],input[type=file]{width:100%;padding:12px;border:1px solid #e5dbd2;border-radius:8px;box-sizing:border-box;background:#fff}.help{display:block;color:#8a7669;font-size:11px;line-height:1.5;margin-top:8px}.current-wrap{display:flex;align-items:center;justify-content:center;min-height:270px;background:#fcf8f3;border:1px dashed #dfcdbd;border-radius:12px;padding:20px}.current,.preview{width:220px;height:220px;object-fit:cover;border-radius:12px;border:1px solid #e5dbd2;background:#f5eee7}.placeholder{width:220px;height:220px;border-radius:12px;background:#f5eee7;border:1px solid #e5dbd2;display:flex;align-items:center;justify-content:center;color:#8a7669;font-size:12px;text-align:center}.caption{text-align:center;color:#8a7669;font-size:11px;margin:12px 0 0}.err{background:#fde3e5;color:#a52c38;padding:12px;border-radius:8px;font-size:12px;margin-bottom:18px}.actions{display:flex;gap:10px;margin-top:22px}button{background:#542D1C;color:#fff;border:0;padding:12px 20px;border-radius:8px;font-weight:bold;cursor:pointer}.cancel{display:inline-flex;align-items:center;background:#f1e9e1;color:#5b2c1f;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:13px}.preview{display:none;margin-top:12px;width:100%;height:160px}.file-label{font-size:12px;color:#6b594e;font-weight:600;margin-bottom:8px;display:block}@media(max-width:760px){.wrap{margin:20px auto}.layout{grid-template-columns:1fr}.current-wrap{min-height:220px}.current,.placeholder{width:180px;height:180px}}
</style>
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
</head><body><div class="wrap"><a class="back" href="produk.php#kelola-kategori">← Kembali</a><div class="head"><h1>Edit Kategori</h1><p>Perbarui nama kategori produk.</p></div><?php if($pesan_error):?><div class="err"><?=htmlspecialchars($pesan_error)?></div><?php endif;?><form method="post">
<div class="layout">
<div class="card">
<h2 class="section-title">Informasi Kategori</h2>
<div class="field">
<label>Nama Kategori *</label>
<input type="text" name="nama_category" value="<?=htmlspecialchars($kategori['nama_category'])?>" required>
</div>
<div class="actions">
<button name="update" type="submit">Simpan Perubahan</button>
<a class="cancel" href="produk.php#kelola-kategori">Batal</a>
</div>
</div>
</div>
</form></div></body></html>
