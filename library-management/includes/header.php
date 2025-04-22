<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
if (!isset($public_page) && !isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gray-50 font-[Poppins]">
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-xl font-bold text-indigo-600">
                            <i class="fas fa-book-reader mr-2"></i><?php echo SITE_NAME; ?>
                        </a>
                    </div>
                    <!-- Navigation Links -->
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?>">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>/books.php" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) === 'books.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?>">
                            <i class="fas fa-book mr-1"></i> Books
                        </a>
                        <?php if (isAdmin()): ?>
                        <a href="<?php echo BASE_URL; ?>/authors.php" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) === 'authors.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?>">
                            <i class="fas fa-user-edit mr-1"></i> Authors
                        </a>
                        <a href="<?php echo BASE_URL; ?>/categories.php" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?>">
                            <i class="fas fa-tags mr-1"></i> Categories
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/borrowings.php" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 <?php echo basename($_SERVER['PHP_SELF']) === 'borrowings.php' ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?>">
                            <i class="fas fa-hand-holding-heart mr-1"></i> Borrowings
                        </a>
                    </div>
                </div>
                <!-- User Menu -->
                <div class="flex items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>/logout.php" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-sign-out-alt mr-1"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" onclick="toggleMobileMenu()" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" 
                       class="block pl-3 pr-4 py-2 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?>">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/books.php" 
                       class="block pl-3 pr-4 py-2 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'books.php' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?>">
                        <i class="fas fa-book mr-1"></i> Books
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo BASE_URL; ?>/authors.php" 
                       class="block pl-3 pr-4 py-2 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'authors.php' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?>">
                        <i class="fas fa-user-edit mr-1"></i> Authors
                    </a>
                    <a href="<?php echo BASE_URL; ?>/categories.php" 
                       class="block pl-3 pr-4 py-2 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?>">
                        <i class="fas fa-tags mr-1"></i> Categories
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/borrowings.php" 
                       class="block pl-3 pr-4 py-2 border-l-4 <?php echo basename($_SERVER['PHP_SELF']) === 'borrowings.php' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?>">
                        <i class="fas fa-hand-holding-heart mr-1"></i> Borrowings
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
