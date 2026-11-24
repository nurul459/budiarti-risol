<?php

include "auth.php";
include "../config/koneksi.php";

$id_admin = (int) $_SESSION['id_user'];

/* =========================
   PASTIKAN KOLOM BRAND TERSEDIA
========================= */
function ensure_brand_columns($koneksi) {
    $columns = ['logo', 'favicon'];

    foreach ($columns as $column) {
        $safe_column = mysqli_real_escape_string($koneksi, $column);
        $check = mysqli_query(
            $koneksi,
            "SELECT COUNT(*) AS jumlah
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'settings'
             AND COLUMN_NAME = '$safe_column'"
        );

        if ($check) {
            $row = mysqli_fetch_assoc($check);
            if ((int) ($row['jumlah'] ?? 0) === 0) {
                $type = $column === 'favicon'
                    ? "VARCHAR(255) NULL AFTER logo"
                    : "VARCHAR(255) NULL AFTER alamat";
                mysqli_query($koneksi, "ALTER TABLE settings ADD COLUMN $column $type");
            }
        }
    }
}

ensure_brand_columns($koneksi);

/* =========================
   KONFIGURASI UPLOAD BRAND
========================= */
$upload_dir = realpath(__DIR__ . '/../assets/uploads/brand');
if ($upload_dir === false) {
    $upload_dir = __DIR__ . '/../assets/uploads/brand';
    @mkdir($upload_dir, 0755, true);
}
$upload_url = '../assets/uploads/brand/';
$max_file_size = 2 * 1024 * 1024; // 2 MB
$allowed_logo_mimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];
$allowed_favicon_mimes = [
    'image/png'  => 'png',
    'image/x-icon' => 'ico',
    'image/vnd.microsoft.icon' => 'ico',
    'image/jpeg' => 'jpg'
];

function hapus_file_brand($filename) {
    if (empty($filename)) {
        return;
    }

    $path = __DIR__ . '/../assets/uploads/brand/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function upload_brand_image($file, $prefix, $allowed_mimes, $max_file_size, $upload_dir) {
    if (!isset($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'empty' => true, 'message' => ''];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'empty' => false, 'message' => 'Gagal mengunggah file.'];
    }

    if (($file['size'] ?? 0) > $max_file_size) {
        return ['success' => false, 'empty' => false, 'message' => 'Ukuran file maksimal 2 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowed_mimes[$mime])) {
        return ['success' => false, 'empty' => false, 'message' => 'Format gambar tidak didukung.'];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'empty' => false, 'message' => 'File yang dipilih bukan gambar yang valid.'];
    }

    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true)) {
        return ['success' => false, 'empty' => false, 'message' => 'Folder upload tidak dapat dibuat.'];
    }

    $extension = $allowed_mimes[$mime];
    $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'empty' => false, 'message' => 'File gagal disimpan.'];
    }

    return ['success' => true, 'empty' => false, 'message' => '', 'filename' => $filename];
}

/* =========================
   PROSES SIMPAN PROFIL ADMIN
========================= */
$pesan_profil = '';

