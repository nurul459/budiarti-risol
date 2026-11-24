
<?php

include "auth.php";
include "../config/koneksi.php";
$isModal = isset($_GET["modal"]) && $_GET["modal"] === "1";

/* =========================
   VALIDASI ID PRODUK
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: produk.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   AMBIL DATA PRODUK
========================= */

$query = mysqli_query(
    $koneksi,
    "SELECT * FROM products WHERE id_product = $id"
);

if (!$query || mysqli_num_rows($query) == 0) {
    echo "Produk tidak ditemukan.";
    exit;
}

$product = mysqli_fetch_assoc($query);

$error = '';

/* =========================
   FOLDER FOTO
========================= */

$folder_upload = __DIR__ . "/../assets/images/";

if (!is_dir($folder_upload)) {
    mkdir($folder_upload, 0777, true);
}

/* =========================
   PROSES UPDATE PRODUK
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_product = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['nama_product'] ?? '')
    );

    $harga = (int) ($_POST['harga'] ?? 0);

    $deskripsi = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['deskripsi'] ?? '')
    );

    $berat = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['berat'] ?? '')
    );

    $komposisi = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['komposisi'] ?? '')
    );

    $cocok_untuk = mysqli_real_escape_string(
        $koneksi,
        trim($_POST['cocok_untuk'] ?? '')
    );

    $stok = (int) ($_POST['stok'] ?? 0);

    $id_category = (int) ($_POST['id_category'] ?? 0);

    /* Foto lama */
    $nama_foto_lama = $product['gambar'] ?? ($product['foto'] ?? '');

    $nama_foto_baru = $nama_foto_lama;
    $foto_berhasil_upload = false;

    /* =========================
       VALIDASI INPUT
    ========================= */

    if (
        $nama_product === '' ||
        $harga < 0 ||
        $stok < 0 ||
        $id_category <= 0
    ) {

        $error = "Harap isi data produk dengan benar.";

    }

    /* =========================
       PROSES UPLOAD FOTO BARU
    ========================= */

    if (
        empty($error) &&
        isset($_FILES['foto']) &&
        $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {

            $error = "Foto gagal diunggah. Silakan coba kembali.";

        } else {

            $tmp_name = $_FILES['foto']['tmp_name'];
            $ukuran = $_FILES['foto']['size'];

            if ($ukuran > 2 * 1024 * 1024) {

                $error = "Ukuran foto maksimal 2 MB.";

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

                    $error = "Format foto harus JPG, JPEG, PNG, atau WEBP.";

                } else {

                    $ekstensi = $format_valid[$mime];

                    $nama_foto_baru = 'produk_' .
                        date('YmdHis') . '_' .
                        bin2hex(random_bytes(4)) . '.' .
                        $ekstensi;

                    $tujuan_upload = $folder_upload . $nama_foto_baru;

                    if (move_uploaded_file($tmp_name, $tujuan_upload)) {

                        $foto_berhasil_upload = true;

                    } else {

                        $error = "Gagal menyimpan foto ke folder images.";

                    }
                }
            }
        }
    }

    /* =========================
       UPDATE DATABASE
    ========================= */

    if (empty($error)) {

        $nama_foto_aman = mysqli_real_escape_string(
            $koneksi,
            $nama_foto_baru
        );

        $update = mysqli_query(
            $koneksi,
            "UPDATE products SET
                nama_product = '$nama_product',
                harga = $harga,
                deskripsi = '$deskripsi',
                berat = '$berat',
                komposisi = '$komposisi',
                cocok_untuk = '$cocok_untuk',
                stok = $stok,
                id_category = $id_category,
                gambar = '$nama_foto_aman'
             WHERE id_product = $id"
        );

        /* Jika kolom gambar tidak tersedia, coba kolom foto */
        if (!$update) {

            $update = mysqli_query(
                $koneksi,
                "UPDATE products SET
                    nama_product = '$nama_product',
                    harga = $harga,
                    deskripsi = '$deskripsi',
                    berat = '$berat',
                    komposisi = '$komposisi',
                    cocok_untuk = '$cocok_untuk',
                    stok = $stok,
                    id_category = $id_category,
                    foto = '$nama_foto_aman'
                 WHERE id_product = $id"
            );

        }

        if ($update) {

            /* Hapus foto lama setelah foto baru berhasil disimpan */
            if (
                $foto_berhasil_upload &&
                !empty($nama_foto_lama) &&
                $nama_foto_lama !== 'default.jpg' &&
                $nama_foto_lama !== $nama_foto_baru
            ) {

                $path_foto_lama = $folder_upload . basename($nama_foto_lama);

                if (file_exists($path_foto_lama)) {
                    unlink($path_foto_lama);
                }

            }

            if ($isModal) {
                echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<style id="edit-modal-mode">
body.modal-mode{background:transparent!important;margin:0;padding:0}.modal-mode .container{max-width:none;margin:0;padding:18px}.modal-mode .back-link{display:none}.modal-mode .card{padding:22px;border:0;border-radius:0;box-shadow:none;background:#F8EFE5}.modal-mode .page-head{margin-bottom:18px}.modal-mode .page-head h2{font-size:25px}.modal-mode .section{background:#fff;border-color:#E5D3C3;padding:17px}.modal-mode .photo-area{background:#F8EFE5}.modal-mode .button-area{position:sticky;bottom:0;background:#F8EFE5;padding:12px 0 2px;z-index:2}
@media(max-width:700px){.modal-mode .container{padding:10px}.modal-mode .card{padding:12px}.modal-mode .form-grid{grid-template-columns:1fr}.modal-mode .full{grid-column:auto}.modal-mode .photo-area{flex-direction:column;align-items:flex-start}}
</style>
</head><body><script>window.parent.location.href="produk.php?pesan=edit_berhasil";</script></body></html>';
                exit;
            }
            header("Location: produk.php?pesan=edit_berhasil");
            exit;

        } else {

            $error = "Gagal mengubah produk: " . mysqli_error($koneksi);

            /* Bersihkan foto baru jika update gagal */
            if (
                $foto_berhasil_upload &&
                file_exists($folder_upload . $nama_foto_baru)
            ) {
                unlink($folder_upload . $nama_foto_baru);
            }

        }
    }

    /* Pertahankan data yang diinput jika terjadi error */
    $product['nama_product'] = $_POST['nama_product'];
    $product['harga'] = $_POST['harga'];
    $product['deskripsi'] = $_POST['deskripsi'];
    $product['berat'] = $_POST['berat'];
    $product['komposisi'] = $_POST['komposisi'];
    $product['cocok_untuk'] = $_POST['cocok_untuk'];
    $product['stok'] = $_POST['stok'];
    $product['id_category'] = $_POST['id_category'];

    if ($foto_berhasil_upload && !empty($error)) {
        $product['gambar'] = $nama_foto_lama;
        $product['foto'] = $nama_foto_lama;
    }

}

