
<?php
session_start();

include "../config/koneksi.php";
include "../config/pengunjung.php";

catat_pengunjung($koneksi);

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = (int) $_SESSION['id_user'];

/* =========================================================
   BUAT TABEL JIKA BELUM ADA
========================================================= */
mysqli_query($koneksi, "
    CREATE TABLE IF NOT EXISTS pengembalian_penukaran (
        id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
        id_order INT NOT NULL,
        id_user INT NOT NULL,
        jenis ENUM('Pengembalian','Penukaran') NOT NULL,
        alasan VARCHAR(100) NOT NULL,
        detail_alasan TEXT NULL,
        bukti_foto VARCHAR(255) NULL,
        status ENUM(
            'Diajukan',
            'Diproses',
            'Disetujui',
            'Ditolak',
            'Selesai'
        ) NOT NULL DEFAULT 'Diajukan',
        catatan_admin TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_pengajuan_order (id_order),
        KEY idx_pengajuan_user (id_user),
        KEY idx_pengajuan_status (status)
    )
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
");

/* TAMBAHKAN KOLOM FOTO JIKA BELUM ADA */
$cek_kolom = mysqli_query(
    $koneksi,
    "SHOW COLUMNS FROM pengembalian_penukaran LIKE 'bukti_foto'"
);

if ($cek_kolom && mysqli_num_rows($cek_kolom) === 0) {
    mysqli_query(
        $koneksi,
        "ALTER TABLE pengembalian_penukaran
         ADD COLUMN bukti_foto VARCHAR(255) NULL
         AFTER detail_alasan"
    );
}

$msg = '';
$err = '';

$selected_order = (int) (
    $_GET['id_order']
    ?? $_POST['id_order']
    ?? 0
);

/* =========================================================
   PROSES PENGAJUAN
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_order = (int) ($_POST['id_order'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');
    $detail = trim($_POST['detail_alasan'] ?? '');

    $allowed = ['Produk tidak sesuai'];

    if (
        !$id_order ||
        !in_array($jenis, ['Pengembalian', 'Penukaran'], true) ||
        !in_array($alasan, $allowed, true)
    ) {
        $err = 'Data pengajuan tidak valid.';
    } else {

        /* CEK PESANAN */
        $st = mysqli_prepare(
            $koneksi,
            "SELECT id_order, status
             FROM orders
             WHERE id_order = ?
               AND id_user = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($st, 'ii', $id_order, $id_user);
        mysqli_stmt_execute($st);

        $ord = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

        mysqli_stmt_close($st);

        if (!$ord) {
            $err = 'Pesanan tidak ditemukan.';
        } elseif (strtolower($ord['status']) !== 'selesai') {
            $err = 'Pengajuan hanya dapat dibuat setelah pesanan selesai.';
        } else {

            /* CEK PENGAJUAN SEBELUMNYA */
            $st = mysqli_prepare(
                $koneksi,
                "SELECT id_pengajuan
                 FROM pengembalian_penukaran
                 WHERE id_order = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param($st, 'i', $id_order);
            mysqli_stmt_execute($st);

            $cek = mysqli_stmt_get_result($st);
            $sudah_ada = mysqli_num_rows($cek) > 0;

            mysqli_stmt_close($st);

            if ($sudah_ada) {
                $err = 'Pesanan ini sudah memiliki pengajuan.';
            } else {

                /* VALIDASI FOTO */
                if (
                    !isset($_FILES['bukti_foto']) ||
                    $_FILES['bukti_foto']['error'] !== UPLOAD_ERR_OK
                ) {
                    $err = 'Bukti foto produk wajib diupload.';
                } else {

                    $file = $_FILES['bukti_foto'];
                    $max_size = 5 * 1024 * 1024;

                    $allowed_mime = [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if ($file['size'] > $max_size) {
                        $err = 'Ukuran foto maksimal 5 MB.';
                    } elseif (!in_array($mime, $allowed_mime, true)) {
                        $err = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
                    } else {

                        /* FOLDER UPLOAD */
                        $upload_dir = __DIR__ . '/../uploads/pengembalian/';

                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $extensions = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp'
                        ];

                        $extension = $extensions[$mime];

                        $filename =
                            'pengembalian_' .
                            $id_user . '_' .
                            $id_order . '_' .
                            time() . '_' .
                            bin2hex(random_bytes(4)) .
                            '.' . $extension;

                        $target = $upload_dir . $filename;

                        if (!move_uploaded_file($file['tmp_name'], $target)) {
                            $err = 'Foto gagal diupload. Silakan coba lagi.';
                        } else {

                            $foto_db = 'uploads/pengembalian/' . $filename;

                            /* SIMPAN PENGAJUAN */
                            $st = mysqli_prepare(
                                $koneksi,
                                "INSERT INTO pengembalian_penukaran
                                (
                                    id_order,
                                    id_user,
                                    jenis,
                                    alasan,
                                    detail_alasan,
                                    bukti_foto
                                )
                                VALUES (?, ?, ?, ?, ?, ?)"
                            );

                            mysqli_stmt_bind_param(
                                $st,
                                'iissss',
                                $id_order,
                                $id_user,
                                $jenis,
                                $alasan,
                                $detail,
                                $foto_db
                            );

                            if (mysqli_stmt_execute($st)) {
                                $msg = 'Pengajuan berhasil dikirim dan menunggu diproses admin.';
                                $selected_order = 0;
                            } else {
                                if (file_exists($target)) {
                                    unlink($target);
                                }

                                $err = 'Pengajuan gagal disimpan.';
                            }

                            mysqli_stmt_close($st);
                        }
                    }
                }
            }
        }
    }
}

/* =========================================================
   PESANAN YANG BISA DIAJUKAN
========================================================= */
$orders = mysqli_query(
    $koneksi,
    "SELECT
        o.id_order,
        o.created_at,
        o.total_pembayaran,
        o.status
     FROM orders o
     LEFT JOIN pengembalian_penukaran p
        ON p.id_order = o.id_order
     WHERE o.id_user = $id_user
       AND LOWER(o.status) = 'selesai'
       AND p.id_pengajuan IS NULL
     ORDER BY o.created_at DESC"
);

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pengembalian & Penukaran - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #F8EFE5;
            color: #542D1C;
            font-family: Arial, sans-serif;
        }

        /* NAVBAR */
        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: -35px;
        }

        .back-circle {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff98a;
            color: #542D1C;
            font-size: 17px;
            text-decoration: none;
            transition: .2s ease;
        }

        .back-circle:hover {
            background: #EBD3C0;
        }

        .brand-copy strong {
            color: #ffffff;
        }

        /* WRAPPER */
        .wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 42px 20px 0;
        }

        /* HEADER */
        .head {
            text-align: center;
            margin: 0 0 28px;
        }

        .head h1 {
            margin: 0 0 5px;
            font-family: Georgia, serif;
            color: #542D1C;
            font-size: 34px;
            line-height: 1.2;
        }

        .head p {
            margin: 0;
            color: #806d5e;
            font-size: 13px;
            line-height: 1.6;
        }

        /* ALERT */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin: 0 0 20px;
            font-size: 12px;
        }

        .ok {
            background: #e1f3df;
            color: #39713d;
        }

        .bad {
            background: #fde2df;
            color: #9a342d;
        }

        /* CARD */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 22px 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 18px rgba(70, 35, 15, .07);
        }

        .card h3 {
            margin: 0 0 4px;
            font-family: Georgia, serif;
            color: #542D1C;
            font-size: 20px;
        }

        .section-note {
            color: #806d5e;
            font-size: 12px;
            line-height: 1.7;
            margin: 0 0 17px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee7e0;
            text-align: left;
            font-size: 12px;
        }

        th {
            color: #765e4c;
            font-weight: 600;
        }

        td {
            color: #542D1C;
        }

        .empty-cell {
            text-align: center !important;
            color: #806d5e;
            padding: 28px !important;
        }

        /* BUTTON */
        .btn {
            display: inline-block;
            border: 0;
            background: #542D1C;
            color: #fff;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .btn:hover {
            background: #65351d;
            transform: translateY(-1px);
        }

        /* FORM */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 11px;
            font-weight: bold;
            color: #542D1C;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #e1d4c8;
            border-radius: 8px;
            background: #fff;
            color: #542D1C;
            font-size: 12px;
            font-family: Arial, sans-serif;
            outline: none;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #b77a50;
        }

        .field textarea {
            min-height: 80px;
            resize: vertical;
        }

        .full {
            grid-column: 1 / -1;
        }

        /* UPLOAD FOTO */
        .photo-upload {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .photo-upload input[type="file"] {
            width: 100%;
            min-height: 42px;
            padding: 6px 8px;
            border: 1px solid #e5d3c3;
            border-radius: 8px;
            background: #fffdfb;
            color: #542D1C;
            font-size: 12px;
            font-family: Arial, sans-serif;
            cursor: pointer;
        }

        .photo-upload input[type="file"]::file-selector-button {
            margin-right: 10px;
            padding: 8px 12px;
            border: 0;
            border-radius: 6px;
            background: #EBD3C0;
            color: #542D1C;
            font: 600 11px Arial, sans-serif;
            cursor: pointer;
        }

        .photo-upload input[type="file"]::file-selector-button:hover {
            background: #C99B78;
        }

        .photo-help {
            margin: 0;
            color: #927c6d;
            font-size: 10px;
            line-height: 1.5;
        }

        /* RESPONSIVE */
        @media (max-width: 720px) {
            .brand-group {
                margin-left: 0;
            }

            .wrap {
                padding: 32px 16px 0;
            }

            .head h1 {
                font-size: 29px;
            }
        }

        @media (max-width: 650px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            .card {
                overflow-x: auto;
                padding: 18px 16px;
            }

            table {
                min-width: 650px;
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

<!-- NAVBAR -->
<header class="navbar">
    <div class="brand-group">

        <a
            href="index.php"
            class="back-circle"
            title="Kembali ke halaman utama"
            aria-label="Kembali"
        >&#8592;</a>

        <a class="brand" href="index.php">
            <span class="brand-mark">👩‍🍳</span>

            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>
        </a>

    </div>
</header>

<!-- CONTENT -->
<div class="wrap">

    <div class="head">
        <h1>Pengembalian & Penukaran</h1>

        <p>
            Ajukan pengembalian atau penukaran
            untuk pesanan yang sudah selesai.
        </p>
    </div>

    <?php if ($msg): ?>
        <div class="alert ok">
            <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert bad">
            <?= e($err) ?>
        </div>
    <?php endif; ?>

    <!-- PESANAN YANG BISA DIAJUKAN -->
    <div class="card">

        <h3>Pesanan yang Bisa Dikembalikan</h3>

        <p class="section-note">
            Hanya pesanan yang sudah selesai dan belum
            memiliki pengajuan pengembalian atau penukaran
            yang ditampilkan di sini.
        </p>

        <table>
            <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $ada_pesanan = false;

                while ($o = mysqli_fetch_assoc($orders)):
                    $ada_pesanan = true;
                ?>

                    <tr>
                        <td>
                            #ORD<?= str_pad(
                                $o['id_order'],
                                3,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </td>

                        <td>
                            <?= e(date(
                                'd/m/Y',
                                strtotime($o['created_at'])
                            )) ?>
                        </td>

                        <td>
                            Rp<?= number_format(
                                (float) $o['total_pembayaran'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <a
                                class="btn"
                                href="pengembalian.php?id_order=<?= $o['id_order'] ?>"
                            >Ajukan</a>
                        </td>
                    </tr>

                <?php endwhile; ?>

                <?php if (!$ada_pesanan): ?>
                    <tr>
                        <td colspan="4" class="empty-cell">
                            Belum ada pesanan yang dapat
                            diajukan untuk pengembalian
                            atau penukaran.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- FORM PENGAJUAN -->
    <?php if ($selected_order > 0): ?>

        <div class="card">

            <h3>Buat Pengajuan</h3>

            <form
                method="post"
                enctype="multipart/form-data"
                class="grid"
            >

                <input
                    type="hidden"
                    name="id_order"
                    value="<?= $selected_order ?>"
                >

                <div class="field">
                    <label>Pesanan</label>

                    <input
                        type="text"
                        value="#ORD<?= str_pad(
                            $selected_order,
                            3,
                            '0',
                            STR_PAD_LEFT
                        ) ?>"
                        readonly
                    >
                </div>

                <div class="field">
                    <label>Jenis</label>

                    <select name="jenis" required>
                        <option value="Pengembalian">
                            Pengembalian
                        </option>

                        <option value="Penukaran">
                            Penukaran
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label>Alasan</label>

                    <select name="alasan" required>
                        <option value="Produk tidak sesuai">
                            Produk tidak sesuai
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label>Bukti Foto Produk</label>

                    <div class="photo-upload">
                        <input
                            type="file"
                            name="bukti_foto"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >

                        <p class="photo-help">
                            Upload foto produk yang ingin
                            dikembalikan atau ditukar.
                            Maksimal 5 MB.
                        </p>
                    </div>
                </div>

                <div class="field full">
                    <label>Detail Alasan</label>

                    <textarea
                        name="detail_alasan"
                        placeholder="Jelaskan kondisi produk atau ketidaksesuaian yang dialami."
                    ></textarea>
                </div>

                <div class="full">
                    <button type="submit" class="btn">
                        Kirim Pengajuan
                    </button>
                </div>

            </form>
        </div>

    <?php endif; ?>

</div>

<!-- FOOTER: MENGIKUTI ORDERS.PHP DAN STYLE.CSS -->
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

</body>
</html>
