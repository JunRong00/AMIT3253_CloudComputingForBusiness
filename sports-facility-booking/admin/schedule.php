<?php
require '../config.php';
require '../auth.php';
require_admin();

$facilities = $conn->query('SELECT * FROM facilities ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$selectedFacilityId = (int)($_GET['facility_id'] ?? ($facilities[0]['id'] ?? 0));
$selectedDate = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare('SELECT id, name FROM courts WHERE facility_id = ? ORDER BY name');
$stmt->bind_param('i', $selectedFacilityId);
$stmt->execute();
$courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$timeSlots = $conn->query('SELECT * FROM time_slots ORDER BY sort_order')->fetch_all(MYSQLI_ASSOC);

$courtIds = array_column($courts, 'id');
$bookedSlots = []; // [court_id][time_slot_id] = ['name' => ..., 'email' => ...]
$closedSlots = []; // [court_id][time_slot_id] = reason
$wholeDayClosed = []; // [court_id] = reason

if ($courtIds !== []) {
    $placeholders = implode(',', array_fill(0, count($courtIds), '?'));
    $types = str_repeat('i', count($courtIds)) . 's';
    $params = array_merge($courtIds, [$selectedDate]);

    $stmt = $conn->prepare("
        SELECT b.court_id, b.time_slot_id, u.name, u.email
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        WHERE b.court_id IN ($placeholders) AND b.booking_date = ?
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['court_id']][$row['time_slot_id']] = ['name' => $row['name'], 'email' => $row['email']];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT court_id, time_slot_id, reason FROM closures WHERE court_id IN ($placeholders) AND closure_date = ?");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if ($row['time_slot_id'] === null) {
            $wholeDayClosed[$row['court_id']] = $row['reason'];
        } else {
            $closedSlots[$row['court_id']][$row['time_slot_id']] = $row['reason'];
        }
    }
    $stmt->close();
}

$pageTitle = 'Facility Schedule';
require 'partials/header.php';
?>
<h1>Facility Schedule</h1>
<p>See every court's bookings and closures at a glance, including who booked each slot.</p>

<form method="get" class="filter-bar">
<label>Facility
<select name="facility_id" onchange="this.form.submit()">
<?php foreach ($facilities as $f): ?>
<option value="<?= (int)$f['id'] ?>" <?= $f['id'] == $selectedFacilityId ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()"></label>
<noscript><button type="submit">View</button></noscript>
</form>

<?php if (empty($courts)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128269;</div>
<p>This facility has no courts set up yet.</p>
</div>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="schedule-table">
<tr>
<th>Time Slot</th>
<?php foreach ($courts as $court): ?>
<th class="schedule-court-th"><?= htmlspecialchars($court['name']) ?></th>
<?php endforeach; ?>
</tr>
<?php foreach ($timeSlots as $t): ?>
<tr>
<td><?= htmlspecialchars($t['label']) ?></td>
<?php foreach ($courts as $court): ?>
<?php
$courtId = $court['id'];
$slotId = $t['id'];
$booking = $bookedSlots[$courtId][$slotId] ?? null;
?>
<td class="schedule-court-td">
<div class="schedule-cell">
<?php if (isset($wholeDayClosed[$courtId]) || isset($closedSlots[$courtId][$slotId])): ?>
<span class="badge badge-critical">&#10005; Closed</span>
<span class="stat-label"><?= htmlspecialchars($wholeDayClosed[$courtId] ?? $closedSlots[$courtId][$slotId]) ?></span>
<?php elseif ($booking): ?>
<span class="badge badge-neutral">&#9679; Booked</span>
<span class="stat-label"><?= htmlspecialchars($booking['name']) ?></span>
<span class="stat-label"><?= htmlspecialchars($booking['email']) ?></span>
<?php else: ?>
<span class="badge badge-good">&#10003; Available</span>
<?php endif; ?>
</div>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
