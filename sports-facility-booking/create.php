<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$error = '';
$selectedCourt    = (int)($_GET['court_id'] ?? 0);
$selectedFacility = (int)($_GET['facility_id'] ?? 0);
$selectedDate     = $_GET['booking_date'] ?? date('Y-m-d');
$selectedSlot     = (int)($_GET['time_slot_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $court_id     = (int)$_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot_id = (int)$_POST['time_slot_id'];
    $uid          = current_user_id();
    $selectedCourt = $court_id;
    $selectedDate  = $booking_date;
    $selectedSlot  = $time_slot_id;

    $stmt = $conn->prepare('SELECT label FROM time_slots WHERE id = ?');
    $stmt->bind_param('i', $time_slot_id);
    $stmt->execute();
    $slotLookup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking_date === '' || $time_slot_id < 1 || $court_id < 1 || !$slotLookup) {
        $error = 'Please choose a court, date and time slot.';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'You cannot book a date in the past.';
    } elseif (is_slot_in_past($booking_date, $slotLookup['label'])) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
    } else {
        $stmt = $conn->prepare('
            SELECT id FROM closures
            WHERE court_id = ? AND closure_date = ? AND (time_slot_id = ? OR time_slot_id IS NULL)
        ');
        $stmt->bind_param('isi', $court_id, $booking_date, $time_slot_id);
        $stmt->execute();
        $closed = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($closed) {
            $error = 'This court is closed for the selected date/time slot.';
        } else {
            $stmt = $conn->prepare('INSERT INTO bookings (user_id, court_id, time_slot_id, booking_date) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiis', $uid, $court_id, $time_slot_id, $booking_date);
            if ($stmt->execute()) {
                $newBookingId = $stmt->insert_id;
                $stmt->close();
                header('Location: confirmation.php?id=' . $newBookingId);
                exit;
            }
            $error = ($conn->errno === 1062)
                ? 'That court has just been booked for this time by someone else. Please choose another.'
                : 'Could not save the booking. Please try again.';
            $stmt->close();
        }
    }
}

$courts = $conn->query('
    SELECT co.id, co.name AS court_name, f.id AS facility_id, f.name AS facility_name
    FROM courts co
    JOIN facilities f ON f.id = co.facility_id
    ORDER BY f.name, co.name
')->fetch_all(MYSQLI_ASSOC);

// If a facility was passed but no specific court, default to its first court.
if (!$selectedCourt && $selectedFacility) {
    foreach ($courts as $c) {
        if ($c['facility_id'] == $selectedFacility) {
            $selectedCourt = $c['id'];
            break;
        }
    }
}

$timeSlots = $conn->query('SELECT * FROM time_slots ORDER BY sort_order')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'New Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>New Facility Booking</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" id="booking-form">
<label>Court
<select name="court_id" id="court-select" required>
<?php $currentFacility = null; ?>
<?php foreach ($courts as $c): ?>
<?php if ($c['facility_id'] !== $currentFacility): ?>
<?php if ($currentFacility !== null): ?></optgroup><?php endif; ?>
<optgroup label="<?= htmlspecialchars($c['facility_name']) ?>">
<?php $currentFacility = $c['facility_id']; ?>
<?php endif; ?>
<option value="<?= (int)$c['id'] ?>" <?= $c['id'] == $selectedCourt ? 'selected' : '' ?>><?= htmlspecialchars($c['court_name']) ?></option>
<?php endforeach; ?>
<?php if ($courts): ?></optgroup><?php endif; ?>
</select>
</label>
<label>Booking Date <input type="date" name="booking_date" id="booking-date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Time Slot
<select name="time_slot_id" id="time-slot-select" required>
<?php foreach ($timeSlots as $t): ?>
<option value="<?= (int)$t['id'] ?>" data-slot-label="<?= htmlspecialchars($t['label']) ?>" <?= $t['id'] == $selectedSlot ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
<?php endforeach; ?>
</select>
</label>
<p class="form-hint" id="slot-availability-hint"></p>
<button type="submit">Book Now</button>
</form>
<script>
(function () {
    var courtSelect = document.getElementById('court-select');
    var dateInput = document.getElementById('booking-date');
    var slotSelect = document.getElementById('time-slot-select');
    var hint = document.getElementById('slot-availability-hint');
    var today = '<?= date('Y-m-d') ?>';
    var nowMinutes = <?= (int)date('H') * 60 + (int)date('i') ?>;

    function slotStartMinutes(label) {
        var parts = label.split(' - ')[0].split(':');
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
        var courtId = courtSelect.value;
        var date = dateInput.value;

        resetOptions();

        if (date === today) {
            Array.prototype.forEach.call(slotSelect.options, function (opt) {
                if (opt.dataset.slotLabel && slotStartMinutes(opt.dataset.slotLabel) < nowMinutes) {
                    markDisabled(opt, 'Past');
                }
            });
        }

        if (!courtId || !date) { return; }

        fetch('slot_availability.php?court_id=' + encodeURIComponent(courtId) + '&booking_date=' + encodeURIComponent(date))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var unavailable = (data.unavailable_slot_ids || []).map(String);
                Array.prototype.forEach.call(slotSelect.options, function (opt) {
                    if (unavailable.indexOf(opt.value) !== -1 && !opt.disabled) {
                        markDisabled(opt, 'Unavailable');
                    }
                });
                hint.textContent = 'Greyed-out slots are either already booked, closed, or already passed today.';
            })
            .catch(function () { /* availability check is a convenience, not required to book */ });
    }

    courtSelect.addEventListener('change', refreshSlots);
    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<div class="card-actions">
<a class="btn btn-secondary btn-small" href="schedule.php<?= $selectedFacility ? '?facility_id=' . (int)$selectedFacility : '' ?>">View Schedule</a>
<a class="btn btn-secondary btn-small" href="index.php">Back to Home</a>
</div>
</div>
<?php require 'partials/footer.php'; ?>
