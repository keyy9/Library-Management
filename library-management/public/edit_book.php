<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: books.php');
    exit();
}

$error = '';
$success = '';
$book = null;
$authors = [];
$categories = [];

// Get book ID from URL
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header('Location: books.php');
    exit();
}

try {
    $db = getDB();
    
    // Get all authors and categories for dropdowns
    $authors = $db->query('SELECT id, name FROM authors ORDER BY name')->fetchAll();
    $categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

    // Get book details
    $stmt = $db->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        header('Location: books.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        $title = trim($_POST['title'] ?? '');
        $author_id = $_POST['author_id'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $isbn = trim($_POST['isbn'] ?? '');
        $published_year = $_POST['published_year'] ?? '';
        $publisher = trim($_POST['publisher'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cover_image = trim($_POST['cover_image'] ?? '');
        $total_copies = (int)($_POST['total_copies'] ?? 1);
        $available_copies = (int)($_POST['available_copies'] ?? 1);

        if (empty($title) || empty($author_id) || empty($category_id) || empty($isbn)) {
            $error = 'Please fill in all required fields.';
        } else {
            // Check if ISBN already exists (excluding current book)
            $stmt = $db->prepare('SELECT id FROM books WHERE isbn = ? AND id != ?');
            $stmt->execute([$isbn, $book_id]);
            if ($stmt->fetch()) {
                $error = 'A book with this ISBN already exists.';
            } else {
                // Update book
                $stmt = $db->prepare('UPDATE books SET 
                    title = ?, author_id = ?, category_id = ?, isbn = ?,
                    published_year = ?, publisher = ?, description = ?,
                    cover_image = ?, total_copies = ?, available_copies = ?
                    WHERE id = ?');
                $stmt->execute([
                    $title, $author_id, $category_id, $isbn,
                    $published_year, $publisher, $description,
                    $cover_image, $total_copies, $available_copies,
                    $book_id
                ]);

                $success = 'Book updated successfully.';
                header('Location: books.php?message=' . urlencode($success) . '&type=success');
                exit();
            }
        }
    }
} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Edit Book</h1>
            <a href="books.php" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to Books
            </a>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white rounded-lg shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo htmlspecialchars($book['title']); ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Author -->
                <div>
                    <label for="author_id" class="block text-sm font-medium text-gray-700 mb-1">Author *</label>
                    <select id="author_id" name="author_id" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Author</option>
                        <?php foreach ($authors as $author): ?>
                            <option value="<?php echo $author['id']; ?>"
                                    <?php echo $book['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($author['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select id="category_id" name="category_id" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo $book['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ISBN -->
                <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">ISBN *</label>
                    <input type="text" id="isbn" name="isbn" required
                           value="<?php echo htmlspecialchars($book['isbn']); ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           pattern="\d{10}|\d{13}" title="Please enter a valid 10 or 13 digit ISBN">
                </div>

                <!-- Published Year -->
                <div>
                    <label for="published_year" class="block text-sm font-medium text-gray-700 mb-1">Published Year</label>
                    <input type="number" id="published_year" name="published_year"
                           value="<?php echo htmlspecialchars($book['published_year']); ?>"
                           min="1800" max="<?php echo date('Y'); ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Publisher -->
                <div>
                    <label for="publisher" class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                    <input type="text" id="publisher" name="publisher"
                           value="<?php echo htmlspecialchars($book['publisher']); ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Total Copies -->
                <div>
                    <label for="total_copies" class="block text-sm font-medium text-gray-700 mb-1">Total Copies</label>
                    <input type="number" id="total_copies" name="total_copies"
                           value="<?php echo htmlspecialchars($book['total_copies']); ?>"
                           min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Available Copies -->
                <div>
                    <label for="available_copies" class="block text-sm font-medium text-gray-700 mb-1">Available Copies</label>
                    <input type="number" id="available_copies" name="available_copies"
                           value="<?php echo htmlspecialchars($book['available_copies']); ?>"
                           min="0" max="<?php echo $book['total_copies']; ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Cover Image URL -->
                <div class="col-span-2">
                    <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-1">Cover Image URL</label>
                    <input type="url" id="cover_image" name="cover_image"
                           value="<?php echo htmlspecialchars($book['cover_image']); ?>"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="https://example.com/book-cover.jpg">
                    <?php if ($book['cover_image']): ?>
                        <div class="mt-2">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="Current cover" 
                                 class="w-32 h-48 object-cover rounded">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><?php echo htmlspecialchars($book['description']); ?></textarea>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-3">
                <button type="button" onclick="window.location='books.php'"
                        class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                    Cancel
                </button>
                <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
                    Update Book
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
