<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../includes/header.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$categories = [];
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();

    // Handle category deletion
    if (isset($_GET['delete'])) {
        try {
            $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$_GET['delete']]);
            $success = 'Category deleted successfully.';
            header('Location: categories.php?message=' . urlencode($success) . '&type=success');
            exit();
        } catch (PDOException $e) {
            $error = 'Cannot delete category. It may have books associated with it.';
        }
    }

    // Handle category addition/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = $_POST['category_id'] ?? null;

        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            if ($category_id) {
                // Update existing category
                $stmt = $db->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $description, $category_id]);
                $success = 'Category updated successfully.';
            } else {
                // Add new category
                $stmt = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
                $stmt->execute([$name, $description]);
                $success = 'Category added successfully.';
            }
            header('Location: categories.php?message=' . urlencode($success) . '&type=success');
            exit();
        }
    }

    // Build query based on search
    $query = 'SELECT c.*, COUNT(b.id) as book_count 
              FROM categories c 
              LEFT JOIN books b ON c.id = b.category_id';
    $params = [];

    if ($search) {
        $query .= ' WHERE c.name LIKE ?';
        $params[] = "%$search%";
    }

    $query .= ' GROUP BY c.id';

    // Get total count for pagination
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM ($query) as count_table");
    $count_stmt->execute($params);
    $total_categories = $count_stmt->fetchColumn();
    $total_pages = ceil($total_categories / $per_page);

    // Add sorting and pagination
    $query .= ' ORDER BY c.name LIMIT ? OFFSET ?';
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Categories Management</h1>
        <button onclick="openModal('categoryModal')" 
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
            <i class="fas fa-plus mr-2"></i> Add New Category
        </button>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form action="" method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="Search categories...">
            </div>
            <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="categories.php" class="text-gray-600 hover:text-gray-800 py-2">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($categories as $category): ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                        <span class="text-sm text-gray-500">
                            <?php echo $category['book_count']; ?> books
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($category['book_count'] == 0): ?>
                            <a href="categories.php?delete=<?php echo $category['id']; ?>" 
                               class="text-red-600 hover:text-red-900"
                               onclick="return confirm('Are you sure you want to delete this category?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">
                    <?php echo htmlspecialchars($category['description'] ?: 'No description available.'); ?>
                </p>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-clock mr-2"></i>
                        Added <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <div class="col-span-full text-center text-gray-500 py-8">
                No categories found.
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                              <?php echo $i === $page ? 'text-indigo-600 bg-indigo-50' : 'text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add New Category</h3>
            <form id="categoryForm" method="POST" action="">
                <input type="hidden" id="category_id" name="category_id">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('categoryModal')"
                            class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                        Cancel
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    // Clear form when opening for new category
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Category';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('category_id').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description || '';
    openModal('categoryModal');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target == modal) {
        closeModal('categoryModal');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
