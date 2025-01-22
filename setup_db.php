<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'auth_tokens'
];

try {
    // Connect without database first
    $pdo = new PDO(
        "mysql:host={$db_config['host']}", 
        $db_config['username'], 
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['database']}");
    echo "Database created or already exists<br>";
    
    // Select the database
    $pdo->exec("USE {$db_config['database']}");
    
    // Create tokens table
    $sql = "CREATE TABLE IF NOT EXISTS tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        access_token TEXT NOT NULL,
        refresh_token TEXT,
        expires_in INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Table 'tokens' created successfully<br>";
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE tokens");
    echo "<pre>Table Structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

echo "Database setup completed successfully!"; 