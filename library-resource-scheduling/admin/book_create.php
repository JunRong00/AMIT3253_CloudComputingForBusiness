<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$error = '';
$uploadDir = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $author       = trim($_POST['author'] ?? '');
    $isbn         = trim($_POST['isbn'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $total_copies = (int)($_POST['total_copies'] ?? 0);

    [$image_url, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'book');

    if ($title === '' || $author === '' || $category === '' || $total_copies < 1) {
        $error = 'Title, author and category are required, and total copies must be at least 1.';
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        $stmt = $conn->prepare('INSERT INTO books (title, author, isbn, category, total_copies, image_url) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssis', $title, $author, $isbn, $category, $total_copies, $image_url);
        $stmt->execute();
        $stmt->close();
        header('Location: books.php');
        exit;
    }
}

$pageTitle = 'Add Book';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Add Book</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<label>Title <input type="text" name="title" required></label>
<label>Author <input type="text" name="author" required></label>
<label>ISBN <input type="text" name="isbn"></label>
<label>Category <input type="text" name="category" placeholder="e.g. Computer Science, Accountancy" required></label>
<label>Total Copies <input type="number" name="total_copies" min="1" required></label>
<label>Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Add Book</button>
</form>
<p><a class="btn btn-secondary btn-small" href="books.php">Back to books</a></p>
</div>
<?php require 'partials/footer.php'; ?>
