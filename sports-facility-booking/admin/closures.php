<?php
require '../config.php';
require '../auth.php';
require_admin();

$error = '';
$conflicts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $court_id     = (int)$_POST['court_id'];
    $closure_date = $_POST['closure_date'];
    $time_slot_id = $_POST['time_slot_id'] !== '' ? (int)$_POST['time_slot_id'] : null;
    $reason       = trim($_POST['reason']);
    $confirmed    = isset($_POST['confirm']);

    if ($closure_date === '' || $reason === '') {
        $error = 'Date and reason are required.';
    } else {
        if ($time_slot_id === null) {
            $stmt = $conn->prepare('
                SELECT b.id, b.user_id, u.name AS user_name, u.email AS user_email, t.label AS time_slot,
                       f.name AS facility_name, co.name AS court_name
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                JOIN time_slots t ON t.id = b.time_slot_id
                JOIN courts co ON co.id = b.court_id
                JOIN facilities f ON f.id = co.facility_id
                WHERE b.court_id = ? AND b.booking_date = ?
                ORDER BY t.sort_order
            ');
            $stmt->bind_param('is', $court_id, $closure_date);
        } else {
            $stmt = $conn->prepare('
                SELECT b.id, b.user_id, u.name AS user_name, u.email AS user_email, t.label AS time_slot,
                       f.name AS facility_name, co.name AS court_name
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                JOIN time_slots t ON t.id = b.time_slot_id
                JOIN courts co ON co.id = b.court_id
                JOIN facilities f ON f.id = co.facility_id
                WHERE b.court_id = ? AND b.booking_date = ? AND b.time_slot_id = ?
            ');
            $stmt->bind_param('isi', $court_id, $closure_date, $time_slot_id);
        }
        $stmt->execute();
        $conflicts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($conflicts) && !$confirmed) {
            // Fall through to render the confirmation screen below instead of saving.
        } else {
            if (!empty($conflicts)) {
                $conflictIds = array_column($conflicts, 'id');
                $placeholders = implode(',', array_fill(0, count($conflictIds), '?'));
                $types = str_repeat('i', count($conflictIds));
                $del = $conn->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
                $del->bind_param($types, ...$conflictIds);
                $del->execute();
                $del->close();

                $notify = $conn->prepare('INSERT INTO notifications (user_id, message) VALUES (?, ?)');
                foreach ($conflicts as $b) {
                    $message = sprintf(
                        'Your booking for %s — %s on %s (%s) was cancelled due to a closure: %s',
                        $b['facility_name'],
                        $b['court_name'],
                        $closure_date,
                        $b['time_slot'],
                        $reason
                    );
                    $notify->bind_param('is', $b['user_id'], $message);
                    $notify->execute();
                }
                $notify->close();
            }

            $stmt = $conn->prepare('INSERT INTO closures (court_id, closure_date, time_slot_id, reason) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isis', $court_id, $closure_date, $time_slot_id, $reason);
            $stmt->execute();
            $stmt->close();
            header('Location: closures.php');
            exit;
        }
    }
}

