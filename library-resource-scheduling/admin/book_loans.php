<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$loans = $conn->query('
    SELECT bl.id, bk.title, bk.author, bl.checkout_date, bl.due_date, bl.returned_at, bl.fine_paid_at, u.name AS user_name, u.email AS user_email
    FROM book_loans bl
    JOIN books bk ON bk.id = bl.book_id
    JOIN users u ON u.id = bl.user_id
    ORDER BY bl.returned_at IS NOT NULL, bl.due_date
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Book Loans';
require 'partials/header.php';
?>
<h1>All Book Loans</h1>
<?php if (empty($loans)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128218;</div>
<p>No book loans yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>Book</th><th>Author</th><th>Checked Out</th><th>Due Back</th><th>Status</th><th>Fine</th><th>Borrower</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($loans as $l): ?>
<?php
$isReturned = $l['returned_at'] !== null;
$isOverdue = !$isReturned && $l['due_date'] < date('Y-m-d');
$fine = book_fine_amount($l['due_date'], $l['returned_at']);
?>
<tr>
<td><?= htmlspecialchars($l['title']) ?></td>
<td><?= htmlspecialchars($l['author']) ?></td>
<td><?= htmlspecialchars($l['checkout_date']) ?></td>
<td><?= htmlspecialchars($l['due_date']) ?></td>
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
<form action="book_loan_return.php" method="post" style="display:inline" onsubmit="return confirm('Mark this book as returned?');">
<input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit" class="btn-small btn-danger">Mark Returned</button>
</form>
<?php endif; ?>
<?php if ($fine > 0 && !$l['fine_paid_at']): ?>
<form action="book_fine_paid.php" method="post" style="display:inline" onsubmit="return confirm('Mark this RM<?= number_format($fine, 2) ?> fine as paid?');">
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
