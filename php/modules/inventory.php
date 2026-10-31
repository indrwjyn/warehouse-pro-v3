<?php
/**
 * Inventory Movement
 * - Full access (direktur, manager, kepala_gudang, admin_gudang, hrd): form input + lihat semua laporan
 * - staff_gudang: form input (nama otomatis = penanggung jawab) + lihat laporan miliknya sendiri
 */

$currentUser = current_user();
$myRole      = $currentUser['role'];
$myId        = $currentUser['id'];
$myName      = $currentUser['name'];

$isStaff = ($myRole === 'staff_gudang');
// HRD bisa lihat semua tapi tetap bisa input (sesuai permintaan: akses sama dengan full-access)
$isFull  = is_full_access();

$error    = '';
$products = $locations = $staff = $productStocks = [];

$pr = $mysqli->query('SELECT id, sku, name, current_stock, storage_location_id FROM products ORDER BY name');
while ($row = $pr->fetch_assoc()) { $products[] = $row; $productStocks[$row['id']] = $row; }

$lr = $mysqli->query('SELECT id, warehouse_name, room_name, rack_number FROM storage_locations ORDER BY warehouse_name, rack_number');
while ($row = $lr->fetch_assoc()) $locations[] = $row;

$sr = $mysqli->query("SELECT id, name FROM users WHERE role = 'staff_gudang' ORDER BY name ASC");
while ($row = $sr->fetch_assoc()) $staff[] = $row;

