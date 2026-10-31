<?php
$stats = get_dashboard_stats($mysqli);
$trend = get_movement_trend($mysqli);
$trendJson = json_encode($trend);
// Query langsung pengganti vw_logistics_report khusus untuk Dashboard
$queryIn = "SELECT CONCAT('IN-', se.id) AS log_code, p.name AS product_name, sl.warehouse_name, sl.rack_number, 
                   'IN' AS movement_type, se.quantity, u_staff.name AS staff_in_charge, se.entry_date AS movement_date 
            FROM stock_entries se 
            LEFT JOIN products p ON se.product_id = p.id 
            LEFT JOIN storage_locations sl ON se.storage_location_id = sl.id
            LEFT JOIN users u_staff ON se.staff_in_charge_id = u_staff.id";

$queryOut = "SELECT CONCAT('OUT-', sx.id) AS log_code, p.name AS product_name, sl.warehouse_name, sl.rack_number, 
                    'OUT' AS movement_type, sx.quantity, u_staff.name AS staff_in_charge, sx.exit_date AS movement_date 
             FROM stock_exits sx 
             LEFT JOIN products p ON sx.product_id = p.id 
             LEFT JOIN storage_locations sl ON sx.storage_location_id = sl.id
             LEFT JOIN users u_staff ON sx.staff_in_charge_id = u_staff.id";

$latest = $mysqli->query("SELECT * FROM (($queryIn) UNION ALL ($queryOut)) AS combined ORDER BY movement_date DESC LIMIT 6");

if (!$latest) {
    die("Gagal memuat aktivitas terbaru: " . $mysqli->error);
}
?>
<section class="page-head fade-in">
    <div>
        <span class="eyebrow">Realtime Operations</span>
        <h1>Dashboard Warehouse</h1>
        <p>Ringkasan stok, lokasi rak, serta transaksi inbound dan outbound.</p>
    </div>
    <a class="btn btn-electric" href="index.php?page=inventory"><i class="fa-solid fa-plus"></i> Input Transaksi</a>
</section>

<section class="stat-grid fade-in">
    <article class="stat-card gradient-blue">
        <i class="fa-solid fa-box-open"></i>
        <span>Total Produk</span>
        <strong><?php echo $stats['products']; ?></strong>
    </article>
    <article class="stat-card gradient-orange">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Stok Rendah</span>
        <strong><?php echo $stats['low_stock']; ?></strong>
    </article>
    <article class="stat-card gradient-green">
        <i class="fa-solid fa-map-location-dot"></i>
        <span>Lokasi Rak</span>
        <strong><?php echo $stats['locations']; ?></strong>
    </article>
    <article class="stat-card gradient-purple">
        <i class="fa-solid fa-right-left"></i>
        <span>Transaksi Hari Ini</span>
        <strong><?php echo $stats['entries_today'] + $stats['exits_today']; ?></strong>
    </article>
</section>

<section class="row g-4 fade-in">
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-title">
                <div>
                    <h2>Movement 7 Hari</h2>
                    <p>Perbandingan barang masuk dan keluar.</p>
                </div>
            </div>
            <canvas id="trendChart" height="130"></canvas>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel">
            <div class="panel-title">
                <div>
                    <h2>Logistik Terbaru</h2>
                    <p>JOIN produk, rak, staff, dan partner pengiriman.</p>
                </div>
            </div>
            <div class="activity-list">
                <?php while ($row = $latest->fetch_assoc()): ?>
                    <div class="activity-item">
                        <span class="activity-icon <?php echo $row['movement_type'] === 'IN' ? 'in' : 'out'; ?>">
                            <i class="fa-solid <?php echo $row['movement_type'] === 'IN' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                        </span>
                        <div>
                            <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                            <small><?php echo htmlspecialchars($row['staff_in_charge']); ?> - <?php echo htmlspecialchars($row['warehouse_name'] . ' / ' . $row['rack_number']); ?></small>
                        </div>
                        <b><?php echo $row['quantity']; ?></b>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<script>
window.addEventListener('DOMContentLoaded', function () {
    const trendData = <?php echo $trendJson; ?>;
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData.labels,
            datasets: [
                { label: 'Barang Masuk', data: trendData.in, backgroundColor: '#22c55e', borderRadius: 10 },
                { label: 'Barang Keluar', data: trendData.out, backgroundColor: '#f97316', borderRadius: 10 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#475569' } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#475569' } },
                y: { beginAtZero: true, ticks: { color: '#475569' } }
            }
        }
    });
});
</script>
