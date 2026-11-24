Tahap 7 - Manajemen Pembayaran

1. Import migration_tahap7.sql bila ingin membuat tabel secara manual.
2. Modul admin/pembayaran.php dapat melakukan sinkronisasi data pembayaran lama dari tabel orders.
3. QRIS dibuat berstatus Menunggu Verifikasi; COD berstatus Belum Dibayar.
4. Admin dapat memverifikasi/menolak QRIS atau menandai COD sebagai Lunas.
5. Bukti pembayaran berasal dari orders.bukti_pembayaran dan ditampilkan di modul pembayaran.
