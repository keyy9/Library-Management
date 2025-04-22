<?php
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $db = getDB();
    
    // Check users table
    $stmt = $db->query("SELECT * FROM users WHERE username = 'admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Admin user details:\n";
    print_r($user);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
