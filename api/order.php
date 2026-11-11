<?php
require __DIR__ . '/../config/db.php';
// Добавим сессию, чтобы знать, кто заказывает
session_start(); 
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name    = trim($data['name'] ?? '');
$phone   = trim($data['phone'] ?? '');
$comment = trim($data['comment'] ?? '');
$product = $data['product'] ?? ''; 

if (is_array($product)) {
    $product = json_encode($product, JSON_UNESCAPED_UNICODE);
}

if ($name === '' || $phone === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Укажите имя и телефон']);
    exit;
}

// Получаем ID пользователя, если он авторизован
$userId = $_SESSION['user']['id'] ?? null;

$stmt = $pdo->prepare("INSERT INTO orders (user_id, name, phone, comment, product) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$userId, $name, $phone, $comment, $product ?: null]);

echo json_encode(['ok' => true]);