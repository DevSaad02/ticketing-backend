<?php

use Dotenv\Dotenv;
// use ORM;

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
    try {
        // Enable event scheduler (only needed once)
        $db->exec("SET GLOBAL event_scheduler = ON;");
    
        // Create or update the scheduled event
        $query = "
            DROP EVENT IF EXISTS delete_expired_bookings;
            CREATE EVENT delete_expired_bookings
            ON SCHEDULE EVERY 1 DAY 
            STARTS TIMESTAMP(CURRENT_DATE, '00:00:00')
            DO
            DELETE FROM booked_slot 
            WHERE CONCAT(date, ' ', end_time) < NOW();
        ";
    
        $db->exec($query);
    
    } catch (Exception $e) {
        error_log("Error setting up database event: " . $e->getMessage());
    }

    // echo "Database connection successful!";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
