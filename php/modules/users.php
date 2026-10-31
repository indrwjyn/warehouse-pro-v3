<?php
/**
 * Manajemen User
 *
 * Siapa yang bisa akses halaman ini:
 *   - Semua "full access" role (direktur, manager, kepala_gudang, admin_gudang, hrd)
 *   - Staff gudang TIDAK bisa sama sekali
 *
 * HRD khusus: hanya bisa EDIT profil + foto, tidak bisa ADD / DELETE
 * Upload foto profil: semua full-access bisa (direktur, manager, kepala_gudang, admin_gudang, hrd)
 * Staff gudang: tidak bisa upload/ubah foto siapapun
 */

if (!can_manage_users()) {
    header('Location: index.php?page=dashboard');
    exit;
}

$currentUser  = current_user();
$myRole       = $currentUser['role'];
$myId         = $currentUser['id'];
$rolesAllowed = allowed_roles_to_create($myRole); // kosong untuk hrd
$hasProfilePhoto = table_column_exists($mysqli, 'users', 'profile_photo');
$profilePhotoSelect = $hasProfilePhoto ? 'profile_photo' : 'NULL AS profile_photo';
$userProfilePhotoSelect = $hasProfilePhoto ? 'u.profile_photo' : 'NULL AS profile_photo';
$canPhoto     = $hasProfilePhoto && can_edit_profile_photo(); // semua full-access = true; staff = false

$action = $_GET['action'] ?? 'list';
$error  = '';

// HRD hanya boleh edit (bukan add/delete)
if ($myRole === 'hrd' && in_array($action, ['add','delete'], true)) {
    header('Location: index.php?page=users');
    exit;
}

/* ===================== UPLOAD HELPER ===================== */
function handle_avatar_upload($fileInput, $oldPhoto = '') {
    if (empty($_FILES[$fileInput]['name'])) return $oldPhoto;

    $file    = $_FILES[$fileInput];
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
        throw new Exception('Upload foto gagal. Coba pilih file lain.');
    // Cek tipe asli file di server, bukan tipe kiriman browser
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime]))
        throw new Exception('Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.');
    if ($file['size'] > $maxSize)
        throw new Exception('Ukuran foto maksimal 2 MB.');

    $dir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = $allowed[$mime]; // ekstensi ditentukan dari MIME asli
    $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest))
        throw new Exception('Gagal menyimpan foto. Periksa izin folder uploads/avatars/');

    if ($oldPhoto && file_exists($dir . $oldPhoto)) @unlink($dir . $oldPhoto);

    return $filename;
}

