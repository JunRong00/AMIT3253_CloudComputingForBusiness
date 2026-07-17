<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$error = '';
$selectedRoom = (int)($_GET['room_id'] ?? 0);
$selectedDate = $_GET['booking_date'] ?? date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id      = (int)$_POST['room_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot    = trim($_POST['time_slot']);
    $purpose      = trim($_POST['purpose']);
    $uid          = current_user_id();
    $selectedRoom = $room_id;
    $selectedDate = $booking_date;

    if ($booking_date === '' || $time_slot === '' || $purpose === '') {
        $error = 'All fields are required.';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'Booking date cannot be in the past.';
    } elseif (is_slot_in_past($booking_date, $time_slot)) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
    } else {
        $stmt = $conn->prepare('INSERT INTO bookings (user_id, room_id, booking_date, time_slot, purpose) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iisss', $uid, $room_id, $booking_date, $time_slot, $purpose);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php');
            exit;
        }
        $error = ($conn->errno === 1062)
            ? 'That room has just been booked for this date and time slot by someone else. Please choose another.'
            : 'Could not save the booking. Please try again.';
        $stmt->close();
    }
}

$rooms = $conn->query('SELECT * FROM rooms ORDER BY room_name');

$pageTitle = 'New Room Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>New Discussion Room Booking</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<label>Room
<select name="room_id" id="room-select" required>
<?php while ($r = $rooms->fetch_assoc()): ?>
<option value="<?= (int)$r['id'] ?>" <?= $r['id'] == $selectedRoom ? 'selected' : '' ?>><?= htmlspecialchars($r['room_name']) ?></option>
<?php endwhile; ?>
</select>
</label>
<label>Booking Date <input type="date" name="booking_date" id="booking-date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Time Slot
<select name="time_slot" id="time-slot-select" required>
<option value="">-- Select a time slot --</option>
<?php foreach (campus_time_slots() as $slot): ?>
<option value="<?= htmlspecialchars($slot) ?>"><?= htmlspecialchars($slot) ?></option>
<?php endforeach; ?>
</select>
</label>
<p class="form-hint" id="slot-availability-hint"></p>
<label>Purpose <input type="text" name="purpose" placeholder="e.g. Group assignment discussion" required></label>
<button type="submit">Book Now</button>
</form>
<script>
(function () {
    var roomSelect = document.getElementById('room-select');
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

        fetch('slot_availability.php?room_id=' + encodeURIComponent(roomId) + '&booking_date=' + encodeURIComponent(date))
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
            .catch(function () { /* availability check is a convenience, not required to book */ });
    }

    roomSelect.addEventListener('change', refreshSlots);
    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
