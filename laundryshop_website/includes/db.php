
<?php
// Connect the laundry shop website to MySQL.

$host = '127.0.0.1';
$dbname = 'laundryshop_db';
$username = 'root';
$password = ''; // Default XAMPP password, if unchanged.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Log technical details rather than displaying them to visitors.
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check your XAMPP MySQL settings.');
}
