
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

/* CEK LOGIN */
if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$id_order = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_order || $id_order <= 0) {
    header("Location: orders.php");
    exit;
}

/* AMBIL DATA PESANAN */
$stmt_order = mysqli_prepare(
    $koneksi,
    "SELECT *
     FROM orders
     WHERE id_order = ? AND id_user = ?
     LIMIT 1"
);

if (!$stmt_order) {
    die("Gagal menyiapkan data pesanan.");
}

mysqli_stmt_bind_param($stmt_order, "ii", $id_order, $id_user);
mysqli_stmt_execute($stmt_order);

$result_order = mysqli_stmt_get_result($stmt_order);
$order = mysqli_fetch_assoc($result_order);

mysqli_stmt_close($stmt_order);

if (!$order) {
    header("Location: orders.php");
    exit;
}

/* AMBIL DETAIL PRODUK */
$stmt_detail = mysqli_prepare(
    $koneksi,
    "SELECT
        order_details.*,
        products.nama_product
     FROM order_details
     JOIN products
        ON order_details.id_product = products.id_product
     WHERE order_details.id_order = ?"
);

if (!$stmt_detail) {
    die("Gagal mengambil detail produk.");
}

mysqli_stmt_bind_param($stmt_detail, "i", $id_order);
mysqli_stmt_execute($stmt_detail);

$query_detail = mysqli_stmt_get_result($stmt_detail);
$jumlah_produk = mysqli_num_rows($query_detail);

/* HITUNG SUBTOTAL PRODUK */
$subtotal_produk = 0;

while ($row = mysqli_fetch_assoc($query_detail)) {
    $subtotal_produk += (float) $row['subtotal'];
}

mysqli_data_seek($query_detail, 0);

/* DATA PEMBAYARAN */
$kode_promo = $order['kode_promo'] ?? '';
$diskon = (float) ($order['diskon'] ?? 0);
$total_pembayaran = max(0, $subtotal_produk - $diskon);

/* STATUS PESANAN */
$status = trim($order['status'] ?? '');

if ($status === '') {
    $status = 'Diproses';
}

$status_lower = strtolower($status);

if ($status_lower == 'selesai' || $status_lower == 'lunas') {
    $bg_status = '#e8f5e9';
    $color_status = '#2e7d32';
} elseif (
    $status_lower == 'diproses' ||
    $status_lower == 'menunggu verifikasi'
) {
    $bg_status = '#e8f0fe';
    $color_status = '#2458a6';
} elseif ($status_lower == 'dibatalkan') {
    $bg_status = '#fdeaea';
    $color_status = '#b83232';
} else {
    $bg_status = '#fff1dc';
    $color_status = '#95601e';
}

/* CEK BUKTI PEMBAYARAN QRIS */
$metode = strtoupper(trim($order['metode_pembayaran'] ?? ''));
$is_qris = ($metode === 'QRIS');

$path_bukti = '';
$bukti_tersedia = false;

if ($is_qris && !empty($order['bukti_pembayaran'])) {
    $nama_file_bukti = ltrim($order['bukti_pembayaran'], '/');
    $lokasi_bukti = __DIR__ . '/../assets/uploads/' . $nama_file_bukti;

    $path_bukti = '../assets/uploads/' . $nama_file_bukti;
    $bukti_tersedia = is_file($lokasi_bukti);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Detail Pesanan | Budiarti Risol</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>

/* BODY */
body {
    margin: 0;
    background: #F8EFE5;
    color: #542D1C;
    font-family: 'DM Sans', Arial, sans-serif;
}

/* HEADER */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    background: #542D1C;
    min-height: 65px;
    box-sizing: border-box;
}

.brand-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.back-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    min-width: 30px;
    background: #fafc96;
    color: #542D1C;
    border-radius: 50%;
    text-decoration: none;
    font-size: 17px;
    font-weight: bold;
    transition: .3s;
}

