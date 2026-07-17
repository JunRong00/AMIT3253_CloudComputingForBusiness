<?php
require 'config.php';
require 'auth.php';
require_login();

header('Content-Type: application/json');

$room_id   = (int)($_GET['room_id'] ?? 0);
$date      = $_GET['booking_date'] ?? '';
$excludeId = (int)($_GET['exclude_booking_id'] ?? 0);

if ($room_id < 1 || $date === '') {
    echo json_encode(['full_slots' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT time_slot FROM bookings WHERE room_id = ? AND booking_date = ? AND id != ?');
$stmt->bind_param('isi', $room_id, $date, $excludeId);
$stmt->execute();
$fullSlots = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'time_slot');
$stmt->close();

echo json_encode(['full_slots' => $fullSlots]);
