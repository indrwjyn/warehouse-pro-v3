<?php
$user   = current_user();
$role   = $user['role'];
$isStaff = ($role === 'staff_gudang');
$active = function ($page) {
    $current = $_GET['page'] ?? 'dashboard';
    return $current === $page ? 'active' : '';
};
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark"><i class="fa-solid fa-warehouse"></i></div>
        <div>
            <h4>Local Warehouse</h4>
            <span><?php echo role_label($role); ?> Console</span>
        </div>
    </div>
    <div class="user-chip">
        <img src="<?php echo htmlspecialchars(user_avatar_url($_SESSION['profile_photo'] ?? '', $user['name'], 64)); ?>"
             alt="Avatar"
             onerror="this.src='<?php echo dicebear_avatar($user['name'], 64); ?>'">
        <div>
            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
            <small><?php echo role_label($role); ?></small>
        </div>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $active('dashboard'); ?>" href="index.php?page=dashboard"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>

        <?php if (!$isStaff): ?>
            <?php if (in_array($role, ['direktur','manager','kepala_gudang','admin_gudang'], true)): ?>
                <a class="nav-link <?php echo $active('produk'); ?>" href="index.php?page=produk"><i class="fa-solid fa-boxes-stacked"></i><span>Produk</span></a>
                <a class="nav-link <?php echo $active('categories'); ?>" href="index.php?page=categories"><i class="fa-solid fa-tags"></i><span>Kategori</span></a>
                <a class="nav-link <?php echo $active('storage_locations'); ?>" href="index.php?page=storage_locations"><i class="fa-solid fa-location-dot"></i><span>Gudang &amp; Rak</span></a>
            <?php endif; ?>
            <a class="nav-link <?php echo $active('users'); ?>" href="index.php?page=users"><i class="fa-solid fa-users-gear"></i><span>Kelola User</span></a>
        <?php endif; ?>

        <a class="nav-link <?php echo $active('inventory'); ?>" href="index.php?page=inventory"><i class="fa-solid fa-right-left"></i><span>Inventory</span></a>

        <?php if (!$isStaff): ?>
            <a class="nav-link <?php echo $active('laporan'); ?>" href="index.php?page=laporan"><i class="fa-solid fa-file-lines"></i><span>Laporan</span></a>
        <?php else: ?>
            <a class="nav-link <?php echo $active('laporan'); ?>" href="index.php?page=laporan"><i class="fa-solid fa-file-lines"></i><span>Laporan Saya</span></a>
        <?php endif; ?>

        <a class="nav-link <?php echo $active('tracking'); ?>" href="index.php?page=tracking"><i class="fa-solid fa-route"></i><span>Cek Resi</span></a>
    </nav>
    <a class="logout-link" href="auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></a>
</aside>
