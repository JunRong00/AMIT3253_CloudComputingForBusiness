<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die('Booking not found or you do not have permission to edit it.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id      = (int)$_POST['room_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot    = trim($_POST['time_slot']);
    $purpose      = trim($_POST['purpose']);

    if ($booking_date === '' || $time_slot === '' || $purpose === '') {
        $error = 'All fields are required.';
        $booking = array_merge($booking, compact('room_id', 'booking_date', 'time_slot', 'purpose'));
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'Booking date cannot be in the past.';
        $booking = array_merge($booking, compact('room_id', 'booking_date', 'time_slot', 'purpose'));
    } elseif (is_slot_in_past($booking_date, $time_slot)) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
        $booking = array_merge($booking, compact('room_id', 'booking_date', 'time_slot', 'purpose'));
    } else {
        $stmt = $conn->prepare('UPDATE bookings SET room_id=?, booking_date=?, time_slot=?, purpose=? WHERE id=? AND user_id=?');
        $stmt->bind_param('isssii', $room_id, $booking_date, $time_slot, $purpose, $id, $uid);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php');
            exit;
        }
        $error = ($conn->errno === 1062)
            ? 'That room has just been booked for this date and time slot by someone else. Please choose another.'
            : 'Could not save the booking. Please try again.';
        $booking = array_merge($booking, compact('room_id', 'booking_date', 'time_slot', 'purpose'));
        $stmt->close();
    }
}

$rooms = $conn->query('SELECT * FROM rooms ORDER BY room_name');

$pageTitle = 'Edit Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Room Booking</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$booking['id'] ?>">
<label>Room
<select name="room_id" id="room-select" required>
<?php while ($r = $rooms->fetch_assoc()): ?>
<option value="<?= (int)$r['id'] ?>" <?= $r['id'] == $booking['room_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['room_name']) ?></option>
<?php endwhile; ?>
</select>
</label>
<label>Booking Date <input type="date" name="booking_date" id="booking-date" value="<?= htmlspecialchars($booking['booking_date']) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Time Slot
<select name="time_slot" id="time-slot-select" required>
<option value="">-- Select a time slot --</option>
<?php foreach (campus_time_slots() as $slot): ?>
<option value="<?= htmlspecialchars($slot) ?>" <?= $booking['time_slot'] === $slot ? 'selected' : '' ?>><?= htmlspecialchars($slot) ?></option>
<?php endforeach; ?>
</select>
</label>
<p class="form-hint" id="slot-availability-hint"></p>
<label>Purpose <input type="text" name="purpose" value="<?= htmlspecialchars($booking['purpose']) ?>" required></label>
<button type="submit">Update Booking</button>
</form>
<script>
(function () {
    var roomSelect = document.getElementById('room-select');
    var excludeBookingId = <?= (int)$booking['id'] ?>;
    var dateInput = document.getElementById('booking-date');
    var slotSelect = document.getElementById('time-slot-select');
    var hint = document.getElementById('slot-availability-hint');
    var today = '<?= date('Y-m-d') ?>';
    var nowMinutes = <?= (int)date('H') * 60 + (int)date('i') ?>;

    function slotStartMinutes(value) {
        var parts = value.split(' - ')[0].split(':');
        return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
    }

    function resetOptions() {
        Array.prototype.forEach.call(slotSelect.options, function (opt) {
            if (opt.dataset.originalText) {
                opt.textContent = opt.dataset.originalText;
            }
            opt.disabled = false;
        });
        hint.textContent = '';
    }

    function markDisabled(opt, label) {
        opt.dataset.originalText = opt.dataset.originalText || opt.textContent;
        opt.textContent = opt.dataset.originalText + ' (' + label + ')';
        opt.disabled = true;
        if (slotSelect.value === opt.value) {
            slotSelect.value = '';
        }
    }

    function refreshSlots() {
        var roomId = roomSelect.value;
        var date = dateInput.value;

        resetOptions();

        if (date === today) {
            Array.prototype.forEach.call(slotSelect.options, function (opt) {
                if (opt.value && slotStartMinutes(opt.value) < nowMinutes) {
                    markDisabled(opt, 'Past');
                }
            });
        }

        if (!roomId || !date) { return; }

        fetch('slot_availability.php?room_id=' + encodeURIComponent(roomId) + '&booking_date=' + encodeURIComponent(date) + '&exclude_booking_id=' + excludeBookingId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var fullSlots = data.full_slots || [];
                Array.prototype.forEach.call(slotSelect.options, function (opt) {
                    if (fullSlots.indexOf(opt.value) !== -1 && !opt.disabled) {
                        markDisabled(opt, 'Booked');
                    }
                });
                hint.textContent = 'Greyed-out slots are either already booked or already passed today.';
            })
            .catch(function () { /* availability check is a convenience, not required to save */ });
    }

    roomSelect.addEventListener('change', refreshSlots);
    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
