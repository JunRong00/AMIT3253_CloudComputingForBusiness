<?php
require 'config.php';
require 'auth.php';
require_login();

header('Content-Type: application/json');

$equipment_id = (int)($_GET['equipment_id'] ?? 0);
$date         = $_GET['loan_date'] ?? '';
$excludeId    = (int)($_GET['exclude_loan_id'] ?? 0);

if ($equipment_id < 1 || $date === '') {
    echo json_encode(['full_slots' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT total_units FROM equipment WHERE id = ?');
$stmt->bind_param('i', $equipment_id);
$stmt->execute();
$equipment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$equipment) {
    echo json_encode(['full_slots' => []]);
    exit;
}

$stmt = $conn->prepare('SELECT time_slot, COALESCE(SUM(quantity), 0) AS loaned FROM equipment_loans WHERE equipment_id = ? AND loan_date = ? AND id != ? GROUP BY time_slot');
$stmt->bind_param('isi', $equipment_id, $date, $excludeId);
$stmt->execute();
$counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$fullSlots = [];
foreach ($counts as $c) {
    if ($c['loaned'] >= $equipment['total_units']) {
        $fullSlots[] = $c['time_slot'];
    }
}

echo json_encode(['full_slots' => $fullSlots]);
