<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';
$book = null;
$borrowing_status = null;

// Get book ID from URL
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header('Location: books.php');
    exit();
}

try {
    $db = getDB();
    
    // Get book details with author and category information
    $stmt = $db->prepare('
        SELECT b.*, a.name as author_name, a.bio as author_bio, 
               c.name as category_name, c.description as category_description
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.id = ?
    ');
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        header('Location: books.php');
        exit();
    }

    // Check if user has already borrowed this book
    if (isLoggedIn()) {
        $stmt = $db->prepare('
            SELECT * FROM borrowings 
            WHERE user_id = ? AND book_id = ? AND status != "returned"
        ');
        $stmt->execute([$_SESSION['user_id'], $book_id]);
        $borrowing_status = $stmt->fetch();
    }

    // Handle borrow request
    if (isset($_POST['borrow']) && isLoggedIn()) {
        if ($book['available_copies'] > 0) {
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Create borrowing record
                $stmt = $db->prepare('
                    INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status)
                    VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), "borrowed")
                ');
                $stmt->execute([$_SESSION['user_id'], $book_id]);

                // Update available copies
                $stmt = $db->prepare('
                    UPDATE books 
                    SET available_copies = available_copies - 1 
                    WHERE id = ? AND available_copies > 0
                ');
                $stmt->execute([$book_id]);

                $db->commit();
                $success = 'Book borrowed successfully. Due date is in 14 days.';
                
                // Refresh page to update status
                header('Location: book_detail.php?id=' . $book_id . '&message=' . urlencode($success) . '&type=success');
                exit();
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to borrow book. Please try again.';
            }
        } else {
            $error = 'Sorry, this book is currently not available.';
        }
    }
} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <a href="books.php" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to Books
            </a>
            <?php if (isAdmin()): ?>
                <a href="edit_book.php?id=<?php echo $book_id; ?>" 
                   class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
                    <i class="fas fa-edit mr-2"></i> Edit Book
                </a>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Book Cover -->
                <div class="md:flex-shrink-0">
                    <img class="h-96 w-full object-cover md:w-64" 
                         src="<?php echo htmlspecialchars($book['cover_image']); ?>"
                         alt="<?php echo htmlspecialchars($book['title']); ?>">
                </div>

                <!-- Book Details -->
                <div class="p-8">
                    <div class="uppercase tracking-wide text-sm text-indigo-600 font-semibold">
                        <?php echo htmlspecialchars($book['category_name']); ?>
                    </div>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h1>
                    <p class="mt-2 text-xl text-gray-600">
                        by <?php echo htmlspecialchars($book['author_name']); ?>
                    </p>
                    
                    <div class="mt-4 flex items-center">
                        <span class="text-gray-600 mr-4">
                            <i class="fas fa-book mr-2"></i>
                            ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                        </span>
                        <span class="text-gray-600">
                            <i class="fas fa-calendar mr-2"></i>
                            Published: <?php echo htmlspecialchars($book['published_year']); ?>
                        </span>
                    </div>

                    <div class="mt-4">
                        <h2 class="text-xl font-semibold text-gray-800">Description</h2>
                        <p class="mt-2 text-gray-600">
                            <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                        </p>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center">
                            <div class="text-gray-600">
                                <span class="font-semibold">Availability:</span>
                                <?php echo $book['available_copies']; ?> of <?php echo $book['total_copies']; ?> copies available
                            </div>
                            <?php if (isLoggedIn() && !isAdmin()): ?>
                                <?php if ($borrowing_status): ?>
                                    <span class="ml-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        Currently Borrowed
                                    </span>
                                <?php elseif ($book['available_copies'] > 0): ?>
                                    <form method="POST" class="ml-4">
                                        <button type="submit" name="borrow" 
                                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
                                            <i class="fas fa-hand-holding-heart mr-2"></i> Borrow Book
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="ml-4 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        Not Available
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="border-t border-gray-200 px-8 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Author Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">About the Author</h2>
                        <p class="text-gray-600">
                            <?php echo nl2br(htmlspecialchars($book['author_bio'] ?? 'No author information available.')); ?>
                        </p>
                    </div>

                    <!-- Category Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">Category Details</h2>
                        <p class="text-gray-600">
                            <?php echo nl2br(htmlspecialchars($book['category_description'] ?? 'No category description available.')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
