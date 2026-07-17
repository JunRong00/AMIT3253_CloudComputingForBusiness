<?php
require 'config.php';
require 'auth.php';
require_login();

header('Content-Type: application/json');

$vendor_id = (int)($_GET['vendor_id'] ?? 0);
$date      = $_GET['date'] ?? '';
$excludeId = (int)($_GET['exclude_booking_id'] ?? 0);

if ($vendor_id < 1 || $date === '') {
    echo json_encode(['full_slots' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT capacity FROM vendors WHERE id = ?');
$stmt->bind_param('i', $vendor_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vendor) {
    echo json_encode(['full_slots' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT time_slot, COUNT(*) AS booked FROM bookings WHERE vendor_id = ? AND booking_date = ? AND id != ? GROUP BY time_slot');
$stmt->bind_param('isi', $vendor_id, $date, $excludeId);
$stmt->execute();
$counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$fullSlots = [];
foreach ($counts as $c) {
    if ($c['booked'] >= $vendor['capacity']) {
        $fullSlots[] = $c['time_slot'];
    }
}

echo json_encode(['full_slots' => $fullSlots]);
