<?php
$orderTabs = [
    ['pesanan.php','Semua Pesanan'],
    ['pembayaran.php','Pembayaran'],
    ['pengiriman.php','Pengiriman'],
    ['pengembalian.php','Pengembalian'],
];
$currentTab = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-tabs" aria-label="Modul pesanan">
<?php foreach ($orderTabs as [$href,$label]): ?>
<a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="<?= $currentTab === $href ? 'active' : '' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endforeach; ?>
</nav>
