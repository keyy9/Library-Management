<?php
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $db = getDB();
    
    // Get admin user
    $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    
    echo "Admin user details:\n";
    print_r($user);
    
    $test_password = 'admin123';
    $hash_matches = password_verify($test_password, $user['password']);
    
    echo "\nPassword verification test:\n";
    echo "Test password: " . $test_password . "\n";
    echo "Stored hash: " . $user['password'] . "\n";
    echo "Hash matches: " . ($hash_matches ? 'yes' : 'no') . "\n";
    
    // Create a new hash for comparison
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    echo "\nNew hash generated: " . $new_hash . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