// Proses POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movement            = ($_POST['movement'] ?? 'IN') === 'OUT' ? 'OUT' : 'IN';
    $product_id          = intval($_POST['product_id'] ?? 0);
    $storage_location_id = intval($_POST['storage_location_id'] ?? 0);
    $quantity            = intval($_POST['quantity'] ?? 0);
    $notes               = clean_input($_POST['notes'] ?? '');
    $currentStock        = $productStocks[$product_id]['current_stock'] ?? 0;

    // Staff: PJ otomatis = diri sendiri
    $staff_in_charge_id = $isStaff ? $myId : intval($_POST['staff_in_charge_id'] ?? 0);

    if ($product_id <= 0 || $storage_location_id <= 0 || $staff_in_charge_id <= 0 || $quantity <= 0) {
        $error = 'Produk, lokasi, dan jumlah wajib diisi.';
    } elseif ($movement === 'OUT' && $quantity > $currentStock) {
        $error = 'Jumlah barang keluar tidak boleh melebihi stok saat ini.';
    } else {
        $mysqli->begin_transaction();
        try {
            if ($movement === 'IN') {
                $vendor      = clean_input($_POST['vendor'] ?? '');
                $sender_name = clean_input($_POST['sender_name'] ?? '');
                $entry_date  = clean_input($_POST['entry_date'] ?? date('Y-m-d\TH:i'));
                if ($vendor === '' || $sender_name === '') throw new Exception('Vendor dan nama pengirim wajib diisi untuk barang masuk.');
                $entryDateSql = date('Y-m-d H:i:s', strtotime($entry_date));
                $stmt = $mysqli->prepare('INSERT INTO stock_entries (product_id,storage_location_id,vendor,sender_name,staff_in_charge_id,quantity,entry_date,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('iissiissi', $product_id, $storage_location_id, $vendor, $sender_name, $staff_in_charge_id, $quantity, $entryDateSql, $notes, $myId);
                $delta = $quantity;
            } else {
                $courier     = clean_input($_POST['courier'] ?? '');
                $awb_number  = clean_input($_POST['awb_number'] ?? '');
                $destination = clean_input($_POST['destination'] ?? '');
                $exit_date   = clean_input($_POST['exit_date'] ?? date('Y-m-d\TH:i'));
                if ($courier === '') throw new Exception('Kurir wajib diisi untuk barang keluar.');
                $exitDateSql = date('Y-m-d H:i:s', strtotime($exit_date));
                $stmt = $mysqli->prepare('INSERT INTO stock_exits (product_id,storage_location_id,courier,staff_in_charge_id,quantity,exit_date,awb_number,destination,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('iisiissssi', $product_id, $storage_location_id, $courier, $staff_in_charge_id, $quantity, $exitDateSql, $awb_number, $destination, $notes, $myId);
                $delta = -$quantity;
            }
            if (!$stmt->execute()) throw new Exception('Transaksi gagal disimpan.');
            $upd = $mysqli->prepare('UPDATE products SET current_stock = current_stock + ?, storage_location_id = ? WHERE id = ?');
            $upd->bind_param('iii', $delta, $storage_location_id, $product_id);
            $upd->execute();
            $mysqli->commit();
            header('Location: index.php?page=inventory'); exit;
        } catch (Exception $ex) {
            $mysqli->rollback();
            $error = $ex->getMessage();
        }
    }
}

// --- PERBAIKAN PENGAMBILAN RIWAYAT LOGISTIK SECARA LANGSUNG (TANPA VIEW) ---

// 1. Query dasar untuk data barang masuk
$queryIn = "SELECT CONCAT('IN-', se.id) AS log_code, p.name AS product_name, p.sku, sl.warehouse_name, sl.room_name, sl.rack_number, 
                   'IN' AS movement_type, se.quantity, p.unit, se.vendor AS partner_name, se.sender_name AS sender_name, 
                   NULL AS courier, NULL AS awb_number, u_staff.name AS staff_in_charge, 
                   se.entry_date AS movement_date 
            FROM stock_entries se 
            LEFT JOIN products p ON se.product_id = p.id 
            LEFT JOIN storage_locations sl ON se.storage_location_id = sl.id
            LEFT JOIN users u_staff ON se.staff_in_charge_id = u_staff.id";

// 2. Query dasar untuk data barang keluar
$queryOut = "SELECT CONCAT('OUT-', sx.id) AS log_code, p.name AS product_name, p.sku, sl.warehouse_name, sl.room_name, sl.rack_number, 
                    'OUT' AS movement_type, sx.quantity, p.unit, sx.destination AS partner_name, NULL AS sender_name, 
                    sx.courier AS courier, sx.awb_number AS awb_number, u_staff.name AS staff_in_charge, 
                    sx.exit_date AS movement_date 
             FROM stock_exits sx 
             LEFT JOIN products p ON sx.product_id = p.id 
             LEFT JOIN storage_locations sl ON sx.storage_location_id = sl.id
             LEFT JOIN users u_staff ON sx.staff_in_charge_id = u_staff.id";

// 3. Gabungkan kedua query (UNION ALL) dan filter berdasarkan hak akses user
if ($isStaff) {
    // Jika staff gudang, filter riwayat miliknya sendiri
    $sql = "SELECT * FROM (
                ($queryIn) 
                UNION ALL 
                ($queryOut)
            ) AS combined_reports 
            WHERE staff_in_charge = ? 
            ORDER BY movement_date DESC LIMIT 10";
            
    $stmt_logs = $mysqli->prepare($sql);
    $stmt_logs->bind_param('s', $myName);
    $stmt_logs->execute();
    $logs = $stmt_logs->get_result();
} else {
    // Jika admin/manager/full-access, tampilkan semua riwayat transaksi terbaru
    $sql = "SELECT * FROM (
                ($queryIn) 
                UNION ALL 
                ($queryOut)
            ) AS combined_reports 
            ORDER BY movement_date DESC LIMIT 10";
            
    $logs = $mysqli->query($sql);
}
// -------------------------------------------------------------------------
?>

<section class="page-head">
    <div>
        <span class="eyebrow">Inventory Movement</span>
        <h1>Barang Masuk &amp; Keluar</h1>
        <?php if ($isStaff): ?>
            <p>Nama Anda <strong><?php echo htmlspecialchars($myName); ?></strong> akan otomatis tercantum sebagai penanggung jawab.</p>
        <?php else: ?>
            <p>Setiap transaksi mencatat staff penanggung jawab, ruangan, rak, dan detail partner logistik.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($error): ?><div class="alert alert-danger shake"><?php echo $error; ?></div><?php endif; ?>

