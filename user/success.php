
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);
include "../config/ensure_erp_tables.php";

ensurePaymentTable($koneksi);

// Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$id_order = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_order <= 0) {
    header("Location: produk.php");
    exit;
}

// Mengambil pesanan milik user yang sedang login
$stmt = mysqli_prepare($koneksi, "
    SELECT *
    FROM orders
    WHERE id_order = ? AND id_user = ?
    LIMIT 1
");

if (!$stmt) {
    die("Gagal mengambil data pesanan.");
}

mysqli_stmt_bind_param($stmt, "ii", $id_order, $id_user);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Menghitung subtotal produk dari rincian pesanan
$stmt_detail = mysqli_prepare($koneksi, "
    SELECT SUM(subtotal) AS subtotal_produk
    FROM order_details
    WHERE id_order = ?
");

if (!$stmt_detail) {
    die("Gagal mengambil rincian pembayaran.");
}

mysqli_stmt_bind_param($stmt_detail, "i", $id_order);
mysqli_stmt_execute($stmt_detail);

$result_detail = mysqli_stmt_get_result($stmt_detail);
$detail = mysqli_fetch_assoc($result_detail);

mysqli_stmt_close($stmt_detail);

// Ambil status pembayaran terbaru agar halaman sukses selalu mengikuti
// verifikasi admin, baik untuk COD, QRIS, maupun Transfer Bank.
$status_pembayaran = 'Menunggu Verifikasi';
$stmt_payment = mysqli_prepare($koneksi, "SELECT status_pembayaran FROM pembayaran WHERE id_order = ? LIMIT 1");
if ($stmt_payment) {
    mysqli_stmt_bind_param($stmt_payment, "i", $id_order);
    mysqli_stmt_execute($stmt_payment);
    $result_payment = mysqli_stmt_get_result($stmt_payment);
    $payment_row = mysqli_fetch_assoc($result_payment);
    mysqli_stmt_close($stmt_payment);
    if (!empty($payment_row['status_pembayaran'])) {
        $status_pembayaran = trim($payment_row['status_pembayaran']);
    }
}

$subtotal_produk = (float) ($detail['subtotal_produk'] ?? 0);
$diskon = (float) ($order['diskon'] ?? 0);

// Total pembayaran tanpa ongkir
$total_pembayaran = max(0, $subtotal_produk - $diskon);

$status = $status_pembayaran;
$metode = strtoupper(trim($order['metode_pembayaran'] ?? ''));

$nomor_order = '#ORD' . str_pad($id_order, 3, '0', STR_PAD_LEFT);

$status_class = 'status-verifikasi';
switch ($status) {
    case 'Terverifikasi':
    case 'Lunas':
        $status_class = 'status-selesai';
        break;
    case 'Ditolak':
        $status_class = 'status-dibatalkan';
        break;
    case 'Belum Dibayar':
    case 'Menunggu Verifikasi':
    default:
        $status_class = 'status-verifikasi';
        break;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pesanan Berhasil - Budiarti Risol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .success-page {
            min-height: 70vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
        }

        .success-box {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 45px 35px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #e8f5e9;
            color: #3b873e;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
        }

        .success-box h1 {
            font-family: Georgia, serif;
            color: #542D1C;
            margin-bottom: 10px;
        }

        .success-box p {
            color: #777;
            line-height: 1.6;
        }

        .order-number {
            background: #faf5ef;
            padding: 15px;
            border-radius: 8px;
            margin: 25px 0;
            color: #542D1C;
            font-weight: bold;
        }

        .payment-detail {
            background: #fffdf8;
            border: 1px solid #eee4d8;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
            text-align: left;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 12px;
            color: #666;
            font-size: 14px;
        }

        .payment-row:last-child {
            margin-bottom: 0;
        }

        .payment-row strong {
            color: #542D1C;
            text-align: right;
        }

        .payment-total {
            border-top: 1px solid #e8ded2;
            padding-top: 14px;
            margin-top: 14px;
            font-size: 17px;
            font-weight: bold;
            color: #542D1C;
        }

        .status-order {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            margin: 10px 0;
        }

        .status-verifikasi {
            background: #fff0c9;
            color: #a66b0c;
        }

        .status-diproses {
            background: #fff0c9;
            color: #bd7a13;
        }

        .status-dikirim {
            background: #dceefa;
            color: #397fa9;
        }

        .status-selesai {
            background: #dcefdc;
            color: #47884d;
        }

        .status-dibatalkan {
            background: #eeeeee;
            color: #777777;
        }

        .payment-note {
            background: #fffaf0;
            border: 1px solid #f2dfb9;
            border-radius: 9px;
            padding: 13px;
            margin: 15px 0;
            font-size: 12px;
            color: #795b2c;
            line-height: 1.6;
            text-align: left;
        }

        .success-buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .success-buttons a {
            flex: 1;
            padding: 13px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-menu {
            background: #eee;
            color: #542D1C;
        }

        .btn-orders {
            background: #542D1C;
            color: white;
        }

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
            transition: 0.2s ease;
        }

        .back-circle:hover {
            background: #EBD3C0;
        }

        @media (max-width: 600px) {
            .success-buttons {
                flex-direction: column;
            }

            .success-box {
                padding: 35px 22px;
            }

            .brand-group {
                margin-left: 0;
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

        <a href="index.php"
           class="back-circle"
           title="Kembali ke halaman utama"
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

<main class="success-page">

    <div class="success-box">

        <div class="success-icon">✓</div>

        <h1>Pesanan Berhasil!</h1>

        <p>
            Terima kasih sudah memesan Budiarti Risol.
            Pesanan kamu berhasil dicatat oleh sistem.
        </p>

        <div class="order-number">
            Nomor Pesanan: <?= htmlspecialchars($nomor_order); ?>
        </div>

        <div class="payment-detail">

            <div class="payment-row">
                <span>Subtotal Produk</span>

                <strong>
                    Rp <?= number_format($subtotal_produk, 0, ',', '.'); ?>
                </strong>
            </div>

            <?php if (!empty($order['kode_promo']) && $diskon > 0): ?>

                <div class="payment-row">
                    <span>
                        Promo <?= htmlspecialchars($order['kode_promo']); ?>
                    </span>

                    <strong style="color:#3b873e;">
                        - Rp <?= number_format($diskon, 0, ',', '.'); ?>
                    </strong>
                </div>

            <?php endif; ?>

            <div class="payment-row payment-total">
                <span>Total Pembayaran</span>

                <strong>
                    Rp <?= number_format($total_pembayaran, 0, ',', '.'); ?>
                </strong>
            </div>

        </div>

        <p>
            Metode Pembayaran:
            <strong><?= htmlspecialchars($order['metode_pembayaran'] ?? '-'); ?></strong>
        </p>

        <div class="status-order <?= $status_class; ?>">
            Status: <?= htmlspecialchars($status); ?>
        </div>

        <?php if ($status === 'Menunggu Verifikasi'): ?>

            <div class="payment-note">
                <strong>Pembayaran sedang menunggu verifikasi.</strong><br>
                Admin akan memeriksa pesanan dan pembayaran kamu terlebih dahulu.
                Setelah admin mengonfirmasi, status akan berubah menjadi Terverifikasi.
            </div>

        <?php elseif ($status === 'Terverifikasi' || $status === 'Lunas'): ?>

            <div class="payment-note">
                <strong>Pembayaran sudah terverifikasi.</strong><br>
                Pesanan kamu selanjutnya diproses oleh admin.
            </div>

        <?php elseif ($status === 'Ditolak'): ?>

            <div class="payment-note">
                Pembayaran belum dapat diverifikasi. Silakan hubungi admin untuk informasi lebih lanjut.
            </div>

        <?php endif; ?>

        <p>
            Kamu bisa melihat perkembangan pesanan melalui halaman
            pesanan saya.
        </p>

        <div class="success-buttons">

            <a href="produk.php" class="btn-menu">
                Kembali ke Menu
            </a>

            <a href="orders.php" class="btn-orders">
                Lihat Pesanan Saya
            </a>

        </div>

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

</body>
</html>
