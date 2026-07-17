<?php
require '../config.php';
require '../auth.php';
require '../helpers.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$uploadDir = __DIR__ . '/../uploads';

$stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    die('Book not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $author       = trim($_POST['author'] ?? '');
    $isbn         = trim($_POST['isbn'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $total_copies = (int)($_POST['total_copies'] ?? 0);

    [$newImageUrl, $uploadError] = handle_image_upload($_FILES['image'] ?? null, $uploadDir, 'book');
    $image_url = $newImageUrl ?? $book['image_url'];

    if ($title === '' || $author === '' || $category === '' || $total_copies < 1) {
        $error = 'Title, author and category are required, and total copies must be at least 1.';
        $book = array_merge($book, compact('title', 'author', 'isbn', 'category', 'total_copies', 'image_url'));
    } elseif ($uploadError) {
        $error = $uploadError;
    } else {
        if ($newImageUrl) {
            delete_image_file($book['image_url'], $uploadDir);
        }

        $stmt = $conn->prepare('UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, image_url=? WHERE id=?');
        $stmt->bind_param('ssssisi', $title, $author, $isbn, $category, $total_copies, $image_url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: books.php');
        exit;
    }
}

$pageTitle = 'Edit Book';
require 'partials/header.php';
?>
<div class="form-card">
<h1>Edit Book</h1>
<?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
<label>Title <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required></label>
<label>Author <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required></label>
<label>ISBN <input type="text" name="isbn" value="<?= htmlspecialchars($book['isbn'] ?? '') ?>"></label>
<label>Category <input type="text" name="category" value="<?= htmlspecialchars($book['category']) ?>" required></label>
<label>Total Copies <input type="number" name="total_copies" min="1" value="<?= (int)$book['total_copies'] ?>" required></label>
<label>Current Photo
<img class="table-thumb" style="width:96px;height:96px;" src="<?= htmlspecialchars(entity_image_url($book)) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
</label>
<label>Replace Photo <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label>
<button type="submit">Update Book</button>
</form>
<p><a class="btn btn-secondary btn-small" href="books.php">Back to books</a></p>
</div>
<?php require 'partials/footer.php'; ?>
