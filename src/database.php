<?php

use Dotenv\Dotenv;
use ORM;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Configure Idiorm using environment variables
    ORM::configure([
        'connection_string' => 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASS'],
        'error_mode' => PDO::ERRMODE_EXCEPTION // Enable exception mode
    ]);

    // Test the connection
    $db = ORM::get_db();
    $db->query('SELECT 1'); // Simple test query

    // echo "Database connection successful!";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
