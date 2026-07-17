<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$loans = $conn->query('
    SELECT l.id, e.equipment_name, l.loan_date, l.time_slot, l.quantity, l.purpose, l.returned_at, l.fine_paid_at, u.name AS user_name, u.email AS user_email
    FROM equipment_loans l
    JOIN equipment e ON e.id = l.equipment_id
    JOIN users u ON u.id = l.user_id
    ORDER BY l.returned_at IS NOT NULL, l.loan_date DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Equipment Loans';
require 'partials/header.php';
?>
<h1>All Equipment Loans</h1>
<?php if (empty($loans)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128187;</div>
<p>No equipment loans yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Equipment</th><th>Date</th><th>Time Slot</th><th>Qty</th><th>Purpose</th><th>Status</th><th>Fine</th><th>Loaned By</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($loans as $l): ?>
<?php
$isReturned = $l['returned_at'] !== null;
$fine = equipment_fine_amount($l['loan_date'], $l['time_slot'], $l['returned_at']);
$isOverdue = !$isReturned && $fine > 0;
?>
<tr>
<td><?= htmlspecialchars($l['equipment_name']) ?></td>
<td><?= htmlspecialchars($l['loan_date']) ?></td>
<td><?= htmlspecialchars($l['time_slot']) ?></td>
<td><?= (int)$l['quantity'] ?></td>
<td><?= htmlspecialchars($l['purpose']) ?></td>
<td>
<?php if ($isReturned): ?>
<span class="badge badge-good">Returned</span>
<?php elseif ($isOverdue): ?>
<span class="badge badge-critical">Overdue</span>
<?php else: ?>
<span class="badge badge-neutral">On Loan</span>
<?php endif; ?>
</td>
<td>
<?php if ($fine <= 0): ?>
&mdash;
<?php elseif ($l['fine_paid_at']): ?>
<span class="badge badge-good">Paid</span>
<?php else: ?>
RM<?= number_format($fine, 2) ?>
<?php endif; ?>
</td>
<td><?= htmlspecialchars($l['user_name']) ?></td>
<td><?= htmlspecialchars($l['user_email']) ?></td>
<td>
<?php if (!$isReturned): ?>
<form action="loan_cancel.php" method="post" style="display:inline" onsubmit="return confirm('Cancel this loan?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn-small btn-danger">Cancel</button>
</form>
<form action="loan_return.php" method="post" style="display:inline" onsubmit="return confirm('Mark this equipment as returned?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn-small btn-danger">Mark Returned</button>
</form>
<?php endif; ?>
<?php if ($fine > 0 && !$l['fine_paid_at']): ?>
<form action="loan_fine_paid.php" method="post" style="display:inline" onsubmit="return confirm('Mark this RM<?= number_format($fine, 2) ?> fine as paid?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn btn-secondary btn-small">Mark Fine Paid</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