/* =========================
   AMBIL KATEGORI
========================= */

$categories = mysqli_query(
    $koneksi,
    "SELECT * FROM categories ORDER BY nama_category ASC"
);

/* =========================
   TENTUKAN FOTO PRODUK
========================= */

$foto_tampil = $product['gambar'] ?? '';

if (empty($foto_tampil)) {
    $foto_tampil = $product['foto'] ?? '';
}

if (empty($foto_tampil)) {
    $foto_tampil = 'default.jpg';
}

$foto_tampil = basename($foto_tampil);

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Produk - Budiarti Risol</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:'DM Sans',sans-serif;background:#F8EFE5;color:#542D1C}
.container{max-width:980px;margin:36px auto;padding:0 20px}
.back-link{display:inline-block;margin-bottom:14px;color:#7c624f;font-size:13px;font-weight:600;text-decoration:none}.back-link:hover{color:#542D1C}
.card{background:#fff;border-radius:14px;padding:30px;box-shadow:0 4px 15px rgba(0,0,0,.07)}
.page-head{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:24px}.page-head h2{font-family:'Playfair Display',serif;margin:0;color:#5b2c1f;font-size:28px}.page-head p{margin:6px 0 0;color:#8a7669;font-size:13px;line-height:1.5}
.error{background:#ffe5e5;color:#a00000;padding:12px;border-radius:8px;margin-bottom:20px;font-size:13px}
.section{border:1px solid #eee1d7;border-radius:12px;padding:20px;margin-bottom:18px;background:#fffdfb}.section-title{font-family:'Playfair Display',serif;font-size:18px;margin:0 0 16px;color:#5b2c1f}.section-note{font-size:12px;color:#8a7669;margin:-9px 0 16px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.full{grid-column:1/-1}.form-group{margin:0}label{display:block;margin-bottom:8px;font-weight:700;font-size:13px;color:#5b2c1f}
input,textarea,select{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;background:#fff}input:focus,textarea:focus,select:focus{border-color:#b4772d;box-shadow:0 0 0 3px rgba(180,119,45,.08)}textarea{min-height:105px;resize:vertical}
.photo-area{display:flex;align-items:center;gap:20px;padding:18px;background:#F8EFE5;border:1px dashed #dfcdbd;border-radius:10px}.photo-preview{width:130px;height:130px;object-fit:cover;border-radius:9px;border:1px solid #e5dbd2;background:#f5eee7;flex-shrink:0}.photo-input{border:none;padding:0;background:transparent;font-size:12px}.photo-input::file-selector-button{background:#5b2c1f;color:#fff;border:none;border-radius:7px;padding:10px 14px;cursor:pointer;margin-right:10px;font-family:'DM Sans',sans-serif}.photo-note{font-size:11px;color:#8a7669;margin:10px 0 0;line-height:1.6}
.button-area{display:flex;gap:10px;margin-top:8px}button,.btn-kembali{padding:12px 20px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif}button{background:#5b2c1f;color:#fff}button:hover{background:#7a3d2b}.btn-kembali{background:#eee8e2;color:#5b2c1f}.btn-kembali:hover{background:#e4d9cf}
@media(max-width:700px){.container{margin:20px auto}.card{padding:20px}.form-grid{grid-template-columns:1fr}.full{grid-column:auto}.photo-area{flex-direction:column;align-items:flex-start}.page-head{align-items:flex-start;flex-direction:column}.button-area{flex-direction:column}.button-area button,.button-area .btn-kembali{width:100%}}
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
<body class="<?= $isModal ? 'modal-mode' : ''; ?>">
<div class="container">
<a href="produk.php" class="back-link">← Kembali ke Daftar Produk</a>
<div class="card">
<div class="page-head"><div><h2>Edit Produk</h2><p>Perbarui informasi produk, harga, stok, dan detail lainnya.</p></div></div>
<?php if (!empty($error)) : ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<div class="section">
<h3 class="section-title">Informasi Produk</h3>
<div class="form-grid">
<div class="form-group"><label>Nama Produk</label><input type="text" name="nama_product" value="<?= htmlspecialchars($product['nama_product'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
<div class="form-group"><label>Harga</label><input type="number" name="harga" value="<?= htmlspecialchars($product['harga'], ENT_QUOTES, 'UTF-8'); ?>" min="0" required></div>
<div class="form-group"><label>Kategori</label><select name="id_category" required><option value="">-- Pilih Kategori --</option><?php while ($category = mysqli_fetch_assoc($categories)) : ?><option value="<?= (int) $category['id_category']; ?>" <?= ($category['id_category'] == $product['id_category']) ? 'selected' : ''; ?>><?= htmlspecialchars($category['nama_category'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
<div class="form-group"><label>Stok</label><input type="number" name="stok" value="<?= htmlspecialchars($product['stok'], ENT_QUOTES, 'UTF-8'); ?>" min="0" required></div>
</div></div>
<div class="section">
<h3 class="section-title">Detail Produk</h3>
<div class="form-grid">
<div class="form-group full"><label>Deskripsi Singkat</label><textarea name="deskripsi" required><?= htmlspecialchars($product['deskripsi'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
<div class="form-group"><label>Berat Bersih</label><input type="text" name="berat" value="<?= htmlspecialchars($product['berat'] ?? '50 gram/pcs', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Contoh: 50 gram/pcs" required></div>
<div class="form-group"><label>Cocok untuk</label><textarea name="cocok_untuk" placeholder="Contoh: Camilan keluarga atau teman minum teh" required><?= htmlspecialchars($product['cocok_untuk'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
<div class="form-group full"><label>Komposisi</label><textarea name="komposisi" placeholder="Masukkan komposisi produk" required><?= htmlspecialchars($product['komposisi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
</div></div>
<div class="section">
<h3 class="section-title">Foto Produk</h3><p class="section-note">Foto saat ini bisa diganti kapan saja.</p>
<div class="photo-area"><img src="../assets/images/<?= htmlspecialchars($foto_tampil, ENT_QUOTES, 'UTF-8'); ?>" id="previewFoto" class="photo-preview" alt="Foto Produk" onerror="this.style.objectFit='contain';this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22130%22 height=%22130%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23f5eee7%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%238a7669%22 font-size=%2212%22%3ENo Image%3C/text%3E%3C/svg%3E';"><div style="width:100%"><input type="file" name="foto" id="fotoInput" class="photo-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" onchange="previewImage(event)"><p class="photo-note">Format JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.</p></div></div>
</div>
<div class="button-area"><button type="submit">Simpan Perubahan</button><a href="produk.php" class="btn-kembali">Kembali</a></div>
</form></div></div>
<script>
function previewImage(event){const file=event.target.files[0];if(!file)return;const allowed=['image/jpeg','image/png','image/webp'];if(!allowed.includes(file.type)){alert('Format foto harus JPG, JPEG, PNG, atau WEBP.');event.target.value='';return}if(file.size>2*1024*1024){alert('Ukuran foto maksimal 2 MB.');event.target.value='';return}const reader=new FileReader();reader.onload=e=>document.getElementById('previewFoto').src=e.target.result;reader.readAsDataURL(file)}
</script>
</body></html>
