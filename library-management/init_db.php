<?php
require_once 'config/config.php';

try {
    // Create/Connect to SQLite database
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop existing tables if they exist
    $db->exec('DROP TABLE IF EXISTS borrowings');
    $db->exec('DROP TABLE IF EXISTS books');
    $db->exec('DROP TABLE IF EXISTS authors');
    $db->exec('DROP TABLE IF EXISTS categories');
    $db->exec('DROP TABLE IF EXISTS users');
    
    echo "Existing tables dropped successfully!\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/library.sqlite.sql');
    $db->exec($sql);
    
    echo "Database initialized successfully!\n";
    
    // Verify admin user
    $stmt = $db->query("SELECT * FROM users WHERE username = 'admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nAdmin user details:\n";
    print_r($user);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
