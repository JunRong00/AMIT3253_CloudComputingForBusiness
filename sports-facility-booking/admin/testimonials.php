<?php
require '../config.php';
require '../auth.php';
require_admin();

$testimonials = $conn->query('
    SELECT t.id, t.comment, t.rating, t.created_at, u.name AS user_name, u.email AS user_email, f.name AS facility_name
    FROM testimonials t
    JOIN users u ON u.id = t.user_id
    JOIN facilities f ON f.id = t.facility_id
    ORDER BY t.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Testimonials';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Testimonials</h1>
<p>Moderate comments left by users.</p>
</div>
<?php if (empty($testimonials)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128172;</div>
<p>No comments yet.</p>
</div>
<?php else: ?>
<table>
<tr><th>User</th><th>Facility</th><th>Rating</th><th>Comment</th><th>Date</th><th>Actions</th></tr>
<?php foreach ($testimonials as $t): ?>
<tr>
<td><?= htmlspecialchars($t['user_name']) ?><br><span class="stat-label"><?= htmlspecialchars($t['user_email']) ?></span></td>
<td><?= htmlspecialchars($t['facility_name']) ?></td>
<td><?= str_repeat('&#9733;', $t['rating']) ?></td>
<td><?= htmlspecialchars($t['comment']) ?></td>
<td><?= htmlspecialchars(date('d M Y', strtotime($t['created_at']))) ?></td>
<td>
<form action="testimonial_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this comment?');">
<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
