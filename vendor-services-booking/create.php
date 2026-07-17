<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$error = '';
$selectedVendor = (int)($_GET['vendor_id'] ?? 0);

$quantity = (int)($_POST['quantity'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id    = (int)$_POST['vendor_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot    = trim($_POST['time_slot']);
    $purpose      = trim($_POST['purpose']);
    $uid          = current_user_id();
    $selectedVendor = $vendor_id;

    $stmt = $conn->prepare('SELECT price_per_unit FROM vendors WHERE id = ?');
    $stmt->bind_param('i', $vendor_id);
    $stmt->execute();
    $priceLookup = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($booking_date === '' || $time_slot === '' || $purpose === '' || !$priceLookup) {
        $error = 'All fields are required.';
    } elseif ($quantity < 1) {
        $error = 'Quantity must be at least 1.';
    } elseif ($booking_date < date('Y-m-d')) {
        $error = 'Booking date cannot be in the past.';
    } elseif (is_slot_in_past($booking_date, $time_slot)) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
    } else {
        $conn->begin_transaction();

        // Lock the vendor row so two concurrent bookings for the same
        // vendor/date/slot can't both squeeze past the capacity check.
        $stmt = $conn->prepare('SELECT capacity, price_per_unit FROM vendors WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $vendor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare('SELECT COUNT(*) AS booked FROM bookings WHERE vendor_id = ? AND booking_date = ? AND time_slot = ?');
        $stmt->bind_param('iss', $vendor_id, $booking_date, $time_slot);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_assoc()['booked'];
        $stmt->close();

        if (!$vendor) {
            $error = 'Please choose a valid vendor.';
            $conn->rollback();
        } elseif ($booked >= $vendor['capacity']) {
            $error = 'That slot is fully booked for this vendor. Please choose another date or time.';
            $conn->rollback();
        } else {
            $estimated_total = $vendor['price_per_unit'] * $quantity;
            $stmt = $conn->prepare('INSERT INTO bookings (user_id, vendor_id, booking_date, time_slot, purpose, quantity, estimated_total) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iisssid', $uid, $vendor_id, $booking_date, $time_slot, $purpose, $quantity, $estimated_total);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            header('Location: index.php');
            exit;
        }
    }
}

$vendors = $conn->query('SELECT * FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'New Vendor Booking';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Book a Vendor Slot</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" id="booking-form">
<label>Vendor
<select name="vendor_id" id="vendor-select" required>
<?php foreach ($vendors as $v): ?>
<option value="<?= (int)$v['id'] ?>" data-price="<?= htmlspecialchars($v['price_per_unit']) ?>" data-unit="<?= htmlspecialchars($v['unit_label']) ?>" <?= $v['id'] == $selectedVendor ? 'selected' : '' ?>><?= htmlspecialchars($v['vendor_name']) ?> (<?= htmlspecialchars($v['category']) ?>)</option>
<?php endforeach; ?>
</select>
</label>
<label>Booking Date <input type="date" name="booking_date" id="booking-date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d')) ?>" required></label>
<label>Time Slot
<select name="time_slot" id="time-slot-select" required>
<option value="">-- Select a time slot --</option>
<?php foreach (campus_time_slots() as $slot): ?>
<option value="<?= htmlspecialchars($slot) ?>"><?= htmlspecialchars($slot) ?></option>
<?php endforeach; ?>
</select>
</label>
<p class="form-hint" id="slot-availability-hint"></p>
<label>Purpose <input type="text" name="purpose" placeholder="e.g. Print and bind assignment report" required></label>
<label>Quantity (<span id="unit-label">item</span>s) <input type="number" name="quantity" id="quantity-input" min="1" value="<?= $quantity > 0 ? (int)$quantity : 1 ?>" required></label>
<p class="price-tag">Estimated total: RM<span id="estimated-total">0.00</span></p>
<p class="form-hint">This is an estimate to help you plan - the vendor confirms the exact price when you collect/drop off your item(s). There's no online payment.</p>
<button type="submit">Book Now</button>
</form>
<script>
(function () {
    var select = document.getElementById('vendor-select');
    var qtyInput = document.getElementById('quantity-input');
    var unitLabel = document.getElementById('unit-label');
    var totalEl = document.getElementById('estimated-total');

    function update() {
        var opt = select.options[select.selectedIndex];
        var price = parseFloat(opt.dataset.price) || 0;
        var qty = parseInt(qtyInput.value, 10) || 0;
        unitLabel.textContent = opt.dataset.unit || 'item';
        totalEl.textContent = (price * qty).toFixed(2);
    }

    select.addEventListener('change', update);
    qtyInput.addEventListener('input', update);
    update();
})();

(function () {
    var vendorSelect = document.getElementById('vendor-select');
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
        var vendorId = vendorSelect.value;
        var date = dateInput.value;

        resetOptions();

        if (date === today) {
            Array.prototype.forEach.call(slotSelect.options, function (opt) {
                if (opt.value && slotStartMinutes(opt.value) < nowMinutes) {
                    markDisabled(opt, 'Past');
                }
            });
        }

        if (!vendorId || !date) { return; }

        fetch('slot_availability.php?vendor_id=' + encodeURIComponent(vendorId) + '&date=' + encodeURIComponent(date))
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
            .catch(function () { /* availability check is a convenience, not required to book */ });
    }

    vendorSelect.addEventListener('change', refreshSlots);
    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
