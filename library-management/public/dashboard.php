<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

try {
    $db = getDB();
    
    // Get total books count
    $stmt = $db->query('SELECT COUNT(*) FROM books');
    $totalBooks = $stmt->fetchColumn();
    
    // Get total available books
    $stmt = $db->query('SELECT SUM(available_copies) FROM books');
    $availableBooks = $stmt->fetchColumn();
    
    // Get total borrowed books
    $stmt = $db->query('SELECT COUNT(*) FROM borrowings WHERE status = "borrowed"');
    $borrowedBooks = $stmt->fetchColumn();
    
    // Get total overdue books
    $stmt = $db->query('SELECT COUNT(*) FROM borrowings WHERE status = "overdue"');
    $overdueBooks = $stmt->fetchColumn();
    
    // Get recent books (last 5 added)
    $stmt = $db->query('SELECT b.*, a.name as author_name, c.name as category_name 
                       FROM books b 
                       JOIN authors a ON b.author_id = a.id 
                       JOIN categories c ON b.category_id = c.id 
                       ORDER BY b.created_at DESC LIMIT 5');
    $recentBooks = $stmt->fetchAll();
    
    // Get user's borrowed books if not admin
    $userBorrowings = [];
    if (!isAdmin()) {
        $stmt = $db->prepare('SELECT b.*, bor.borrow_date, bor.due_date, bor.status,
                             a.name as author_name 
                             FROM borrowings bor
                             JOIN books b ON bor.book_id = b.id
                             JOIN authors a ON b.author_id = a.id
                             WHERE bor.user_id = ? AND bor.status != "returned"
                             ORDER BY bor.due_date ASC');
        $stmt->execute([$_SESSION['user_id']]);
        $userBorrowings = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Welcome Message -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </h1>
        <p class="text-gray-600">
            Here's an overview of the library system.
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Books -->
        <div class="bg-white rounded-lg shadow-lg p-6 dashboard-card">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-book fa-2x"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Books</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo $totalBooks; ?></p>
                </div>
            </div>
        </div>

        <!-- Available Books -->
        <div class="bg-white rounded-lg shadow-lg p-6 dashboard-card">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-book-open fa-2x"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Available Books</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo $availableBooks; ?></p>
                </div>
            </div>
        </div>

        <!-- Borrowed Books -->
        <div class="bg-white rounded-lg shadow-lg p-6 dashboard-card">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-hand-holding-heart fa-2x"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Borrowed Books</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo $borrowedBooks; ?></p>
                </div>
            </div>
        </div>

        <!-- Overdue Books -->
        <div class="bg-white rounded-lg shadow-lg p-6 dashboard-card">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Overdue Books</p>
                    <p class="text-2xl font-semibold text-gray-800"><?php echo $overdueBooks; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recently Added Books -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recently Added Books</h2>
            <div class="space-y-4">
                <?php foreach ($recentBooks as $book): ?>
                    <div class="flex items-center space-x-4 p-4 hover:bg-gray-50 rounded-lg transition duration-150">
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="w-16 h-24 object-cover rounded-lg shadow">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                by <?php echo htmlspecialchars($book['author_name']); ?>
                            </p>
                            <span class="inline-block px-2 py-1 text-xs font-semibold text-indigo-600 bg-indigo-100 rounded-full">
                                <?php echo htmlspecialchars($book['category_name']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-right">
                <a href="books.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    View all books <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- User's Borrowed Books (if not admin) -->
        <?php if (!isAdmin() && !empty($userBorrowings)): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Borrowed Books</h2>
            <div class="space-y-4">
                <?php foreach ($userBorrowings as $borrow): ?>
                    <div class="flex items-center space-x-4 p-4 hover:bg-gray-50 rounded-lg transition duration-150">
                        <img src="<?php echo htmlspecialchars($borrow['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($borrow['title']); ?>"
                             class="w-16 h-24 object-cover rounded-lg shadow">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-800">
                                <?php echo htmlspecialchars($borrow['title']); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                by <?php echo htmlspecialchars($borrow['author_name']); ?>
                            </p>
                            <div class="mt-2">
                                <span class="text-sm text-gray-600">
                                    Due: <?php echo date('M d, Y', strtotime($borrow['due_date'])); ?>
                                </span>
                                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $borrow['status'] === 'overdue' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                    <?php echo ucfirst($borrow['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-right">
                <a href="borrowings.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                    View all borrowings <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions for Admin -->
        <?php if (isAdmin()): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="books.php?action=add" class="p-4 bg-indigo-50 rounded-lg text-center hover:bg-indigo-100 transition duration-150">
                    <i class="fas fa-book-medical text-2xl text-indigo-600 mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Add New Book</p>
                </a>
                <a href="authors.php?action=add" class="p-4 bg-green-50 rounded-lg text-center hover:bg-green-100 transition duration-150">
                    <i class="fas fa-user-plus text-2xl text-green-600 mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Add New Author</p>
                </a>
                <a href="categories.php?action=add" class="p-4 bg-purple-50 rounded-lg text-center hover:bg-purple-100 transition duration-150">
                    <i class="fas fa-tags text-2xl text-purple-600 mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Add New Category</p>
                </a>
                <a href="borrowings.php" class="p-4 bg-yellow-50 rounded-lg text-center hover:bg-yellow-100 transition duration-150">
                    <i class="fas fa-clock text-2xl text-yellow-600 mb-2"></i>
                    <p class="text-sm font-medium text-gray-800">Manage Borrowings</p>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