$courts = $conn->query('
    SELECT c.id, c.name AS court_name, f.name AS facility_name
    FROM courts c
    JOIN facilities f ON f.id = c.facility_id
    ORDER BY f.name, c.name
')->fetch_all(MYSQLI_ASSOC);
$timeSlots = $conn->query('SELECT * FROM time_slots ORDER BY sort_order');
$closures = $conn->query('
    SELECT cl.id, f.name AS facility_name, co.name AS court_name, cl.closure_date, t.label AS time_slot, cl.reason
    FROM closures cl
    JOIN courts co ON co.id = cl.court_id
    JOIN facilities f ON f.id = co.facility_id
    LEFT JOIN time_slots t ON t.id = cl.time_slot_id
    ORDER BY cl.closure_date DESC
')->fetch_all(MYSQLI_ASSOC);

$pendingClosure = null;
if (!empty($conflicts)) {
    $courtLabel = '';
    foreach ($courts as $c) {
        if ($c['id'] == $court_id) {
            $courtLabel = $c['facility_name'] . ' — ' . $c['court_name'];
            break;
        }
    }
    $pendingClosure = [
        'court_id'      => $court_id,
        'closure_date'  => $closure_date,
        'time_slot_id'  => $time_slot_id,
        'reason'        => $reason,
        'court_label'   => $courtLabel,
    ];
}

$pageTitle = 'Manage Closures';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Court Closures</h1>
<p>Mark a specific court closed for maintenance, for a time slot or the whole day.</p>
</div>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<?php if ($pendingClosure): ?>
<div class="form-card">
<h2>&#9888;&#65039; This Closure Conflicts With Existing Bookings</h2>
<p class="alert alert-error">
Closing <strong><?= htmlspecialchars($pendingClosure['court_label']) ?></strong> on
<strong><?= htmlspecialchars($pendingClosure['closure_date']) ?></strong>
<?= $pendingClosure['time_slot_id'] ? 'for the ' . htmlspecialchars($conflicts[0]['time_slot']) . ' slot' : '(whole day)' ?>
will cancel <?= count($conflicts) ?> existing booking<?= count($conflicts) === 1 ? '' : 's' ?> below.
This cannot be undone.
</p>
<table>
<tr><th>Time Slot</th><th>Booked By</th><th>Email</th></tr>
<?php foreach ($conflicts as $b): ?>
<tr>
<td><?= htmlspecialchars($b['time_slot']) ?></td>
<td><?= htmlspecialchars($b['user_name']) ?></td>
<td><?= htmlspecialchars($b['user_email']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<form method="post" style="margin-top:16px;">
<input type="hidden" name="court_id" value="<?= (int)$pendingClosure['court_id'] ?>">
<input type="hidden" name="closure_date" value="<?= htmlspecialchars($pendingClosure['closure_date']) ?>">
<input type="hidden" name="time_slot_id" value="<?= htmlspecialchars((string)$pendingClosure['time_slot_id']) ?>">
<input type="hidden" name="reason" value="<?= htmlspecialchars($pendingClosure['reason']) ?>">
<input type="hidden" name="confirm" value="1">
<div class="card-actions">
<button type="submit" class="btn-danger">Cancel Booking<?= count($conflicts) === 1 ? '' : 's' ?> &amp; Proceed</button>
<a class="btn btn-secondary" href="closures.php">Go Back</a>
</div>
</form>
</div>
<?php else: ?>
<div class="form-card">
<h2>Add Closure</h2>
<form method="post">
<label>Court
<select name="court_id" required>
<?php foreach ($courts as $c): ?>
<option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['facility_name']) ?> &mdash; <?= htmlspecialchars($c['court_name']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Date <input type="date" name="closure_date" required></label>
<label>Time Slot (leave blank to close the whole day)
<select name="time_slot_id">
<option value="">-- Whole Day --</option>
<?php while ($t = $timeSlots->fetch_assoc()): ?>
<option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['label']) ?></option>
<?php endwhile; ?>
</select>
</label>
<label>Reason <input type="text" name="reason" placeholder="e.g. Maintenance" required></label>
<button type="submit">Add Closure</button>
</form>
</div>
<?php endif; ?>

<h2>Existing Closures</h2>
<?php if (empty($closures)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#9989;</div>
<p>No closures — every court is open as scheduled.</p>
</div>
<?php else: ?>
<table>
<tr><th>Facility</th><th>Court</th><th>Date</th><th>Time Slot</th><th>Reason</th><th>Actions</th></tr>
<?php foreach ($closures as $c): ?>
<tr>
<td><?= htmlspecialchars($c['facility_name']) ?></td>
<td><?= htmlspecialchars($c['court_name']) ?></td>
<td><?= htmlspecialchars($c['closure_date']) ?></td>
<td><?= $c['time_slot'] ? htmlspecialchars($c['time_slot']) : 'Whole Day' ?></td>
<td><?= htmlspecialchars($c['reason']) ?></td>
<td>
<form action="closure_delete.php" method="post" style="display:inline" onsubmit="return confirm('Remove this closure?');">
<input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
<button type="submit" class="btn-small btn-secondary">Reopen</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
