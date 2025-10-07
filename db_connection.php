<?php
// db_connection.php
// $2y$10$

$host = 'localhost';
$db   = 'money_system'; // Must match your database name
$user = 'root';        // CHANGE THIS
$pass = '';            // CHANGE THIS
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     error_log("Database Connection Error: " . $e->getMessage());
     throw new \PDOException("Could not connect to the database. Please try again later.", (int)$e->getCode());
}