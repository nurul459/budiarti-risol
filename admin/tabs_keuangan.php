<?php
$financeTabs = [
    ['laporan.php','Ringkasan'],
    ['pengeluaran.php','Pengeluaran'],
];
$currentTab = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-tabs" aria-label="Modul keuangan">
<?php foreach ($financeTabs as [$href,$label]): ?>
<a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="<?= $currentTab === $href ? 'active' : '' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php endforeach; ?>
</nav>