<section class="row g-4">
    <div class="col-xl-5">
        <div class="panel sticky-xl-top form-panel">
            <div class="panel-title">
                <div>
                    <h2>Form Transaksi</h2>
                    <?php if ($isStaff): ?>
                        <p>Penanggung jawab otomatis: <strong><?php echo htmlspecialchars($myName); ?></strong></p>
                    <?php else: ?>
                        <p>Pilih tipe lalu lengkapi field wajib.</p>
                    <?php endif; ?>
                </div>
            </div>
            <form method="post" id="inventoryForm" class="needs-live-validation" novalidate>
                <div class="segmented mb-3">
                    <input type="radio" name="movement" id="moveIn" value="IN" checked>
                    <label for="moveIn"><i class="fa-solid fa-arrow-down"></i> Masuk</label>
                    <input type="radio" name="movement" id="moveOut" value="OUT">
                    <label for="moveOut"><i class="fa-solid fa-arrow-up"></i> Keluar</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Produk</label>
                    <select id="product_id" name="product_id" class="form-select" required>
                        <option value="">Pilih produk</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-location="<?php echo $p['storage_location_id']; ?>"><?php echo htmlspecialchars($p['name']); ?> - Stok <?php echo $p['current_stock']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gudang / Ruangan / Rak</label>
                    <select name="storage_location_id" id="storage_location_id" class="form-select" required>
                        <option value="">Pilih lokasi</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['warehouse_name'].' - '.$loc['room_name'].' - '.$loc['rack_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!$isStaff): ?>
                <div class="mb-3">
                    <label class="form-label">Staff Penanggung Jawab</label>
                    <select name="staff_in_charge_id" class="form-select" required>
                        <option value="">Pilih staff lapangan</option>
                        <?php foreach ($staff as $person): ?>
                            <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label">Penanggung Jawab</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($myName); ?>" readonly>
                </div>
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Jumlah</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-6 date-in">
                        <label class="form-label">Tanggal Masuk</label>
                        <input type="datetime-local" name="entry_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    <div class="col-md-6 date-out d-none">
                        <label class="form-label">Tanggal Keluar</label>
                        <input type="datetime-local" name="exit_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>
                </div>
                <div class="movement-in">
                    <div class="mb-3"><label class="form-label">Vendor</label><input type="text" name="vendor" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama Pengirim</label><input type="text" name="sender_name" class="form-control" required></div>
                </div>
                <div class="movement-out d-none">
                    <div class="mb-3">
                        <label class="form-label">Kurir</label>
                        <select name="courier" class="form-select">
                            <option value="">Pilih kurir</option>
                            <option value="jnt" selected>J&amp;T Express</option>
                            <option value="jne">JNE Express</option>
                            <option value="pos">POS Indonesia</option>
                            <option value="sicepat">SiCepat</option>
                            <option value="tiki">TIKI</option>
                            <option value="anteraja">AnterAja</option>
                            <option value="wahana">Wahana</option>
                            <option value="ninja">Ninja Xpress</option>
                            <option value="lion">Lion Parcel</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nomor Resi</label><input type="text" name="awb_number" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Tujuan</label><input type="text" name="destination" class="form-control"></div>
                </div>
                <div class="mb-3"><label class="form-label">Catatan</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                <button class="btn btn-electric w-100" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi</button>
            </form>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-title">
                <div>
                    <h2>Laporan Logistik Detail</h2>
                    <?php if ($isStaff): ?>
                        <p>Menampilkan transaksi dengan penanggung jawab: <strong><?php echo htmlspecialchars($myName); ?></strong></p>
                    <?php else: ?>
                        <p>Seluruh riwayat transaksi barang masuk dan keluar.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Kode</th><th>Produk</th><th>Lokasi</th><th>Tipe</th><th>Qty</th><th>Partner</th><th>Staff PJ</th><th>Tanggal</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['log_code']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br><small><?php echo htmlspecialchars($row['sku']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['warehouse_name']); ?><br><small><?php echo htmlspecialchars($row['room_name'].' / '.$row['rack_number']); ?></small></td>
                            <td><span class="badge <?php echo $row['movement_type']==='IN' ? 'badge-in' : 'badge-out'; ?>"><?php echo $row['movement_type']; ?></span></td>
                            <td><?php echo $row['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($row['movement_type']==='IN' ? ($row['partner_name'].'/'.($row['sender_name']??'')) : ($row['courier'].'/'.($row['awb_number']??''))); ?></td>
                            <td><?php echo htmlspecialchars($row['staff_in_charge']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($row['movement_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<script>
window.productStockMap = <?php echo json_encode($productStocks); ?>;
</script>
