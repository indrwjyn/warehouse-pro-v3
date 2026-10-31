<?php
session_start();

if (!defined('BINDERBYTE_API_KEY')) {
    $binderByteKey = getenv('BINDERBYTE_API_KEY') ?: '';
    define('BINDERBYTE_API_KEY', $binderByteKey);
}

if (!defined('BINDERBYTE_BASE_URL')) {
    define('BINDERBYTE_BASE_URL', 'https://api.binderbyte.com/v1');
}

function binderbyte_endpoint($endpoint) {
    $paths = [
        'track' => 'track',
        'list_courier' => 'list_courier',
    ];

    return BINDERBYTE_BASE_URL . '/' . ($paths[$endpoint] ?? ltrim((string) $endpoint, '/'));
}

function tracking_couriers() {
    return [
        'jne' => 'JNE',
        'pos' => 'POS Indonesia',
        'tiki' => 'TIKI',
        'sicepat' => 'SiCepat',
        'anteraja' => 'Anteraja',
        'lion' => 'Lion Parcel',
        'ninja' => 'Ninja Express',
        'sap' => 'SAP Express',
        'ide' => 'ID Express',
        'jnt' => 'J&T Express',
        'wahana' => 'Wahana',
        'spx' => 'Shopee Express',
        'kurir_tokopedia' => 'Kurir Tokopedia',
    ];
}

function normalize_tracking_courier($courier) {
    $value = strtolower(trim((string) $courier));
    $value = str_replace(['&', '.', '-', '_'], '', $value);
    $value = preg_replace('/\s+/', '', $value);

    $aliases = [
        'auto' => 'auto',
        'otomatis' => 'auto',
        'jne' => 'jne',
        'jneexpress' => 'jne',
        'pos' => 'pos',
        'posindonesia' => 'pos',
        'tiki' => 'tiki',
        'sicepat' => 'sicepat',
        'sicepatexpress' => 'sicepat',
        'anteraja' => 'anteraja',
        'lion' => 'lion',
        'lionparcel' => 'lion',
        'ninja' => 'ninja',
        'ninjaexpress' => 'ninja',
        'ninjaxpress' => 'ninja',
        'sap' => 'sap',
        'sapexpress' => 'sap',
        'ide' => 'ide',
        'idexpress' => 'ide',
        'idexpress' => 'ide',
        'jnt' => 'jnt',
        'jt' => 'jnt',
        'jtexpress' => 'jnt',
        'jntexpress' => 'jnt',
        'wahana' => 'wahana',
        'spx' => 'spx',
        'shopeeexpress' => 'spx',
        'kurirtokopedia' => 'kurir_tokopedia',
        'tokopedia' => 'kurir_tokopedia',
    ];

    return $aliases[$value] ?? strtolower(trim((string) $courier));
}

function tracking_auto_couriers($awb = '') {
    $awb = strtoupper(trim((string) $awb));
    $first = [];

    if (preg_match('/^TKP/i', $awb)) $first[] = 'kurir_tokopedia';
    if (preg_match('/^SPXID|^SPX/i', $awb)) $first[] = 'spx';
    if (preg_match('/^NJVID|^NVS/i', $awb)) $first[] = 'ninja';
    if (preg_match('/^SICS|^SICC|^089|^099/i', $awb)) $first[] = 'sicepat';
    if (preg_match('/^IDE|^IDEX/i', $awb)) $first[] = 'ide';
    if (preg_match('/^SAP/i', $awb)) $first[] = 'sap';
    if (preg_match('/^LP|^CLI/i', $awb)) $first[] = 'lion';
    if (preg_match('/^WH|^WHN/i', $awb)) $first[] = 'wahana';
    if (preg_match('/^JP|^6901|^6000/i', $awb)) $first[] = 'jnt';
    if (preg_match('/^(10|300)/i', $awb) && strlen($awb) >= 12) $first[] = 'anteraja';
    if (preg_match('/^[A-Z]{2}[0-9]{8,9}[A-Z]{2}$/i', $awb)) $first[] = 'pos';
    if (preg_match('/^[0-9]{10,12}$/', $awb)) $first[] = 'tiki';

    return array_values(array_unique(array_merge($first, [
        'jne',
        'jnt',
        'sicepat',
        'tiki',
        'anteraja',
        'pos',
        'spx',
        'ninja',
        'lion',
        'wahana',
        'sap',
        'ide',
        'kurir_tokopedia',
    ])));
}

