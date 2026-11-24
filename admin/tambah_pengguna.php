<?php
include "auth.php";
include "../config/koneksi.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($nama === '' || $email === '' || $no_hp === '' || $password_input === '') {
        $error = 'Semua data wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password_input) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array($role, ['user', 'admin'], true)) {
        $error = 'Role tidak valid.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $email_terdaftar = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($email_terdaftar) {
            $error = 'Email tersebut sudah digunakan oleh pengguna lain.';
        } else {
            $password = password_hash($password_input, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (nama, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'sssss', $nama, $email, $no_hp, $password, $role);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: pengguna.php?pesan=tambah_berhasil');
                exit;
            }

            $error = 'Pengguna gagal ditambahkan: ' . mysqli_error($koneksi);
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pengguna - Budiarti Risol</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.container{max-width:680px;margin:45px auto;padding:0 20px}h1{font-family:Georgia,serif;color:#542D1C;margin-bottom:8px}.subtitle{color:#8b7668;font-size:13px;margin-bottom:22px}.card{background:#fff;padding:25px;border-radius:14px;box-shadow:0 3px 12px rgba(70,35,15,.08)}label{display:block;margin:0 0 8px;color:#5b2c1f;font-weight:600;font-size:13px}input,select{width:100%;padding:12px;margin-bottom:18px;border:1px solid #ddd;border-radius:8px;font:inherit}button{background:#8b4513;color:#fff;border:0;padding:11px 18px;border-radius:8px;cursor:pointer;font-weight:600}.back{display:inline-block;margin-left:8px;color:#8b4513;text-decoration:none;font-size:13px}.error{background:#ffe5e5;color:#a00000;padding:11px;border-radius:8px;margin-bottom:16px;font-size:13px}.hint{font-size:12px;color:#8b7668;margin-top:-10px;margin-bottom:18px}@media(max-width:600px){.container{margin:25px auto}}
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
    <h1>Tambah Pengguna</h1>
    <p class="subtitle">Tambahkan akun pelanggan atau administrator ke sistem Budiarti Risol.</p>
    <div class="card">
        <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <label>Nama</label>
            <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? ''); ?>" required>
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            <label>No. HP</label>
            <input type="text" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp'] ?? ''); ?>" required>
            <label>Password</label>
            <input type="password" name="password" minlength="6" required>
            <p class="hint">Minimal 6 karakter. Password akan disimpan dalam bentuk terenkripsi (hash).</p>
            <label>Role</label>
            <select name="role" required>
                <option value="user" <?= (($_POST['role'] ?? 'user') === 'user') ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
            <button type="submit">Simpan Pengguna</button>
            <a class="back" href="pengguna.php">Kembali</a>
        </form>
    </div>
</div>
</body>
</html>