if (isset($_POST['simpan_profil'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($nama == '' || $email == '') {
        $pesan_profil = "Nama dan email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_profil = "Format email tidak valid.";
    } else {
        $stmt_cek = mysqli_prepare(
            $koneksi,
            "SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt_cek, "si", $email, $id_admin);
        mysqli_stmt_execute($stmt_cek);
        $hasil_cek = mysqli_stmt_get_result($stmt_cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $pesan_profil = "Email sudah digunakan oleh pengguna lain.";
        } else {
            $stmt_update = mysqli_prepare(
                $koneksi,
                "UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id_user = ?"
            );
            mysqli_stmt_bind_param($stmt_update, "sssi", $nama, $email, $no_hp, $id_admin);

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $pesan_profil = "Profil admin berhasil diperbarui.";
            } else {
                $pesan_profil = "Profil admin gagal diperbarui.";
            }
            mysqli_stmt_close($stmt_update);
        }
        mysqli_stmt_close($stmt_cek);
    }
}

/* =========================
   PROSES HAPUS LOGO / FAVICON
========================= */
$pesan_brand = '';

if (isset($_POST['hapus_logo']) || isset($_POST['hapus_favicon'])) {
    $field = isset($_POST['hapus_logo']) ? 'logo' : 'favicon';

    $query_old = mysqli_query($koneksi, "SELECT id_setting, $field FROM settings LIMIT 1");
    $old = $query_old ? mysqli_fetch_assoc($query_old) : null;

    if ($old) {
        $id_setting = (int) $old['id_setting'];
        $old_file = $old[$field] ?? '';

        $stmt = mysqli_prepare($koneksi, "UPDATE settings SET $field = NULL WHERE id_setting = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_setting);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
            hapus_file_brand($old_file);
            $pesan_brand = ucfirst($field) . " berhasil dihapus.";
        } else {
            $pesan_brand = "Gagal menghapus " . $field . ".";
        }
    } else {
        $pesan_brand = "Data pengaturan toko belum tersedia.";
    }
}

/* =========================
   PROSES SIMPAN INFORMASI TOKO + BRAND
========================= */
$pesan_toko = '';

if (isset($_POST['simpan_toko'])) {
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $slogan = trim($_POST['slogan'] ?? '');
    $no_toko = trim($_POST['no_toko'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    $logo_upload = upload_brand_image(
        $_FILES['logo'] ?? null,
        'logo',
        $allowed_logo_mimes,
        $max_file_size,
        $upload_dir
    );
    $favicon_upload = upload_brand_image(
        $_FILES['favicon'] ?? null,
        'favicon',
        $allowed_favicon_mimes,
        $max_file_size,
        $upload_dir
    );

    if (!$logo_upload['empty'] && !$logo_upload['success']) {
        $pesan_toko = $logo_upload['message'];
    } elseif (!$favicon_upload['empty'] && !$favicon_upload['success']) {
        $pesan_toko = $favicon_upload['message'];
    } elseif ($nama_toko == '') {
        $pesan_toko = "Nama toko wajib diisi.";
    } else {
        $cek_setting = mysqli_query($koneksi, "SELECT id_setting, logo, favicon FROM settings LIMIT 1");

        if (!$cek_setting) {
            $pesan_toko = "Gagal memeriksa informasi toko.";
        } elseif (mysqli_num_rows($cek_setting) > 0) {
            $setting = mysqli_fetch_assoc($cek_setting);
            $id_setting = (int) $setting['id_setting'];
            $logo_lama = $setting['logo'] ?? '';
            $favicon_lama = $setting['favicon'] ?? '';
            $logo_baru = $logo_upload['success'] ? $logo_upload['filename'] : $logo_lama;
            $favicon_baru = $favicon_upload['success'] ? $favicon_upload['filename'] : $favicon_lama;

            $stmt_toko = mysqli_prepare(
                $koneksi,
                "UPDATE settings SET nama_toko = ?, slogan = ?, no_hp = ?, alamat = ?, logo = ?, favicon = ? WHERE id_setting = ?"
            );
            mysqli_stmt_bind_param($stmt_toko, "ssssssi", $nama_toko, $slogan, $no_toko, $alamat, $logo_baru, $favicon_baru, $id_setting);
            $update_toko = mysqli_stmt_execute($stmt_toko);
            mysqli_stmt_close($stmt_toko);

            if ($update_toko) {
                if ($logo_upload['success'] && $logo_lama && $logo_lama !== $logo_baru) {
                    hapus_file_brand($logo_lama);
                }
                if ($favicon_upload['success'] && $favicon_lama && $favicon_lama !== $favicon_baru) {
                    hapus_file_brand($favicon_lama);
                }
                $pesan_toko = "Informasi toko berhasil diperbarui.";
            } else {
                if ($logo_upload['success']) hapus_file_brand($logo_upload['filename']);
                if ($favicon_upload['success']) hapus_file_brand($favicon_upload['filename']);
                $pesan_toko = "Informasi toko gagal diperbarui.";
            }
        } else {
            $logo_baru = $logo_upload['success'] ? $logo_upload['filename'] : null;
            $favicon_baru = $favicon_upload['success'] ? $favicon_upload['filename'] : null;

            $stmt_toko = mysqli_prepare(
                $koneksi,
                "INSERT INTO settings (nama_toko, slogan, no_hp, alamat, logo, favicon) VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_toko, "ssssss", $nama_toko, $slogan, $no_toko, $alamat, $logo_baru, $favicon_baru);
            $update_toko = mysqli_stmt_execute($stmt_toko);
            mysqli_stmt_close($stmt_toko);

            if ($update_toko) {
                $pesan_toko = "Informasi toko berhasil diperbarui.";
            } else {
                if ($logo_upload['success']) hapus_file_brand($logo_upload['filename']);
                if ($favicon_upload['success']) hapus_file_brand($favicon_upload['filename']);
                $pesan_toko = "Informasi toko gagal diperbarui.";
            }
        }
    }
}

/* =========================
   AMBIL DATA ADMIN & TOKO
========================= */
$query_admin = mysqli_query($koneksi, "SELECT nama, email, no_hp, role FROM users WHERE id_user = $id_admin LIMIT 1");
$data_admin = mysqli_fetch_assoc($query_admin);

$query_toko = mysqli_query($koneksi, "SELECT * FROM settings LIMIT 1");
$data_toko = $query_toko ? mysqli_fetch_assoc($query_toko) : null;

if (!$data_toko) {
    $data_toko = [
        'nama_toko' => 'Budiarti Risol',
        'slogan' => 'Risol Lezat, Setiap Saat',
        'no_hp' => '',
        'alamat' => '',
        'logo' => '',
        'favicon' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($data_toko['favicon'])) : ?>
        <link rel="icon" type="image/png" href="<?= htmlspecialchars($upload_url . basename($data_toko['favicon'])); ?>">
    <?php endif; ?>
    <title>Pengaturan - <?= htmlspecialchars($data_toko['nama_toko'] ?? 'Budiarti Risol'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #F8EFE5; color: #542D1C; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 230px; background: #542D1C; color: white; position: fixed; left: 0; top: 0; bottom: 0; padding: 25px 15px; overflow-y: auto; }
        .sidebar-logo { text-align: center; padding-bottom: 30px; }
        .sidebar-logo .brand-image { width: 74px; height: 54px; object-fit: contain; margin: 0 auto 8px; display: block; }
        .sidebar-logo .logo-icon { font-size: 32px; margin-bottom: 5px; }
        .sidebar-logo h2 { font-family: Georgia, serif; font-size: 22px; font-style: italic; color: white; }
        .sidebar-logo p { font-size: 9px; letter-spacing: 1px; color: #EBD3C0; margin-top: 3px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 7px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 8px; color: #f6e8dc; font-size: 13px; text-decoration: none; transition: 0.2s; }
        .sidebar-menu a:hover { background: #633419; }
        .sidebar-menu a.active { background: #75401f; color: white; }
        .menu-icon { width: 20px; text-align: center; font-size: 15px; }
        .main-content { margin-left: 230px; width: calc(100% - 230px); min-height: 100vh; }
        .top-header { height: 70px; background: #C99B78; border-bottom: 1px solid #eee3d8; display: flex; align-items: center; justify-content: space-between; padding: 0 35px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; color: #5b2c1f; font-size: 13px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: #d9ad63; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .admin-name { font-weight: 600; }
        .page-content { padding: 35px; }
        .page-header { margin-bottom: 25px; }
        .page-title h1 { font-family: Georgia, serif; font-size: 27px; color: #542D1C; margin-bottom: 6px; }
        .page-title p { font-size: 13px; color: #8b7668; }
        .setting-card { background: white; border: 1px solid #eee3d8; border-radius: 14px; padding: 25px; margin-bottom: 22px; box-shadow: 0 3px 12px rgba(70, 35, 15, 0.04); }
        .card-title { font-family: Georgia, serif; font-size: 19px; color: #542D1C; margin-bottom: 5px; }
        .card-description { font-size: 12px; color: #8b7668; margin-bottom: 22px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; gap: 7px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 12px; font-weight: 600; color: #5b2c1f; }
        .form-group input, .form-group textarea { width: 100%; border: 1px solid #dfd1c4; border-radius: 8px; padding: 11px 13px; font-family: Arial, sans-serif; font-size: 13px; color: #542D1C; background: #fffdf9; outline: none; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .button-area { margin-top: 22px; display: flex; justify-content: flex-end; }
        .save-button { border: none; background: #6b3518; color: white; padding: 11px 20px; border-radius: 8px; font-size: 13px; cursor: pointer; }
        .save-button:hover { background: #54270f; }
        .danger-button { border: 1px solid #d9b4aa; background: #fff7f5; color: #a33b2b; padding: 9px 13px; border-radius: 8px; font-size: 12px; cursor: pointer; }
        .danger-button:hover { background: #fff0ed; }
        .success-message { background: #edf7ee; color: #36713d; border: 1px solid #cfe6d1; padding: 11px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 18px; }
        .error-message { background: #fff0ed; color: #a33b2b; border: 1px solid #f0c9c2; padding: 11px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 18px; }
        .brand-box { border: 1px dashed #d9c6b6; background: #fffaf4; border-radius: 10px; padding: 15px; min-height: 180px; display: flex; flex-direction: column; justify-content: space-between; gap: 12px; }
        .brand-preview { min-height: 90px; display: flex; align-items: center; justify-content: center; padding: 10px; }
        .brand-preview img.logo-preview { max-width: 220px; max-height: 90px; object-fit: contain; }
        .brand-preview img.favicon-preview { width: 64px; height: 64px; object-fit: contain; image-rendering: auto; }
        .empty-preview { color: #a58e7f; font-size: 12px; text-align: center; }
        .brand-actions { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .file-input { font-size: 12px; max-width: 100%; }
        .hint { color: #8b7668; font-size: 11px; line-height: 1.5; }
        @media (max-width: 800px) {
            .sidebar { position: static; width: 100%; min-height: auto; }
            .admin-wrapper { display: block; }
            .main-content { margin-left: 0; width: 100%; }
            .top-header { padding: 0 20px; }
            .page-content { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: auto; }
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
            <div class="page-header">
                <div class="page-title">
                    <h1>Pengaturan</h1>
                    <p>Kelola profil admin, identitas toko, logo, favicon, dan informasi kontak Budiarti Risol.</p>
                </div>
            </div>

            <?php if ($pesan_brand != '') : ?>
                <div class="<?= strpos($pesan_brand, 'berhasil') !== false ? 'success-message' : 'error-message'; ?>">
                    <?= htmlspecialchars($pesan_brand); ?>
                </div>
            <?php endif; ?>

            <!-- PROFIL ADMIN -->
            <div class="setting-card">
                <h2 class="card-title">Profil Admin</h2>
                <p class="card-description">Atur informasi akun admin yang digunakan untuk mengelola sistem.</p>
                <?php if ($pesan_profil != '') : ?>
                    <div class="<?= strpos($pesan_profil, 'berhasil') !== false ? 'success-message' : 'error-message'; ?>">
                        <?= htmlspecialchars($pesan_profil); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Admin</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($data_admin['nama'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($data_admin['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>No. HP</label>
                            <input type="text" name="no_hp" value="<?= htmlspecialchars($data_admin['no_hp'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="<?= htmlspecialchars(ucfirst($data_admin['role'] ?? 'Admin')); ?>" readonly>
                        </div>
                    </div>
                    <div class="button-area">
                        <button type="submit" name="simpan_profil" class="save-button">Simpan Profil</button>
                    </div>
                </form>
            </div>

            <!-- INFORMASI TOKO -->
            <div class="setting-card">
                <h2 class="card-title">Informasi Toko</h2>
                <p class="card-description">Informasi umum toko, identitas brand, dan kontak yang digunakan pada website.</p>
                <?php if ($pesan_toko != '') : ?>
                    <div class="<?= strpos($pesan_toko, 'berhasil') !== false ? 'success-message' : 'error-message'; ?>">
                        <?= htmlspecialchars($pesan_toko); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Toko</label>
                            <input type="text" name="nama_toko" value="<?= htmlspecialchars($data_toko['nama_toko'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Slogan</label>
                            <input type="text" name="slogan" value="<?= htmlspecialchars($data_toko['slogan'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>No. HP Toko</label>
                            <input type="text" name="no_toko" value="<?= htmlspecialchars($data_toko['no_hp'] ?? ''); ?>">
                        </div>
                        <div class="form-group full">
                            <label>Alamat Toko</label>
                            <textarea name="alamat" placeholder="Masukkan alamat toko..."><?= htmlspecialchars($data_toko['alamat'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Logo Toko</label>
                            <div class="brand-box">
                                <div class="brand-preview">
                                    <?php if (!empty($data_toko['logo'])) : ?>
                                        <img class="logo-preview" src="<?= htmlspecialchars($upload_url . basename($data_toko['logo'])); ?>" alt="Logo toko">
                                    <?php else : ?>
                                        <div class="empty-preview">Belum ada logo toko</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input class="file-input" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                                    <div class="hint">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                                </div>
                                <?php if (!empty($data_toko['logo'])) : ?>
                                    <div class="brand-actions">
                                        <span class="hint">Logo aktif: <?= htmlspecialchars($data_toko['logo']); ?></span>
                                        <button type="submit" name="hapus_logo" class="danger-button" formnovalidate onclick="return confirm('Hapus logo toko?');">Hapus Logo</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Favicon Website</label>
                            <div class="brand-box">
                                <div class="brand-preview">
                                    <?php if (!empty($data_toko['favicon'])) : ?>
                                        <img class="favicon-preview" src="<?= htmlspecialchars($upload_url . basename($data_toko['favicon'])); ?>" alt="Favicon website">
                                    <?php else : ?>
                                        <div class="empty-preview">Belum ada favicon</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <input class="file-input" type="file" name="favicon" accept="image/png,image/x-icon,image/jpeg">
                                    <div class="hint">PNG, ICO, atau JPG. Maksimal 2 MB. Disarankan gambar persegi.</div>
                                </div>
                                <?php if (!empty($data_toko['favicon'])) : ?>
                                    <div class="brand-actions">
                                        <span class="hint">Favicon aktif: <?= htmlspecialchars($data_toko['favicon']); ?></span>
                                        <button type="submit" name="hapus_favicon" class="danger-button" formnovalidate onclick="return confirm('Hapus favicon website?');">Hapus Favicon</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="button-area">
                        <button type="submit" name="simpan_toko" class="save-button">Simpan Pengaturan Toko</button>
                    </div>
                </form>
            </div>
        <div style="margin-top:20px;background:#fff;border:1px solid #eee4db;border-radius:14px;padding:20px;box-shadow:0 3px 12px rgba(74,34,14,.05);display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap"><div><strong style="font-size:14px;color:#542D1C">Kelola Akun Pengguna</strong><p style="font-size:11px;color:#8a7669;margin-top:5px">Tambah, ubah, atau hapus akun yang terdaftar di sistem.</p></div><a href="pengguna.php" style="background:#6d3519;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-size:11px;font-weight:bold">Buka Akun Pengguna</a></div>
</section>
    </main>
</div>
</body>
</html>
