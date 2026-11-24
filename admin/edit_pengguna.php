<?php

include "auth.php";
include "../config/koneksi.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pengguna.php");
    exit;
}

$id = (int) $_GET['id'];

$data = mysqli_query(
    $koneksi,
    "SELECT * FROM users
     WHERE id_user = $id"
);

if (mysqli_num_rows($data) == 0) {
    echo "Pengguna tidak ditemukan.";
    exit;
}

$user = mysqli_fetch_assoc($data);

if (isset($_POST['update'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $role = $_POST['role'];

    if ($nama === '' || $email === '' || $no_hp === '') {

        $error = "Semua data harus diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif (!in_array($role, ['user', 'admin'])) {

        $error = "Role tidak valid.";

    } else {

        $nama = mysqli_real_escape_string($koneksi, $nama);
        $email = mysqli_real_escape_string($koneksi, $email);
        $no_hp = mysqli_real_escape_string($koneksi, $no_hp);

        // Cek email agar tidak sama dengan pengguna lain
        $cek_email = mysqli_query(
            $koneksi,
            "SELECT id_user
             FROM users
             WHERE email = '$email'
             AND id_user != $id"
        );

        if (mysqli_num_rows($cek_email) > 0) {

            $error = "Email tersebut sudah digunakan oleh pengguna lain.";

        } else {

            // Admin yang sedang login tidak boleh mengubah
            // role dirinya sendiri menjadi user
            if (
                $id == $_SESSION['id_user'] &&
                $role !== 'admin'
            ) {

                $error = "Akun admin yang sedang digunakan tidak dapat diubah menjadi user.";

            } else {

                $role = mysqli_real_escape_string($koneksi, $role);

                $query = mysqli_query(
                    $koneksi,
                    "UPDATE users SET
                        nama = '$nama',
                        email = '$email',
                        no_hp = '$no_hp',
                        role = '$role'
                     WHERE id_user = $id"
                );

                if ($query) {

                    // Jika admin mengubah data akun yang sedang login,
                    // perbarui session nama dan email
                    if ($id == $_SESSION['id_user']) {
                        $_SESSION['nama'] = $nama;
                        $_SESSION['email'] = $email;
                    }

                    header("Location: pengguna.php");
                    exit;

                } else {

                    $error = "Data pengguna gagal diubah: " . mysqli_error($koneksi);

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

    <title>Edit Pengguna - Budiarti Risol</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #F8EFE5;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        h2 {
            color: #5b2c1f;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #5b2c1f;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            background: #8b4513;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: #6f350f;
        }

        .kembali {
            display: inline-block;
            margin-left: 10px;
            color: #8b4513;
            text-decoration: none;
        }

        .error {
            background: #ffe5e5;
            color: #a00000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
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

<div class="container">

    <h2>Edit Pengguna</h2>

    <div class="card">

        <?php if (isset($error)) { ?>

            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <form method="POST">

            <label>Nama</label>

            <input
                type="text"
                name="nama"
                value="<?= htmlspecialchars($user['nama']); ?>"
                required
            >


            <label>Email</label>

            <input
                type="email"
                name="email"
                value="<?= htmlspecialchars($user['email']); ?>"
                required
            >


            <label>No. HP</label>

            <input
                type="text"
                name="no_hp"
                value="<?= htmlspecialchars($user['no_hp']); ?>"
                required
            >


            <label>Role</label>

            <select name="role" required>

                <option
                    value="user"
                    <?= ($user['role'] == 'user') ? 'selected' : ''; ?>
                >
                    User
                </option>

                <option
                    value="admin"
                    <?= ($user['role'] == 'admin') ? 'selected' : ''; ?>
                >
                    Admin
                </option>

            </select>


            <button type="submit" name="update">
                Update Pengguna
            </button>

            <a
                href="pengguna.php"
                class="kembali"
            >
                Kembali
            </a>

        </form>

    </div>

</div>

</body>

</html>