/* ===================== ADD / EDIT ===================== */
if ($action === 'add' || $action === 'edit') {
    $id           = $action === 'edit' ? intval($_GET['id'] ?? 0) : 0;
    $name = $username = $phone = $profilePhoto = '';
    $roleEdit     = $rolesAllowed[0] ?? 'staff_gudang';

    if ($action === 'edit') {
        $stmt = $mysqli->prepare("SELECT name, username, role, phone, {$profilePhotoSelect} FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($name, $username, $roleEdit, $phone, $profilePhoto);
        if (!$stmt->fetch()) { header('Location: index.php?page=users'); exit; }
        $stmt->close();

        // HRD boleh edit siapa saja; role lain cek hierarki
        if ($myRole !== 'hrd' && !in_array($roleEdit, $rolesAllowed, true)) {
            header('Location: index.php?page=users'); exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = clean_input($_POST['name'] ?? '');
        $username = clean_input($_POST['username'] ?? '');
        $phone    = clean_input($_POST['phone'] ?? '');
        $rolePost = $_POST['role'] ?? $roleEdit;
        $password = $_POST['password'] ?? '';

        if ($myRole === 'hrd') {
            $rolePost = $roleEdit; // HRD tidak bisa ubah role
        } elseif (!in_array($rolePost, $rolesAllowed, true)) {
            $error = 'Role tidak diizinkan untuk akun Anda.';
        }

        if (!$error && ($name === '' || $username === '')) $error = 'Nama dan username wajib diisi.';
        if (!$error && $action === 'add' && $password === '') $error = 'Password wajib diisi untuk user baru.';

        if (!$error) {
            try {
                $newPhoto = $profilePhoto;
                if ($canPhoto) $newPhoto = handle_avatar_upload('profile_photo', $profilePhoto);

                if ($action === 'add') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($hasProfilePhoto) {
                        $stmt = $mysqli->prepare('INSERT INTO users (name,username,password,role,phone,profile_photo,created_by) VALUES (?,?,?,?,?,?,?)');
                        $stmt->bind_param('ssssssi', $name, $username, $hash, $rolePost, $phone, $newPhoto, $myId);
                    } else {
                        $stmt = $mysqli->prepare('INSERT INTO users (name,username,password,role,phone,created_by) VALUES (?,?,?,?,?,?)');
                        $stmt->bind_param('sssssi', $name, $username, $hash, $rolePost, $phone, $myId);
                    }
                } else {
                    if ($password !== '') {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        if ($hasProfilePhoto) {
                            $stmt = $mysqli->prepare('UPDATE users SET name=?,username=?,password=?,role=?,phone=?,profile_photo=? WHERE id=?');
                            $stmt->bind_param('ssssssi', $name, $username, $hash, $rolePost, $phone, $newPhoto, $id);
                        } else {
                            $stmt = $mysqli->prepare('UPDATE users SET name=?,username=?,password=?,role=?,phone=? WHERE id=?');
                            $stmt->bind_param('sssssi', $name, $username, $hash, $rolePost, $phone, $id);
                        }
                    } else {
                        if ($hasProfilePhoto) {
                            $stmt = $mysqli->prepare('UPDATE users SET name=?,username=?,role=?,phone=?,profile_photo=? WHERE id=?');
                            $stmt->bind_param('sssssi', $name, $username, $rolePost, $phone, $newPhoto, $id);
                        } else {
                            $stmt = $mysqli->prepare('UPDATE users SET name=?,username=?,role=?,phone=? WHERE id=?');
                            $stmt->bind_param('ssssi', $name, $username, $rolePost, $phone, $id);
                        }
                    }
                }
                if ($stmt->execute()) { header('Location: index.php?page=users'); exit; }
                $error = 'Gagal menyimpan user. Username mungkin sudah digunakan.';
                $stmt->close();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

/* ===================== DELETE ===================== */
if ($action === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $chk = $mysqli->prepare("SELECT role, {$profilePhotoSelect} FROM users WHERE id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($targetRole, $oldPhoto);
    $chk->fetch();
    $chk->close();

    if (in_array($targetRole, $rolesAllowed, true) && $id !== $myId) {
        if ($oldPhoto && file_exists(__DIR__ . '/../uploads/avatars/' . $oldPhoto)) @unlink(__DIR__ . '/../uploads/avatars/' . $oldPhoto);
        $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: index.php?page=users'); exit;
}

/* ===================== REMOVE PHOTO ===================== */
if ($action === 'remove_photo' && $canPhoto) {
    $id = intval($_GET['id'] ?? 0);
    $chk = $mysqli->prepare('SELECT profile_photo, role FROM users WHERE id = ?');
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($oldPhoto, $targetRole);
    $chk->fetch();
    $chk->close();

    $canRemove = ($myRole === 'hrd') || in_array($targetRole, $rolesAllowed, true);
    if ($canRemove && $oldPhoto) {
        if (file_exists(__DIR__ . '/../uploads/avatars/' . $oldPhoto)) @unlink(__DIR__ . '/../uploads/avatars/' . $oldPhoto);
        $stmt = $mysqli->prepare('UPDATE users SET profile_photo = NULL WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: index.php?page=users&action=edit&id=' . $id); exit;
}

/* ===================== LIST ===================== */
if ($myRole === 'hrd') {
    // HRD lihat semua user
    $usersResult = $mysqli->query("SELECT u.id, u.name, u.username, u.role, u.phone, {$userProfilePhotoSelect}, u.last_login, c.name AS created_by_name
        FROM users u LEFT JOIN users c ON u.created_by = c.id ORDER BY u.role, u.name");
} elseif (empty($rolesAllowed)) {
    // fallback kosong
    $usersResult = $mysqli->query("SELECT u.id, u.name, u.username, u.role, u.phone, {$userProfilePhotoSelect}, u.last_login, c.name AS created_by_name FROM users u LEFT JOIN users c ON u.created_by = c.id WHERE 1=0");
} else {
    $placeholders = implode(',', array_fill(0, count($rolesAllowed), '?'));
    $types        = str_repeat('s', count($rolesAllowed));
    $usersQuery   = $mysqli->prepare("SELECT u.id, u.name, u.username, u.role, u.phone, {$userProfilePhotoSelect}, u.last_login, c.name AS created_by_name
        FROM users u LEFT JOIN users c ON u.created_by = c.id
        WHERE u.role IN ($placeholders) ORDER BY u.role, u.name");
    $usersQuery->bind_param($types, ...$rolesAllowed);
    $usersQuery->execute();
    $usersResult = $usersQuery->get_result();
}
?>

<?php if ($action === 'list'): ?>
<section class="page-head">
    <div>
        <span class="eyebrow">Manajemen User</span>
        <h1>Kelola User</h1>
        <?php if ($myRole === 'hrd'): ?>
            <p><span class="badge bg-secondary"><i class="fa-solid fa-eye"></i> HRD</span> Anda dapat melihat semua user dan mengedit profil &amp; foto.</p>
        <?php elseif (!empty($rolesAllowed)): ?>
            <p>Anda dapat mengelola: <?php echo implode(', ', array_map('role_label', $rolesAllowed)); ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($rolesAllowed) && $myRole !== 'hrd'): ?>
        <a href="index.php?page=users&action=add" class="btn btn-electric"><i class="fa-solid fa-user-plus"></i> Tambah User</a>
    <?php endif; ?>
</section>

<div class="panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th>Foto</th><th>Staff</th><th>Username</th><th>Role</th><th>Telepon</th><th>Dibuat Oleh</th><th>Last Login</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php while ($row = $usersResult->fetch_assoc()): ?>
                <tr>
                    <td>
                        <img src="<?php echo htmlspecialchars(user_avatar_url($row['profile_photo'], $row['name'], 56)); ?>"
                             alt="Foto <?php echo htmlspecialchars($row['name']); ?>"
                             class="user-avatar-thumb"
                             onerror="this.src='<?php echo dicebear_avatar($row['name'], 56); ?>'">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        <br><small class="text-muted">ID <?php echo $row['id']; ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><span class="role-badge <?php echo role_badge_class($row['role']); ?>"><?php echo role_label($row['role']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['phone'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['created_by_name'] ?: '-'); ?></td>
                    <td><?php echo $row['last_login'] ? date('d M Y H:i', strtotime($row['last_login'])) : '-'; ?></td>
                    <td>
                        <a href="index.php?page=users&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php if ($myRole !== 'hrd' && $row['id'] != $myId && in_array($row['role'], $rolesAllowed, true)): ?>
                            <a href="index.php?page=users&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Hapus user ini?');"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
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
    <div>
        <span class="eyebrow">Form User</span>
        <h1><?php echo $action === 'add' ? 'Tambah User' : 'Edit User'; ?></h1>
        <?php if ($myRole === 'hrd'): ?>
            <p><span class="badge bg-secondary">HRD</span> Anda dapat mengedit data dan foto profil. Role tidak dapat diubah.</p>
        <?php endif; ?>
    </div>
    <a href="index.php?page=users" class="btn btn-light"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
</section>

<?php if ($error): ?><div class="alert alert-danger shake"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="panel">
    <form method="post" enctype="multipart/form-data" class="needs-live-validation" novalidate>
        <div class="row g-4">

            <!-- Kolom kiri: foto profil -->
            <div class="col-md-3 text-center">
                <label class="form-label d-block fw-semibold mb-2">Foto Profil</label>
                <div class="avatar-upload-wrap">
                    <img id="avatarPreview"
                         src="<?php echo htmlspecialchars(user_avatar_url($profilePhoto, $name, 120)); ?>"
                         alt="Preview"
                         class="avatar-preview"
                         onerror="this.src='<?php echo dicebear_avatar($name ?: 'user', 120); ?>'">

                    <?php if ($canPhoto): ?>
                        <label for="profile_photo" class="btn btn-sm btn-outline-secondary mt-2 w-100">
                            <i class="fa-solid fa-camera"></i> <?php echo empty($profilePhoto) ? 'Upload Foto' : 'Ganti Foto'; ?>
                        </label>
                        <input type="file" id="profile_photo" name="profile_photo"
                               class="d-none" accept="image/jpeg,image/png,image/gif,image/webp"
                               onchange="previewAvatar(this)">
                        <small class="text-muted d-block mt-1">JPG/PNG/WEBP, maks 2MB</small>
                        <?php if (!empty($profilePhoto)): ?>
                            <a href="index.php?page=users&action=remove_photo&id=<?php echo $id; ?>"
                               class="btn btn-sm btn-outline-danger mt-1 w-100"
                               onclick="return confirm('Hapus foto profil ini?');">
                                <i class="fa-solid fa-trash"></i> Hapus Foto
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- staff_gudang: tidak bisa edit foto siapapun -->
                        <div class="alert alert-warning mt-2 p-2" style="font-size:.8rem;">
                            <i class="fa-solid fa-lock"></i> Staff gudang tidak dapat mengubah foto
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kolom kanan: form data -->
            <div class="col-md-9">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control"
                               value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                               value="<?php echo htmlspecialchars($username); ?>"
                               <?php echo $myRole === 'hrd' ? '' : 'required'; ?>>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <?php if ($myRole === 'hrd'): ?>
                            <input type="text" class="form-control" value="<?php echo role_label($roleEdit); ?>" readonly>
                        <?php else: ?>
                            <select name="role" class="form-select" required>
                                <?php foreach ($rolesAllowed as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo $roleEdit === $r ? 'selected' : ''; ?>><?php echo role_label($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Password <?php echo $action === 'edit' ? '(opsional)' : ''; ?></label>
                        <?php if ($myRole === 'hrd'): ?>
                            <input type="password" class="form-control" placeholder="(tidak bisa diubah HRD)" disabled>
                        <?php else: ?>
                            <input type="password" name="password" class="form-control"
                                   <?php echo $action === 'add' ? 'required' : ''; ?>>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-electric">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('avatarPreview').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php endif; ?>
