<?php
session_start();
include "../config/koneksi.php";

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenHash = hash('sha256', $token);
$error = "";
$success = false;
$resetRow = null;

if ($token !== '' && ctype_xdigit($token) && strlen($token) === 64) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, "s", $tokenHash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        $resetRow = mysqli_fetch_assoc($result);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$resetRow) {
        $error = "Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = "Password minimal 8 karakter.";
        } elseif ($password !== $confirm) {
            $error = "Konfirmasi password tidak sama.";
        } else {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update = mysqli_prepare(
                $koneksi,
                "UPDATE users SET password = ? WHERE id_user = (SELECT id_user FROM password_resets WHERE id = ?)"
            );
            mysqli_stmt_bind_param($update, "si", $newHash, $resetRow['id']);
            if (mysqli_stmt_execute($update)) {
                $used = mysqli_prepare($koneksi, "UPDATE password_resets SET used_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($used, "i", $resetRow['id']);
                mysqli_stmt_execute($used);
                $success = true;
            } else {
                $error = "Password belum berhasil diperbarui. Silakan coba lagi.";
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
<title>Buat Password Baru - Budiarti's Risol</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:Arial,sans-serif;background:linear-gradient(rgba(55,27,15,.60),rgba(55,27,15,.60)),url("../assets/login-bg.jpg") center/cover fixed;display:flex;align-items:center;justify-content:center;padding:20px;position:relative}
body:before{content:"";position:fixed;inset:0;backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px)}
.card{position:relative;width:420px;max-width:100%;background:rgba(255,248,240,.98);padding:36px;border-radius:22px;text-align:center;box-shadow:0 25px 60px #0004}
.logo{width:48px;height:48px;margin:0 auto 12px;border-radius:50%;background:#7a3514;color:#f4c27a;display:grid;place-items:center;font-size:21px}
.brand{margin:0;color:#432315;font: bold 27px Georgia,serif}.brand span{display:block;color:#a95120;font-size:21px;font-style:italic}
.tag{margin:8px 0 25px;color:#a18a7b;font-size:9px;letter-spacing:2px}
.line{width:45px;height:1px;background:#c47a42;margin:0 auto 20px}
.eyebrow{color:#a95120;font-size:9px;font-weight:bold;letter-spacing:3px}
h2{margin:9px 0;color:#3d2116;font: bold 27px Georgia,serif}
.desc{font-size:12px;line-height:1.7;color:#9b887c;margin:8px 0 22px}
.group{text-align:left;margin-bottom:15px}
label{display:block;margin-bottom:7px;color:#533a2d;font-size:11px;font-weight:bold}
input{width:100%;padding:13px 14px;border:1px solid #dfcdbf;border-radius:10px;background:#fffdfb;color:#4b3024;outline:none;font-size:13px}
input:focus{border-color:#a95120}
button{width:100%;margin-top:5px;padding:13px;border:0;border-radius:10px;background:linear-gradient(135deg,#68300f,#a95120);color:white;font-weight:bold;cursor:pointer}
.message{padding:11px;border-radius:8px;margin:0 0 15px;font-size:12px;line-height:1.5;background:#e8f3e8;color:#315b35}
.error{background:#f9dddd;color:#9b2d2d}
.back{display:inline-block;margin-top:20px;color:#a95120;text-decoration:none;font-size:11px;font-weight:bold}
@media(max-width:500px){.card{padding:29px 24px}}
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
<div class="card">
    <div class="logo">✦</div>
    <h1 class="brand">Budiarti<span>Risol</span></h1>
    <div class="tag">HOMEMADE • FRESHLY MADE • PRE-ORDER</div>
    <div class="line"></div>
    <div class="eyebrow">SECURE YOUR ACCOUNT</div>
    <h2>Buat Password Baru</h2>
    <p class="desc">Gunakan password baru minimal 8 karakter.</p>

    <?php if ($success): ?>
        <div class="message">Password berhasil diperbarui. Silakan login menggunakan password baru.</div>
        <a class="back" href="login.php">Kembali ke Login →</a>
    <?php elseif (!$resetRow): ?>
        <div class="message error">Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.</div>
        <a class="back" href="lupa_password.php">Minta Tautan Baru</a>
    <?php else: ?>
        <?php if ($error !== ""): ?><div class="message error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
            <div class="group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" minlength="8" placeholder="Minimal 8 karakter" required>
            </div>
            <div class="group">
                <label for="confirm_password">Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" placeholder="Ulangi password baru" required>
            </div>
            <button type="submit">Simpan Password Baru</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
