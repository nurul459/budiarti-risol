<?php

include "auth.php";
include "../config/koneksi.php";

$query = mysqli_query(
    $koneksi,
    "SELECT id_user, nama, email, no_hp, role
     FROM users
     ORDER BY id_user DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pengguna - Budiarti Risol</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #F8EFE5;
            color: #542D1C;
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
            font-family: Georgia, serif;
            font-size: 22px;
            font-style: italic;
            color: white;
        }

        .sidebar-logo p {
            font-size: 9px;
            letter-spacing: 1px;
            color: #EBD3C0;
            margin-top: 3px;
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
    text-decoration: none; /* <-- TAMBAHKAN BARIS INI UNTUK MENGHILANGKAN GARIS BAWAH */
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

        .hamburger {
            font-size: 20px;
            color: #5b2c1f;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #5b2c1f;
            font-size: 13px;
        }

        .notification {
            font-size: 18px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #d9ad63;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .admin-name {
            font-weight: 600;
        }

        /* CONTENT */

        .page-content {
            padding: 35px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-family: Georgia, serif;
            font-size: 27px;
            color: #542D1C;
            margin-bottom: 6px;
        }

        .page-title p {
            font-size: 13px;
            color: #8b7668;
        }

        .add-button {
            display: inline-block;
            text-decoration: none;
            background: #8b4513;
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .add-button:hover {
            background: #6f350f;
        }

        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .history-button {
            display: inline-block;
            text-decoration: none;
            background: #eee9e3;
            color: #5b2c1f;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
        }

        .delete-button {
            border: 0;
            background: #f8dddd;
            color: #9b2d2d;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .delete-button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        /* CARD */

        .user-card {
            background: white;
            border: 1px solid #eee3d8;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(70, 35, 15, 0.04);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .card-title {
            font-family: Georgia, serif;
            font-size: 18px;
            color: #542D1C;
        }

        .user-count {
            font-size: 12px;
            color: #8b7668;
        }

        /* TABLE */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f1e9;
            color: #5b2c1f;
            padding: 13px 14px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            border-bottom: 1px solid #eaded2;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #eee7e0;
            font-size: 12px;
            color: #5d4a3e;
        }

        tbody tr:hover {
            background: #F8EFE5;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* ROLE */

        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .role-admin {
            background: #f7e4c5;
            color: #855016;
        }

        .role-user {
            background: #eee9e3;
            color: #6d5b4e;
        }

        /* BUTTON */

        .edit-button {
            display: inline-block;
            text-decoration: none;
            background: #f2e4d6;
            color: #6b3518;
            padding: 7px 12px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
        }

        .edit-button:hover {
            background: #e8d3c0;
        }

        /* EMPTY */

        .empty-data {
            text-align: center;
            padding: 35px;
            color: #8b7668;
            font-size: 13px;
        }

        @media (max-width: 800px) {

            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }

            .page-content {
                padding: 25px;
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
</head>

<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->

    <?php include __DIR__ . '/sidebar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOP HEADER -->

        <header class="top-header">
    <div class="hamburger" aria-label="Buka atau tutup sidebar">☰</div>
    <div class="admin-profile">
        <div class="avatar" aria-hidden="true">👤</div>
        <span class="admin-name">Admin</span>
    </div>
</header>


        <!-- PAGE CONTENT -->

        <section class="page-content">

            <div class="page-header">

                <div class="page-title">

                    <h1>Data Pengguna</h1>

                    <p>
                        Kelola data pengguna yang terdaftar pada sistem Budiarti Risol.
                    </p>

                </div>

                <a href="tambah_pengguna.php" class="add-button">+ Tambah Pengguna</a>

            </div>


            <?php if (isset($_GET['pesan'])) { ?>
                <?php
                $pesan = $_GET['pesan'];
                $pesan_map = [
                    'tambah_berhasil' => 'Pengguna berhasil ditambahkan.',
                    'hapus_berhasil' => 'Pengguna berhasil dihapus.',
                    'hapus_diri_sendiri' => 'Akun yang sedang digunakan tidak dapat dihapus.',
                    'hapus_memiliki_pesanan' => 'Pengguna tidak dapat dihapus karena masih memiliki riwayat pesanan.',
                    'hapus_gagal' => 'Pengguna gagal dihapus.'
                ];
                ?>
                <?php if (isset($pesan_map[$pesan])) { ?>
                    <div style="background:#fff4d6;color:#6b4b12;padding:11px 14px;border-radius:8px;margin-bottom:18px;font-size:12px;">
                        <?= htmlspecialchars($pesan_map[$pesan]); ?>
                    </div>
                <?php } ?>
            <?php } ?>

            <!-- USER CARD -->

            <div class="user-card">

                <div class="card-header">

                    <h2 class="card-title">
                        Daftar Pengguna
                    </h2>


                    <span class="user-count">

                        <?php
                        $jumlah_user = mysqli_num_rows($query);
                        echo $jumlah_user . " pengguna";
                        ?>

                    </span>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Nama</th>

                                <th>Email</th>

                                <th>No. HP</th>

                                <th>Role</th>

                                <th>Aksi</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (mysqli_num_rows($query) > 0) { ?>

                                <?php while ($row = mysqli_fetch_assoc($query)) { ?>

                                    <tr>

                                        <td>
                                            #<?php echo $row['id_user']; ?>
                                        </td>


                                        <td>
                                            <?php echo htmlspecialchars($row['nama']); ?>
                                        </td>


                                        <td>
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </td>


                                        <td>
                                            <?php echo htmlspecialchars($row['no_hp']); ?>
                                        </td>


                                        <td>

                                            <?php if ($row['role'] === 'admin') { ?>

                                                <span class="role-badge role-admin">
                                                    Admin
                                                </span>

                                            <?php } else { ?>

                                                <span class="role-badge role-user">
                                                    User
                                                </span>

                                            <?php } ?>

                                        </td>


                                        <td>

                                            <div class="action-group">

                                                <a
                                                    href="edit_pengguna.php?id=<?php echo $row['id_user']; ?>"
                                                    class="edit-button"
                                                >
                                                    Edit
                                                </a>

                                                <?php if ($row['role'] === 'user') { ?>
                                                    <a
                                                        href="riwayat_pelanggan.php?id=<?php echo $row['id_user']; ?>"
                                                        class="history-button"
                                                    >
                                                        Riwayat
                                                    </a>
                                                <?php } ?>

                                                <form
                                                    method="POST"
                                                    action="hapus_pengguna.php"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('Hapus pengguna ini? Data yang sudah memiliki pesanan tidak dapat dihapus.');"
                                                >
                                                    <input type="hidden" name="id_user" value="<?php echo $row['id_user']; ?>">
                                                    <button
                                                        type="submit"
                                                        class="delete-button"
                                                        <?php echo ((int)$row['id_user'] === (int)$_SESSION['id_user']) ? 'disabled title="Akun yang sedang digunakan tidak dapat dihapus."' : ''; ?>
                                                    >
                                                        Hapus
                                                    </button>
                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php } ?>

                            <?php } else { ?>

                                <tr>

                                    <td
                                        colspan="6"
                                        class="empty-data"
                                    >
                                        Belum ada data pengguna.
                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>