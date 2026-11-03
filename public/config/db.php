<?php
$DB_HOST = 'MySQL-8.4';
$DB_NAME = 'aist';
$DB_USER = 'root';
$DB_PASS = ''; // в Open Server по умолчанию пусто

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('DB error: ' . $e->getMessage());
}