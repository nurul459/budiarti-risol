<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$productPages = ['produk.php','kategori.php','stok.php','stok_produk.php','tambah_produk.php','edit_produk.php','hapus_produk.php','tambah_kategori.php','edit_kategori.php','hapus_kategori.php'];
$orderPages = ['pesanan.php','detail_pesanan.php','update_status.php','pengiriman.php','pembayaran.php','pengembalian.php'];
$financePages = ['laporan.php','pengeluaran.php'];
$analyticsPages = ['pengunjung.php'];
$customerPages = ['crm.php','riwayat_pelanggan.php'];
$settingsPages = ['pengaturan.php','pengguna.php','tambah_pengguna.php','edit_pengguna.php','hapus_pengguna.php'];

$active = $currentPage;
if (in_array($currentPage, $productPages, true)) $active = 'produk.php';
elseif (in_array($currentPage, $orderPages, true)) $active = 'pesanan.php';
elseif (in_array($currentPage, $financePages, true)) $active = 'laporan.php';
elseif (in_array($currentPage, $analyticsPages, true)) $active = 'pengunjung.php';
elseif (in_array($currentPage, $customerPages, true)) $active = 'crm.php';
elseif (in_array($currentPage, $settingsPages, true)) $active = 'pengaturan.php';
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">👩‍🍳</div>
        <h2>Budiarti Risol</h2>
        <p>HOMEMADE • FRESHLY MADE</p>
    </div>

    <nav class="sidebar-menu" aria-label="Navigasi admin">
        <div class="sidebar-group">
            <div class="sidebar-group-title">MENU UTAMA</div>
            <a href="dashboard.php" class="sidebar-main-link<?= $currentPage === 'dashboard.php' ? ' active' : '' ?>" title="Dashboard"><span class="menu-icon">⌂</span><span class="menu-label">Dashboard</span></a>
            <a href="pesanan.php" class="sidebar-main-link<?= $active === 'pesanan.php' ? ' active' : '' ?>" title="Pesanan"><span class="menu-icon">▤</span><span class="menu-label">Pesanan</span></a>
            <a href="produk.php" class="sidebar-main-link<?= $active === 'produk.php' ? ' active' : '' ?>" title="Produk"><span class="menu-icon">▣</span><span class="menu-label">Produk</span></a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">MANAJEMEN</div>
            <a href="laporan.php" class="sidebar-main-link<?= $active === 'laporan.php' ? ' active' : '' ?>" title="Keuangan"><span class="menu-icon">▥</span><span class="menu-label">Keuangan</span></a>
            <a href="pengunjung.php" class="sidebar-main-link<?= $active === 'pengunjung.php' ? ' active' : '' ?>" title="Analitik"><span class="menu-icon">▤</span><span class="menu-label">Analitik</span></a>
            <a href="crm.php" class="sidebar-main-link<?= $active === 'crm.php' ? ' active' : '' ?>" title="Pelanggan"><span class="menu-icon">♟</span><span class="menu-label">Pelanggan</span></a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">LAINNYA</div>
            <a href="pengaturan.php" class="sidebar-main-link<?= $active === 'pengaturan.php' ? ' active' : '' ?>" title="Pengaturan"><span class="menu-icon">⚙</span><span class="menu-label">Pengaturan</span></a>
        </div>

        <a href="../user/logout.php" class="sidebar-main-link sidebar-logout" title="Logout" onclick="return confirm('Apakah kamu yakin ingin keluar dari halaman admin?');"><span class="menu-icon">⇥</span><span class="menu-label">Logout</span></a>
    </nav>
</aside>

