<?php
require __DIR__ . '/../config/auth.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if ($u) {
    echo json_encode([
        'logged_in' => true,
        'id'        => $u['id'],
        'name'      => $u['name'],
        'role'      => $u['role'],
        'phone'     => $u['phone'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['logged_in' => false]);
}