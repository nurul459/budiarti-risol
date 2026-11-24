<?php
include "auth.php";
include "../config/koneksi.php";
include "../config/ensure_erp_tables.php";

ensureCategoryImageColumn($koneksi);

$isModal = isset($_GET['modal']) && $_GET['modal'] === '1';

$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kategori'])) {

    $nama = trim($_POST['nama_kategori'] ?? '');

    if ($nama === '') {
        $pesan_error = 'Nama kategori tidak boleh kosong.';
    }

    if ($pesan_error === '') {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_category
             FROM categories
             WHERE nama_category = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, 's', $nama);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_get_result($stmt)->num_rows) {
            $pesan_error = 'Kategori tersebut sudah ada.';
        }

        mysqli_stmt_close($stmt);
    }

    if ($pesan_error === '') {

        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO categories (nama_category)
             VALUES (?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $nama
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            if ($isModal) {
                echo '<!doctype html><html lang="id"><head><meta charset="UTF-8"><title>Berhasil</title></head><body>';
                echo '<script>';
                echo 'if (window.parent && window.parent !== window) {';
                echo 'window.parent.location.href="produk.php?pesan=tambah_kategori_berhasil";';
                echo '} else {';
                echo 'window.location.href="kategori.php?pesan=tambah_berhasil";';
                echo '}'
                . '</script></body></html>';
                exit;
            }

            header('Location: kategori.php?pesan=tambah_berhasil');
            exit;
        }

        mysqli_stmt_close($stmt);
        $pesan_error = 'Gagal menyimpan kategori: ' . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori - Budiarti Risol</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #F8EFE5;
            color: #542D1C;
            margin: 0;
        }

        .wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 30px;
        }

        .back {
            display: inline-block;
            margin-bottom: 15px;
            color: #6b3518;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        h1 {
            font-family: Georgia, serif;
            font-size: 28px;
            margin: 0 0 7px;
            color: #542D1C;
        }

        .intro {
            margin: 0 0 22px;
            color: #8a7669;
            font-size: 13px;
            line-height: 1.5;
        }

        .card {
            background: #fff;
            padding: 28px;
            border-radius: 14px;
            border: 1px solid #E5D3C3;
            box-shadow: 0 4px 15px rgba(80, 40, 10, .06);
        }

        .field {
            margin: 0 0 20px;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #542D1C;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5dbd2;
            border-radius: 8px;
            background: #fff;
            color: #542D1C;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        input:focus {
            border-color: #C99B78;
            outline: none;
        }

        small {
            display: block;
            color: #8a7669;
            font-size: 10px;
            line-height: 1.5;
            margin-top: 7px;
        }

        button {
            background: #542D1C;
            color: #fff;
            border: 0;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
        }

        button:hover {
            background: #6c3a24;
        }

        .err {
            background: #fde3e5;
            color: #a52c38;
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 18px;
        }
/* Saat dibuka dari modal, tampilkan isi form saja. */
        body.modal-mode {
            background: #F8EFE5;
        }

        .modal-mode .wrap {
            max-width: none;
            margin: 0;
            padding: 20px;
        }

        .modal-mode .back {
            display: none;
        }

        .modal-mode .card {
            border: 0;
            box-shadow: none;
            background: transparent;
            padding: 0;
        }

        .modal-mode h1 {
            font-size: 25px;
            margin-bottom: 6px;
        }

        .modal-mode .intro {
            margin-bottom: 20px;
        }

        .modal-mode .actions {
            position: sticky;
            bottom: 0;
            background: #F8EFE5;
            padding: 12px 0 2px;
            z-index: 2;
        }

        @media (max-width: 650px) {
            .wrap {
                padding: 20px;
            }

            .card {
                padding: 20px;
            }

            .modal-mode .wrap {
                padding: 12px;
            }
        }
    </style>
</head>

<body class="<?= $isModal ? 'modal-mode' : ''; ?>">

<div class="wrap">

    <a class="back" href="produk.php">
        ← Kembali ke Daftar Produk
    </a>

    <h1>
        Tambah Kategori
    </h1>

    <p class="intro">
        Tambahkan kategori untuk mengelompokkan produk Budiarti Risol.
    </p>

    <?php if ($pesan_error): ?>
        <div class="err">
            <?= htmlspecialchars($pesan_error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <div class="field">
                <label for="nama_kategori">Nama Kategori *</label>
                <input type="text" id="nama_kategori" name="nama_kategori" required
                       value="<?= htmlspecialchars($_POST['nama_kategori'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Contoh: Risol Ayam">
                <small>Gunakan nama kategori yang singkat dan mudah dikenali.</small>
            </div>
            <div class="actions">
                <button type="submit" name="simpan_kategori">Simpan Kategori</button>
            </div>
        </form>
    </div>

</div>



</body>
</html>
