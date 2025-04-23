<?php
// Directories to create
$directories = [
    'public/assets/css',
    'public/assets/js'
];

// Files to copy
$files = [
    'assets/css/style.css' => 'public/assets/css/style.css',
    'assets/js/script.js' => 'public/assets/js/script.js'
];

// Create directories
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

// Copy files
foreach ($files as $source => $dest) {
    if (file_exists($source)) {
        copy($source, $dest);
        echo "Copied: $source -> $dest\n";
    } else {
        echo "Source file not found: $source\n";
    }
}

echo "Setup complete!\n";
