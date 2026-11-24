
<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);
include "../config/ensure_erp_tables.php";

ensurePaymentTable($koneksi);

/* Pastikan tabel pengembalian/penukaran tersedia untuk riwayat pesanan. */
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS pengembalian_penukaran (
    id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_user INT NOT NULL,
    jenis ENUM('Pengembalian','Penukaran') NOT NULL,
    alasan VARCHAR(100) NOT NULL,
    detail_alasan TEXT NULL,
    status ENUM('Diajukan','Diproses','Disetujui','Ditolak','Selesai') NOT NULL DEFAULT 'Diajukan',
    catatan_admin TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pengajuan_order (id_order),
    KEY idx_pengajuan_user (id_user),
    KEY idx_pengajuan_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* CEK LOGIN */
if (!isset($_SESSION['id_user']) || (int) $_SESSION['id_user'] <= 0) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

$isLogin = isset($_SESSION['id_user']);
$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$email_user = $_SESSION['email'] ?? '';
$inisial = strtoupper(substr(trim($nama_user), 0, 1));

/* AMBIL SEMUA PESANAN MILIK USER */
$stmt = mysqli_prepare($koneksi, "
    SELECT o.*,
           p.status_pembayaran,
           p.tanggal_bayar,
           COALESCE(pg.status_pengiriman, 'Belum Diproses') AS status_pengiriman,
           pg.kurir,
           r.id_pengajuan,
           r.jenis AS jenis_pengajuan,
           r.status AS status_pengajuan,
           r.catatan_admin AS catatan_pengajuan
    FROM orders o
    LEFT JOIN pembayaran p ON p.id_order = o.id_order
    LEFT JOIN pengiriman pg ON pg.id_order = o.id_order
    LEFT JOIN pengembalian_penukaran r ON r.id_order = o.id_order
    WHERE o.id_user = ?
    ORDER BY o.created_at DESC, o.id_order DESC
");

if (!$stmt) {
    die("Gagal mengambil data pesanan.");
}

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$query = mysqli_stmt_get_result($stmt);

/* FORMAT RUPIAH */
function rupiah($angka) {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/* NORMALISASI STATUS */
function normalisasiStatus($status) {
    return strtolower(trim((string) $status));
}

/* WARNA STATUS */
function warnaStatus($status) {
    switch (normalisasiStatus($status)) {
        case 'menunggu verifikasi':
            return ['#fff0d0', '#a66b0c'];

        case 'terverifikasi':
        case 'lunas':
            return ['#e8f5e9', '#2e7d32'];

        case 'diproses':
            return ['#e8f0fe', '#2458a6'];

        case 'dikirim':
            return ['#e0f2fe', '#1475a5'];

        case 'selesai':
            return ['#e8f5e9', '#2e7d32'];

        case 'dibatalkan':
            return ['#fdeaea', '#b83232'];

        default:
            return ['#fff1dc', '#95601e'];
    }
}

/* STATUS PEMBAYARAN */
function pembayaranSelesai($status) {
    return in_array(normalisasiStatus($status), [
        'terverifikasi',
        'lunas',
        'berhasil',
        'dibayar'
    ], true);
}

/* STATUS PEMESANAN */
function pemesananDimulai($status) {
    return in_array(normalisasiStatus($status), [
        'diproses',
        'sedang diproses',
        'dipersiapkan',
        'disiapkan',
        'dikirim',
        'dalam pengiriman',
        'sedang dikirim',
        'selesai'
    ], true);
}

/* STATUS PENGIRIMAN */
function pengirimanDimulai($status) {
    return in_array(normalisasiStatus($status), [
        'dikirim',
        'dalam pengiriman',
        'sedang dikirim',
        'selesai'
    ], true);
}

/* TAMPILAN KARTU PESANAN */
function tampilkanPesanan($order, $koneksi) {

    $id_order = (int) $order['id_order'];

    $status_teks = trim($order['status'] ?? '');
    if ($status_teks === '') {
        $status_teks = 'Menunggu Verifikasi';
    }

    $status_pembayaran_teks = trim($order['status_pembayaran'] ?? '');
    if ($status_pembayaran_teks === '') {
        $status_pembayaran_teks = 'Menunggu Verifikasi';
    }

    $status_pesanan = trim($order['status'] ?? 'Menunggu Verifikasi');

    if ($status_pesanan === '') {
        $status_pesanan = 'Diproses';
    }

    [$bg_status, $color_status] = warnaStatus($status_teks);

    $status_normal = normalisasiStatus($status_pesanan);

    $bayar_selesai = pembayaranSelesai($status_teks);
    $pesanan_diproses = pemesananDimulai($status_pesanan);
    $pengiriman_dimulai = pengirimanDimulai($status_pesanan);
    $pesanan_selesai = ($status_normal === 'selesai');
    $pesanan_dibatalkan = ($status_normal === 'dibatalkan');

    $pembayaran_class = $bayar_selesai ? 'completed' : 'current';

    if ($pengiriman_dimulai || $pesanan_selesai) {
        $pemesanan_class = 'completed';
    } elseif ($pesanan_diproses && $bayar_selesai) {
        $pemesanan_class = 'current';
    } else {
        $pemesanan_class = 'pending';
    }

    if ($pesanan_selesai) {
        $pengiriman_class = 'completed';
    } elseif ($pengiriman_dimulai && $bayar_selesai) {
        $pengiriman_class = 'current';
    } else {
        $pengiriman_class = 'pending';
    }

    /* SUBTOTAL PRODUK */
    $stmt_subtotal = mysqli_prepare($koneksi, "
        SELECT COALESCE(SUM(subtotal), 0) AS subtotal_produk
        FROM order_details
        WHERE id_order = ?
    ");

    if (!$stmt_subtotal) {
        $subtotal_produk = 0;
    } else {
        mysqli_stmt_bind_param($stmt_subtotal, "i", $id_order);
        mysqli_stmt_execute($stmt_subtotal);

        $result_subtotal = mysqli_stmt_get_result($stmt_subtotal);
        $data_subtotal = mysqli_fetch_assoc($result_subtotal);

        $subtotal_produk = (float) ($data_subtotal['subtotal_produk'] ?? 0);

        mysqli_stmt_close($stmt_subtotal);
    }

    $diskon = (float) ($order['diskon'] ?? 0);
    $total_pembayaran = max(0, $subtotal_produk - $diskon);
    $kode_promo = $order['kode_promo'] ?? '';
    ?>

    <div class="order-card">

        <div class="order-header">
            <div>
                <div class="order-label">NOMOR PESANAN</div>
                <div class="order-number">
                    #ORD<?= str_pad($id_order, 3, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <div class="order-status-area">
                <div class="status-badge"
                     style="background: <?= $bg_status; ?>; color: <?= $color_status; ?>;">
                    <?= htmlspecialchars($status_teks); ?>
                </div>

                <div class="status-caption">
                    Pembayaran: <?= htmlspecialchars($status_pembayaran_teks); ?>
                </div>
            </div>
        </div>

        <!-- PELACAKAN PESANAN -->
        <div class="tracking-section">
            <h3 class="tracking-title">Pelacakan Pesanan</h3>

            <?php if ($pesanan_dibatalkan): ?>

                <div class="tracking-cancelled">
                    Pesanan ini telah dibatalkan.
                </div>

            <?php else: ?>

                <div class="tracking-timeline">

                    <div class="tracking-item <?= $pembayaran_class; ?>">
                        <div class="tracking-circle">
                            <?= $bayar_selesai ? '✓' : '1'; ?>
                        </div>

                        <div class="tracking-name">Pembayaran</div>

                        <div class="tracking-description">
                            <?= $bayar_selesai
                                ? 'Pembayaran terverifikasi'
                                : 'Menunggu verifikasi pembayaran'; ?>
                        </div>
                    </div>

                    <div class="tracking-item <?= $pemesanan_class; ?>">
                        <div class="tracking-circle">
                            <?= ($pengiriman_dimulai || $pesanan_selesai) ? '✓' : '2'; ?>
                        </div>

                        <div class="tracking-name">Pemesanan</div>

                        <div class="tracking-description">
                            <?php
                            if ($pengiriman_dimulai || $pesanan_selesai) {
                                echo 'Pesanan sudah diproses';
                            } elseif ($pesanan_diproses && $bayar_selesai) {
                                echo 'Pesanan sedang disiapkan';
                            } else {
                                echo 'Menunggu pesanan diproses';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="tracking-item <?= $pengiriman_class; ?>">
                        <div class="tracking-circle">
                            <?= $pesanan_selesai ? '✓' : '3'; ?>
                        </div>

                        <div class="tracking-name">Pengiriman</div>

                        <div class="tracking-description">
                            <?php
                            if ($pesanan_selesai) {
                                echo 'Pesanan telah selesai';
                            } elseif ($pengiriman_dimulai) {
                                echo 'Pesanan sedang dikirim';
                            } else {
                                echo 'Menunggu pengiriman';
                            }
                            ?>
                        </div>
                    </div>

                </div>

            <?php endif; ?>
        </div>

        <!-- INFORMASI PENGIRIMAN -->
        <div class="order-section">
            <h3 class="section-title">Informasi Pengiriman</h3>

            <div class="order-info">

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
                    <span class="info-label">Alamat Pengiriman</span>
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

        <!-- RINCIAN PEMBAYARAN -->
        <div class="payment-detail">
            <h3 class="payment-title">Rincian Pembayaran</h3>

            <div class="payment-row">
                <span>Subtotal Produk</span>
                <strong><?= rupiah($subtotal_produk); ?></strong>
            </div>

            <?php if (!empty($kode_promo) && $diskon > 0): ?>
                <div class="payment-row">
                    <span>Promo <?= htmlspecialchars($kode_promo); ?></span>
                    <strong class="discount-value">
                        - <?= rupiah($diskon); ?>
                    </strong>
                </div>
            <?php endif; ?>

            <hr class="payment-divider">

            <div class="payment-row">
                <strong>Total Pembayaran</strong>
                <strong><?= rupiah($total_pembayaran); ?></strong>
            </div>
        </div>

        <?php if ($pesanan_selesai): ?>
            <div class="return-history-box">
                <div>
                    <div class="return-history-title">Pengembalian / Penukaran</div>
                    <?php if (!empty($order['id_pengajuan'])): ?>
                        <div class="return-history-status">
                            Pengajuan <?= htmlspecialchars($order['jenis_pengajuan'] ?? ''); ?> · <?= htmlspecialchars($order['status_pengajuan'] ?? 'Diajukan'); ?>
                        </div>
                        <?php if (!empty($order['catatan_pengajuan'])): ?>
                            <div class="return-history-note">Catatan admin: <?= htmlspecialchars($order['catatan_pengajuan']); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="return-history-description">Pesanan sudah selesai. Jika ada masalah dengan pesanan, kamu dapat mengajukan pengembalian atau penukaran.</div>
                    <?php endif; ?>
                </div>

                <?php if (empty($order['id_pengajuan'])): ?>
                    <a href="pengembalian.php?id_order=<?= $id_order; ?>" class="return-history-btn">Ajukan Pengembalian / Penukaran</a>
                <?php else: ?>
                    <a href="pengembalian.php?id_order=<?= $id_order; ?>" class="return-history-link">Lihat Pengajuan →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="order-actions">
            <a href="order_detail.php?id=<?= $id_order; ?>"
               class="detail-button">
                Lihat Detail Pesanan →
            </a>
        </div>

    </div>

    <?php
}

/* PISAHKAN PESANAN AKTIF DAN RIWAYAT */
$pesanan_aktif = [];
$riwayat_pesanan = [];

while ($order = mysqli_fetch_assoc($query)) {

    $status = normalisasiStatus($order['status'] ?? '');

    /*
     * Riwayat hanya berisi pesanan yang benar-benar berakhir.
     * Pembayaran terverifikasi/lunas TIDAK membuat pesanan pindah ke riwayat;
     * pesanan tetap aktif sampai status utamanya Selesai atau Dibatalkan.
     */
    if (in_array($status, ['selesai', 'dibatalkan'], true)) {
        $riwayat_pesanan[] = $order;
    } else {
        $pesanan_aktif[] = $order;
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pesanan Saya - Budiarti Risol</title>

<link rel="stylesheet" href="../assets/css/style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

/* ================= NAVBAR ================= */

.navbar {
    width: 100%;
    min-height: 78px;
    background: #542D1C;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 5%;
    box-sizing: border-box;
    gap: 25px;
    position: relative;
    z-index: 100;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    flex-shrink: 0;
}

.brand-mark {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 50%;
    background: #fff98a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.brand-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1;
}

.brand-copy strong {
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.5px;
    white-space: nowrap;
}

.brand-copy small {
    display: block;
    margin-top: 5px;
    font-family: 'DM Sans', sans-serif;
    font-size: 8px;
    font-weight: 500;
    letter-spacing: 1.5px;
    color: #fff;
    white-space: nowrap;
}

.nav-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 27px;
    flex: 1;
}

.nav-menu a {
    position: relative;
    padding: 8px 0;
    color: #fff;
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
    white-space: nowrap;
}

.nav-menu a:hover,
.nav-menu a.active {
    color: #fff98a;
}

.nav-menu a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 2px;
    background: #fff98a;
    transition: .25s;
}

.nav-menu a:hover::after,
.nav-menu a.active::after {
    width: 100%;
}

.nav-icons {
    display: flex;
    align-items: center;
    gap: 17px;
    flex-shrink: 0;
}

.nav-icon-link {
    color: #fff;
    text-decoration: none;
    font-size: 21px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: .2s;
}

.nav-icon-link:hover {
    color: #fff98a;
    transform: translateY(-2px);
}

/* DROPDOWN AKUN */

.nav-account {
    position: relative;
}

.account-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    border: none;
    background: transparent;
    color: white;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    padding: 5px;
}

.account-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #fff98a;
    color: #542D1C;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.account-arrow {
    font-size: 11px;
    transition: .2s;
}

.nav-account.open .account-arrow {
    transform: rotate(180deg);
}

.account-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    width: 255px;
    background: #fff;
    border-radius: 13px;
    box-shadow: 0 12px 35px rgba(0,0,0,.18);
    padding: 12px;
    display: none;
    z-index: 999;
    animation: dropdownShow .2s ease;
}

.nav-account.open .account-dropdown {
    display: block;
}

@keyframes dropdownShow {
    from {
        opacity: 0;
        transform: translateY(-7px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.account-user-info {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 8px 15px;
}

.account-user-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: #fff1c7;
    color: #542D1C;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.account-user-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
    gap: 4px;
}

.account-user-text strong {
    font-size: 13px;
    color: #542D1C;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-user-text small {
    font-size: 11px;
    color: #998575;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.account-divider {
    height: 1px;
    background: #eee7df;
    margin: 5px 0 8px;
}

.account-menu-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 11px 10px;
    border-radius: 8px;
    color: #5b3c29;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: .2s;
}

.account-menu-link:hover {
    background: #fff5df;
    color: #8b4513;
}

.menu-icon {
    width: 20px;
    text-align: center;
    font-size: 15px;
}

.account-menu-link.logout {
    color: #b83232;
}

.account-menu-link.logout:hover {
    background: #fff0f0;
}

.account-login-link {
    display: block;
    text-align: center;
    padding: 11px;
    border-radius: 8px;
    background: #542D1C;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

/* ================= HALAMAN PESANAN ================= */

body {
    background: #F8EFE5;
    margin: 0;
    color: #542D1C;
}

.orders-container {
    width: 88%;
    max-width: 1050px;
    margin: 45px auto 70px;
}

.orders-heading {
    text-align: center;
    margin-bottom: 30px;
}

.orders-heading .eyebrow {
    color: #95602e;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 3px;
    margin-bottom: 10px;
}

.orders-heading h1 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    color: #542D1C;
    margin: 0 0 10px;
}

.orders-heading p {
    color: #806d5e;
    font-size: 14px;
    margin: 0;
}

/* TAB PESANAN */

.order-tabs {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 30px;
}

.order-tab {
    border: 1px solid #d9c9b8;
    background: #fffdf4;
    color: #74513a;
    border-radius: 10px;
    padding: 13px 23px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.order-tab:hover {
    border-color: #542D1C;
}

.order-tab.active {
    background: #542D1C;
    color: #fff;
    border-color: #542D1C;
}

.tab-count {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 21px;
    height: 21px;
    padding: 0 5px;
    margin-left: 6px;
    border-radius: 20px;
    background: rgba(0,0,0,.08);
    font-size: 11px;
    box-sizing: border-box;
}

.order-tab.active .tab-count {
    background: rgba(255,255,255,.2);
    color: white;
}

.order-panel {
    display: none;
}

.order-panel.active {
    display: block;
}

.panel-heading {
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    color: #542D1C;
    margin: 0 0 20px;
}

/* KARTU PESANAN */

.order-card {
    background: #fff;
    border-radius: 18px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 8px 25px rgba(74,34,14,.08);
    border: 1px solid rgba(74,34,14,.05);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee8e2;
    gap: 15px;
}

.order-label {
    color: #a17b5b;
    font-size: 12px;
    margin-bottom: 6px;
}

.order-number {
    font-size: 18px;
    font-weight: bold;
    color: #542D1C;
}

.order-status-area {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 9px 17px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: bold;
    white-space: nowrap;
}

.status-caption {
    font-size: 11px;
    color: #8a7669;
    margin-top: 7px;
}

.order-section {
    padding: 22px 0;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    color: #542D1C;
    margin: 0 0 18px;
}

.order-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.info-label {
    font-size: 12px;
    color: #9b8878;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #493426;
    overflow-wrap: anywhere;
}

/* PELACAKAN */

.tracking-section {
    padding: 22px 0;
    border-bottom: 1px solid #eee8e2;
}

.tracking-title {
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    color: #542D1C;
    margin: 0 0 25px;
}

.tracking-timeline {
    display: flex;
    width: 100%;
}

.tracking-item {
    flex: 1;
    position: relative;
    text-align: center;
    min-width: 0;
}

.tracking-item:not(:last-child)::after {
    content: "";
    position: absolute;
    height: 3px;
    width: 100%;
    background: #e9e1d8;
    top: 17px;
    left: 50%;
    z-index: 0;
}

.tracking-item.completed:not(:last-child)::after {
    background: #6d9b55;
}

.tracking-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #eee9e3;
    color: #9b8b7d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: bold;
    margin: 0 auto 10px;
    position: relative;
    z-index: 1;
    border: 3px solid #fff;
    box-sizing: border-box;
}

.tracking-item.completed .tracking-circle {
    background: #6d9b55;
    color: white;
}

.tracking-item.current .tracking-circle {
    background: #fff0d0;
    color: #a66b0c;
    border: 3px solid #d99a36;
}

.tracking-name {
    font-size: 13px;
    font-weight: bold;
    color: #a39486;
    margin-bottom: 5px;
}

.tracking-item.completed .tracking-name {
    color: #4b783b;
}

.tracking-item.current .tracking-name {
    color: #a66b0c;
}

.tracking-description {
    font-size: 11px;
    color: #a39486;
    line-height: 1.5;
    padding: 0 5px;
}

.tracking-item.completed .tracking-description,
.tracking-item.current .tracking-description {
    color: #79695c;
}

.tracking-cancelled {
    padding: 13px 15px;
    border-radius: 9px;
    background: #fdeaea;
    color: #b83232;
    font-size: 12px;
    text-align: center;
}

/* RINCIAN PEMBAYARAN */

.payment-detail {
    background: #fffdf8;
    border: 1px solid #eee4d8;
    border-radius: 12px;
    padding: 20px;
    margin-top: 22px;
}

.payment-title {
    font-family: 'Playfair Display', serif;
    font-size: 19px;
    color: #542D1C;
    margin: 0 0 18px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 13px;
    color: #806d5e;
    font-size: 14px;
}

.payment-row strong {
    color: #493426;
    text-align: right;
}

.discount-value {
    color: #328044 !important;
}

.payment-divider {
    border: none;
    border-top: 1px solid #eee4d8;
    margin: 15px 0;
}

/* TOMBOL DETAIL */

.return-history-box {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-top:20px;
    padding:16px 18px;
    background:#fff8f1;
    border:1px solid #eadfd5;
    border-radius:12px;
}
.return-history-title {
    font-size:13px;
    font-weight:700;
    color:#542D1C;
    margin-bottom:5px;
}
.return-history-description,.return-history-status,.return-history-note {
    font-size:11px;
    line-height:1.55;
    color:#806d5e;
}
.return-history-status {
    color:#855016;
    font-weight:700;
}
.return-history-note {
    margin-top:4px;
}
.return-history-btn {
    flex-shrink:0;
    background:#542D1C;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    padding:10px 13px;
    font-size:11px;
    font-weight:700;
}
.return-history-link {
    flex-shrink:0;
    color:#8a421b;
    text-decoration:none;
    font-size:11px;
    font-weight:700;
}

.order-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 22px;
}

.detail-button {
    display: inline-block;
    padding: 13px 23px;
    background: #542D1C;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: bold;
    transition: .3s;
}

.detail-button:hover {
    background: #8b4513;
    transform: translateY(-2px);
}

/* PESANAN KOSONG */

.empty-order {
    background: white;
    border-radius: 18px;
    padding: 55px 25px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(74,34,14,.08);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-order h2 {
    font-family: 'Playfair Display', serif;
    color: #542D1C;
    margin-bottom: 10px;
}

.empty-order p {
    color: #88796c;
    font-size: 14px;
}

.btn-menu {
    display: inline-block;
    margin-top: 18px;
    padding: 13px 25px;
    background: #542D1C;
    color: white;
    text-decoration: none;
    border-radius: 9px;
    font-weight: bold;
    font-size: 13px;
}

.btn-menu:hover {
    background: #8b4513;
}

/* FOOTER */

.footer {
    margin-top: 50px;
    text-align: center;
    padding: 30px 20px;
}

/* RESPONSIVE */

@media (max-width: 900px) {
    .navbar {
        flex-wrap: wrap;
        padding: 15px 5%;
        gap: 15px;
    }

    .nav-menu {
        order: 3;
        flex-basis: 100%;
        gap: 20px;
        overflow-x: auto;
        justify-content: center;
    }
}

@media (max-width: 650px) {

    .navbar {
        min-height: 68px;
        padding: 13px 15px;
        gap: 10px;
    }

    .brand-copy strong {
        font-size: 21px;
    }

    .brand-copy small {
        font-size: 7px;
        letter-spacing: 1.2px;
    }

    .brand-mark {
        width: 36px;
        height: 36px;
        min-width: 36px;
        font-size: 21px;
    }

    .nav-menu {
        justify-content: flex-start;
        gap: 22px;
        padding: 5px 0;
    }

    .nav-menu a {
        font-size: 11px;
    }

    .nav-icons {
        gap: 10px;
    }

    .account-dropdown {
        width: 245px;
        right: 0;
    }

    .orders-container {
        width: 92%;
        margin-top: 30px;
    }

    .orders-heading h1 {
        font-size: 30px;
    }

    .order-tabs {
        gap: 8px;
    }

    .order-tab {
        flex: 1;
        padding: 12px 8px;
        font-size: 11px;
    }

    .order-card {
        padding: 20px;
    }

    .order-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-status-area {
        text-align: left;
    }

    .order-info {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .tracking-name {
        font-size: 11px;
    }

    .tracking-description {
        font-size: 10px;
    }

    .tracking-circle {
        width: 32px;
        height: 32px;
        font-size: 13px;
    }

    .tracking-item:not(:last-child)::after {
        top: 15px;
    }

    .detail-button {
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }

    .payment-row {
        font-size: 13px;
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

    <a class="brand" href="index.php">
        <span class="brand-mark">👩‍🍳</span>

        <span class="brand-copy">
            <strong>Budiarti Risol</strong>
            <small>HOMEMADE • FRESHLY MADE</small>
        </span>
    </a>

        <?php if ($isLogin): ?>

            <div class="nav-account" id="navAccount">

                <button
                    type="button"
                    class="account-trigger"
                    id="accountTrigger"
                    aria-expanded="false"
                    aria-label="Menu akun"
                >
                    <span class="account-avatar">
                        <?= e($inisial); ?>
                    </span>

                    <span class="account-arrow">▼</span>
                </button>

                <div class="account-dropdown">

                    <div class="account-user-info">

                        <div class="account-user-avatar">
                            <?= e($inisial); ?>
                        </div>

                        <div class="account-user-text">
                            <strong><?= e($nama_user); ?></strong>
                            <small><?= e($email_user); ?></small>
                        </div>

                    </div>

                    <div class="account-divider"></div>

                    <a href="profil.php" class="account-menu-link">
                        <span class="menu-icon">👤</span>
                        Profil Saya
                    </a>

                    <a href="orders.php" class="account-menu-link">
                        <span class="menu-icon">📦</span>
                        Pesanan Saya
                    </a>

                    <a href="cart.php#wishlist" class="account-menu-link">
                        <span class="menu-icon">♡</span>
                        Wishlist Saya
                    </a>

                    <div class="account-divider"></div>

                    <a href="logout.php" class="account-menu-link logout">
                        <span class="menu-icon">↪</span>
                        Logout
                    </a>

                </div>

            </div>

        <?php else: ?>

            <a href="login.php" class="account-login-link">
                Login
            </a>

        <?php endif; ?>

    </div>

</header>

<main class="orders-container">

    <div class="orders-heading">

        <div class="eyebrow">RIWAYAT PEMESANAN</div>

        <h1>Pesanan Saya</h1>

        <p>Lihat informasi dan status seluruh pesanan kamu di sini.</p>

    </div>

    <!-- TAB PESANAN -->
    <div class="order-tabs">

        <button
            type="button"
            class="order-tab active"
            id="tabAktif"
            onclick="gantiTab('aktif')"
        >
            Pesanan Aktif

            <span class="tab-count">
                <?= count($pesanan_aktif); ?>
            </span>
        </button>

        <button
            type="button"
            class="order-tab"
            id="tabRiwayat"
            onclick="gantiTab('riwayat')"
        >
            Riwayat Pemesanan

            <span class="tab-count">
                <?= count($riwayat_pesanan); ?>
            </span>
        </button>

    </div>

    <!-- PANEL PESANAN AKTIF -->
    <div class="order-panel active" id="panelAktif">

        <h2 class="panel-heading">Pesanan yang Sedang Berlangsung</h2>

        <?php if (count($pesanan_aktif) > 0): ?>

            <?php foreach ($pesanan_aktif as $order): ?>
                <?php tampilkanPesanan($order, $koneksi); ?>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-order">
                <div class="empty-icon">🛍️</div>

                <h2>Belum Ada Pesanan Aktif</h2>

                <p>
                    Kamu belum memiliki pesanan yang sedang diproses.
                </p>

                <a href="produk.php" class="btn-menu">
                    Mulai Belanja →
                </a>
            </div>

        <?php endif; ?>

    </div>

    <!-- PANEL RIWAYAT PESANAN -->
    <div class="order-panel" id="panelRiwayat">

        <h2 class="panel-heading">Riwayat Pemesanan</h2>

        <?php if (count($riwayat_pesanan) > 0): ?>

            <?php foreach ($riwayat_pesanan as $order): ?>
                <?php tampilkanPesanan($order, $koneksi); ?>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-order">
                <div class="empty-icon">📋</div>

                <h2>Belum Ada Riwayat Pesanan</h2>

                <p>
                    Semua pesanan kamu akan muncul di bagian ini.
                </p>
            </div>

        <?php endif; ?>

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

/* GANTI TAB PESANAN */
function gantiTab(tab) {

    const tabAktif = document.getElementById('tabAktif');
    const tabRiwayat = document.getElementById('tabRiwayat');

    const panelAktif = document.getElementById('panelAktif');
    const panelRiwayat = document.getElementById('panelRiwayat');

    if (tab === 'aktif') {

        tabAktif.classList.add('active');
        tabRiwayat.classList.remove('active');

        panelAktif.classList.add('active');
        panelRiwayat.classList.remove('active');

    } else {

        tabRiwayat.classList.add('active');
        tabAktif.classList.remove('active');

        panelRiwayat.classList.add('active');
        panelAktif.classList.remove('active');

    }
}

/* DROPDOWN AKUN */
const navAccount = document.getElementById('navAccount');
const accountTrigger = document.getElementById('accountTrigger');

if (navAccount && accountTrigger) {

    accountTrigger.addEventListener('click', function (event) {
        event.stopPropagation();

        navAccount.classList.toggle('open');

        const isOpen = navAccount.classList.contains('open');

        accountTrigger.setAttribute('aria-expanded', isOpen);
    });

    document.addEventListener('click', function (event) {

        if (!navAccount.contains(event.target)) {
            navAccount.classList.remove('open');
            accountTrigger.setAttribute('aria-expanded', 'false');
        }

    });

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            navAccount.classList.remove('open');
            accountTrigger.setAttribute('aria-expanded', 'false');
        }

    });
}

</script>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
