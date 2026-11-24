<?php

include "auth.php";
include "../config/koneksi.php";

$error = "";
$berhasil = "";
$id_product = 0;
$jumlah = "";
$aksi = "tambah";

/*
|--------------------------------------------------------------------------
| AMBIL DATA PRODUK
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !ctype_digit((string) $_GET['id']) ||
    (int) $_GET['id'] <= 0
) {
    header("Location: produk.php");
    exit;
}

$id_product = (int) $_GET['id'];

$stmt_produk = mysqli_prepare(
    $koneksi,
    "SELECT id_product, nama_product, stok
     FROM products
     WHERE id_product = ?"
);

if (!$stmt_produk) {
    die("Gagal menyiapkan data produk.");
}

mysqli_stmt_bind_param($stmt_produk, "i", $id_product);
mysqli_stmt_execute($stmt_produk);

$hasil_produk = mysqli_stmt_get_result($stmt_produk);
$produk = mysqli_fetch_assoc($hasil_produk);

mysqli_stmt_close($stmt_produk);

if (!$produk) {
    header("Location: produk.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| PROSES PERUBAHAN STOK
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? 'tambah';
    $jumlah_input = $_POST['jumlah'] ?? '';

    if (!in_array($aksi, ['tambah', 'kurangi'], true)) {
        $error = "Pilihan pengelolaan stok tidak valid.";
    } elseif (
        $jumlah_input === '' ||
        !ctype_digit((string) $jumlah_input) ||
        (int) $jumlah_input <= 0 ||
        (int) $jumlah_input > 2147483647
    ) {
        $error = "Jumlah stok harus berupa bilangan bulat positif yang valid.";
    } else {

        $jumlah = (int) $jumlah_input;
        $transaksi_dimulai = false;

        try {

            /*
             * Mulai transaksi sebelum melakukan perubahan stok.
             */
            if (!mysqli_begin_transaction($koneksi)) {
                throw new Exception(
                    "Transaksi database gagal dimulai."
                );
            }

            $transaksi_dimulai = true;

            /*
             * Kunci data produk agar perubahan stok bersamaan
             * tidak saling menimpa.
             */
            $stmt_cek = mysqli_prepare(
                $koneksi,
                "SELECT stok
                 FROM products
                 WHERE id_product = ?
                 FOR UPDATE"
            );

            if (!$stmt_cek) {
                throw new Exception(
                    "Gagal memeriksa stok produk."
                );
            }

            mysqli_stmt_bind_param(
                $stmt_cek,
                "i",
                $id_product
            );

            if (!mysqli_stmt_execute($stmt_cek)) {
                mysqli_stmt_close($stmt_cek);

                throw new Exception(
                    "Gagal mengambil stok produk."
                );
            }

            $hasil_cek = mysqli_stmt_get_result($stmt_cek);
            $data_stok = mysqli_fetch_assoc($hasil_cek);

            mysqli_stmt_close($stmt_cek);

            if (!$data_stok) {
                throw new Exception(
                    "Produk tidak ditemukan."
                );
            }

            $stok_sekarang = (int) $data_stok['stok'];

            if ($stok_sekarang < 0) {
                throw new Exception(
                    "Stok produk tidak valid. Periksa data di database."
                );
            }

            if ($stok_sekarang > 2147483647) {
                throw new Exception(
                    "Nilai stok melebihi batas kolom database."
                );
            }

            /*
             * Hitung stok baru.
             */
            if ($aksi === 'tambah') {

                if ($jumlah > 2147483647 - $stok_sekarang) {
                    throw new Exception(
                        "Stok baru melebihi batas maksimum database."
                    );
                }

                $stok_baru = $stok_sekarang + $jumlah;

            } else {

                if ($jumlah > $stok_sekarang) {
                    throw new Exception(
                        "Stok tidak mencukupi. Stok saat ini hanya " .
                        $stok_sekarang . " pcs."
                    );
                }

                $stok_baru = $stok_sekarang - $jumlah;
            }

            /*
             * Simpan stok baru.
             */
            $stmt_update = mysqli_prepare(
                $koneksi,
                "UPDATE products
                 SET stok = ?
                 WHERE id_product = ?"
            );

            if (!$stmt_update) {
                throw new Exception(
                    "Gagal menyiapkan perubahan stok."
                );
            }

            mysqli_stmt_bind_param(
                $stmt_update,
                "ii",
                $stok_baru,
                $id_product
            );

            if (!mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);

                throw new Exception(
                    "Stok gagal diperbarui."
                );
            }

            mysqli_stmt_close($stmt_update);

            /*
             * Simpan transaksi.
             */
            if (!mysqli_commit($koneksi)) {
                throw new Exception(
                    "Perubahan stok gagal disimpan."
                );
            }

            $transaksi_dimulai = false;

            header(
                "Location: stok_produk.php?id=" . $id_product .
                "&berhasil=" . urlencode(
                    $aksi === 'tambah'
                        ? "Stok berhasil ditambahkan."
                        : "Stok berhasil dikurangi."
                )
            );
            exit;

        } catch (Throwable $e) {

            if ($transaksi_dimulai) {
                mysqli_rollback($koneksi);
            }

            $error = $e instanceof Exception
                ? $e->getMessage()
                : "Terjadi kesalahan saat memperbarui stok.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| AMBIL ULANG DATA PRODUK
|--------------------------------------------------------------------------
*/

$stmt_produk = mysqli_prepare(
    $koneksi,
    "SELECT id_product, nama_product, stok
     FROM products
     WHERE id_product = ?"
);

if (!$stmt_produk) {
    die("Gagal mengambil ulang data produk.");
}

mysqli_stmt_bind_param($stmt_produk, "i", $id_product);
mysqli_stmt_execute($stmt_produk);

$hasil_produk = mysqli_stmt_get_result($stmt_produk);
$produk = mysqli_fetch_assoc($hasil_produk);

mysqli_stmt_close($stmt_produk);

if (!$produk) {
    header("Location: produk.php");
    exit;
}

if (isset($_GET['berhasil'])) {
    $berhasil = (string) $_GET['berhasil'];
}

?>

<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Kelola Stok - Budiarti Risol</title>
<style>
*{box-sizing:border-box}body{margin:0;padding:30px 15px;font-family:Arial,Helvetica,sans-serif;background:#F8EFE5;color:#542D1C}.container{max-width:760px;margin:25px auto}.back{display:inline-block;margin:0 0 14px;color:#6b3518;text-decoration:none;font-size:13px;font-weight:600}.card{background:#fff;padding:30px;border:1px solid #eee4db;border-radius:14px;box-shadow:0 4px 15px rgba(80,40,10,.06)}h1{margin:0;color:#542D1C;font-family:Georgia,serif;font-size:28px}.subtitle{margin:7px 0 24px;color:#8a7669;font-size:13px;line-height:1.6}.message{padding:12px 14px;margin-bottom:18px;border-radius:8px;font-size:13px;line-height:1.5}.error{background:#ffe5e5;color:#a00000}.success{background:#e5f5e5;color:#276b32}.product-summary{display:grid;grid-template-columns:1fr 170px;gap:18px;align-items:stretch;margin-bottom:24px}.summary-main,.summary-stock{background:#fcf8f3;border:1px solid #eee1d7;border-radius:12px;padding:18px}.summary-label{font-size:11px;color:#8a7669;margin-bottom:7px}.product-name{font-size:19px;font-weight:700;color:#542D1C}.summary-stock{text-align:center;display:flex;flex-direction:column;justify-content:center}.stock-label{font-size:11px;color:#8a7669}.stock-number{font-size:27px;font-weight:700;color:#9a5d27;margin:3px 0}.stock-status{font-size:11px;font-weight:700}.safe{color:#4f7a3b}.low{color:#9a6b21}.empty{color:#a52c38}.section{border:1px solid #eee1d7;border-radius:12px;padding:20px;background:#fffdfb}.section-title{font-family:Georgia,serif;font-size:19px;margin:0 0 16px;color:#5b2c1f}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.form-group{margin:0}label{display:block;margin-bottom:8px;font-size:13px;font-weight:bold}select,input{width:100%;padding:12px;border:1px solid #e5dbd2;border-radius:8px;background:#fff;color:#4b392f;font-size:14px}select:focus,input:focus{outline:none;border-color:#b4772d}.help-text{margin-top:7px;color:#8a7669;font-size:11px;line-height:1.5}.result-box{margin-top:18px;padding:14px 16px;background:#f8f0e8;border-radius:10px;display:flex;justify-content:space-between;align-items:center;gap:15px}.result-label{font-size:12px;color:#765a46}.result-number{font-size:20px;font-weight:700;color:#542D1C}.button-area{display:flex;gap:10px;margin-top:22px}button,.back-button{display:inline-block;padding:12px 18px;border:none;border-radius:8px;font-size:13px;font-weight:bold;text-align:center;text-decoration:none;cursor:pointer}button{flex:1;background:#542D1C;color:#fff}button:hover{background:#6a3418}.back-button{background:#f1e9e1;color:#5b2c1f}.back-button:hover{background:#e6d8ca}@media(max-width:620px){body{padding:18px 10px}.card{padding:20px}.product-summary{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.result-box{align-items:flex-start;flex-direction:column}.button-area{flex-direction:column}button,.back-button{width:100%}}
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
</head><body><div class="container"><a class="back" href="stok.php">← Kembali ke Stok</a><div class="card"><h1>Kelola Stok Produk</h1><p class="subtitle">Perbarui persediaan produk dengan menambah stok hasil produksi atau mengurangi stok yang rusak maupun hilang.</p><?php if($error!==''):?><div class="message error"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8');?></div><?php endif;?><?php if($berhasil!==''):?><div class="message success"><?=htmlspecialchars($berhasil,ENT_QUOTES,'UTF-8');?></div><?php endif;?><div class="product-summary"><div class="summary-main"><div class="summary-label">PRODUK YANG DIKELOLA</div><div class="product-name"><?=htmlspecialchars($produk['nama_product'],ENT_QUOTES,'UTF-8');?></div><div style="font-size:11px;color:#8a7669;margin-top:7px">Perubahan stok akan langsung diperbarui pada produk ini.</div></div><div class="summary-stock"><div class="stock-label">STOK SAAT INI</div><div class="stock-number"><?= (int)$produk['stok']; ?> pcs</div><?php $stokSaatIni=(int)$produk['stok']; $statusClass=$stokSaatIni<=0?'empty':($stokSaatIni<=5?'low':'safe'); $statusText=$stokSaatIni<=0?'Stok Habis':($stokSaatIni<=5?'Stok Menipis':'Stok Aman'); ?><div class="stock-status <?=$statusClass?>"><?=$statusText?></div></div></div><div class="section"><h2 class="section-title">Perubahan Stok</h2><form method="POST" action="stok_produk.php?id=<?=$id_product;?>"><div class="form-grid"><div class="form-group"><label for="aksi">Jenis Perubahan Stok</label><select name="aksi" id="aksi" required><option value="tambah" <?=$aksi==='tambah'?'selected':'';?>>Tambah Stok</option><option value="kurangi" <?=$aksi==='kurangi'?'selected':'';?>>Kurangi Stok</option></select></div><div class="form-group"><label for="jumlah">Jumlah Stok (pcs)</label><input type="number" id="jumlah" name="jumlah" min="1" max="2147483647" step="1" value="<?=htmlspecialchars((string)$jumlah,ENT_QUOTES,'UTF-8');?>" placeholder="Masukkan jumlah" required><p class="help-text">Masukkan jumlah perubahan, bukan jumlah stok akhir.</p></div></div><div class="result-box"><div><div class="result-label">STOK SETELAH PERUBAHAN</div><div style="font-size:11px;color:#8a7669;margin-top:3px" id="formula">Masukkan jumlah untuk melihat hasil.</div></div><div class="result-number" id="hasilStok">— pcs</div></div><div class="button-area"><button type="submit" onclick="return confirm('Yakin ingin menyimpan perubahan stok?');">Simpan Perubahan</button><a href="stok.php" class="back-button">Kembali ke Stok</a></div></form></div></div></div><script>const stokAwal=<?= (int)$produk['stok'];?>;const aksi=document.getElementById('aksi');const jumlah=document.getElementById('jumlah');const hasil=document.getElementById('hasilStok');const formula=document.getElementById('formula');function updatePreview(){const n=parseInt(jumlah.value||'0',10);if(!n||n<1){hasil.textContent='— pcs';formula.textContent='Masukkan jumlah untuk melihat hasil.';return}const baru=aksi.value==='tambah'?stokAwal+n:stokAwal-n;hasil.textContent=(baru<0?'—':baru)+' pcs';formula.textContent=aksi.value==='tambah'?`${stokAwal} pcs + ${n} pcs`:`${stokAwal} pcs − ${n} pcs`;hasil.style.color=baru<0?'#a52c38':'#542D1C'}aksi.addEventListener('change',updatePreview);jumlah.addEventListener('input',updatePreview);updatePreview();</script></body></html>
