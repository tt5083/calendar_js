<?php
// connection/db.php

function get_db_connection() {
    $host = 'localhost';
    $port = '3306';
    $dbname = 'stcalendar'; // As seen in your original files
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        // In a real application, you would log this error, not display it
        // For development, it's okay to show the error.
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Database connection failed',
            'details' => $e->getMessage()
        ]);
        // Stop execution if the connection fails
        exit;
    }
}