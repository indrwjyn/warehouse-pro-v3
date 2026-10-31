<?php
$action = $_GET['action'] ?? 'list';
$error = '';
$locations = [];
$locationResult = $mysqli->query('SELECT id, warehouse_name, room_name, rack_number FROM storage_locations ORDER BY warehouse_name, rack_number');
while ($row = $locationResult->fetch_assoc()) {
    $locations[] = $row;
}

if ($action === 'add' || $action === 'edit') {
    $id = $action === 'edit' ? intval($_GET['id'] ?? 0) : 0;
    $sku = $name = $description = $unit = $image_seed = $image_path = '';
    $stock_min = $current_stock = $storage_location_id = 0;

    if ($action === 'edit') {
        $stmt = $mysqli->prepare('SELECT sku, name, description, unit, stock_min, current_stock, storage_location_id, image_seed, image_path FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result) {
            header('Location: index.php?page=produk');
            exit;
        }
        extract($result);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sku = clean_input($_POST['sku'] ?? '');
        $name = clean_input($_POST['name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $unit = clean_input($_POST['unit'] ?? 'pcs');
        $stock_min = intval($_POST['stock_min'] ?? 0);
        $current_stock = intval($_POST['current_stock'] ?? 0);
        $storage_location_id = intval($_POST['storage_location_id'] ?? 0);
        $image_seed = clean_input($_POST['image_seed'] ?? $image_seed ?: $name);
        $uploadedImagePath = $image_path;

        if (!empty($_FILES['product_image']['name'])) {
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];
            $file = $_FILES['product_image'];
            $tmpName = $file['tmp_name'] ?? '';
            $mimeType = $tmpName ? mime_content_type($tmpName) : '';

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $error = 'Upload gambar gagal. Coba pilih file lain.';
            } elseif (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2MB.';
            } elseif (!isset($allowedTypes[$mimeType])) {
                $error = 'Format gambar harus JPG, PNG, WebP, atau GIF.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/products';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $safeSku = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($sku));
                $fileName = $safeSku . '-' . time() . '.' . $allowedTypes[$mimeType];
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImagePath = 'uploads/products/' . $fileName;
                } else {
                    $error = 'Gambar tidak berhasil dipindahkan ke folder upload.';
                }
            }
        }

        if ($error !== '') {
            // Error upload sudah diisi di atas.
        } elseif ($sku === '' || $name === '' || $unit === '' || $storage_location_id <= 0) {
            $error = 'SKU, nama, satuan, dan lokasi rak wajib diisi.';
        } elseif ($stock_min < 0 || $current_stock < 0) {
            $error = 'Nilai stok tidak boleh negatif.';
        } else {
            if ($action === 'add') {
                $stmt = $mysqli->prepare('INSERT INTO products (sku, name, description, unit, stock_min, current_stock, storage_location_id, image_seed, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssssiiiss', $sku, $name, $description, $unit, $stock_min, $current_stock, $storage_location_id, $image_seed, $uploadedImagePath);
            } else {
                $stmt = $mysqli->prepare('UPDATE products SET sku = ?, name = ?, description = ?, unit = ?, stock_min = ?, current_stock = ?, storage_location_id = ?, image_seed = ?, image_path = ? WHERE id = ?');
                $stmt->bind_param('ssssiiissi', $sku, $name, $description, $unit, $stock_min, $current_stock, $storage_location_id, $image_seed, $uploadedImagePath, $id);
            }
            if ($stmt->execute()) {
                header('Location: index.php?page=produk');
                exit;
            }
            $error = 'Gagal menyimpan produk. SKU mungkin sudah digunakan.';
            $stmt->close();
        }
    }
}

if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?page=produk');
    exit;
}

