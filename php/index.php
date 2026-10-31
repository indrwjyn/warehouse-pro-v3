<?php
require_once 'config/config.php';
require_once 'config/auth.php';
require_login();

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard','produk','categories','storage_locations','users','inventory','tracking','laporan'];
if ($page === 'stock_mutations') $page = 'inventory';
if (!in_array($page, $allowedPages, true)) $page = 'dashboard';

$role = $_SESSION['role'] ?? '';

// Halaman khusus produk/lokasi/kategori: direktur, manager, kepala_gudang, admin_gudang (HRD tidak)
if (in_array($page, ['produk','storage_locations','categories'], true)) {
    require_role(['direktur','manager','kepala_gudang','admin_gudang']);
}

// Halaman manajemen user: semua kecuali staff_gudang
if ($page === 'users') {
    require_role(['direktur','manager','kepala_gudang','admin_gudang','hrd']);
}

// Laporan & inventory: semua role
if (in_array($page, ['laporan','inventory'], true)) {
    require_role(['direktur','manager','kepala_gudang','admin_gudang','staff_gudang','hrd']);
}

$pageTitle = 'Local Warehouse Tracking - ' . ucfirst(str_replace('_',' ',$page));
define('PAGE_TITLE', $pageTitle);

require 'layout/header.php';
require 'layout/sidebar.php';
?>
<main class="content">
    <?php include "modules/{$page}.php"; ?>
</main>
<?php require 'layout/footer.php';
