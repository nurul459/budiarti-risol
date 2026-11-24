
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Tambahkan kolom foto profil otomatis jika database lama belum memilikinya.
$cek_kolom_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
if ($cek_kolom_foto && mysqli_num_rows($cek_kolom_foto) === 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD foto_profil VARCHAR(255) DEFAULT NULL");
}


// Tabel preferensi notifikasi dibuat otomatis jika belum tersedia.
mysqli_query($koneksi, "
    CREATE TABLE IF NOT EXISTS preferensi_notifikasi (
        id_user INT NOT NULL PRIMARY KEY,
        notif_pesanan TINYINT(1) NOT NULL DEFAULT 1,
        notif_promo TINYINT(1) NOT NULL DEFAULT 0,
        notif_info TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$pesan = "";
$jenis_pesan = "";
$active_tab = "home";

// Ambil data pengguna
$stmt = mysqli_prepare($koneksi, "
    SELECT id_user, nama, email, no_hp, password, foto_profil
    FROM users
    WHERE id_user = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Update informasi pribadi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {

    $active_tab = "informasi";

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $foto_baru = $user['foto_profil'] ?? null;
    $upload_foto_error = '';
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
            $upload_foto_error = 'Foto profil gagal diunggah.';
        } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
            $upload_foto_error = 'Ukuran foto maksimal 2 MB.';
        } else {
            $info_gambar = @getimagesize($_FILES['foto_profil']['tmp_name']);
            $mime = $info_gambar['mime'] ?? '';
            $ekstensi_valid = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!$info_gambar || !isset($ekstensi_valid[$mime])) {
                $upload_foto_error = 'Format foto harus JPG, PNG, atau WEBP.';
            } else {
                $folder_foto = __DIR__ . '/../assets/images/profil/';
                if (!is_dir($folder_foto)) mkdir($folder_foto, 0755, true);
                $nama_file_foto = 'profil_' . $id_user . '_' . bin2hex(random_bytes(6)) . '.' . $ekstensi_valid[$mime];
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $folder_foto . $nama_file_foto)) {
                    $foto_baru = 'profil/' . $nama_file_foto;
                } else {
                    $upload_foto_error = 'Foto profil tidak dapat disimpan.';
                }
            }
        }
    }

    if ($upload_foto_error !== '') {
        $pesan = $upload_foto_error;
        $jenis_pesan = 'error';
    } elseif ($nama === '' || $email === '' || $no_hp === '') {
        $pesan = "Semua kolom wajib diisi.";
        $jenis_pesan = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid.";
        $jenis_pesan = "error";

    } else {
        $cek = mysqli_prepare($koneksi, "
            SELECT id_user FROM users
            WHERE email = ? AND id_user != ?
        ");
        mysqli_stmt_bind_param($cek, "si", $email, $id_user);
        mysqli_stmt_execute($cek);
        $cek_result = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($cek_result) > 0) {
            $pesan = "Email sudah digunakan akun lain.";
            $jenis_pesan = "error";

        } else {
            $update = mysqli_prepare($koneksi, "
                UPDATE users
                SET nama = ?, email = ?, no_hp = ?, foto_profil = ?
                WHERE id_user = ?
            ");

            mysqli_stmt_bind_param(
                $update,
                "ssssi",
                $nama,
                $email,
                $no_hp,
                $foto_baru,
                $id_user
            );

            if (mysqli_stmt_execute($update)) {
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $_SESSION['foto_profil'] = $foto_baru;

                $pesan = "Informasi pribadi berhasil diperbarui!";
                $jenis_pesan = "success";
            } else {
                $pesan = "Gagal memperbarui informasi pribadi.";
                $jenis_pesan = "error";
            }
        }
    }
}

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {

    $active_tab = "keamanan";

    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($password_lama === '' || $password_baru === '' || $konfirmasi === '') {
        $pesan = "Semua kolom password wajib diisi.";
        $jenis_pesan = "error";

    } elseif (!password_verify($password_lama, $user['password'])) {
        $pesan = "Password lama yang kamu masukkan salah.";
        $jenis_pesan = "error";

    } elseif (strlen($password_baru) < 8) {
        $pesan = "Password baru minimal 8 karakter.";
        $jenis_pesan = "error";

    } elseif ($password_baru !== $konfirmasi) {
        $pesan = "Konfirmasi password tidak sesuai.";
        $jenis_pesan = "error";

    } elseif (password_verify($password_baru, $user['password'])) {
        $pesan = "Password baru harus berbeda dari password lama.";
        $jenis_pesan = "error";

    } else {
        $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);

        $update_password = mysqli_prepare($koneksi, "
            UPDATE users
            SET password = ?
            WHERE id_user = ?
        ");

        mysqli_stmt_bind_param(
            $update_password,
            "si",
            $password_hash,
            $id_user
        );

        if (mysqli_stmt_execute($update_password)) {
            $pesan = "Password berhasil diganti!";
            $jenis_pesan = "success";
        } else {
            $pesan = "Gagal mengganti password.";
            $jenis_pesan = "error";
        }
    }
}

