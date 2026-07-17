<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user_id();
$error = '';

$stmt = $conn->prepare('
    SELECT b.*, v.vendor_name, v.price_per_unit, v.unit_label, v.capacity
    FROM bookings b
    JOIN vendors v ON v.id = b.vendor_id
    WHERE b.id = ? AND b.user_id = ?
');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die('Booking not found or you do not have permission to edit it.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = $_POST['booking_date'];
    $time_slot    = trim($_POST['time_slot']);
    $purpose      = trim($_POST['purpose']);
    $quantity     = (int)($_POST['quantity'] ?? 1);

    if ($booking_date === '' || $time_slot === '' || $purpose === '') {
        $error = 'All fields are required.';
    } elseif ($quantity < 1) {
        $error = 'Quantity must be at least 1.';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'Booking date cannot be in the past.';
    } elseif (is_slot_in_past($booking_date, $time_slot)) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
    } else {
        $conn->begin_transaction();

        $stmt = $conn->prepare('SELECT capacity FROM vendors WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $booking['vendor_id']);
        $stmt->execute();
        $capacity = $stmt->get_result()->fetch_assoc()['capacity'];
        $stmt->close();

        $stmt = $conn->prepare('SELECT COUNT(*) AS booked FROM bookings WHERE vendor_id = ? AND booking_date = ? AND time_slot = ? AND id != ?');
        $stmt->bind_param('issi', $booking['vendor_id'], $booking_date, $time_slot, $id);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_assoc()['booked'];
        $stmt->close();

        if ($booked >= $capacity) {
            $error = 'That slot is fully booked for this vendor. Please choose another date or time.';
            $conn->rollback();
            $booking = array_merge($booking, compact('booking_date', 'time_slot', 'purpose', 'quantity'));
        } else {
            $estimated_total = $booking['price_per_unit'] * $quantity;
            $stmt = $conn->prepare('UPDATE bookings SET booking_date=?, time_slot=?, purpose=?, quantity=?, estimated_total=? WHERE id=? AND user_id=?');
            $stmt->bind_param('sssidii', $booking_date, $time_slot, $purpose, $quantity, $estimated_total, $id, $uid);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Edit Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Booking</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$booking['id'] ?>">
<label>Vendor <input type="text" value="<?= htmlspecialchars($booking['vendor_name']) ?>" disabled></label>
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
<label>Quantity (<?= htmlspecialchars($booking['unit_label']) ?>s) <input type="number" name="quantity" id="quantity-input" min="1" value="<?= (int)$booking['quantity'] ?>" required></label>
<p class="price-tag">Estimated total: RM<span id="estimated-total">0.00</span></p>
<button type="submit">Update Booking</button>
</form>
<script>
(function () {
    var price = <?= (float)$booking['price_per_unit'] ?>;
    var qtyInput = document.getElementById('quantity-input');
    var totalEl = document.getElementById('estimated-total');
    function update() {
        totalEl.textContent = (price * (parseInt(qtyInput.value, 10) || 0)).toFixed(2);
    }
    qtyInput.addEventListener('input', update);
    update();
})();

(function () {
    var vendorId = <?= (int)$booking['vendor_id'] ?>;
    var excludeBookingId = <?= (int)$booking['id'] ?>;
    var dateInput = document.getElementById('booking-date');
    var slotSelect = document.getElementById('time-slot-select');
    var hint = document.getElementById('slot-availability-hint');
    var today = '<?= date('Y-m-d') ?>';
    var nowMinutes = <?= (int)date('H') * 60 + (int)date('i') ?>;

    function slotStartMinutes(optionValue) {
        var start = optionValue.split(' - ')[0];
        var parts = start.split(':');
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
        var date = dateInput.value;

        resetOptions();

        if (date === today) {
            Array.prototype.forEach.call(slotSelect.options, function (opt) {
                if (opt.value && slotStartMinutes(opt.value) < nowMinutes) {
                    markDisabled(opt, 'Past');
                }
            });
        }

        if (!date) { return; }

        fetch('slot_availability.php?vendor_id=' + vendorId + '&date=' + encodeURIComponent(date) + '&exclude_booking_id=' + excludeBookingId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var fullSlots = data.full_slots || [];
                Array.prototype.forEach.call(slotSelect.options, function (opt) {
                    if (fullSlots.indexOf(opt.value) !== -1 && !opt.disabled) {
                        markDisabled(opt, 'Full');
                    }
                });
                hint.textContent = 'Greyed-out slots are either already fully booked or already passed today.';
            })
            .catch(function () { /* availability check is a convenience, not required to save */ });
    }

    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
