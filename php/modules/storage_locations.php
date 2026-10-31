<?php
$action = $_GET['action'] ?? 'list';
$error = '';

if ($action === 'add' || $action === 'edit') {
    $id = $action === 'edit' ? intval($_GET['id'] ?? 0) : 0;
    $warehouse_name = $room_name = $rack_number = $notes = '';
    $capacity = 0;

    if ($action === 'edit') {
        $stmt = $mysqli->prepare('SELECT warehouse_name, room_name, rack_number, capacity, notes FROM storage_locations WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($warehouse_name, $room_name, $rack_number, $capacity, $notes);
        if (!$stmt->fetch()) {
            header('Location: index.php?page=storage_locations');
            exit;
        }
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $warehouse_name = clean_input($_POST['warehouse_name'] ?? '');
        $room_name = clean_input($_POST['room_name'] ?? '');
        $rack_number = clean_input($_POST['rack_number'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 0);
        $notes = clean_input($_POST['notes'] ?? '');

        if ($warehouse_name === '' || $room_name === '' || $rack_number === '' || $capacity <= 0) {
            $error = 'Gudang, ruangan, nomor rak, dan kapasitas wajib diisi.';
        } else {
            if ($action === 'add') {
                $stmt = $mysqli->prepare('INSERT INTO storage_locations (warehouse_name, room_name, rack_number, capacity, notes) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssis', $warehouse_name, $room_name, $rack_number, $capacity, $notes);
            } else {
                $stmt = $mysqli->prepare('UPDATE storage_locations SET warehouse_name = ?, room_name = ?, rack_number = ?, capacity = ?, notes = ? WHERE id = ?');
                $stmt->bind_param('sssisi', $warehouse_name, $room_name, $rack_number, $capacity, $notes, $id);
            }
            if ($stmt->execute()) {
                header('Location: index.php?page=storage_locations');
                exit;
            }
            $error = 'Gagal menyimpan lokasi. Kombinasi gudang, ruangan, dan rak mungkin sudah ada.';
            $stmt->close();
        }
    }
}

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare('DELETE FROM storage_locations WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?page=storage_locations');
    exit;
}

$locations = $mysqli->query('SELECT * FROM storage_locations ORDER BY warehouse_name, room_name, rack_number');
?>
<?php if ($action === 'list'): ?>
<section class="page-head">
    <div><span class="eyebrow">Storage Map</span><h1>Gudang & Nomor Rak</h1><p>Master lokasi penyimpanan untuk setiap produk dan transaksi.</p></div>
    <a href="index.php?page=storage_locations&action=add" class="btn btn-electric"><i class="fa-solid fa-plus"></i> Tambah Lokasi</a>
</section>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Gudang</th><th>Ruangan</th><th>Rak</th><th>Kapasitas</th><th>Catatan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while ($row = $locations->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['warehouse_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                    <td><span class="stock-pill"><?php echo htmlspecialchars($row['rack_number']); ?></span></td>
                    <td><?php echo $row['capacity']; ?></td>
                    <td><?php echo htmlspecialchars($row['notes'] ?: '-'); ?></td>
                    <td>
                        <a href="index.php?page=storage_locations&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i></a>
                        <a href="index.php?page=storage_locations&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus lokasi ini?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<section class="page-head">
    <div><span class="eyebrow">Form Lokasi</span><h1><?php echo $action === 'add' ? 'Tambah Lokasi' : 'Edit Lokasi'; ?></h1></div>
    <a href="index.php?page=storage_locations" class="btn btn-light"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</section>
<?php if ($error): ?><div class="alert alert-danger shake"><?php echo $error; ?></div><?php endif; ?>
<div class="panel">
    <form method="post" class="needs-live-validation" novalidate>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Nama Gudang</label><input type="text" name="warehouse_name" class="form-control" value="<?php echo htmlspecialchars($warehouse_name); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Ruangan</label><input type="text" name="room_name" class="form-control" value="<?php echo htmlspecialchars($room_name); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Nomor Rak</label><input type="text" name="rack_number" class="form-control" value="<?php echo htmlspecialchars($rack_number); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Kapasitas</label><input type="number" name="capacity" class="form-control" min="1" value="<?php echo htmlspecialchars((string) $capacity); ?>" required></div>
            <div class="col-md-8"><label class="form-label">Catatan</label><input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($notes); ?>"></div>
            <div class="col-12"><button class="btn btn-electric"><i class="fa-solid fa-floppy-disk"></i> Simpan Lokasi</button></div>
        </div>
    </form>
</div>
<?php endif; ?>
