<?php
require 'config.php';
require 'auth.php';
require 'helpers.php';
require_login();

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('SELECT * FROM equipment_loans WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$loan) {
    die('Loan not found or you do not have permission to edit it.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = (int)$_POST['equipment_id'];
    $loan_date    = $_POST['loan_date'];
    $time_slot    = trim($_POST['time_slot']);
    $quantity     = (int)$_POST['quantity'];
    $purpose      = trim($_POST['purpose']);

    if ($loan_date === '' || $time_slot === '' || $quantity < 1 || $purpose === '') {
        $error = 'All fields are required, and quantity must be at least 1.';
        $loan = array_merge($loan, compact('equipment_id', 'loan_date', 'time_slot', 'quantity', 'purpose'));
    } elseif ($loan_date < date('Y-m-d')) {
        $error = 'Loan date cannot be in the past.';
        $loan = array_merge($loan, compact('equipment_id', 'loan_date', 'time_slot', 'quantity', 'purpose'));
    } elseif (is_slot_in_past($loan_date, $time_slot)) {
        $error = 'That time slot has already passed today. Please choose a later time slot.';
        $loan = array_merge($loan, compact('equipment_id', 'loan_date', 'time_slot', 'quantity', 'purpose'));
    } else {
        $conn->begin_transaction();

        $stmt = $conn->prepare('SELECT total_units FROM equipment WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $equipment_id);
        $stmt->execute();
        $equipmentRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$equipmentRow) {
            $error = 'Equipment not found.';
            $conn->rollback();
        } else {
            $stmt = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) AS loaned FROM equipment_loans WHERE equipment_id = ? AND loan_date = ? AND time_slot = ? AND id != ?');
            $stmt->bind_param('issi', $equipment_id, $loan_date, $time_slot, $id);
            $stmt->execute();
            $loaned = (int)$stmt->get_result()->fetch_assoc()['loaned'];
            $stmt->close();

            if ($loaned + $quantity > $equipmentRow['total_units']) {
                $available = $equipmentRow['total_units'] - $loaned;
                $error = $available > 0
                    ? "Only $available unit(s) of this equipment are available for that date and time slot."
                    : 'All units of this equipment are already loaned out for that date and time slot.';
                $loan = array_merge($loan, compact('equipment_id', 'loan_date', 'time_slot', 'quantity', 'purpose'));
                $conn->rollback();
            } else {
                $stmt = $conn->prepare('UPDATE equipment_loans SET equipment_id=?, loan_date=?, time_slot=?, quantity=?, purpose=? WHERE id=? AND user_id=?');
                $stmt->bind_param('issisii', $equipment_id, $loan_date, $time_slot, $quantity, $purpose, $id, $uid);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                header('Location: index.php');
                exit;
            }
        }
    }
}

$equipment = $conn->query('SELECT * FROM equipment ORDER BY category, equipment_name');

$pageTitle = 'Edit Loan';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Equipment Loan</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<input type="hidden" name="id" value="<?= (int)$loan['id'] ?>">
<label>Equipment
<select name="equipment_id" id="equipment-select" required>
<?php while ($e = $equipment->fetch_assoc()): ?>
<option value="<?= (int)$e['id'] ?>" <?= $e['id'] == $loan['equipment_id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['equipment_name']) ?> (<?= (int)$e['total_units'] ?> units)</option>
<?php endwhile; ?>
</select>
</label>
<label>Loan Date <input type="date" name="loan_date" id="loan-date" value="<?= htmlspecialchars($loan['loan_date']) ?>" min="<?= date('Y-m-d') ?>" required></label>
<label>Time Slot
<select name="time_slot" id="time-slot-select" required>
<option value="">-- Select a time slot --</option>
<?php foreach (campus_time_slots() as $slot): ?>
<option value="<?= htmlspecialchars($slot) ?>" <?= $loan['time_slot'] === $slot ? 'selected' : '' ?>><?= htmlspecialchars($slot) ?></option>
<?php endforeach; ?>
</select>
</label>
<p class="form-hint" id="slot-availability-hint"></p>
<label>Quantity <input type="number" name="quantity" min="1" value="<?= (int)$loan['quantity'] ?>" required></label>
<label>Purpose <input type="text" name="purpose" value="<?= htmlspecialchars($loan['purpose']) ?>" required></label>
<button type="submit">Update Loan</button>
</form>
<script>
(function () {
    var equipmentSelect = document.getElementById('equipment-select');
    var excludeLoanId = <?= (int)$loan['id'] ?>;
    var dateInput = document.getElementById('loan-date');
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
        var equipmentId = equipmentSelect.value;
        var date = dateInput.value;

        resetOptions();

        if (date === today) {
            Array.prototype.forEach.call(slotSelect.options, function (opt) {
                if (opt.value && slotStartMinutes(opt.value) < nowMinutes) {
                    markDisabled(opt, 'Past');
                }
            });
        }

        if (!equipmentId || !date) { return; }

        fetch('equipment_availability.php?equipment_id=' + encodeURIComponent(equipmentId) + '&loan_date=' + encodeURIComponent(date) + '&exclude_loan_id=' + excludeLoanId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var fullSlots = data.full_slots || [];
                Array.prototype.forEach.call(slotSelect.options, function (opt) {
                    if (fullSlots.indexOf(opt.value) !== -1 && !opt.disabled) {
                        markDisabled(opt, 'Fully Loaned');
                    }
                });
                hint.textContent = 'Greyed-out slots are either fully loaned out or already passed today.';
            })
            .catch(function () { /* availability check is a convenience, not required to save */ });
    }

    equipmentSelect.addEventListener('change', refreshSlots);
    dateInput.addEventListener('change', refreshSlots);
    refreshSlots();
})();
</script>
<p><a class="btn btn-secondary btn-small" href="index.php">Back to home</a></p>
</div>
<?php require 'partials/footer.php'; ?>
