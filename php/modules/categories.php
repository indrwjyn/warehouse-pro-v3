<?php
$action = $_GET['action'] ?? 'list';
$error = '';

if ($action === 'add' || $action === 'edit') {
    $id_category = $action === 'edit' ? intval($_GET['id'] ?? 0) : 0;
    $name_category = '';

    if ($action === 'edit') {
        $stmt = $mysqli->prepare('SELECT name_category FROM categories WHERE id_category = ?');
        $stmt->bind_param('i', $id_category);
        $stmt->execute();
        $stmt->bind_result($name_category);
        if (!$stmt->fetch()) {
            header('Location: index.php?page=categories');
            exit;
        }
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name_category = clean_input($_POST['name_category'] ?? '');
        if ($name_category === '') {
            $error = 'Nama kategori wajib diisi.';
        } else {
            if ($action === 'add') {
                $stmt = $mysqli->prepare('INSERT INTO categories (name_category) VALUES (?)');
                $stmt->bind_param('s', $name_category);
            } else {
                $stmt = $mysqli->prepare('UPDATE categories SET name_category = ? WHERE id_category = ?');
                $stmt->bind_param('si', $name_category, $id_category);
            }
            if ($stmt->execute()) {
                header('Location: index.php?page=categories');
                exit;
            }
            $error = 'Gagal menyimpan kategori.';
            $stmt->close();
        }
    }
}

if ($action === 'delete') {
    $id_category = intval($_GET['id'] ?? 0);
    $stmt = $mysqli->prepare('DELETE FROM categories WHERE id_category = ?');
    $stmt->bind_param('i', $id_category);
    $stmt->execute();
    $stmt->close();
    header('Location: index.php?page=categories');
    exit;
}

if ($action === 'list') {
    $categoriesResult = $mysqli->query('SELECT * FROM categories ORDER BY name_category ASC');
}
?>
<?php if ($action === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Kategori Produk</h1>
            <p class="text-muted">Kelola kategori master data.</p>
        </div>
        <a href="index.php?page=categories&action=add" class="btn btn-success">Tambah Kategori</a>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $categoriesResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_category']; ?></td>
                            <td><?php echo htmlspecialchars($row['name_category']); ?></td>
                            <td>
                                <a href="index.php?page=categories&action=edit&id=<?php echo $row['id_category']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="index.php?page=categories&action=delete&id=<?php echo $row['id_category']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus kategori?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?php echo $action === 'add' ? 'Tambah Kategori' : 'Edit Kategori'; ?></h1>
        </div>
        <a href="index.php?page=categories" class="btn btn-secondary">Kembali</a>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" id="categoryForm">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="name_category" class="form-control" value="<?php echo htmlspecialchars($name_category); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Simpan' : 'Perbarui'; ?></button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('categoryForm').addEventListener('submit', function (event) {
            const value = this.querySelector('[name="name_category"]').value.trim();
            if (!value) {
                event.preventDefault();
                alert('Nama kategori tidak boleh kosong.');
            }
        });
    </script>
<?php endif; ?>