.back-circle:hover {
    background: #f0ed70;
    transform: translateX(-3px);
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.brand-mark {
    font-size: 28px;
}

.brand-copy {
    display: flex;
    flex-direction: column;
}

.brand-copy strong {
    font-family: Georgia, serif;
    font-size: 22px;
    color: #ffffff;
    line-height: 1.2;
}

.brand-copy small {
    font-size: 9px;
    letter-spacing: 1.2px;
    color: #e0d0b8;
    margin-top: 4px;
}

/* CONTAINER */
.detail-container {
    width: 88%;
    max-width: 1050px;
    margin: 45px auto 70px;
}

/* HEADING */
.detail-heading {
    text-align: center;
    margin-bottom: 35px;
}

.detail-heading h1 {
    font-family: Georgia, serif;
    font-size: 36px;
    color: #542D1C;
    margin: 0 0 10px;
}

.detail-heading p {
    color: #806d5e;
    font-size: 14px;
    margin: 0;
}

/* CARD */
.detail-card {
    background: #fff;
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 22px;
    box-shadow: 0 5px 22px rgba(59, 29, 13, .07);
    border: 1px solid #f0e8df;
}

.card-heading {
    font-family: Georgia, serif;
    font-size: 21px;
    color: #542D1C;
    margin: 0 0 24px;
}

/* ORDER NUMBER */
.order-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding-bottom: 22px;
    border-bottom: 1px solid #eee8e2;
}

.order-caption {
    font-size: 11px;
    color: #a17b5b;
    letter-spacing: 1.2px;
    margin-bottom: 8px;
}

.order-number {
    font-size: 22px;
    font-weight: 700;
    color: #542D1C;
}

.status-badge {
    padding: 9px 18px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* INFORMATION */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 25px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 0;
}

.info-label {
    color: #9b8878;
    font-size: 12px;
}

.info-value {
    color: #493426;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

/* PRODUCT TABLE */
.product-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 550px;
}

.product-table th {
    background: #faf4eb;
    color: #76502e;
    font-size: 12px;
    font-weight: 700;
    padding: 16px 14px;
    text-align: left;
}

.product-table td {
    padding: 18px 14px;
    border-bottom: 1px solid #f0ebe5;
    color: #493426;
    font-size: 13px;
}

.product-table tbody tr:last-child td {
    border-bottom: none;
}

.product-name {
    font-weight: 700;
    color: #542D1C;
}

.product-quantity {
    background: #fff4df;
    border-radius: 20px;
    padding: 6px 13px;
    display: inline-block;
    font-weight: 700;
}

/* PAYMENT SUMMARY */
.payment-summary {
    background: #faf5eb;
    border: 1px solid #eee2cc;
    border-radius: 13px;
    padding: 23px;
    margin-top: 25px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    color: #806d5e;
    font-size: 14px;
}

.summary-row strong {
    color: #493426;
    text-align: right;
}

.discount-value {
    color: #328044 !important;
}

.summary-divider {
    border: none;
    border-top: 1px solid #e7d9c3;
    margin: 18px 0;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    color: #542D1C;
    font-size: 15px;
    font-weight: 700;
}

.summary-total strong {
    font-size: 23px;
}

/* BUKTI PEMBAYARAN */
.proof-box {
    margin-top: 22px;
    padding: 23px;
    background: #fffdf8;
    border: 1px solid #eee4d8;
    border-radius: 13px;
}

.proof-title {
    font-family: Georgia, serif;
    color: #542D1C;
    font-size: 21px;
    margin: 0 0 15px;
}

.proof-image {
    display: block;
    max-width: 100%;
    max-height: 400px;
    margin: 18px auto;
    border-radius: 10px;
    object-fit: contain;
}

.proof-success {
    color: #328044;
    font-size: 13px;
}

.proof-warning {
    background: #fff0d0;
    color: #95601e;
    padding: 13px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.6;
}

.proof-error {
    color: #b83232;
    font-size: 13px;
}

/* BUTTON */
.button-group {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 28px;
}

.back-button,
.home-button {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 13px 22px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    transition: .25s;
}

.back-button {
    background: #542D1C;
    color: #fff;
}

.back-button:hover {
    background: #70401f;
    transform: translateY(-2px);
}

