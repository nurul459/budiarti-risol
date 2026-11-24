<?php
include "auth.php";
include "../config/koneksi.php";

// Pastikan tabel pengeluaran tersedia.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengeluaran (
    id_pengeluaran INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    keterangan VARCHAR(255) NOT NULL,
    nominal DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pesan = '';
$error = '';

// Tambah pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengeluaran'])) {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $kategori = trim($_POST['kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal_raw = str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? '');
    $nominal = (float) $nominal_raw;

    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        $error = 'Tanggal pengeluaran tidak valid.';
    } elseif ($kategori === '' || $keterangan === '') {
        $error = 'Kategori dan keterangan wajib diisi.';
    } elseif ($nominal <= 0) {
        $error = 'Nominal pengeluaran harus lebih dari 0.';
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengeluaran (tanggal, kategori, keterangan, nominal) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssd', $tanggal, $kategori, $keterangan, $nominal);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = 'Pengeluaran berhasil ditambahkan.';
        } else {
            $error = 'Pengeluaran gagal ditambahkan.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Edit pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pengeluaran'])) {
    $id = (int) ($_POST['id_pengeluaran'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $kategori = trim($_POST['kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal_raw = str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? '');
    $nominal = (float) $nominal_raw;

    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal);
    if ($id <= 0) {
        $error = 'Data pengeluaran tidak valid.';
    } elseif (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal) {
        $error = 'Tanggal pengeluaran tidak valid.';
    } elseif ($kategori === '' || $keterangan === '') {
        $error = 'Kategori dan keterangan wajib diisi.';
    } elseif ($nominal <= 0) {
        $error = 'Nominal pengeluaran harus lebih dari 0.';
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE pengeluaran SET tanggal=?, kategori=?, keterangan=?, nominal=? WHERE id_pengeluaran=?");
        mysqli_stmt_bind_param($stmt, 'sssdi', $tanggal, $kategori, $keterangan, $nominal, $id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = 'Pengeluaran berhasil diperbarui.';
        } else {
            $error = 'Pengeluaran gagal diperbarui.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Hapus pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengeluaran'])) {
    $id = (int) ($_POST['id_pengeluaran'] ?? 0);
    if ($id > 0) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM pengeluaran WHERE id_pengeluaran = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $pesan = 'Pengeluaran berhasil dihapus.';
        } else {
            $error = 'Pengeluaran gagal dihapus.';
        }
        mysqli_stmt_close($stmt);
    }
}

$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

// Ambil data pengeluaran untuk periode terpilih.
// Hasil query langsung dibaca dan statement ditutup agar koneksi MySQL
// tidak mengalami "Commands out of sync" saat query berikutnya dijalankan.
$query_pengeluaran = mysqli_prepare($koneksi, "SELECT id_pengeluaran, tanggal, kategori, keterangan, nominal FROM pengeluaran WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC, id_pengeluaran DESC");
mysqli_stmt_bind_param($query_pengeluaran, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($query_pengeluaran);
$result_pengeluaran = mysqli_stmt_get_result($query_pengeluaran);
$pengeluaran_rows = $result_pengeluaran ? mysqli_fetch_all($result_pengeluaran, MYSQLI_ASSOC) : [];
mysqli_stmt_close($query_pengeluaran);

$query_total = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(nominal), 0) AS total, COUNT(*) AS jumlah FROM pengeluaran WHERE tanggal BETWEEN ? AND ?");
mysqli_stmt_bind_param($query_total, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($query_total);
$result_total = mysqli_stmt_get_result($query_total);
$total_data = $result_total ? mysqli_fetch_assoc($result_total) : [];
mysqli_stmt_close($query_total);

$total_pengeluaran = (float) ($total_data['total'] ?? 0);
$jumlah_transaksi = (int) ($total_data['jumlah'] ?? 0);

// Pengeluaran terbesar pada periode terpilih.
$query_terbesar = mysqli_prepare($koneksi, "SELECT kategori, keterangan, nominal FROM pengeluaran WHERE tanggal BETWEEN ? AND ? ORDER BY nominal DESC, tanggal DESC, id_pengeluaran DESC LIMIT 1");
mysqli_stmt_bind_param($query_terbesar, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($query_terbesar);
$result_terbesar = mysqli_stmt_get_result($query_terbesar);
$pengeluaran_terbesar = $result_terbesar ? (mysqli_fetch_assoc($result_terbesar) ?: null) : null;
mysqli_stmt_close($query_terbesar);

// Ringkasan berdasarkan kategori.
$query_kategori = mysqli_prepare($koneksi, "SELECT kategori, COALESCE(SUM(nominal),0) AS total FROM pengeluaran WHERE tanggal BETWEEN ? AND ? GROUP BY kategori ORDER BY total DESC, kategori ASC");
mysqli_stmt_bind_param($query_kategori, 'ss', $tanggal_mulai, $tanggal_selesai);
mysqli_stmt_execute($query_kategori);
$result_kategori = mysqli_stmt_get_result($query_kategori);
$kategori_rows = $result_kategori ? mysqli_fetch_all($result_kategori, MYSQLI_ASSOC) : [];
mysqli_stmt_close($query_kategori);

// Perbandingan periode sebelumnya tidak ditampilkan di halaman agar tetap minimalis.

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengeluaran - Budiarti Risol</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}a{text-decoration:none}
.admin-wrapper{display:flex;min-height:100vh}.sidebar{width:230px;background:#542D1C;color:#fff;position:fixed;left:0;top:0;bottom:0;padding:25px 15px}
.sidebar-logo{text-align:center;padding-bottom:30px}.sidebar-logo .logo-icon{font-size:32px;margin-bottom:5px}.sidebar-logo h2{font-family:Georgia,serif;font-size:22px;font-style:italic;color:#fff}.sidebar-logo p{font-size:9px;letter-spacing:1px;color:#EBD3C0;margin-top:3px}
.sidebar-menu{display:flex;flex-direction:column;gap:7px}.sidebar-menu a{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:8px;color:#f6e8dc;font-size:13px;transition:.2s}.sidebar-menu a:hover{background:#633419}.sidebar-menu a.active{background:#75401f;color:#fff}.menu-icon{width:20px;text-align:center;font-size:15px}
.main-content{margin-left:230px;width:calc(100% - 230px);min-height:100vh}.top-header{height:70px;background:#C99B78;border-bottom:1px solid #eee3d8;display:flex;align-items:center;justify-content:space-between;padding:0 35px}.hamburger{font-size:20px;color:#5b2c1f}.admin-profile{display:flex;align-items:center;gap:10px;font-size:13px;color:#542D1C}.avatar{width:34px;height:34px;border-radius:50%;background:#EBD3C0;display:flex;align-items:center;justify-content:center;font-size:17px}.admin-name{font-weight:bold}
.page-content{padding:35px}.page-header{display:flex;justify-content:space-between;align-items:end;margin-bottom:25px;gap:20px;flex-wrap:wrap}.page-title h1{font-family:Georgia,serif;font-size:27px;color:#542D1C;margin-bottom:7px}.page-title p{color:#8a7669;font-size:13px}
.page-actions{display:flex;align-items:center;gap:10px}.add-button{height:38px;padding:0 15px;border:0;border-radius:8px;background:#542D1C;color:#fff;font-weight:bold;font-size:12px;cursor:pointer}.add-button:hover{background:#633419}
.filter-strip{display:flex;align-items:end;gap:12px;flex-wrap:wrap;padding:0 0 22px;border-bottom:1px solid #e7dcd2;margin-bottom:22px}.date-group{display:flex;flex-direction:column;gap:6px}.date-group label{font-size:11px;color:#8d796b;font-weight:bold}.date-group input{height:40px;border:1px solid #dfd2c7;border-radius:8px;padding:0 12px;outline:none;font-size:12px;color:#4b392f;background:#fffdfb}.date-group input:focus{border-color:#b4772d}
.filter-button{height:40px;border:0;border-radius:8px;padding:0 18px;background:#EBD3C0;color:#542D1C;font-weight:bold;cursor:pointer;font-size:12px}.filter-button:hover{background:#eab958}.reset-button{height:40px;padding:0 12px;display:flex;align-items:center;color:#745d4d;font-size:12px}
.expense-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
.expense-stat{background:#fff;border:1px solid #eee4db;border-radius:12px;padding:19px 20px;min-height:118px}
.expense-stat .label{font-size:11px;color:#8d796b;margin-bottom:9px;font-weight:bold}
.expense-stat .value{font-size:21px;font-weight:bold;color:#542D1C;line-height:1.25}
.expense-stat .sub{font-size:11px;color:#8a7669;margin-top:7px;line-height:1.4}
.expense-stat .sub strong{color:#6d3519}
.expense-category-section{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px;margin-bottom:18px}
.category-heading{display:flex;justify-content:space-between;align-items:end;margin-bottom:16px}
.category-heading h2{font-family:Georgia,serif;font-size:19px;color:#542D1C}
.category-heading p{font-size:11px;color:#8a7669}
.category-list{display:grid;gap:12px}
.category-row{display:grid;grid-template-columns:120px minmax(120px,1fr) 48px 105px;align-items:center;gap:10px}
.category-name{font-size:11px;font-weight:600;color:#5b4a40;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.category-track{height:9px;background:#f3eadf;border-radius:999px;overflow:hidden}
.category-fill{height:100%;background:#EBD3C0;border-radius:999px}
.category-percent{font-size:10px;color:#7b6757;text-align:right}
.category-total{font-size:11px;font-weight:bold;color:#542D1C;text-align:right;white-space:nowrap}
.empty-category{text-align:center;padding:15px;color:#927e70;font-size:12px}
.notice{padding:11px 13px;border-radius:8px;margin-bottom:16px;font-size:12px}.success{background:#edf8ee;color:#2f6a37;border:1px solid #cfe7d1}.error{background:#fff0ee;color:#9a3f35;border:1px solid #f1c9c3}
.table-heading{display:flex;justify-content:space-between;align-items:end;margin-bottom:12px}.table-heading h2{font-family:Georgia,serif;font-size:19px;color:#542D1C}.table-heading p{font-size:11px;color:#8a7669;margin-top:4px}
.table-section{background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px}.table-wrapper{overflow-x:auto;border-top:1px solid #e7dcd2}table{width:100%;border-collapse:collapse;min-width:720px}th{background:transparent;text-align:left;padding:12px 10px;font-size:10px;color:#8d796b;font-weight:bold;border-bottom:1px solid #e7dcd2}td{padding:12px 10px;border-bottom:1px solid #f1ebe5;font-size:11px;color:#5b4a40;vertical-align:middle}tr:last-child td{border-bottom:none}.number{color:#927e70}.nominal{font-weight:bold;color:#542D1C}.action-buttons{display:flex;align-items:center;gap:6px}.edit-button,.delete-button{border-radius:7px;padding:7px 10px;font-size:11px;cursor:pointer}.edit-button{border:1px solid #e2c7a8;background:#fff8ee;color:#7d4a1d}.edit-button:hover{background:#fff0d9}.delete-button{border:1px solid #efc8c2;background:#fff4f2;color:#9b3f35}.delete-button:hover{background:#ffeae6}.empty{text-align:center;padding:40px;color:#927e70;font-size:12px}
.modal-backdrop{position:fixed;inset:0;background:rgba(48,25,13,.42);display:none;align-items:center;justify-content:center;padding:20px;z-index:1000}.modal-backdrop.show{display:flex}
.modal{width:min(440px,100%);background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(40,20,8,.2);padding:22px}.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}.modal-header h2{font-family:Georgia,serif;font-size:20px;color:#542D1C}.close-modal{width:30px;height:30px;border:0;border-radius:7px;background:#f7efe8;color:#6d4a35;font-size:18px;cursor:pointer}.modal-form{display:grid;gap:13px}.field{display:flex;flex-direction:column;gap:6px}.field label{font-size:11px;color:#8d796b;font-weight:bold}.field input,.field select{height:40px;border:1px solid #dfd2c7;border-radius:8px;padding:0 12px;outline:none;font-size:12px;color:#4b392f;background:#fffdfb}.field input:focus,.field select:focus{border-color:#b4772d}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:6px}.cancel-button{height:38px;padding:0 14px;border:1px solid #dfd2c7;border-radius:8px;background:#fff;color:#745d4d;font-size:12px;cursor:pointer}.save-button{height:38px;padding:0 16px;border:0;border-radius:8px;background:#EBD3C0;color:#542D1C;font-weight:bold;font-size:12px;cursor:pointer}
.admin-tabs{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 25px;padding:5px;background:#fff;border:1px solid #eadfd5;border-radius:12px;box-shadow:0 2px 8px rgba(74,34,14,.05)}.admin-tabs a{display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;color:#765a46;text-decoration:none;font-size:12px;font-weight:600}.admin-tabs a:hover{background:#f8f0e8}.admin-tabs a.active{background:#6d3519;color:#fff}
@media(max-width:900px){.expense-summary{grid-template-columns:1fr}.category-row{grid-template-columns:100px minmax(100px,1fr) 42px 95px}}\n@media(max-width:700px){.sidebar{width:190px}.main-content{margin-left:190px;width:calc(100% - 190px)}.page-content{padding:24px}.top-header{padding:0 24px}.header-total{text-align:left}.page-header{align-items:flex-start}.page-actions{width:100%}.add-button{width:100%}}
</style>
<link rel="stylesheet" href="../assets/css/admin-sidebar.css">
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
<main class="main-content">
<header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>

<section class="page-content">
<?php include __DIR__ . '/tabs_keuangan.php'; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Pengeluaran</h1>
        <p>Catat dan kelola biaya operasional toko.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="add-button" id="openExpenseModal">＋ Tambah Pengeluaran</button>
    </div>
</div>

<?php if ($pesan): ?><div class="notice success"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<form method="GET" class="filter-strip">
    <div class="date-group">
        <label>Periode</label>
        <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai); ?>" required>
    </div>
    <div class="date-group">
        <label>&nbsp;</label>
        <input type="date" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai); ?>" required>
    </div>
    <button class="filter-button" type="submit">Tampilkan</button>
    <a class="reset-button" href="pengeluaran.php">Reset</a>
</form>

<section class="expense-summary">
    <div class="expense-stat">
        <div class="label">TOTAL PENGELUARAN</div>
        <div class="value">Rp<?= number_format($total_pengeluaran,0,',','.'); ?></div>
    </div>
    <div class="expense-stat">
        <div class="label">JUMLAH TRANSAKSI</div>
        <div class="value"><?= $jumlah_transaksi; ?> transaksi</div>
    </div>
    <div class="expense-stat">
        <div class="label">PENGELUARAN TERBESAR</div>
        <?php if ($pengeluaran_terbesar): ?>
            <div class="value">Rp<?= number_format($pengeluaran_terbesar['nominal'],0,',','.'); ?></div>
            <div class="sub"><strong><?= htmlspecialchars($pengeluaran_terbesar['kategori']); ?></strong><br><?= htmlspecialchars($pengeluaran_terbesar['keterangan']); ?></div>
        <?php else: ?>
            <div class="value">Rp0</div>
            <div class="sub">Belum ada pengeluaran.</div>
        <?php endif; ?>
    </div>
</section>

<section class="expense-category-section">
    <div class="category-heading">
        <div><h2>Pengeluaran Berdasarkan Kategori</h2></div>
        <p>Periode yang dipilih</p>
    </div>
    <?php if ($total_pengeluaran > 0 && count($kategori_rows) > 0): ?>
    <div class="category-list">
        <?php foreach ($kategori_rows as $cat): 
            $cat_total=(float)$cat['total'];
            $pct=$total_pengeluaran>0 ? ($cat_total/$total_pengeluaran)*100 : 0;
        ?>
        <div class="category-row">
            <div class="category-name"><?= htmlspecialchars($cat['kategori']); ?></div>
            <div class="category-track"><div class="category-fill" style="width:<?= min(100,$pct); ?>%"></div></div>
            <div class="category-percent"><?= number_format($pct,0); ?>%</div>
            <div class="category-total">Rp<?= number_format($cat_total,0,',','.'); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-category">Belum ada pengeluaran pada periode tersebut.</div>
    <?php endif; ?>
</section>

<section class="table-section">
<div class="table-heading">
    <div>
        <h2>Daftar Pengeluaran</h2>
        <p>Catatan biaya berdasarkan periode yang dipilih.</p>
    </div>
    <p><?= $jumlah_transaksi; ?> transaksi</p>
</div>

<div class="table-wrapper">
<table>
<thead><tr><th>NO</th><th>TANGGAL</th><th>KATEGORI</th><th>PENGELUARAN UNTUK APA</th><th>NOMINAL</th><th>AKSI</th></tr></thead>
<tbody>
<?php if (count($pengeluaran_rows) > 0): $no=1; foreach ($pengeluaran_rows as $row): ?>
<tr>
    <td class="number"><?= $no++; ?></td>
    <td><?= date('d/m/Y',strtotime($row['tanggal'])); ?></td>
    <td><?= htmlspecialchars($row['kategori']); ?></td>
    <td><?= htmlspecialchars($row['keterangan']); ?></td>
    <td class="nominal">Rp<?= number_format($row['nominal'],0,',','.'); ?></td>
    <td>
        <div class="action-buttons">
            <button type="button" class="edit-button"
                data-id="<?= (int)$row['id_pengeluaran']; ?>"
                data-tanggal="<?= htmlspecialchars($row['tanggal'], ENT_QUOTES); ?>"
                data-kategori="<?= htmlspecialchars($row['kategori'], ENT_QUOTES); ?>"
                data-keterangan="<?= htmlspecialchars($row['keterangan'], ENT_QUOTES); ?>"
                data-nominal="<?= htmlspecialchars($row['nominal'], ENT_QUOTES); ?>">Edit</button>
            <form method="POST" onsubmit="return confirm('Hapus data pengeluaran ini?');">
                <input type="hidden" name="id_pengeluaran" value="<?= (int)$row['id_pengeluaran']; ?>">
                <button class="delete-button" name="hapus_pengeluaran" type="submit">Hapus</button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6"><div class="empty">Belum ada data pengeluaran pada periode tersebut.</div></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</section>
</section>
</main>
</div>

<div class="modal-backdrop" id="expenseModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="expenseModalTitle">
        <div class="modal-header">
            <h2 id="expenseModalTitle">Tambah Pengeluaran</h2>
            <button type="button" class="close-modal" id="closeExpenseModal" aria-label="Tutup">×</button>
        </div>
        <form method="POST" class="modal-form" id="expenseForm">
            <input type="hidden" name="id_pengeluaran" id="expenseId" value="">
            <div class="field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="expenseTanggal" value="<?= htmlspecialchars(date('Y-m-d')); ?>" required>
            </div>
            <div class="field">
                <label>Kategori</label>
                <select name="kategori" id="expenseKategori" required>
                    <option value="">Pilih kategori</option>
                    <option>Bahan Baku</option><option>Operasional</option><option>Pengiriman</option>
                    <option>Kemasan</option><option>Promosi</option><option>Lainnya</option>
                </select>
            </div>
            <div class="field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" id="expenseKeterangan" maxlength="255" placeholder="Contoh: Pembelian tepung dan minyak" required>
            </div>
            <div class="field">
                <label>Nominal (Rp)</label>
                <input type="number" name="nominal" id="expenseNominal" min="1" step="0.01" placeholder="50000" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-button" id="cancelExpenseModal">Batal</button>
                <button class="save-button" type="submit" id="expenseSubmit" name="tambah_pengeluaran">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const modal=document.getElementById('expenseModal');
    const open=document.getElementById('openExpenseModal');
    const close=document.getElementById('closeExpenseModal');
    const cancel=document.getElementById('cancelExpenseModal');
    const form=document.getElementById('expenseForm');
    const title=document.getElementById('expenseModalTitle');
    const submit=document.getElementById('expenseSubmit');
    const id=document.getElementById('expenseId');
    const tanggal=document.getElementById('expenseTanggal');
    const kategori=document.getElementById('expenseKategori');
    const keterangan=document.getElementById('expenseKeterangan');
    const nominal=document.getElementById('expenseNominal');
    function resetForm(){
        form.reset();
        id.value='';
        tanggal.value='<?= htmlspecialchars(date('Y-m-d')); ?>';
        title.textContent='Tambah Pengeluaran';
        submit.textContent='Simpan';
        submit.name='tambah_pengeluaran';
    }
    function showAdd(){resetForm();modal.classList.add('show');modal.setAttribute('aria-hidden','false');tanggal.focus();}
    function showEdit(btn){
        id.value=btn.dataset.id;
        tanggal.value=btn.dataset.tanggal;
        kategori.value=btn.dataset.kategori;
        keterangan.value=btn.dataset.keterangan;
        nominal.value=btn.dataset.nominal;
        title.textContent='Edit Pengeluaran';
        submit.textContent='Simpan Perubahan';
        submit.name='edit_pengeluaran';
        modal.classList.add('show');modal.setAttribute('aria-hidden','false');tanggal.focus();
    }
    function hide(){modal.classList.remove('show');modal.setAttribute('aria-hidden','true');}
    open.addEventListener('click',showAdd); close.addEventListener('click',hide); cancel.addEventListener('click',hide);
    modal.addEventListener('click',function(e){if(e.target===modal) hide();});
    document.addEventListener('click',function(e){const btn=e.target.closest('.edit-button');if(btn) showEdit(btn);});
    document.addEventListener('keydown',function(e){if(e.key==='Escape') hide();});
    <?php if ($error): ?>showAdd();<?php endif; ?>
})();
</script>
</body>
</html>