// ============================================================
//  ROLES YANG ADA:
//  direktur, manager, kepala_gudang, admin_gudang, staff_gudang, hrd
//
//  "FULL AKSES" (akses sistem sama, bisa semua fitur kecuali yg khusus staff):
//    direktur, manager, kepala_gudang, admin_gudang, hrd
//
//  "TERBATAS":
//    staff_gudang → hanya login/logout + input inventory (nama otomatis PJ)
// ============================================================

/** Semua role selain staff_gudang */
define('FULL_ACCESS_ROLES', ['direktur','manager','kepala_gudang','admin_gudang','hrd']);

function clean_input($value) {
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: auth/login.php');
        exit;
    }
}

/** Versi untuk endpoint JSON: balas 401 (bukan redirect) bila belum login */
function require_login_json() {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => 401, 'message' => 'Silakan login terlebih dahulu.']);
        exit;
    }
}

function require_role($roles = []) {
    $allowed = array_map('strtolower', (array) $roles);
    if (empty($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed, true)) {
        header('Location: index.php?page=dashboard');
        exit;
    }
}

// --- Helper role ---
function is_direktur()     { return ($_SESSION['role'] ?? '') === 'direktur'; }
function is_manager()      { return ($_SESSION['role'] ?? '') === 'manager'; }
function is_kepala_gudang(){ return ($_SESSION['role'] ?? '') === 'kepala_gudang'; }
function is_admin_gudang() { return ($_SESSION['role'] ?? '') === 'admin_gudang'; }
function is_staff_gudang() { return ($_SESSION['role'] ?? '') === 'staff_gudang'; }
function is_hrd()          { return ($_SESSION['role'] ?? '') === 'hrd'; }

/** Apakah role ini termasuk "full access" (bukan staff_gudang) */
function is_full_access() {
    return in_array($_SESSION['role'] ?? '', FULL_ACCESS_ROLES, true);
}

/** Bisa kelola user (semua kecuali staff_gudang) */
function can_manage_users() {
    return is_full_access();
}

/** Bisa input transaksi barang */
function can_input_inventory() {
    // semua role bisa input; staff_gudang dengan nama otomatis
    return in_array($_SESSION['role'] ?? '', array_merge(FULL_ACCESS_ROLES, ['staff_gudang']), true);
}

/** Bisa upload/edit foto profil (semua kecuali staff_gudang) */
function can_edit_profile_photo() {
    return is_full_access();
}

/** Bisa kelola produk/lokasi/kategori (HRD tidak bisa) */
function can_manage_products() {
    return in_array($_SESSION['role'] ?? '', ['direktur','manager','kepala_gudang','admin_gudang'], true);
}

/**
 * Role yang boleh dibuat oleh masing-masing pembuat.
 * Semua "full access role" bisa buat user baru (kecuali staff_gudang yg tidak bisa).
 * Hierarki: direktur paling tinggi, lalu semuanya setara, 
 * tapi tidak boleh buat role yang lebih tinggi dari dirinya.
 *
 * Aturan sederhana:
 *   direktur      → semua
 *   manager       → kepala_gudang, admin_gudang, staff_gudang, hrd
 *   kepala_gudang → admin_gudang, staff_gudang
 *   admin_gudang  → staff_gudang
 *   hrd           → (tidak tambah user, hanya edit profil)
 *   staff_gudang  → (tidak ada)
 */
function allowed_roles_to_create($creatorRole) {
    switch ($creatorRole) {
        case 'direktur':     return ['manager','kepala_gudang','admin_gudang','staff_gudang','hrd'];
        case 'manager':      return ['kepala_gudang','admin_gudang','staff_gudang','hrd'];
        case 'kepala_gudang':return ['admin_gudang','staff_gudang'];
        case 'admin_gudang': return ['staff_gudang'];
        default:             return [];
    }
}

