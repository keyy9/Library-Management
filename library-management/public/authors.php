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
$authors = [];
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $db = getDB();

    // Handle author deletion
    if (isset($_GET['delete'])) {
        try {
            $stmt = $db->prepare('DELETE FROM authors WHERE id = ?');
            $stmt->execute([$_GET['delete']]);
            $success = 'Author deleted successfully.';
            header('Location: authors.php?message=' . urlencode($success) . '&type=success');
            exit();
        } catch (PDOException $e) {
            $error = 'Cannot delete author. They may have books associated with them.';
        }
    }

    // Handle author addition/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $author_id = $_POST['author_id'] ?? null;

        if (empty($name)) {
            $error = 'Author name is required.';
        } else {
            if ($author_id) {
                // Update existing author
                $stmt = $db->prepare('UPDATE authors SET name = ?, bio = ? WHERE id = ?');
                $stmt->execute([$name, $bio, $author_id]);
                $success = 'Author updated successfully.';
            } else {
                // Add new author
                $stmt = $db->prepare('INSERT INTO authors (name, bio) VALUES (?, ?)');
                $stmt->execute([$name, $bio]);
                $success = 'Author added successfully.';
            }
            header('Location: authors.php?message=' . urlencode($success) . '&type=success');
            exit();
        }
    }

    // Build query based on search
    $query = 'SELECT a.*, COUNT(b.id) as book_count 
              FROM authors a 
              LEFT JOIN books b ON a.id = b.author_id';
    $params = [];

    if ($search) {
        $query .= ' WHERE a.name LIKE ?';
        $params[] = "%$search%";
    }

    $query .= ' GROUP BY a.id';

    // Get total count for pagination
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM ($query) as count_table");
    $count_stmt->execute($params);
    $total_authors = $count_stmt->fetchColumn();
    $total_pages = ceil($total_authors / $per_page);

    // Add sorting and pagination
    $query .= ' ORDER BY a.name LIMIT ? OFFSET ?';
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $authors = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = handleDBError($e);
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Authors Management</h1>
        <button onclick="openModal('addAuthorModal')" 
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition duration-150">
            <i class="fas fa-plus mr-2"></i> Add New Author
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
                       placeholder="Search authors...">
            </div>
            <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-150">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="authors.php" class="text-gray-600 hover:text-gray-800 py-2">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Authors Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Books</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($authors as $author): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($author['name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500 truncate max-w-xs">
                                <?php echo htmlspecialchars($author['bio'] ?: 'No bio available'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo $author['book_count']; ?> books
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editAuthor(<?php echo htmlspecialchars(json_encode($author)); ?>)"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($author['book_count'] == 0): ?>
                                <a href="authors.php?delete=<?php echo $author['id']; ?>" 
                                   class="text-red-600 hover:text-red-900"
                                   onclick="return confirm('Are you sure you want to delete this author?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($authors)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No authors found.
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

<!-- Add/Edit Author Modal -->
<div id="authorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modalTitle">Add New Author</h3>
            <form id="authorForm" method="POST" action="">
                <input type="hidden" id="author_id" name="author_id">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="mb-4">
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea id="bio" name="bio" rows="4"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('authorModal')"
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
    // Clear form when opening for new author
    document.getElementById('authorForm').reset();
    document.getElementById('author_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add New Author';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function editAuthor(author) {
    document.getElementById('modalTitle').textContent = 'Edit Author';
    document.getElementById('author_id').value = author.id;
    document.getElementById('name').value = author.name;
    document.getElementById('bio').value = author.bio || '';
    openModal('authorModal');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('authorModal');
    if (event.target == modal) {
        closeModal('authorModal');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
