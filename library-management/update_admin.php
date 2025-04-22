<?php
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $db = getDB();
    
    // Create new password hash
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin user password
    $stmt = $db->prepare('UPDATE users SET password = ? WHERE username = ?');
    $result = $stmt->execute([$hash, 'admin']);
    
    if ($result) {
        echo "Admin password updated successfully!\n";
        
        // Verify the update
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute(['admin']);
        $user = $stmt->fetch();
        
        echo "\nVerifying new password:\n";
        echo "Password verification result: " . (password_verify($password, $user['password']) ? 'success' : 'failed') . "\n";
    } else {
        echo "Failed to update admin password.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