function role_label($role) {
    $map = [
        'direktur'     => 'Direktur',
        'manager'      => 'Manager',
        'kepala_gudang'=> 'Kepala Gudang',
        'admin_gudang' => 'Admin Gudang',
        'staff_gudang' => 'Staff Gudang',
        'hrd'          => 'HRD',
    ];
    return $map[$role] ?? ucfirst($role);
}

function role_badge_class($role) {
    $map = [
        'direktur'     => 'role-direktur',
        'manager'      => 'role-manager',
        'kepala_gudang'=> 'role-kepala',
        'admin_gudang' => 'role-admin',
        'staff_gudang' => 'role-staff',
        'hrd'          => 'role-hrd',
    ];
    return $map[$role] ?? 'role-staff';
}

function current_user() {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ];
}

function dicebear_avatar($seed, $size = 96) {
    return 'https://api.dicebear.com/8.x/thumbs/svg?seed=' . rawurlencode($seed) . '&size=' . intval($size);
}

/**
 * Ambil URL foto profil user.
 * Jika ada file upload → pakai file.
 * Jika tidak → fallback ke DiceBear.
 */
function user_avatar_url($profilePhoto, $nameSeed, $size = 96) {
    if (!empty($profilePhoto) && file_exists(__DIR__ . '/../uploads/avatars/' . $profilePhoto)) {
        return 'uploads/avatars/' . rawurlencode($profilePhoto);
    }
    return dicebear_avatar($nameSeed, $size);
}

function product_image_url($seed) {
    if ($seed && preg_match('/^uploads\//', $seed)) return $seed;
    $query = rawurlencode($seed ?: 'warehouse product');
    return "https://source.unsplash.com/360x240/?{$query},warehouse";
}

function product_display_image($imagePath, $fallbackSeed) {
    return !empty($imagePath) ? product_image_url($imagePath) : product_image_url($fallbackSeed);
}

function get_dashboard_stats($mysqli) {
    $stats = ['products'=>0,'low_stock'=>0,'locations'=>0,'entries_today'=>0,'exits_today'=>0];
    foreach ([
        'products'     => 'SELECT COUNT(*) AS total FROM products',
        'low_stock'    => 'SELECT COUNT(*) AS total FROM products WHERE current_stock <= stock_min',
        'locations'    => 'SELECT COUNT(*) AS total FROM storage_locations',
        'entries_today'=> 'SELECT COUNT(*) AS total FROM stock_entries WHERE DATE(entry_date) = CURDATE()',
        'exits_today'  => 'SELECT COUNT(*) AS total FROM stock_exits WHERE DATE(exit_date) = CURDATE()',
    ] as $key => $query) {
        $result = $mysqli->query($query);
        if ($result) $stats[$key] = (int)$result->fetch_assoc()['total'];
    }
    return $stats;
}

function get_movement_trend($mysqli) {
    $data = ['labels'=>[],'in'=>[],'out'=>[]];
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $days[$date] = ['label'=>date('d M', strtotime($date)),'in'=>0,'out'=>0];
    }
    $r = $mysqli->query("SELECT DATE(entry_date) AS day, SUM(quantity) AS qty FROM stock_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(entry_date)");
    if ($r) while ($row = $r->fetch_assoc()) if (isset($days[$row['day']])) $days[$row['day']]['in'] = (int)$row['qty'];
    $r = $mysqli->query("SELECT DATE(exit_date) AS day, SUM(quantity) AS qty FROM stock_exits WHERE exit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(exit_date)");
    if ($r) while ($row = $r->fetch_assoc()) if (isset($days[$row['day']])) $days[$row['day']]['out'] = (int)$row['qty'];
    foreach ($days as $day) { $data['labels'][] = $day['label']; $data['in'][] = $day['in']; $data['out'][] = $day['out']; }
    return $data;
}

function get_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function table_column_exists($mysqli, $table, $column) {
    $query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = $mysqli->query($query);
    return $result && $result->num_rows > 0;
}