if ($action === 'list') {
    $search = '%' . $mysqli->real_escape_string($_GET['search'] ?? '') . '%';
    $stmt = $mysqli->prepare('SELECT p.*, sl.warehouse_name, sl.room_name, sl.rack_number FROM products p JOIN storage_locations sl ON p.storage_location_id = sl.id WHERE p.name LIKE ? OR p.sku LIKE ? ORDER BY p.id DESC');
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $products = $stmt->get_result();
}
?>
<?php if ($action === 'list'): ?>
<section class="page-head">
    <div><span class="eyebrow">Master Data</span><h1>Produk</h1><p>Produk tersambung langsung ke lokasi gudang dan nomor rak.</p></div>
    <a href="index.php?page=produk&action=add" class="btn btn-electric"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
</section>
<div class="panel">
    <form class="row g-3 mb-3" method="get" action="index.php">
        <input type="hidden" name="page" value="produk">
        <div class="col-md-9"><input type="text" id="productSearch" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-control" placeholder="Cari produk atau SKU secara cepat"></div>
        <div class="col-md-3"><button class="btn btn-dark w-100"><i class="fa-solid fa-magnifying-glass"></i> Cari</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Produk</th><th>SKU</th><th>Lokasi</th><th>Stok</th><th>Minimum</th><th>Aksi</th></tr></thead>
            <tbody id="productTableBody">
            <?php while ($product = $products->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?php echo product_display_image($product['image_path'], $product['image_seed'] ?: $product['name']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div><strong><?php echo htmlspecialchars($product['name']); ?></strong><small><?php echo htmlspecialchars($product['unit']); ?></small></div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td><?php echo htmlspecialchars($product['warehouse_name'] . ' / ' . $product['room_name'] . ' / ' . $product['rack_number']); ?></td>
                    <td><span class="stock-pill"><?php echo $product['current_stock']; ?></span></td>
                    <td><?php echo $product['stock_min']; ?></td>
                    <td>
                        <a href="index.php?page=produk&action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i></a>
                        <a href="index.php?page=produk&action=delete&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?');"><i class="fa-solid fa-trash"></i></a>
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
    <div><span class="eyebrow">Form Produk</span><h1><?php echo $action === 'add' ? 'Tambah Produk' : 'Edit Produk'; ?></h1><p>Lengkapi data produk dan lokasi penyimpanannya.</p></div>
    <a href="index.php?page=produk" class="btn btn-light"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</section>
<?php if ($error): ?><div class="alert alert-danger shake"><?php echo $error; ?></div><?php endif; ?>
<div class="panel">
    <form method="post" class="needs-live-validation" enctype="multipart/form-data" novalidate>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">SKU</label><input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($sku); ?>" required></div>
            <div class="col-md-8"><label class="form-label">Nama Produk</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Satuan</label><input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($unit ?: 'pcs'); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Stok Minimum</label><input type="number" name="stock_min" min="0" class="form-control" value="<?php echo htmlspecialchars((string) $stock_min); ?>" required></div>
            <div class="col-md-4"><label class="form-label">Stok Saat Ini</label><input type="number" name="current_stock" min="0" class="form-control" value="<?php echo htmlspecialchars((string) $current_stock); ?>" required></div>
            <div class="col-md-8">
                <label class="form-label">Lokasi Gudang & Rak</label>
                <select name="storage_location_id" class="form-select" required>
                    <option value="">Pilih lokasi</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo $location['id']; ?>" <?php echo $location['id'] == $storage_location_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($location['warehouse_name'] . ' - ' . $location['room_name'] . ' - ' . $location['rack_number']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Gambar Produk</label>
                <input type="file" name="product_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="text-muted">Upload JPG, PNG, WebP, atau GIF. Maksimal 2MB.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Fallback Seed Gambar</label>
                <input type="text" name="image_seed" class="form-control" value="<?php echo htmlspecialchars($image_seed); ?>" placeholder="contoh: barcode scanner">
            </div>
            <div class="col-md-4">
                <label class="form-label">Preview</label>
                <div class="product-image-preview">
                    <img src="<?php echo product_display_image($image_path, $image_seed ?: $name ?: 'warehouse product'); ?>" alt="Preview gambar produk">
                </div>
            </div>
            <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($description); ?></textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-electric"><i class="fa-solid fa-floppy-disk"></i> Simpan Produk</button></div>
        </div>
    </form>
</div>
<?php endif; ?>
