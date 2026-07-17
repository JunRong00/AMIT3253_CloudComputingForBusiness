<?php
require 'config.php';
require 'auth.php';
require_login();

header('Content-Type: application/json');

$court_id  = (int)($_GET['court_id'] ?? 0);
$date      = $_GET['booking_date'] ?? '';
$excludeId = (int)($_GET['exclude_booking_id'] ?? 0);

if ($court_id < 1 || $date === '') {
    echo json_encode(['unavailable_slot_ids' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT time_slot_id FROM bookings WHERE court_id = ? AND booking_date = ? AND id != ?');
$stmt->bind_param('isi', $court_id, $date, $excludeId);
$stmt->execute();
$booked = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'time_slot_id'));
$stmt->close();

$stmt = $conn->prepare('SELECT time_slot_id FROM closures WHERE court_id = ? AND closure_date = ?');
$stmt->bind_param('is', $court_id, $date);
$stmt->execute();
$closures = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$closedAllDay = false;
$closedSlotIds = [];
foreach ($closures as $c) {
    if ($c['time_slot_id'] === null) {
        $closedAllDay = true;
    } else {
        $closedSlotIds[] = (int)$c['time_slot_id'];
    }
}

if ($closedAllDay) {
    $unavailable = array_map('intval', array_column($conn->query('SELECT id FROM time_slots')->fetch_all(MYSQLI_ASSOC), 'id'));
} else {
    $unavailable = array_values(array_unique(array_merge($booked, $closedSlotIds)));
}

echo json_encode(['unavailable_slot_ids' => $unavailable]);
