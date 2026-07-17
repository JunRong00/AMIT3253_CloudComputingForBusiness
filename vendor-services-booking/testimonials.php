<?php
require 'config.php';
require 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $vendor_id = (int)$_POST['vendor_id'];
    $comment   = trim($_POST['comment']);
    $rating    = (int)$_POST['rating'];
    $uid       = current_user_id();

    if ($vendor_id < 1 || $comment === '' || $rating < 1 || $rating > 5) {
        $error = 'Please choose a vendor, write a comment, and pick a rating between 1 and 5.';
    } else {
        $stmt = $conn->prepare('INSERT INTO testimonials (user_id, vendor_id, comment, rating) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iisi', $uid, $vendor_id, $comment, $rating);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: testimonials.php');
            exit;
        }
        $error = 'Could not post your comment. Please try again.';
        $stmt->close();
    }
}

$vendors = $conn->query('SELECT id, vendor_name FROM vendors ORDER BY vendor_name')->fetch_all(MYSQLI_ASSOC);

$testimonials = $conn->query('
    SELECT t.id, t.comment, t.rating, t.created_at, t.user_id, u.name AS user_name, v.vendor_name
    FROM testimonials t
    JOIN users u ON u.id = t.user_id
    JOIN vendors v ON v.id = t.vendor_id
    ORDER BY t.created_at DESC
')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Testimonials';
require 'partials/header.php';
?>
<div class="page-header">
<h1>Testimonials</h1>
<p>What students are saying about on-campus vendor services.</p>
</div>

<?php if (current_user_id()): ?>
<div class="form-card" style="margin-bottom:24px; max-width:600px;">
<h2>Leave a Comment</h2>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
<label>Vendor
<select name="vendor_id" required>
<option value="">-- Select a vendor --</option>
<?php foreach ($vendors as $v): ?>
<option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['vendor_name']) ?></option>
<?php endforeach; ?>
</select>
</label>
<label>Rating
<select name="rating" required>
<option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; Excellent</option>
<option value="4">&#9733;&#9733;&#9733;&#9733;&#9734; Good</option>
<option value="3">&#9733;&#9733;&#9733;&#9734;&#9734; Okay</option>
<option value="2">&#9733;&#9733;&#9734;&#9734;&#9734; Poor</option>
<option value="1">&#9733;&#9734;&#9734;&#9734;&#9734; Bad</option>
</select>
</label>
<label>Comment <textarea name="comment" rows="3" required></textarea></label>
<button type="submit">Post Comment</button>
</form>
</div>
<?php else: ?>
<p><a href="login.php">Login</a> or <a href="register.php">register</a> to leave a comment.</p>
<?php endif; ?>

<?php if (empty($testimonials)): ?>
<div class="empty-state">
<div class="empty-state-icon">&#128172;</div>
<p>No comments yet. Be the first to share your experience.</p>
</div>
<?php else: ?>
<div class="testimonial-list">
<?php foreach ($testimonials as $t): ?>
<div class="testimonial-card">
<span class="badge badge-accent"><?= htmlspecialchars($t['vendor_name']) ?></span>
<div class="testimonial-stars"><?= str_repeat('&#9733;', $t['rating']) . str_repeat('&#9734;', 5 - $t['rating']) ?></div>
<p class="testimonial-comment"><?= nl2br(htmlspecialchars($t['comment'])) ?></p>
<div class="testimonial-meta">
<span><?= htmlspecialchars($t['user_name']) ?> &middot; <?= htmlspecialchars(date('d M Y', strtotime($t['created_at']))) ?></span>
<?php if (current_user_id() == $t['user_id']): ?>
<form action="testimonial_delete.php" method="post" style="display:inline" onsubmit="return confirm('Delete this comment?');">
<input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
<button type="submit" class="btn-small btn-danger">Delete</button>
</form>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'partials/footer.php'; ?>