// Simpan preferensi notifikasi pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifikasi'])) {

    $active_tab = "notifikasi";
    $notif_pesanan = isset($_POST['notif_pesanan']) ? 1 : 0;
    $notif_promo = isset($_POST['notif_promo']) ? 1 : 0;
    $notif_info = isset($_POST['notif_info']) ? 1 : 0;

    $simpan_notifikasi = mysqli_prepare($koneksi, "
        INSERT INTO preferensi_notifikasi
            (id_user, notif_pesanan, notif_promo, notif_info)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            notif_pesanan = VALUES(notif_pesanan),
            notif_promo = VALUES(notif_promo),
            notif_info = VALUES(notif_info)
    ");

    mysqli_stmt_bind_param(
        $simpan_notifikasi,
        "iiii",
        $id_user,
        $notif_pesanan,
        $notif_promo,
        $notif_info
    );

    if (mysqli_stmt_execute($simpan_notifikasi)) {
        $pesan = "Preferensi notifikasi berhasil disimpan!";
        $jenis_pesan = "success";
    } else {
        $pesan = "Gagal menyimpan preferensi notifikasi.";
        $jenis_pesan = "error";
    }
}

// Ambil preferensi notifikasi pengguna
$pref_query = mysqli_prepare($koneksi, "
    SELECT notif_pesanan, notif_promo, notif_info
    FROM preferensi_notifikasi
    WHERE id_user = ?
");
mysqli_stmt_bind_param($pref_query, "i", $id_user);
mysqli_stmt_execute($pref_query);
$pref_result = mysqli_stmt_get_result($pref_query);
$preferensi = mysqli_fetch_assoc($pref_result);

// Nilai bawaan untuk akun yang belum pernah mengatur notifikasi
if (!$preferensi) {
    $preferensi = [
        'notif_pesanan' => 1,
        'notif_promo' => 0,
        'notif_info' => 1
    ];
}

// Ambil ulang data terbaru
$stmt = mysqli_prepare($koneksi, "
    SELECT id_user, nama, email, no_hp, foto_profil
    FROM users
    WHERE id_user = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$nama_user = $user['nama'];
$email_user = $user['email'];
$inisial = strtoupper(substr($nama_user, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya - Budiarti Risol</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #F8EFE5;
    color: #542D1C;
    font-family: var(--sans, 'DM Sans', Arial, sans-serif);\n    font-size: var(--text-base, 15px);
}

/* NAVBAR */
.navbar {
    position: relative;
    z-index: 1000;
}

/* BRAND BUDIARTI RISOL */
.navbar .brand {
    text-decoration: none;
}

.navbar .brand .brand-copy strong,
.navbar .brand .brand-copy small {
    color: #ffffff !important;
}

/* MENU NAVBAR */
.navbar .nav-menu a {
    color: #ffffff !important;
}

.navbar .nav-menu a:hover {
    color: #ffe2a8 !important;
}

.nav-icons {
    display: flex;
    align-items: center;
    gap: 15px;
}

.nav-icons > a {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    color: white;
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 50%;
    text-decoration: none;
}

.nav-account {
    position: relative;
}

.account-trigger {
    display: flex;
    align-items: center;
    gap: 7px;
    border: none;
    background: transparent;
    cursor: pointer;
}

.account-avatar {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #fff4e5;
    color: #713f24;
    font-weight: bold;
}

.account-arrow {
    color: white;
    font-size: 11px;
}

.account-dropdown {
    display: none;
    position: absolute;
    top: 45px;
    right: 0;
    width: 260px;
    padding: 12px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,.16);
    z-index: 9999;
}

.nav-account.open .account-dropdown {
    display: block;
}

.account-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
}

.account-user-avatar {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: #fff4e5;
    color: #713f24;
    border-radius: 50%;
    font-weight: bold;
}

.account-user-text {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.account-user-text strong {
    font-size: 13px;
    color: #542D1C;
}

.account-user-text small {
    font-size: 11px;
    color: #92765e;
    overflow-wrap: anywhere;
}

.account-divider {
    height: 1px;
    background: #eee3ce;
    margin: 8px 0;
}

.nav-icons .account-dropdown .account-menu-link {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    height: auto;
    padding: 11px;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: #60432e;
    font-size: 13px;
    text-decoration: none;
}

.nav-icons .account-dropdown .account-menu-link:hover {
    background: #fff4e5;
    color: #a45b19;
}

.nav-icons .account-dropdown .logout {
    color: #b44a36;
}

/* HALAMAN PROFIL */
.profile-page {
    max-width: 1150px;
    margin: 45px auto 75px;
    padding: 0 25px;
}

.profile-heading {
    margin-bottom: 30px;
}

.profile-heading small {
    color: #c17b36;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 2px;
}

.profile-heading h1 {
    margin: 10px 0;
    font-family: var(--serif, 'Playfair Display', Georgia, serif);
    font-size: 35px;
}

.profile-heading p {
    color: #927d6b;
    font-size: 14px;
}

/* KARTU MENU PENGATURAN AKUN */
.profile-layout {
    display: block;
}

.profile-content {
    min-width: 0;
}

.profile-home {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.profile-menu-card {
    display: flex;
    align-items: center;
    gap: 15px;
    min-height: 118px;
    padding: 22px;
    background: #fff;
    border: 1px solid #f0e8d8;
    border-radius: 17px;
    box-shadow: 0 5px 20px rgba(79,48,22,.04);
    color: #542D1C;
    text-decoration: none;
    transition: .2s ease;
}

.profile-menu-card:hover {
    transform: translateY(-3px);
    border-color: #e5bd82;
    box-shadow: 0 9px 24px rgba(79,48,22,.09);
}

.profile-menu-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: #f8ead7;
    color: #8b4b24;
    font-size: 22px;
}

.profile-menu-text {
    flex: 1;
    min-width: 0;
}

.profile-menu-text strong {
    display: block;
    margin-bottom: 6px;
    font-size: 15px;
}

.profile-menu-text small {
    display: block;
    color: #9b8978;
    font-size: 12px;
    line-height: 1.5;
}

.profile-menu-arrow {
    color: #b58b63;
    font-size: 20px;
}

.profile-panel {
    display: none;
    padding: 30px;
    background: white;
    border: 1px solid #f0e8d8;
    border-radius: 18px;
    box-shadow: 0 5px 20px rgba(79,48,22,.04);
    animation: fadeIn .25s ease;
}

.profile-panel.active {
    display: block;
}

.profile-home.hidden {
    display: none;
}

.profile-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 23px;
    padding: 9px 13px;
    border: 1px solid #efdfca;
    border-radius: 9px;
    background: #F8EFE5;
    color: #8b4b24;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.profile-back:hover {
    background: #fff1dc;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-heading {
    margin-bottom: 25px;
}

.card-heading h2 {
    margin: 0 0 8px;
    font-family: var(--serif, 'Playfair Display', Georgia, serif);
    font-size: 23px;
}

.card-heading p {
    margin: 0;
    color: #9b8978;
    font-size: 13px;
    line-height: 1.6;
}

/* FOTO PROFIL */
.profile-photo-editor{display:flex;align-items:center;gap:20px;margin:0 0 25px;padding:18px;border:1px solid #f0e8d8;border-radius:14px;background:#fffefa}
.profile-photo-preview{width:92px;height:92px;flex:0 0 92px;display:grid;place-items:center;overflow:hidden;border-radius:50%;background:#f4dfbd;color:#80420f;font-size:32px;font-weight:700}
.profile-photo-preview img{width:100%;height:100%;object-fit:cover}
.profile-photo-fields{display:flex;flex-direction:column;gap:8px;min-width:0}
.profile-photo-fields label{color:#60432e;font-size:13px;font-weight:700}
.profile-photo-fields input{max-width:100%;font-size:13px}
.profile-photo-fields small{color:#9b8978;font-size:11px}
@media(max-width:520px){.profile-photo-editor{align-items:flex-start;flex-direction:column}}

/* FORM */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 13px;
    font-weight: bold;
    color: #60432e;
}

.form-group input {
    width: 100%;
    padding: 14px 15px;
    border: 1px solid #eadfce;
    border-radius: 10px;
    background: #fffefa;
    color: #542D1C;
    font-size: 13px;
    outline: none;
    transition: .2s;
}

.form-group input:focus {
    border-color: #d89b4b;
    box-shadow: 0 0 0 3px rgba(216,155,75,.12);
}

.form-group input::placeholder {
    color: #b7a999;
}

.form-note {
    margin-top: 17px;
    color: #a18e7c;
    font-size: 11px;
    line-height: 1.6;
}

.btn-save {
    margin-top: 25px;
    padding: 14px 25px;
    border: none;
    border-radius: 10px;
    background: #a85e25;
    color: white;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.btn-save:hover {
    background: #82451c;
    transform: translateY(-2px);
}

/* KEAMANAN */
.security-icon {
    width: 45px;
    height: 45px;
    display: grid;
    place-items: center;
    background: #fff3dc;
    border-radius: 12px;
    font-size: 21px;
}

.security-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 25px;
}

.security-heading h2 {
    margin: 0 0 6px;
    font-family: var(--serif, 'Playfair Display', Georgia, serif);
    font-size: 23px;
}

.security-heading p {
    margin: 0;
    color: #9b8978;
    font-size: 12px;
}

/* PREFERENSI NOTIFIKASI */
.notification-intro {
    margin: -10px 0 22px;
    color: #9b8978;
    font-size: 13px;
    line-height: 1.6;
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 18px;
    border: 1px solid #f0e8d8;
    border-radius: 13px;
    background: #fffefa;
}

.notification-option h3 {
    margin: 0 0 6px;
    color: #60432e;
    font-size: 14px;
}

.notification-option p {
    margin: 0;
    color: #9b8978;
    font-size: 12px;
    line-height: 1.5;
}

.notification-toggle {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 27px;
    flex: 0 0 48px;
}

.notification-toggle input {
    width: 1px;
    height: 1px;
    opacity: 0;
    position: absolute;
}

.notification-slider {
    position: absolute;
    inset: 0;
    cursor: pointer;
    background: #d8d0c5;
    border-radius: 30px;
    transition: .2s;
}

.notification-slider::before {
    content: "";
    position: absolute;
    width: 19px;
    height: 19px;
    left: 4px;
    top: 4px;
    background: white;
    border-radius: 50%;
    transition: .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.18);
}

.notification-toggle input:checked + .notification-slider {
    background: #b87532;
}

.notification-toggle input:checked + .notification-slider::before {
    transform: translateX(21px);
}

.notification-toggle input:focus-visible + .notification-slider {
    outline: 3px solid rgba(184,117,50,.25);
    outline-offset: 3px;
}

/* NOTIFIKASI */
.alert {
    padding: 14px 17px;
    margin-bottom: 22px;
    border-radius: 10px;
    font-size: 13px;
}

.alert.success {
    background: #edf8eb;
    color: #4d813e;
    border: 1px solid #d5ebd0;
}

.alert.error {
    background: #fff0ed;
    color: #b44a36;
    border: 1px solid #f5d5cf;
}

.profile-footer {
    text-align: center;
    padding: 25px;
    color: #9b8978;
    font-size: 12px;
}

/* RESPONSIVE */
@media(max-width: 800px) {
    .profile-home {
        grid-template-columns: 1fr 1fr;
    }
}

@media(max-width: 550px) {
    .profile-page {
        margin-top: 30px;
        padding: 0 15px;
    }

    .profile-heading h1 {
        font-size: 28px;
    }

    .profile-panel {
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .profile-home {
        grid-template-columns: 1fr;
    }

    .profile-menu-card {
        min-height: 100px;
        padding: 18px;
    }

    .btn-save {
        width: 100%;
    }
}
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

<header class="navbar">

    <a class="brand" href="index.php">
        <span class="brand-mark">👩‍🍳</span>
        <span class="brand-copy">
            <strong>Budiarti Risol</strong>
            <small>HOMEMADE • FRESHLY MADE</small>
        </span>
    </a>

    <nav class="nav-menu">
        <a href="index.php">Beranda</a>
        <a href="produk.php">Menu</a>
        <a href="tentang.php">Tentang Kami</a>
        <a href="blog.php">Blog</a>
        <a href="kontak.php">Kontak</a>
    </nav>

    <div class="nav-icons">
        <a href="cart.php" aria-label="Keranjang" title="Keranjang">🛒</a>

        <div class="nav-account" id="navAccount">
            <button type="button" class="account-trigger" id="accountTrigger">
                <span class="account-avatar">
                    <?php if (!empty($user['foto_profil']) && is_file(__DIR__ . '/../assets/images/' . $user['foto_profil'])): ?>
                        <img src="../assets/images/<?= e($user['foto_profil']) ?>" alt="Foto profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?= e($inisial) ?>
                    <?php endif; ?>
                </span>
                <span class="account-arrow">▼</span>
            </button>

            <div class="account-dropdown">
                <div class="account-user-info">
                    <div class="account-user-avatar"><?= e($inisial) ?></div>
                    <div class="account-user-text">
                        <strong><?= e($nama_user) ?></strong>
                        <small><?= e($email_user) ?></small>
                    </div>
                </div>

                <div class="account-divider"></div>

                <a href="profil.php" class="account-menu-link">👤 Profil Saya</a>
                <a href="orders.php" class="account-menu-link">📦 Pesanan Saya</a>
                <a href="cart.php#wishlist" class="account-menu-link">♡ Wishlist Saya</a>

                <div class="account-divider"></div>

                <a href="logout.php" class="account-menu-link logout"
                   onclick="return confirm('Apakah kamu yakin ingin keluar?')">
                    ↪ Logout
                </a>
            </div>
        </div>
    </div>

</header>

<main class="profile-page">

    <div class="profile-heading">
        <small>MY ACCOUNT</small>
        <h1>Profil Saya</h1>
        <p>Kelola informasi pribadi dan pengaturan akun Budiarti Risol kamu.</p>
    </div>

    <?php if ($pesan !== ''): ?>
        <div class="alert <?= e($jenis_pesan) ?>">
            <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">

        <section class="profile-home <?= $active_tab === 'home' ? '' : 'hidden' ?>" id="profileHome">
            <a href="#informasi" class="profile-menu-card" data-tab="informasi">
                <span class="profile-menu-icon">👤</span>
                <span class="profile-menu-text">
                    <strong>Informasi Pribadi</strong>
                    <small>Nama, email, dan nomor handphone</small>
                </span>
                <span class="profile-menu-arrow">›</span>
            </a>

            <a href="#keamanan" class="profile-menu-card" data-tab="keamanan">
                <span class="profile-menu-icon">🛡️</span>
                <span class="profile-menu-text">
                    <strong>Keamanan Akun</strong>
                    <small>Ganti password dan jaga keamanan akun</small>
                </span>
                <span class="profile-menu-arrow">›</span>
            </a>

            <a href="#notifikasi" class="profile-menu-card" data-tab="notifikasi">
                <span class="profile-menu-icon">🔔</span>
                <span class="profile-menu-text">
                    <strong>Preferensi Notifikasi</strong>
                    <small>Atur pemberitahuan pesanan dan promo</small>
                </span>
                <span class="profile-menu-arrow">›</span>
            </a>
        </section>

        <section class="profile-content">

            <!-- INFORMASI PRIBADI -->
            <div class="profile-panel <?= $active_tab === 'informasi' ? 'active' : '' ?>" id="panel-informasi">
                <button type="button" class="profile-back" data-home>← Kembali ke Pengaturan Akun</button>
                <div class="card-heading">
                    <h2>Informasi Pribadi</h2>
                    <p>Perbarui informasi pribadi yang terhubung dengan akun kamu.</p>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-photo-editor">
                        <div class="profile-photo-preview">
                            <?php if (!empty($user['foto_profil']) && is_file(__DIR__ . '/../assets/images/' . $user['foto_profil'])): ?>
                                <img src="../assets/images/<?= e($user['foto_profil']) ?>" alt="Foto profil">
                            <?php else: ?>
                                <span><?= e($inisial) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-photo-fields">
                            <label for="foto_profil">Foto Profil</label>
                            <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small>Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" value="<?= e($user['nama']) ?>" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat Email</label>
                            <input type="email" name="email" value="<?= e($user['email']) ?>" placeholder="nama@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor Handphone</label>
                            <input type="tel" name="no_hp" value="<?= e($user['no_hp']) ?>" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>
                    <button type="submit" name="update_profil" class="btn-save">Simpan Perubahan</button>
                </form>
            </div>

            <!-- KEAMANAN AKUN -->
            <div class="profile-panel <?= $active_tab === 'keamanan' ? 'active' : '' ?>" id="panel-keamanan">
                <button type="button" class="profile-back" data-home>← Kembali ke Pengaturan Akun</button>
                <div class="security-heading">
                    <div class="security-icon">🔐</div>
                    <div>
                        <h2>Keamanan Akun</h2>
                        <p>Ubah password untuk menjaga keamanan akun kamu.</p>
                    </div>
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Password Saat Ini</label>
                            <input type="password" name="password_lama" placeholder="Masukkan password saat ini" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password_baru" placeholder="Minimal 8 karakter" minlength="8" required>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="konfirmasi_password" placeholder="Ulangi password baru" minlength="8" required>
                        </div>
                    </div>
                    <p class="form-note">Gunakan password minimal 8 karakter dan jangan membagikannya kepada orang lain.</p>
                    <button type="submit" name="update_password" class="btn-save">Ganti Password</button>
                </form>
            </div>

            <!-- PREFERENSI NOTIFIKASI -->
            <div class="profile-panel <?= $active_tab === 'notifikasi' ? 'active' : '' ?>" id="panel-notifikasi">
                <button type="button" class="profile-back" data-home>← Kembali ke Pengaturan Akun</button>
                <div class="card-heading">
                    <h2>Preferensi Notifikasi</h2>
                    <p>Atur informasi apa saja yang ingin kamu terima dari Budiarti Risol.</p>
                </div>
                <p class="notification-intro">Kamu bisa mengaktifkan atau menonaktifkan pemberitahuan sesuai kebutuhan. Perubahan akan tersimpan untuk akun kamu.</p>
                <form method="POST">
                    <div class="notification-list">
                        <div class="notification-option">
                            <div>
                                <h3>Informasi Status Pesanan</h3>
                                <p>Pemberitahuan saat pesanan dikonfirmasi, diproses, dikirim, atau selesai.</p>
                            </div>
                            <label class="notification-toggle" aria-label="Notifikasi status pesanan">
                                <input type="checkbox" name="notif_pesanan" value="1" <?= !empty($preferensi['notif_pesanan']) ? 'checked' : '' ?>>
                                <span class="notification-slider"></span>
                            </label>
                        </div>
                        <div class="notification-option">
                            <div>
                                <h3>Promo dan Diskon</h3>
                                <p>Informasi promo, potongan harga, dan penawaran spesial.</p>
                            </div>
                            <label class="notification-toggle" aria-label="Notifikasi promo dan diskon">
                                <input type="checkbox" name="notif_promo" value="1" <?= !empty($preferensi['notif_promo']) ? 'checked' : '' ?>>
                                <span class="notification-slider"></span>
                            </label>
                        </div>
                        <div class="notification-option">
                            <div>
                                <h3>Informasi Terbaru</h3>
                                <p>Berita, produk baru, dan informasi terbaru dari Budiarti Risol.</p>
                            </div>
                            <label class="notification-toggle" aria-label="Notifikasi informasi terbaru">
                                <input type="checkbox" name="notif_info" value="1" <?= !empty($preferensi['notif_info']) ? 'checked' : '' ?>>
                                <span class="notification-slider"></span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="update_notifikasi" class="btn-save">Simpan Pengaturan</button>
                </form>
            </div>

        </section>
    </div>
</main>

<footer class="footer">

    <div class="footer-sprig"></div>

    <h2>Budiarti Risol</h2>

    <p>Risol homemade dengan rasa yang dibuat sepenuh hati.</p>

    <div class="footer-links">
        <a href="syarat.php">Syarat dan Ketentuan</a>
        &nbsp; | &nbsp;
        <a href="privasi.php">Kebijakan Privasi</a>
    </div>

    <span class="footer-line"></span>

    <small>© 2026 Budiarti Risol</small>

</footer>


<script>
// Dropdown akun
const account = document.getElementById('navAccount');
const trigger = document.getElementById('accountTrigger');

if (account && trigger) {
    trigger.addEventListener('click', function(event) {
        event.stopPropagation();
        account.classList.toggle('open');
    });

    document.addEventListener('click', function(event) {
        if (!account.contains(event.target)) {
            account.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            account.classList.remove('open');
        }
    });
}

// Navigasi pengaturan akun
const profileHome = document.getElementById('profileHome');
const profileCards = document.querySelectorAll('.profile-menu-card[data-tab]');
const profilePanels = document.querySelectorAll('.profile-panel');
const backButtons = document.querySelectorAll('[data-home]');

function bukaPengaturan(tab) {
    profileHome.classList.add('hidden');
    profilePanels.forEach(panel => {
        panel.classList.toggle('active', panel.id === 'panel-' + tab);
    });
    history.replaceState(null, '', '#' + tab);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function kembaliKePengaturan() {
    profilePanels.forEach(panel => panel.classList.remove('active'));
    profileHome.classList.remove('hidden');
    history.replaceState(null, '', window.location.pathname);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

profileCards.forEach(card => {
    card.addEventListener('click', function(event) {
        event.preventDefault();
        bukaPengaturan(this.dataset.tab);
    });
});

backButtons.forEach(button => {
    button.addEventListener('click', kembaliKePengaturan);
});

const hashTab = window.location.hash.replace('#', '');
if (['informasi', 'keamanan', 'notifikasi'].includes(hashTab)) {
    bukaPengaturan(hashTab);
}
</script>

</body>
</html>
