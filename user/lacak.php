<?php
session_start();
include "../config/koneksi.php";
include "../config/pengunjung.php";
catat_pengunjung($koneksi);

$keyword = trim($_GET['order'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$result_data = null;
$error = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value) {
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function normalize_phone($value) {
    return preg_replace('/\D+/', '', (string)$value);
}

function status_class($status) {
    $s = strtolower(trim($status));
    if (in_array($s, ['selesai', 'sampai tujuan', 'lunas'], true)) return 'done';
    if (in_array($s, ['dikirim', 'dalam perjalanan'], true)) return 'shipping';
    if (in_array($s, ['diproses'], true)) return 'process';
    if (in_array($s, ['dibatalkan', 'gagal'], true)) return 'failed';
    return 'waiting';
}

if ($keyword !== '' || $phone !== '') {
    $order_number = strtoupper($keyword);
    $order_number = preg_replace('/^#?ORD/i', '', $order_number);
    $id_order = ctype_digit($order_number) ? (int)$order_number : 0;

    if ($id_order <= 0 || $phone === '') {
        $error = 'Masukkan nomor pesanan dan nomor HP penerima.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT o.*, p.kurir, p.status_pengiriman, p.tanggal_kirim, p.estimasi_sampai, p.catatan AS catatan_pengiriman FROM orders o LEFT JOIN pengiriman p ON p.id_order=o.id_order WHERE o.id_order=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id_order);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row || normalize_phone($row['no_hp'] ?? '') !== normalize_phone($phone)) {
            $error = 'Pesanan tidak ditemukan. Periksa kembali nomor pesanan dan nomor HP penerima.';
        } else {
            $shipping_status = trim($row['status_pengiriman'] ?? '');
            if ($shipping_status === '') {
                $shipping_status = trim($row['status'] ?? 'Belum Diproses');
            }
            $result_data = $row;
            $result_data['display_status'] = $shipping_status;
        }
    }
}

$brand = ['nama_toko' => 'Budiarti Risol', 'slogan' => 'Risol Lezat, Setiap Saat'];
if (mysqli_query($koneksi, "SHOW TABLES LIKE 'settings'")) {
    $qbrand = mysqli_query($koneksi, "SELECT nama_toko, slogan FROM settings LIMIT 1");
    if ($qbrand && ($b = mysqli_fetch_assoc($qbrand))) $brand = array_merge($brand, $b);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lacak Pesanan | <?=e($brand['nama_toko'])?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;background:#F8EFE5;color:#542D1C;font-family:'DM Sans',Arial,sans-serif}.navbar{height:72px;background:#542D1C;display:flex;align-items:center;justify-content:space-between;padding:0 45px}.brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none}.brand-mark{width:42px;height:42px;border-radius:50%;background:#fff98a;display:flex;align-items:center;justify-content:center;font-size:24px}.brand-copy strong{display:block;font-family:'Playfair Display',serif;font-size:23px}.brand-copy small{display:block;font-size:8px;letter-spacing:1.4px;margin-top:4px}.nav-links{display:flex;gap:18px}.nav-links a{color:#fff;text-decoration:none;font-size:13px}.wrap{width:90%;max-width:900px;margin:48px auto 70px}.heading{text-align:center;margin-bottom:30px}.eyebrow{font-size:11px;letter-spacing:3px;color:#95602e;font-weight:700}.heading h1{font-family:'Playfair Display',serif;font-size:38px;margin:8px 0;color:#542D1C}.heading p{color:#806d5e;font-size:14px}.track-card,.result-card{background:#fff;border-radius:18px;padding:28px;box-shadow:0 8px 28px rgba(74,34,14,.08);border:1px solid rgba(74,34,14,.05);margin-bottom:22px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.group{display:flex;flex-direction:column;gap:7px}.group label{font-size:12px;font-weight:700}.group input{padding:12px 13px;border:1px solid #decfc1;border-radius:9px;font-size:13px;outline:none}.group input:focus{border-color:#9b6a45}.full{grid-column:1/-1}.btn{display:inline-block;border:0;border-radius:9px;padding:12px 18px;font-weight:700;font-size:12px;text-decoration:none;cursor:pointer}.btn-primary{background:#542D1C;color:#fff}.btn-light{background:#f2e8df;color:#542D1C}.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}.alert{padding:13px 15px;border-radius:10px;font-size:12px;margin-bottom:18px}.error{background:#fde8e5;color:#9c3e31}.order-top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;border-bottom:1px solid #eee4dc;padding-bottom:20px}.order-no{font-size:20px;font-weight:700}.date{font-size:11px;color:#8a7669;margin-top:4px}.badge{display:inline-block;padding:8px 12px;border-radius:20px;font-size:11px;font-weight:700}.waiting{background:#f1eee9;color:#75685e}.process{background:#fff0c9;color:#a86d16}.shipping{background:#dceefa;color:#32779f}.done{background:#dcefdc;color:#43804a}.failed{background:#fde1df;color:#a43c34}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:22px 0}.summary-item{background:#faf7f4;border-radius:12px;padding:14px}.summary-item small{display:block;color:#8a7669;font-size:10px;margin-bottom:5px}.summary-item strong{font-size:13px}.timeline{margin-top:25px}.timeline h2{font-family:'Playfair Display',serif;font-size:22px;margin:0 0 18px}.step{display:flex;gap:14px;position:relative;padding-bottom:20px}.step:not(:last-child):before{content:'';position:absolute;left:8px;top:18px;width:2px;height:calc(100% - 8px);background:#e5d9ce}.dot{width:18px;height:18px;border-radius:50%;background:#e4d7cc;border:4px solid #fff;box-shadow:0 0 0 1px #d6c6b7;z-index:1;flex-shrink:0}.step.active .dot{background:#542D1C;box-shadow:0 0 0 1px #542D1C}.step h3{font-size:13px;margin:0 0 4px}.step p{font-size:11px;color:#8a7669;margin:0}.info{background:#faf7f4;border-radius:12px;padding:15px;margin-top:18px;font-size:12px;line-height:1.6}.muted{color:#8a7669}@media(max-width:650px){.navbar{padding:0 18px}.nav-links a{font-size:11px}.brand-copy strong{font-size:18px}.brand-copy small{display:none}.wrap{width:92%;margin-top:30px}.heading h1{font-size:31px}.form-grid,.summary{grid-template-columns:1fr}.full{grid-column:auto}.order-top{flex-direction:column}.actions{justify-content:stretch}.actions .btn{flex:1;text-align:center}}
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
<nav class="navbar"><a class="brand" href="index.php"><span class="brand-mark">🥟</span><span class="brand-copy"><strong><?=e($brand['nama_toko'])?></strong><small><?=e($brand['slogan'])?></small></span></a><div class="nav-links"><a href="index.php">Beranda</a><a href="orders.php">Pesanan Saya</a><a href="lacak.php">Lacak Pesanan</a></div></nav>
<main class="wrap">
<section class="heading"><div class="eyebrow">ORDER TRACKING</div><h1>Lacak Pesanan</h1><p>Masukkan nomor pesanan dan nomor HP penerima untuk melihat status pengiriman.</p></section>
<section class="track-card">
<form method="get"><div class="form-grid"><div class="group"><label>Nomor Pesanan</label><input name="order" value="<?=e($keyword)?>" placeholder="Contoh: ORD001 atau #ORD001" required></div><div class="group"><label>Nomor HP Penerima</label><input name="phone" value="<?=e($phone)?>" placeholder="Contoh: 081234567890" required></div></div><div class="actions"><button class="btn btn-primary" type="submit">Lacak Pesanan</button></div></form>
<?php if($error): ?><div class="alert error" style="margin-top:18px;margin-bottom:0"><?=e($error)?></div><?php endif; ?>
</section>
<?php if($result_data): $r=$result_data; $st=$r['display_status']; $cls=status_class($st); ?>
<section class="result-card"><div class="order-top"><div><div class="order-no">#ORD<?=str_pad((int)$r['id_order'],3,'0',STR_PAD_LEFT)?></div><div class="date">Pesanan dibuat <?=e(date('d/m/Y H:i',strtotime($r['created_at'])))?></div></div><span class="badge <?=$cls?>"><?=e($st)?></span></div>
<div class="summary"><div class="summary-item"><small>Layanan Pengiriman</small><strong><?=e($r['kurir'] ?: 'Belum ditentukan')?></strong></div><div class="summary-item"><small>Status Pengiriman</small><strong><?=e($r['status_pengiriman'] ?: 'Belum Diproses')?></strong></div><div class="summary-item"><small>Total Pesanan</small><strong><?=rupiah($r['total_harga'])?></strong></div></div>
<div class="timeline"><h2>Perjalanan Pesanan</h2>
<?php $steps=['Diproses','Dikirim','Dalam Perjalanan','Sampai Tujuan','Selesai']; $rank=['belum diproses'=>0,'diproses'=>1,'dikirim'=>2,'dalam perjalanan'=>3,'sampai tujuan'=>4,'selesai'=>5,'gagal'=>-1,'dibatalkan'=>-1]; $current=$rank[strtolower($st)]??0; foreach($steps as $i=>$step): $n=$i+1; $active=$current >= $n; ?><div class="step <?=$active?'active':''?>"><span class="dot"></span><div><h3><?=e($step)?></h3><?php if($step==='Dikirim' && !empty($r['tanggal_kirim'])): ?><p><?=e(date('d/m/Y H:i',strtotime($r['tanggal_kirim'])))?></p><?php elseif($step==='Sampai Tujuan' && !empty($r['estimasi_sampai'])): ?><p>Estimasi <?=e(date('d/m/Y',strtotime($r['estimasi_sampai'])))?></p><?php elseif($active): ?><p>Status sudah mencapai tahap ini.</p><?php else: ?><p>Belum tercapai.</p><?php endif; ?></div></div><?php endforeach; ?>
</div>
<div class="info"><strong>Alamat pengiriman</strong><br><?=e($r['alamat'])?><br><?=e($r['kecamatan'])?>, <?=e($r['kota'])?>, <?=e($r['provinsi'])?> - <?=e($r['kode_pos'])?><?php if(!empty($r['catatan_pengiriman'])): ?><br><br><strong>Catatan pengiriman:</strong> <?=e($r['catatan_pengiriman'])?><?php endif; ?></div>
<div class="actions"><a class="btn btn-light" href="orders.php">Pesanan Saya</a><a class="btn btn-primary" href="lacak.php">Lacak Pesanan Lain</a></div>
</section>
<?php endif; ?>
</main>
</body></html>