.home-button {
    background: #fff;
    color: #542D1C;
    border: 1px solid #e8d6c5;
}

.home-button:hover {
    background: #faf4eb;
}

/* FOOTER */
.footer {
    text-align: center;
    padding: 45px 20px 30px;
    background: #542D1C;
    color: #e0d0b8;
    margin-top: 60px;
}

.footer h2 {
    font-family: Georgia, serif;
    font-size: 27px;
    color: #ffffff;
    margin: 10px 0;
}

.footer p {
    font-size: 13px;
    color: #e0d0b8;
    margin: 10px 0 20px;
}

.footer-line {
    display: block;
    width: 50px;
    height: 2px;
    background: #ffffff;
    margin: 20px auto;
}

.footer small {
    font-size: 11px;
    color: #bfae99;
}

/* RESPONSIVE */
@media (max-width: 650px) {

    .navbar {
        padding: 13px 18px;
    }

    .brand-group {
        gap: 10px;
    }

    .brand-mark {
        font-size: 23px;
    }

    .brand-copy strong {
        font-size: 22px;
    }

    .brand-copy small {
        font-size: 8px;
    }

    .back-circle {
        width: 35px;
        height: 35px;
        min-width: 35px;
        font-size: 18px;
    }

    .detail-container {
        width: 92%;
        margin: 30px auto 50px;
    }

    .detail-heading {
        margin-bottom: 25px;
    }

    .detail-heading h1 {
        font-size: 29px;
    }

    .detail-card {
        padding: 20px;
    }

    .order-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .info-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .summary-total {
        align-items: flex-start;
        flex-direction: column;
    }

    .summary-total strong {
        font-size: 21px;
    }

    .button-group {
        flex-direction: column;
    }

    .back-button,
    .home-button {
        width: 100%;
        box-sizing: border-box;
    }

    .footer {
        padding: 35px 15px 25px;
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

    <div class="brand-group">

        <a href="orders.php"
           class="back-circle"
           title="Kembali ke Pesanan Saya"
           aria-label="Kembali">
            &#8592;
        </a>

        <a class="brand" href="index.php">

            <span class="brand-mark">👩‍🍳</span>

            <span class="brand-copy">
                <strong>Budiarti Risol</strong>
                <small>HOMEMADE • FRESHLY MADE</small>
            </span>

        </a>

    </div>

</header>

<main class="detail-container">

    <div class="detail-heading">

        <h1>Detail Pesanan</h1>

        <p>
            Informasi lengkap mengenai pesanan kamu.
        </p>

    </div>

    <!-- INFORMASI PESANAN -->

    <section class="detail-card">

        <div class="order-top">

            <div>

                <div class="order-caption">
                    NOMOR PESANAN
                </div>

                <div class="order-number">
                    #ORD<?= str_pad(
                        $order['id_order'],
                        3,
                        '0',
                        STR_PAD_LEFT
                    ); ?>
                </div>

            </div>

            <div
                class="status-badge"
                style="background: <?= $bg_status ?>; color: <?= $color_status ?>;"
            >
                <?= htmlspecialchars($status); ?>
            </div>

        </div>

        <div style="padding-top: 25px;">

            <h2 class="card-heading">
                Informasi Pesanan
            </h2>

            <div class="info-grid">

                <div class="info-item">
                    <span class="info-label">Metode Pembayaran</span>
                    <span class="info-value">
                        <?= htmlspecialchars($order['metode_pembayaran'] ?? '-'); ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="info-label">Tanggal Pesanan</span>
                    <span class="info-value">
                        <?= !empty($order['created_at'])
                            ? date('d F Y, H:i', strtotime($order['created_at']))
                            : '-'; ?>
                    </span>
                </div>

            </div>

        </div>

    </section>

    <!-- INFORMASI PENGIRIMAN -->

    <section class="detail-card">

        <h2 class="card-heading">
            Informasi Pengiriman
        </h2>

        <div class="info-grid">

            <div class="info-item">
                <span class="info-label">Nama Penerima</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['nama_penerima'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Nomor Handphone</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['no_hp'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Alamat Lengkap</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['alamat'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Kecamatan</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['kecamatan'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Kota</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['kota'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Provinsi</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['provinsi'] ?? '-'); ?>
                </span>
            </div>

            <div class="info-item">
                <span class="info-label">Kode Pos</span>
                <span class="info-value">
                    <?= htmlspecialchars($order['kode_pos'] ?? '-'); ?>
                </span>
            </div>

        </div>

    </section>

    <!-- PRODUK DAN PEMBAYARAN -->

    <section class="detail-card">

        <h2 class="card-heading">
            Produk yang Dibeli
        </h2>

        <div class="product-table-wrapper">

            <table class="product-table">

                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($jumlah_produk > 0): ?>

                        <?php while ($detail = mysqli_fetch_assoc($query_detail)): ?>

                            <tr>

                                <td class="product-name">
                                    <?= htmlspecialchars($detail['nama_product']); ?>
                                </td>

                                <td>
                                    Rp <?= number_format(
                                        (float) $detail['harga'],
                                        0,
                                        ',',
                                        '.'
                                    ); ?>
                                </td>

                                <td>
                                    <span class="product-quantity">
                                        <?= (int) $detail['jumlah']; ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        Rp <?= number_format(
                                            (float) $detail['subtotal'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </strong>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4">
                                Belum ada detail produk.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <!-- RINGKASAN PEMBAYARAN -->

        <div class="payment-summary">

            <div class="summary-row">

                <span>Subtotal Produk</span>

                <strong>
                    Rp <?= number_format(
                        $subtotal_produk,
                        0,
                        ',',
                        '.'
                    ); ?>
                </strong>

            </div>

            <?php if (!empty($kode_promo) && $diskon > 0): ?>

                <div class="summary-row">

                    <span>
                        Promo <?= htmlspecialchars($kode_promo); ?>
                    </span>

                    <strong class="discount-value">
                        - Rp <?= number_format(
                            $diskon,
                            0,
                            ',',
                            '.'
                        ); ?>
                    </strong>

                </div>

            <?php endif; ?>

            <hr class="summary-divider">

            <div class="summary-total">

                <span>Total Pembayaran</span>

                <strong>
                    Rp <?= number_format(
                        $total_pembayaran,
                        0,
                        ',',
                        '.'
                    ); ?>
                </strong>

            </div>

        </div>

        <!-- BUKTI PEMBAYARAN QRIS -->

        <?php if ($is_qris): ?>

            <div class="proof-box">

                <h3 class="proof-title">
                    Bukti Pembayaran QRIS
                </h3>

                <?php if ($bukti_tersedia): ?>

                    <img
                        src="<?= htmlspecialchars($path_bukti); ?>"
                        alt="Bukti pembayaran QRIS"
                        class="proof-image"
                    >

                    <p class="proof-success">
                        ✓ Bukti pembayaran berhasil diunggah.
                    </p>

                <?php else: ?>

                    <p class="proof-error">
                        Bukti pembayaran belum tersedia atau file tidak ditemukan.
                    </p>

                <?php endif; ?>

                <?php if ($status === 'Menunggu Verifikasi'): ?>

                    <p class="proof-warning">
                        Pembayaran kamu sedang menunggu pemeriksaan admin.
                        Setelah pembayaran dikonfirmasi, pesanan akan diproses.
                    </p>

                <?php elseif ($status === 'Dibatalkan'): ?>

                    <p class="proof-warning">
                        Pesanan ini telah dibatalkan.
                    </p>

                <?php else: ?>

                    <p class="proof-success">
                        Pembayaran sudah diverifikasi oleh admin.
                    </p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>

    <!-- TOMBOL -->

    <div class="button-group">

        <a href="orders.php" class="back-button">
            &#8592; Kembali ke Pesanan Saya
        </a>

        <a href="index.php" class="home-button">
            🏠 Kembali ke Beranda
        </a>

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

<?php
mysqli_stmt_close($stmt_detail);
?>

</body>
</html>
