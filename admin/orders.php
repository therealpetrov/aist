<?php
require __DIR__ . '/../config/auth.php';
require_role('admin', 'manager');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
    json_out(['orders' => $rows]);
}

if ($method === 'PATCH') {
    $id = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $status = $data['status'] ?? '';
    if (!in_array($status, ['new','processing','done','canceled'], true))
        json_out(['error' => 'Неверный статус'], 400);
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);
    json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);