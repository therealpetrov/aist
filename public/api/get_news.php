<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');

// Получаем 3 последние новости (без проверки авторизации)
$stmt = $pdo->query("
    SELECT n.id, n.title, n.content, n.image, n.created_at, u.name AS author_name 
    FROM news n LEFT JOIN users u ON u.id = n.author_id 
    ORDER BY n.created_at DESC LIMIT 3
");
$news = $stmt->fetchAll();

echo json_encode(['news' => $news], JSON_UNESCAPED_UNICODE);