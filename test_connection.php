<?php
// Test database connection
require_once 'config.php';

try {
    // Test the connection
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    echo "<p>Number of users in database: " . $result['user_count'] . "</p>";
    
    // Test user data
    $stmt = $pdo->query("SELECT id, username, name FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Users:</h3>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['id']}, Username: {$user['username']}, Name: {$user['name']}</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>Database Connection Test</h2>";
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>
