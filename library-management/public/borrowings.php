<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';
$borrowings = [];
$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();
    
    // Handle return action
    if (isset($_POST['return']) && isset($_POST['borrow_id'])) {
        $borrow_id = $_POST['borrow_id'];
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Get borrowing details
            $stmt = $db->prepare('SELECT book_id FROM borrowings WHERE id = ?');
            $stmt->execute([$borrow_id]);
            $borrowing = $stmt->fetch();

            if ($borrowing) {
                // Update borrowing status using SQLite's date('now')
                $stmt = $db->prepare('
                    UPDATE borrowings 
                    SET status = "returned", return_date = date("now") 
                    WHERE id = ?
                ');
                $stmt->execute([$borrow_id]);

                // Increment available copies
                $stmt = $db->prepare('
                    UPDATE books 
                    SET available_copies = available_copies + 1 
                    WHERE id = ?
                ');
                $stmt->execute([$borrowing['book_id']]);

                $db->commit();
                $success = 'Book returned successfully.';
                header('Location: borrowings.php?message=' . urlencode($success) . '&type=success');
                exit();
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to return book. Please try again.';
        }
    }

    // Build query based on user role and filter
    $query = '
        SELECT b.*, bk.title as book_title, bk.cover_image,
               u.username, a.name as author_name
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        JOIN users u ON b.user_id = u.id
        JOIN authors a ON bk.author_id = a.id
    ';
    $params = [];

    if (!isAdmin()) {
        $query .= ' WHERE b.user_id = ?';
        $params[] = $_SESSION['user_id'];
    }

    // Apply filter
    if ($filter !== 'all') {
        $query .= (count($params) ? ' AND' : ' WHERE') . ' b.status = ?';
        $params[] = $filter;
    }

    // Update overdue status using SQLite's date('now')
    $db->query('
        UPDATE borrowings 
        SET status = "overdue" 
        WHERE date(due_date) < date("now") 
        AND status = "borrowed"
    ');

    // Get total count for pagination
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM ($query) as count_table");
    $count_stmt->execute($params);
    $total_borrowings = $count_stmt->fetchColumn();
    $total_pages = ceil($total_borrowings / $per_page);

    // Add sorting and pagination
    $query .= ' ORDER BY b.borrow_date DESC LIMIT ? OFFSET ?';
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Borrowings Management</h1>
        
        <!-- Filter Options -->
        <div class="flex space-x-2">
            <a href="?filter=all" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                All
            </a>
            <a href="?filter=borrowed" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'borrowed' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                Borrowed
            </a>
            <a href="?filter=returned" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'returned' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                Returned
            </a>
            <a href="?filter=overdue" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'overdue' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                Overdue
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Borrowings Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                    <?php if (isAdmin()): ?>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($borrowings)): ?>
                    <?php foreach ($borrowings as $borrowing): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-16 w-12">
                                        <img class="h-16 w-12 object-cover rounded" 
                                             src="<?php echo htmlspecialchars($borrowing['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($borrowing['book_title']); ?>">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($borrowing['book_title']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            by <?php echo htmlspecialchars($borrowing['author_name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <?php if (isAdmin()): ?>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($borrowing['username']); ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                    switch ($borrowing['status']) {
                                        case 'borrowed':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'returned':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'overdue':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>">
                                    <?php echo ucfirst($borrowing['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="borrow_id" value="<?php echo $borrowing['id']; ?>">
                                        <button type="submit" name="return" 
                                                class="text-indigo-600 hover:text-indigo-900"
                                                onclick="return confirm('Are you sure you want to return this book?')">
                                            Return
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="book_detail.php?id=<?php echo $borrowing['book_id']; ?>" 
                                   class="text-gray-600 hover:text-gray-900 ml-3">
                                    View Book
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo isAdmin() ? '6' : '5'; ?>" class="px-6 py-4 text-center text-gray-500">
                            No borrowings found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                              <?php echo $i === $page ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