<style id="budiarti-sidebar-unified-styles">
/* Standar sidebar bersama untuk seluruh halaman admin Budiarti Risol */
.sidebar {
    width:253px !important;
    min-width:253px !important;
    background:#542D1C !important;
    color:#fff !important;
    position:fixed !important;
    inset:0 auto 0 0 !important;
    z-index:100 !important;
    padding:25px 15px 18px !important;
    display:flex !important;
    flex-direction:column !important;
    overflow-x:hidden !important;
    overflow-y:auto !important;
    transition:width .25s ease, min-width .25s ease, padding .25s ease !important;
}
.sidebar-logo {text-align:center !important;padding:0 0 25px !important;flex:0 0 auto !important;}
.sidebar-logo .logo-icon {font-size:34px !important;line-height:1.2 !important;margin:0 0 5px !important;}
.sidebar-logo h2 {font-family:'Playfair Display',Georgia,serif !important;font-size:24px !important;font-style:normal !important;font-weight:700 !important;color:#fff !important;white-space:nowrap !important;margin:0 !important;}
.sidebar-logo p {font-family:'DM Sans',Arial,sans-serif !important;font-size:10px !important;letter-spacing:1px !important;color:#EBD3C0 !important;margin:3px 0 0 !important;white-space:nowrap !important;}
.sidebar-menu {display:flex !important;flex-direction:column !important;gap:0 !important;flex:1 1 auto !important;min-height:0 !important;}
.sidebar-group {display:flex !important;flex-direction:column !important;gap:5px !important;margin:0 0 13px !important;padding:0 0 12px !important;border-bottom:1px solid rgba(235,211,192,.14) !important;}
.sidebar-group-title {font-family:'DM Sans',Arial,sans-serif !important;font-size:10px !important;font-weight:600 !important;letter-spacing:1.15px !important;color:#C99B78 !important;opacity:.78 !important;padding:4px 14px 6px !important;white-space:nowrap !important;}
.sidebar-menu a.sidebar-main-link {display:flex !important;align-items:center !important;gap:12px !important;width:100% !important;min-height:43px !important;padding:0 14px !important;border-radius:9px !important;color:#F6E8DC !important;font-family:'DM Sans',Arial,sans-serif !important;font-size:13px !important;font-weight:500 !important;text-decoration:none !important;white-space:nowrap !important;overflow:hidden !important;background:transparent !important;transition:background .2s ease,color .2s ease !important;}
.sidebar-menu a.sidebar-main-link:hover {background:rgba(255,255,255,.12) !important;color:#fff !important;}
.sidebar-menu a.sidebar-main-link.active {background:#35190F !important;color:#fff !important;}
.sidebar-menu .menu-icon {display:inline-flex !important;align-items:center !important;justify-content:center !important;flex:0 0 20px !important;width:20px !important;min-width:20px !important;text-align:center !important;font-size:15px !important;line-height:1 !important;}
.sidebar-menu .menu-label {display:inline-block !important;opacity:1 !important;visibility:visible !important;transition:opacity .15s ease !important;}
.sidebar-menu a.sidebar-logout {margin-top:auto !important;flex:0 0 auto !important;}
.main-content {margin-left:253px !important;width:calc(100% - 253px) !important;min-width:0 !important;transition:margin-left .25s ease,width .25s ease !important;}
body.sidebar-collapsed .sidebar {width:72px !important;min-width:72px !important;padding:22px 9px 16px !important;}
body.sidebar-collapsed .sidebar-logo {padding-bottom:22px !important;}
body.sidebar-collapsed .sidebar-logo h2,
body.sidebar-collapsed .sidebar-logo p,
body.sidebar-collapsed .sidebar-group-title,
body.sidebar-collapsed .sidebar-menu .menu-label {display:none !important;}
body.sidebar-collapsed .sidebar-logo .logo-icon {font-size:30px !important;margin-top:2px !important;}
body.sidebar-collapsed .sidebar-group {gap:6px !important;margin-bottom:10px !important;padding-bottom:10px !important;}
body.sidebar-collapsed .sidebar-menu a.sidebar-main-link {justify-content:center !important;gap:0 !important;padding:0 !important;min-height:43px !important;}
body.sidebar-collapsed .sidebar-menu .menu-icon {flex-basis:22px !important;width:22px !important;min-width:22px !important;}
body.sidebar-collapsed .main-content {margin-left:72px !important;width:calc(100% - 72px) !important;}
@media(max-width:750px){
    .sidebar {width:253px !important;min-width:253px !important;}
    .main-content {margin-left:253px !important;width:calc(100% - 253px) !important;}
    body.sidebar-collapsed .sidebar {width:72px !important;min-width:72px !important;}
    body.sidebar-collapsed .main-content {margin-left:72px !important;width:calc(100% - 72px) !important;}
}
</style>

<script>
(function () {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    const key = 'budiartiAdminSidebarCollapsed';
    const saved = localStorage.getItem(key) === '1';
    if (saved) {
        sidebar.classList.add('is-collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
    function getToggle() {
        return document.querySelector('.top-header .hamburger') || document.querySelector('.top-header > div:first-child');
    }
    function apply(collapsed) {
        sidebar.classList.toggle('is-collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem(key, collapsed ? '1' : '0');
        const toggle = getToggle();
        if (toggle) {
            toggle.classList.add('sidebar-toggle-ready');
            toggle.setAttribute('role', 'button');
            toggle.setAttribute('tabindex', '0');
            toggle.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
            toggle.setAttribute('title', collapsed ? 'Buka sidebar' : 'Tutup sidebar');
        }
    }
    function bind() {
        const toggle = getToggle();
        if (!toggle || toggle.dataset.sidebarBound === '1') return;
        toggle.dataset.sidebarBound = '1';
        toggle.classList.add('sidebar-toggle-ready');
        toggle.addEventListener('click', function () { apply(!sidebar.classList.contains('is-collapsed')); });
        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); apply(!sidebar.classList.contains('is-collapsed')); }
        });
        apply(sidebar.classList.contains('is-collapsed'));
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
    else bind();
})();
</script>
