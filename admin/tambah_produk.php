
<?php
include "auth.php";
include "../config/koneksi.php";
$isModal = isset($_GET["modal"]) && $_GET["modal"] === "1";

/* =========================
   FOLDER UPLOAD
========================= */

$folder_upload = __DIR__ . "/../assets/images/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

/* =========================
   DATA KATEGORI
========================= */

$query_kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM categories ORDER BY id_category ASC"
);

if (!$query_kategori) {
    die("Gagal mengambil kategori: " . mysqli_error($koneksi));
}

$pesan_error = '';

/* =========================
   PROSES SIMPAN PRODUK
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_produk'])) {

    $nama_product = trim($_POST['nama_product'] ?? '');
    $id_category  = (int) ($_POST['id_category'] ?? 0);
    $harga        = (int) ($_POST['harga'] ?? 0);
    $stok         = (int) ($_POST['stok'] ?? 0);
    $deskripsi    = trim($_POST['deskripsi'] ?? '');

    if ($nama_product === '' || $id_category <= 0 || $harga <= 0 || $stok < 0) {

        $pesan_error = "Harap isi semua kolom wajib dengan benar!";

    } else {

        $nama_foto_db = 'default.jpg';
        $foto_berhasil_upload = false;

        /* =========================
           UPLOAD FOTO
        ========================= */

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {

                $pesan_error = "Foto gagal diunggah. Silakan coba kembali.";

            } else {

                $tmp_name = $_FILES['foto']['tmp_name'];
                $ukuran = $_FILES['foto']['size'];

                if ($ukuran > 2 * 1024 * 1024) {

                    $pesan_error = "Ukuran foto terlalu besar. Maksimal 2 MB.";

                } else {

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);

                    $format_valid = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (!isset($format_valid[$mime])) {

                        $pesan_error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";

                    } else {

                        $ekstensi = $format_valid[$mime];

                        $nama_foto_baru = 'produk_' .
                            date('YmdHis') . '_' .
                            bin2hex(random_bytes(4)) . '.' .
                            $ekstensi;

                        $tujuan_upload = $folder_upload . $nama_foto_baru;

                        if (move_uploaded_file($tmp_name, $tujuan_upload)) {

                            $nama_foto_db = $nama_foto_baru;
                            $foto_berhasil_upload = true;

                        } else {

                            $pesan_error = "Gagal menyimpan foto ke folder images.";

                        }
                    }
                }
            }
        }

        /* =========================
           SIMPAN KE DATABASE
        ========================= */

        if (empty($pesan_error)) {

            $nama_aman = mysqli_real_escape_string($koneksi, $nama_product);
            $deskripsi_aman = mysqli_real_escape_string($koneksi, $deskripsi);
            $foto_aman = mysqli_real_escape_string($koneksi, $nama_foto_db);

            /* Gunakan kolom gambar terlebih dahulu */
            $sql = "INSERT INTO products
                    (nama_product, id_category, harga, stok, deskripsi, gambar)
                    VALUES
                    ('$nama_aman', $id_category, $harga, $stok, '$deskripsi_aman', '$foto_aman')";

            if (mysqli_query($koneksi, $sql)) {

                if ($isModal) { echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<style id="tambah-modal-mode">
body.modal-mode{background:#F8EFE5!important;margin:0!important;padding:0!important;width:100%!important;overflow-x:hidden!important}.modal-mode .sidebar,.modal-mode .top-header,.modal-mode > .sidebar,.modal-mode > .top-header{display:none!important}.modal-mode .admin-wrapper{display:block!important;min-height:0!important;width:100%!important;margin:0!important;padding:0!important}.modal-mode .main-content{display:block!important;margin:0!important;padding:0!important;width:100%!important;min-height:0!important}.modal-mode .page-content{box-sizing:border-box!important;width:100%!important;max-width:none!important;margin:0!important;padding:28px 40px 35px!important}.modal-mode .back-link{display:none}.modal-mode .page-title{width:100%!important}.modal-mode .page-title h1{font-size:25px}.modal-mode .page-title p{margin-bottom:22px}.modal-mode .form-card{box-sizing:border-box!important;width:100%!important;max-width:none!important;margin:0!important;padding:28px!important;border:1px solid #E5D3C3;box-shadow:none;background:#fff;border-radius:14px}.modal-mode .form-actions{position:sticky;bottom:0;background:#F8EFE5;padding:12px 0 2px;z-index:2}.modal-mode .btn-cancel{border:0;cursor:pointer;font-family:inherit}
@media(max-width:750px){.modal-mode .page-content{padding:20px 16px 24px!important}.modal-mode .form-card{padding:18px!important}.modal-mode .form-grid{grid-template-columns:1fr}.modal-mode .form-group.full{grid-column:span 1}.modal-mode .upload-area{align-items:flex-start;flex-direction:column}}
</style>
</head><body><script>window.parent.location.href="produk.php?pesan=tambah_berhasil";</script></body></html>'; exit; }
                header("Location: produk.php?pesan=tambah_berhasil");
                exit;

            } else {

                /* Jika kolom gambar tidak tersedia, coba kolom foto */
                $sql_fallback = "INSERT INTO products
                    (nama_product, id_category, harga, stok, deskripsi, foto)
                    VALUES
                    ('$nama_aman', $id_category, $harga, $stok, '$deskripsi_aman', '$foto_aman')";

                if (mysqli_query($koneksi, $sql_fallback)) {

                    if ($isModal) { echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"></head><body><script>window.parent.location.href="produk.php?pesan=tambah_berhasil";</script></body></html>'; exit; }
                    header("Location: produk.php?pesan=tambah_berhasil");
                    exit;

                } else {

                    $pesan_error = "Gagal menyimpan produk. Periksa kolom gambar/foto pada tabel products.";

                    if (
                        $foto_berhasil_upload &&
                        file_exists($folder_upload . $nama_foto_db)
                    ) {
                        unlink($folder_upload . $nama_foto_db);
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Produk - Budiarti Risol</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', Arial, sans-serif;
            background: #F8EFE5;
            color: #542D1C;
        }

        a {
            text-decoration: none;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 230px;
            background: #542D1C;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .sidebar-logo {
            text-align: center;
            padding-bottom: 30px;
        }

        .sidebar-logo .logo-icon {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .sidebar-logo h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: white;
        }

        .sidebar-logo p {
            font-family: 'DM Sans', sans-serif;
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 1.5px;
            color: #EBD3C0;
            margin-top: 5px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 8px;
            color: #f6e8dc;
            font-size: 13px;
            transition: 0.2s;
        }

        .sidebar-menu a:hover {
            background: #633419;
        }

        .sidebar-menu a.active {
            background: #75401f;
            color: white;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        /* MAIN */

        .main-content {
            margin-left: 230px;
            width: calc(100% - 230px);
            min-height: 100vh;
        }

        /* HEADER */

        .top-header {
            height: 70px;
            background: #C99B78;
            border-bottom: 1px solid #eee3d8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #542D1C;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #EBD3C0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        /* CONTENT */

        .page-content {
            padding: 35px;
            max-width: 850px;
        }

        .back-link {
            display: inline-block;
            color: #7c624f;
            font-size: 12px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .back-link:hover {
            color: #542D1C;
        }

        .page-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 27px;
            color: #542D1C;
            margin-bottom: 7px;
        }

        .page-title p {
            color: #8a7669;
            font-size: 13px;
            margin-bottom: 25px;
        }

        /* FORM CARD */

        .form-card {
            background: white;
            border: 1px solid #eee4db;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 3px 12px rgba(80, 40, 10, 0.04);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: span 2;
        }

        label {
            font-size: 11px;
            font-weight: bold;
            color: #5b4a40;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e5dbd2;
            border-radius: 8px;
            font-size: 13px;
            color: #542D1C;
            background: #fffdfb;
            outline: none;
            transition: 0.2s;
            font-family: 'DM Sans', sans-serif;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #b4772d;
            background: #ffffff;
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* UPLOAD FOTO */

        .upload-area {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #F8EFE5;
            border: 1px dashed #e5dbd2;
            padding: 15px;
            border-radius: 10px;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            border-radius: 8px;
            background: #f1ebe5;
            object-fit: cover;
            border: 1px solid #e0d4c8;
        }

        .upload-input {
            width: 100%;
            font-size: 12px;
            color: #5b4a40;
        }

        .upload-input::file-selector-button {
            background: #542D1C;
            color: white;
            border: none;
            padding: 9px 13px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 8px;
            font-size: 11px;
        }

        .upload-input::file-selector-button:hover {
            background: #6a3418;
        }

        /* FORM ACTIONS */

        .form-actions {
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-submit {
            background: #542D1C;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background: #6a3418;
        }

        .btn-cancel {
            color: #8a7669;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 18px;
        }

        .btn-cancel:hover {
            color: #542D1C;
        }

        /* ERROR */

        .alert-error {
            background: #fde3e5;
            color: #a52c38;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* RESPONSIVE */

        @media (max-width: 750px) {

            .sidebar {
                width: 190px;
            }

            .main-content {
                margin-left: 190px;
                width: calc(100% - 190px);
            }

            .page-content {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: span 1;
            }

            .upload-area {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

<link rel="stylesheet" href="../assets/css/admin-sidebar.css"><link rel="stylesheet" href="../assets/css/admin-topbar.css">

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

<!-- Penyesuaian khusus saat form dibuka di dalam modal produk -->
<style id="tambah-produk-modal-layout">
body.modal-mode{
    width:100% !important;
    min-width:0 !important;
    margin:0 !important;
    padding:0 !important;
    overflow-x:hidden !important;
    background:#F8EFE5 !important;
}
body.modal-mode .admin-wrapper{
    display:block !important;
    width:100% !important;
    min-height:0 !important;
    margin:0 !important;
    padding:0 !important;
}
body.modal-mode .main-content{
    display:block !important;
    width:100% !important;
    min-height:0 !important;
    margin:0 !important;
    padding:0 !important;
}
body.modal-mode .page-content{
    box-sizing:border-box !important;
    display:block !important;
    width:100% !important;
    max-width:none !important;
    margin:0 !important;
    padding:28px 40px 35px !important;
}
body.modal-mode .page-title,
body.modal-mode .form-card{
    box-sizing:border-box !important;
    width:100% !important;
    max-width:none !important;
    margin-left:0 !important;
    margin-right:0 !important;
}
body.modal-mode .form-card{
    padding:28px !important;
}
@media(max-width:750px){
    body.modal-mode .page-content{padding:20px 16px 24px !important;}
    body.modal-mode .form-card{padding:18px !important;}
    body.modal-mode .form-grid{grid-template-columns:1fr !important;}
    body.modal-mode .form-group.full{grid-column:span 1 !important;}
    body.modal-mode .upload-area{align-items:flex-start;flex-direction:column;}
}
</style>
</head>

<body class="<?= $isModal ? 'modal-mode' : ''; ?>">

<div class="admin-wrapper">

    <?php if (!$isModal): ?>

        <!-- SIDEBAR -->
        <?php include __DIR__ . '/sidebar.php'; ?>

    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <?php if (!$isModal): ?>

            <!-- HEADER -->
            <header class="top-header">
                <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
                <div class="admin-profile">
                    <div class="avatar" aria-hidden="true">👤</div>
                    <span class="admin-name">Admin</span>
                </div>
            </header>

        <?php endif; ?>

        <!-- PAGE CONTENT -->

        <section class="page-content">

            <a href="produk.php" class="back-link">
                ← Kembali ke Daftar Produk
            </a>

            <div class="page-title">

                <h1>Tambah Produk Baru</h1>

                <p>
                    Isi rincian informasi produk makanan dan unggah foto dari perangkat.
                </p>

            </div>

            <?php if (!empty($pesan_error)) : ?>

                <div class="alert-error">
                    <?= htmlspecialchars($pesan_error, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php endif; ?>

            <div class="form-card">

                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="form-grid">

                        <div class="form-group full">

                            <label>Nama Produk *</label>

                            <input
                                type="text"
                                name="nama_product"
                                placeholder="Contoh: Risol Mayo Smoked Beef"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Kategori *</label>

                            <select name="id_category" required>

                                <option value="">-- Pilih Kategori --</option>

                                <?php while ($cat = mysqli_fetch_assoc($query_kategori)) : ?>

                                    <option value="<?= (int) $cat['id_category']; ?>">

                                        <?= htmlspecialchars(
                                            $cat['nama_category'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>Harga (Rp) *</label>

                            <input
                                type="number"
                                name="harga"
                                placeholder="Contoh: 15000"
                                min="1"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Stok Awal *</label>

                            <input
                                type="number"
                                name="stok"
                                value="0"
                                min="0"
                                required
                            >

                        </div>

                        <!-- UPLOAD FOTO -->

                        <div class="form-group full">

                            <label>Foto Produk</label>

                            <div class="upload-area">

                                <img
                                    id="preview"
                                    src="../assets/images/default.jpg"
                                    alt="Preview Foto Produk"
                                    class="image-preview"
                                >

                                <div style="width:100%;">

                                    <input
                                        type="file"
                                        name="foto"
                                        id="fotoInput"
                                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                        class="upload-input"
                                        onchange="previewImage(event)"
                                    >

                                    <p style="font-size:10px; color:#9a8779; margin-top:8px;">
                                        Pilih foto dari perangkat kamu.
                                        Format JPG, JPEG, PNG, WEBP.
                                        Maksimal 2 MB.
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="form-group full">

                            <label>Deskripsi Produk</label>

                            <textarea
                                name="deskripsi"
                                placeholder="Tuliskan varian rasa, porsi, atau catatan penting lainnya..."
                            ></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <button
                            type="submit"
                            name="simpan_produk"
                            class="btn-submit"
                        >
                            Simpan Produk
                        </button>

                        <?php if ($isModal) : ?>
                        <button type="button" class="btn-cancel" onclick="window.parent.tutupModalProduk()">Batal</button>
                        <?php else : ?>
                        <a href="produk.php" class="btn-cancel">Batal</a>
                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

<!-- JAVASCRIPT PREVIEW FOTO -->

<script>

function previewImage(event) {

    const file = event.target.files[0];

    if (!file) {
        return;
    }

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    if (!allowedTypes.includes(file.type)) {

        alert('Format foto harus JPG, JPEG, PNG, atau WEBP.');

        event.target.value = '';

        return;
    }

    if (file.size > 2 * 1024 * 1024) {

        alert('Ukuran foto maksimal 2 MB.');

        event.target.value = '';

        return;
    }

    const reader = new FileReader();

    reader.onload = function(e) {

        document.getElementById('preview').src = e.target.result;

    };

    reader.readAsDataURL(file);

}

</script>

</body>
</html>
