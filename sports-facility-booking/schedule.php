<?php
require 'config.php';
require 'auth.php';

$facilities = $conn->query('SELECT * FROM facilities ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$selectedFacilityId = (int)($_GET['facility_id'] ?? ($facilities[0]['id'] ?? 0));
$selectedDate = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare('SELECT id, name FROM courts WHERE facility_id = ? ORDER BY name');
$stmt->bind_param('i', $selectedFacilityId);
$stmt->execute();
$courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$timeSlots = $conn->query('SELECT * FROM time_slots ORDER BY sort_order')->fetch_all(MYSQLI_ASSOC);

$uid = current_user_id();

$courtIds = array_column($courts, 'id');
$bookedSlots = []; // [court_id][time_slot_id] = user_id
$closedSlots = []; // [court_id][time_slot_id] = reason
$wholeDayClosed = []; // [court_id] = reason

if ($courtIds !== []) {
    $placeholders = implode(',', array_fill(0, count($courtIds), '?'));
    $types = str_repeat('i', count($courtIds)) . 's';
    $params = array_merge($courtIds, [$selectedDate]);

    $stmt = $conn->prepare("SELECT court_id, time_slot_id, user_id FROM bookings WHERE court_id IN ($placeholders) AND booking_date = ?");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['court_id']][$row['time_slot_id']] = $row['user_id'];
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
<div class="page-header">
<h1>Facility Schedule</h1>
<p>Check available, booked and closed courts before booking.</p>
</div>

<form method="get" class="filter-bar">
<label>Facility
<select name="facility_id" onchange="this.form.submit()">
<?php foreach ($facilities as $f): ?>
<option value="<?= (int)$f['id'] ?>" <?= $f['id'] == $selectedFacilityId ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Date <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" onchange="this.form.submit()"></label>
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

if (isset($wholeDayClosed[$courtId]) || isset($closedSlots[$courtId][$slotId])) {
    $status = 'Closed';
    $reason = $wholeDayClosed[$courtId] ?? $closedSlots[$courtId][$slotId];
    $badgeClass = 'badge-critical';
    $icon = '&#10005;';
} elseif (isset($bookedSlots[$courtId][$slotId])) {
    $isMine = $uid && $bookedSlots[$courtId][$slotId] == $uid;
    $status = $isMine ? 'Booked by you' : 'Booked';
    $reason = null;
    $badgeClass = $isMine ? 'badge-accent' : 'badge-neutral';
    $icon = $isMine ? '&#10003;' : '&#9679;';
} else {
    $status = 'Available';
    $reason = null;
    $badgeClass = 'badge-good';
    $icon = '&#10003;';
}
?>
<td class="schedule-court-td">
<div class="schedule-cell">
<span class="badge <?= $badgeClass ?>"><?= $icon ?> <?= htmlspecialchars($status) ?></span>
<?php if ($reason): ?><span class="stat-label"><?= htmlspecialchars($reason) ?></span><?php endif; ?>
<?php if ($status === 'Available' && $selectedDate >= date('Y-m-d')): ?>
<?php if ($uid): ?>
<a class="btn btn-small" href="create.php?court_id=<?= (int)$courtId ?>&booking_date=<?= htmlspecialchars($selectedDate) ?>&time_slot_id=<?= (int)$slotId ?>">Book</a>
<?php else: ?>
<a class="btn btn-small" href="login.php">Login to Book</a>
<?php endif; ?>
<?php endif; ?>
</div>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="card-actions" style="margin-top:20px;">
<a class="btn btn-secondary btn-small" href="index.php">Back to Home</a>
</div>
<?php require 'partials/footer.php'; ?>
