<?php
/**
 * Laporan
 * - Full access (direktur, manager, kepala_gudang, admin_gudang, hrd) : lihat semua
 * - staff_gudang : hanya lihat laporan milik dirinya
 */

$currentUser = current_user();
$myRole      = $currentUser['role'];
$myName      = $currentUser['name'];
$isStaff     = ($myRole === 'staff_gudang');

// 1. Query untuk mengambil data barang masuk (stock_entries) sesuai database asli Anda
$queryIn = "SELECT CONCAT('IN-', se.id) AS log_code, p.name AS product_name, p.sku, sl.warehouse_name, sl.room_name, sl.rack_number, 
                   'IN' AS movement_type, se.quantity, p.unit, se.vendor AS partner_name, se.sender_name AS sender_name, 
                   NULL AS courier, NULL AS awb_number, u_staff.name AS staff_in_charge, IFNULL(u_creator.name, 'System') AS recorded_by, 
                   se.entry_date AS movement_date, se.notes 
            FROM stock_entries se 
            LEFT JOIN products p ON se.product_id = p.id 
            LEFT JOIN storage_locations sl ON se.storage_location_id = sl.id
            LEFT JOIN users u_staff ON se.staff_in_charge_id = u_staff.id
            LEFT JOIN users u_creator ON se.created_by = u_creator.id";

// 2. Query untuk mengambil data barang keluar (stock_exits) sesuai database asli Anda
$queryOut = "SELECT CONCAT('OUT-', sx.id) AS log_code, p.name AS product_name, p.sku, sl.warehouse_name, sl.room_name, sl.rack_number, 
                    'OUT' AS movement_type, sx.quantity, p.unit, sx.destination AS partner_name, NULL AS sender_name, 
                    sx.courier AS courier, sx.awb_number AS awb_number, u_staff.name AS staff_in_charge, IFNULL(u_creator.name, 'System') AS recorded_by, 
                    sx.exit_date AS movement_date, sx.notes 
             FROM stock_exits sx 
             LEFT JOIN products p ON sx.product_id = p.id 
             LEFT JOIN storage_locations sl ON sx.storage_location_id = sl.id
             LEFT JOIN users u_staff ON sx.staff_in_charge_id = u_staff.id
             LEFT JOIN users u_creator ON sx.created_by = u_creator.id";

// 3. Menjalankan query gabungan berdasarkan hak akses user (Role)
if ($isStaff) {
    // Jika staff gudang, filter berdasarkan nama staff_in_charge hasil gabungan
    $sql = "SELECT * FROM (
                ($queryIn) 
                UNION ALL 
                ($queryOut)
            ) AS combined_reports 
            WHERE staff_in_charge = ? 
            ORDER BY movement_date DESC LIMIT 500";
            
    $logs = $mysqli->prepare($sql);
    if ($logs) {
        $logs->bind_param('s', $myName);
        $logs->execute();
        $logs = $logs->get_result();
    } else {
        die("Query Error: " . $mysqli->error);
    }
} else {
    // Jika admin/manager/direktur, ambil semua data tanpa filter nama
    $sql = "SELECT * FROM (
                ($queryIn) 
                UNION ALL 
                ($queryOut)
            ) AS combined_reports 
            ORDER BY movement_date DESC LIMIT 500";
            
    $logs = $mysqli->query($sql);
    if (!$logs) {
        die("Query Error: " . $mysqli->error);
    }
}
?>
<section class="page-head">
    <div>
        <span class="eyebrow">Laporan Sistem</span>
        <h1>Laporan Barang Keluar &amp; Masuk</h1>
        <?php if ($isStaff): ?>
            <p><span class="badge bg-warning text-dark"><i class="fa-solid fa-user"></i> Laporan Saya</span> Hanya transaksi dengan penanggung jawab: <strong><?php echo htmlspecialchars($myName); ?></strong></p>
        <?php else: ?>
            <p>Seluruh riwayat transaksi barang masuk dan keluar sistem.</p>
        <?php endif; ?>
    </div>
</section>

<div class="panel">
    <div class="panel-title">
        <div><h2>Riwayat Transaksi</h2></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr><th>Kode</th><th>Produk</th><th>Lokasi</th><th>Tipe</th><th>Qty</th><th>Partner / Kurir</th><th>Staff PJ</th><th>Dicatat Oleh</th><th>Tanggal</th><th>Catatan</th></tr>
            </thead>
            <tbody>
            <?php while ($row = $logs->fetch_assoc()): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($row['log_code']); ?></code></td>
                    <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($row['sku']); ?></small></td>
                    <td><?php echo htmlspecialchars($row['warehouse_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($row['room_name'].' / '.$row['rack_number']); ?></small></td>
                    <td><span class="badge <?php echo $row['movement_type']==='IN'?'badge-in':'badge-out'; ?>"><?php echo $row['movement_type']; ?></span></td>
                    <td><?php echo $row['quantity']; ?> <?php echo htmlspecialchars($row['unit']); ?></td>
                    <td>
                        <?php if ($row['movement_type']==='IN'): ?>
                            <small><?php echo htmlspecialchars($row['partner_name']); ?></small><br>
                            <small class="text-muted">Pengirim: <?php echo htmlspecialchars($row['sender_name']??'-'); ?></small>
                        <?php else: ?>
                            <small><?php echo htmlspecialchars($row['courier']); ?></small><br>
                            <small class="text-muted">AWB: <?php echo htmlspecialchars($row['awb_number']??'-'); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['staff_in_charge']); ?></td>
                    <td><?php echo htmlspecialchars($row['recorded_by']); ?></td>
                    <td><?php echo date('d M Y H:i', strtotime($row['movement_date'])); ?></td>
                    <td><small><?php echo htmlspecialchars($row['notes']??'-'); ?></small></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
