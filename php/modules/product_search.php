<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_login_json();

$term = '%' . ($mysqli->real_escape_string($_GET['term'] ?? '')) . '%';
$stmt = $mysqli->prepare('SELECT p.id, p.sku, p.name, p.unit, p.current_stock, p.stock_min, p.image_seed, p.image_path, sl.warehouse_name, sl.room_name, sl.rack_number FROM products p JOIN storage_locations sl ON p.storage_location_id = sl.id WHERE p.name LIKE ? OR p.sku LIKE ? ORDER BY p.name ASC LIMIT 30');
$stmt->bind_param('ss', $term, $term);
$stmt->execute();
$result = $stmt->get_result();
$output = [];
while ($row = $result->fetch_assoc()) {
    $row['image_url'] = product_display_image($row['image_path'], $row['image_seed'] ?: $row['name']);
    $output[] = $row;
}
get_json_response($output);
