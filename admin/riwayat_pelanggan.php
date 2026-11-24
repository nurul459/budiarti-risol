<?php
include "auth.php";
include "../config/koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: pengguna.php');
    exit;
}

$id_user = (int) $_GET['id'];
$stmt = mysqli_prepare($koneksi, 'SELECT id_user, nama, email, no_hp FROM users WHERE id_user = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    echo 'Pelanggan tidak ditemukan.';
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    'SELECT id_order, nama_penerima, total_pembayaran, metode_pembayaran, status, created_at
     FROM orders WHERE id_user = ? ORDER BY created_at DESC, id_order DESC'
);
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
$total_pesanan = mysqli_num_rows($orders);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pesanan Pelanggan - Budiarti Risol</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.container{max-width:1100px;margin:35px auto;padding:0 20px}.back{display:inline-block;margin-bottom:16px;color:#6b3518;text-decoration:none;font-size:13px}.header{margin-bottom:22px}.header h1{font-family:Georgia,serif;color:#542D1C;margin-bottom:7px}.header p{font-size:13px;color:#8b7668}.profile{background:#fff;border-radius:14px;padding:18px 20px;margin-bottom:18px;box-shadow:0 3px 12px rgba(70,35,15,.06);display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.profile span{display:block;font-size:11px;color:#9a8575;margin-bottom:4px}.profile strong{font-size:13px;color:#5b2c1f}.card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 3px 12px rgba(70,35,15,.06);overflow:auto}table{width:100%;border-collapse:collapse;min-width:720px}th{background:#f8f1e9;color:#5b2c1f;padding:12px;text-align:left;font-size:12px}td{padding:13px 12px;border-bottom:1px solid #eee7e0;font-size:12px;color:#5d4a3e}.detail{display:inline-block;background:#f2e4d6;color:#6b3518;padding:7px 10px;border-radius:7px;text-decoration:none;font-weight:600;font-size:11px}.empty{text-align:center;padding:30px;color:#8b7668}.status{display:inline-block;padding:5px 9px;border-radius:20px;background:#f7e4c5;color:#855016;font-size:11px;font-weight:600}@media(max-width:700px){.profile{grid-template-columns:1fr}}
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
</head>
<body>
<div class="container">
    <a class="back" href="pengguna.php">← Kembali ke Data Pengguna</a>
    <div class="header">
        <h1>Riwayat Pesanan Pelanggan</h1>
        <p>Riwayat transaksi pelanggan dalam sistem Budiarti Risol.</p>
    </div>
    <div class="profile">
        <div><span>Nama</span><strong><?= htmlspecialchars($user['nama']); ?></strong></div>
        <div><span>Email</span><strong><?= htmlspecialchars($user['email']); ?></strong></div>
        <div><span>No. HP</span><strong><?= htmlspecialchars($user['no_hp']); ?></strong></div>
    </div>
    <div class="card">
        <h2 style="font-family:Georgia,serif;font-size:18px;color:#542D1C;margin-bottom:16px;">Daftar Pesanan (<?= $total_pesanan; ?>)</h2>
        <?php if ($total_pesanan > 0): ?>
        <table>
            <thead><tr><th>ID Pesanan</th><th>Tanggal</th><th>Penerima</th><th>Pembayaran</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($orders)): ?>
            <tr>
                <td>#ORD<?= str_pad($row['id_order'], 3, '0', STR_PAD_LEFT); ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                <td><?= htmlspecialchars($row['nama_penerima']); ?></td>
                <td><?= htmlspecialchars($row['metode_pembayaran']); ?></td>
                <td>Rp <?= number_format((float)$row['total_pembayaran'], 0, ',', '.'); ?></td>
                <td><span class="status"><?= htmlspecialchars($row['status']); ?></span></td>
                <td><a class="detail" href="detail_pesanan.php?id=<?= (int)$row['id_order']; ?>">Detail</a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty">Pelanggan ini belum memiliki riwayat pesanan.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<?php mysqli_stmt_close($stmt); ?>
