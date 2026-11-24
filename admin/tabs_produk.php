<?php
$productTabs = [
    ['produk.php','Produk'],
    ['kategori.php','Kategori'],
    ['stok.php','Stok'],
];
$currentTab = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-tabs" aria-label="Modul produk">
<?php foreach ($productTabs as [$href,$label]): ?>
<a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="<?= $currentTab === $href ? 'active' : '' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endforeach; ?>
</nav>
