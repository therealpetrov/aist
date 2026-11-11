<?php
require __DIR__ . '/../config/auth.php';
require_role('admin', 'editor'); // Доступ для админа и редактора

$method = $_SERVER['REQUEST_METHOD'];

// Если пришел POST с _method=PUT, меняем метод на PUT
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
}

if ($method === 'GET') {
    $rows = $pdo->query("
        SELECT n.*, u.name AS author_name 
        FROM news n LEFT JOIN users u ON u.id = n.author_id 
        ORDER BY n.created_at DESC
    ")->fetchAll();
    json_out(['news' => $rows]);
}

if ($method === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $imagePath = null;

    if (!$title || !$content) json_out(['error' => 'Заполните заголовок и текст'], 400);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image']);
    }

    $stmt = $pdo->prepare("INSERT INTO news (title, content, image, author_id) VALUES (?,?,?,?)");
    $stmt->execute([$title, $content, $imagePath, current_user()['id']]);
    json_out(['ok' => true]);
}

// Обработка обновления
if ($method === 'PUT') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (!$id || !$title || !$content) json_out(['error' => 'Не хватает данных для обновления'], 400);

    // Получаем текущую картинку
    $stmt = $pdo->prepare("SELECT image FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $currentImage = $stmt->fetchColumn();

    $imagePath = $currentImage;

    // Если загрузили новую картинку
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadImage($_FILES['image']);
        // Удаляем старую картинку с диска (если она была)
        if ($currentImage && file_exists(__DIR__ . '/../' . $currentImage)) {
            unlink(__DIR__ . '/../' . $currentImage);
        }
    }

    $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ?, image = ? WHERE id = ?");
    $stmt->execute([$title, $content, $imagePath, $id]);
    json_out(['ok' => true]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    // Удаляем картинку с диска перед удалением записи
    $stmt = $pdo->prepare("SELECT image FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists(__DIR__ . '/../' . $img)) {
        unlink(__DIR__ . '/../' . $img);
    }
    
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    json_out(['ok' => true]);
}

json_out(['error' => 'Method not allowed'], 405);

// Вспомогательная функция для загрузки изображения
function uploadImage($file) {
    $uploadDir = __DIR__ . '/../uploads/'; 
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Проверка на допустимые расширения
    if (!in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        json_out(['error' => 'Недопустимый формат файла'], 400);
    }

    $fileName = time() . '_' . uniqid() . '.' . $fileExt;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return '/uploads/' . $fileName;
    }
    return null;
}