<?php
require __DIR__ . '/../config/auth.php';
require_role('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = $pdo->query("
        SELECT u.id, u.login, u.name, u.created_at, r.code AS role, r.title AS role_title
        FROM users u JOIN roles r ON r.id = u.role_id
        ORDER BY u.id
    ")->fetchAll();
    $roles = $pdo->query("SELECT id, code, title FROM roles")->fetchAll();
    json_out(['users' => $rows, 'roles' => $roles]);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $login = trim($data['login'] ?? '');
    $name  = trim($data['name'] ?? '');
    $pass  = $data['password'] ?? '';
    $role  = $data['role'] ?? 'user';
    $phone = trim($data['phone'] ?? '');

    if (!$login || !$name || !$pass) json_out(['error' => 'Заполните все поля'], 400);

    $roleId = $pdo->prepare("SELECT id FROM roles WHERE code = ?");
    $roleId->execute([$role]);
    $rid = $roleId->fetchColumn();
    if (!$rid) json_out(['error' => 'Неизвестная роль'], 400);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (login, phone, password_hash, name, role_id) VALUES (?,?,?,?,?)");
        $stmt->execute([$login, $phone ?: null, password_hash($pass, PASSWORD_DEFAULT), $name, $rid]);
    } catch (PDOException $e) {
        json_out(['error' => 'Логин или телефон уже занят'], 400);
    }
    json_out(['ok' => true]);
}

if ($method === 'PATCH') {
    $id = (int)($_GET['id'] ?? 0);
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (!$id) json_out(['error' => 'Нет id'], 400);

    if (isset($data['role'])) {
        $roleId = $pdo->prepare("SELECT id FROM roles WHERE code = ?");
        $roleId->execute([$data['role']]);
        $rid = $roleId->fetchColumn();
        if (!$rid) json_out(['error' => 'Неизвестная роль'], 400);
        $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?")->execute([$rid, $id]);
    }
    if (isset($data['name'])) {
        $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$data['name'], $id]);
    }
    if (isset($data['password']) && $data['password'] !== '') {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($data['password'], PASSWORD_DEFAULT), $id]);
    }
    json_out(['ok' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id === (int)current_user()['id']) json_out(['error' => 'Нельзя удалить себя'], 400);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);