<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $hasProfilePhoto = table_column_exists($mysqli, 'users', 'profile_photo');
        $profileSelect = $hasProfilePhoto ? 'profile_photo' : 'NULL AS profile_photo';
        $stmt = $mysqli->prepare("SELECT id, name, username, password, role, {$profileSelect} FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $name, $dbUsername, $dbPassword, $role, $profilePhoto);
            $stmt->fetch();
            if (password_verify($password, $dbPassword)) {
                session_regenerate_id(true);
                $_SESSION['user_id']      = $id;
                $_SESSION['name']         = $name;
                $_SESSION['role']         = $role;
                $_SESSION['profile_photo']= $profilePhoto;

                $update = $mysqli->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
                $update->bind_param('i', $id);
                $update->execute();
                $update->close();

                header('Location: ../index.php');
                exit;
            }
        }
        $error = 'Username atau password tidak valid.';
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Local Warehouse Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-visual">
        <div class="login-badge"><i class="fa-solid fa-shield-halved"></i> Secure Role Access</div>
        <h1>Local Warehouse Tracking</h1>
        <p>Kontrol barang masuk, barang keluar, staff penanggung jawab, dan lokasi rak dalam satu dashboard kontras.</p>
    </section>
    <section class="login-panel">
        <div class="brand-mark mx-auto mb-3"><i class="fa-solid fa-warehouse"></i></div>
        <h2>Masuk Sistem</h2>
        <p class="text-muted">Gunakan akun sesuai role: Direktur, Manager, Admin Gudang, Staff Gudang, atau HRD.</p>
        <?php if ($error): ?>
            <div class="alert alert-danger shake"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" id="loginForm" class="needs-live-validation" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="loginUsername" class="form-control" placeholder="manager" required>
                <div class="invalid-feedback">Username tidak boleh kosong.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="password" required>
                <div class="invalid-feedback">Password tidak boleh kosong.</div>
            </div>
            <button type="submit" class="btn btn-electric w-100"><i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk</button>
        </form>
        <div class="demo-accounts">
            <span>Direktur: <strong>direktur / direktur123</strong></span>
            <span>Manager: <strong>manager / manager123</strong></span>
            <span>Admin Gudang: <strong>admingudang / admin123</strong></span>
            <span>Staff Gudang: <strong>staff / staff123</strong></span>
            <span>Kepala Gudang: <strong>kepalagudang / kepala123</strong></span>
            <span>HRD: <strong>hrd / hrd123</strong></span>
        </div>
    </section>
</main>
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="appToast" class="toast text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
