<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

// Initialize variables
$error = '';
$success = '';
$books = [];
$authors = [];
$categories = [];
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Get all authors and categories for forms
    $authors = $db->query('SELECT id, name FROM authors ORDER BY name')->fetchAll();
    $categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

    // Build the query based on search and filter
    $query = 'SELECT b.*, a.name as author_name, c.name as category_name 
              FROM books b 
              JOIN authors a ON b.author_id = a.id 
              JOIN categories c ON b.category_id = c.id';
    $params = [];

    if ($search || $category_filter) {
        $query .= ' WHERE';
        if ($search) {
            $query .= ' (b.title LIKE ? OR a.name LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($category_filter) {
            $query .= ($search ? ' AND' : '') . ' c.id = ?';
            $params[] = $category_filter;
        }
    }

    // Get total count for pagination
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM ($query) as count_table");
    $count_stmt->execute($params);
    $total_books = $count_stmt->fetchColumn();
    $total_pages = ceil($total_books / $per_page);

    // Add pagination to the main query
    $query .= ' ORDER BY b.title LIMIT ? OFFSET ?';
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = handleDBError($e);
}

// Handle book deletion
if (isset($_GET['delete']) && isAdmin()) {
    try {
        $stmt = $db->prepare('DELETE FROM books WHERE id = ?');
        $stmt->execute([$_GET['delete']]);
        $success = 'Book deleted successfully.';
        header('Location: books.php?message=' . urlencode($success) . '&type=success');
        exit();
    } catch (PDOException $e) {
        $error = handleDBError($e);
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Books Management</h1>
        <?php if (isAdmin()): ?>
        <a href="add_book.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
            <i class="fas fa-plus mr-2"></i> Add New Book
        </a>
        <?php endif; ?>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Search by title or author...">
            </div>
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="category" name="category" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
                <?php if ($search || $category_filter): ?>
                    <a href="books.php" class="ml-2 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Books Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($books as $book): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden book-card">
                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                     class="w-full h-64 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h3>
                    <p class="text-gray-600 mb-2">
                        by <?php echo htmlspecialchars($book['author_name']); ?>
                    </p>
                    <span class="inline-block px-2 py-1 text-sm font-semibold text-indigo-600 bg-indigo-100 rounded-full">
                        <?php echo htmlspecialchars($book['category_name']); ?>
                    </span>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            Available: <?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?>
                        </span>
                        <div class="space-x-2">
                            <?php if (isAdmin()): ?>
                                <a href="edit_book.php?id=<?php echo $book['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="books.php?delete=<?php echo $book['id']; ?>" 
                                   class="text-red-600 hover:text-red-800"
                                   onclick="return confirm('Are you sure you want to delete this book?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                            <a href="book_detail.php?id=<?php echo $book['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                              <?php echo $i === $page ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
