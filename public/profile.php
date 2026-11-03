<?php
require __DIR__ . '/config/auth.php';
require_login(); // Если не авторизован — перекинет на вход
$u = current_user();

// Получаем заказы пользователя
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$u['id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет · Аист №1</title>
    <style>
        body { font-family: Ubuntu, sans-serif; background: #fbf7f1; color: #2a2220; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        h1 { margin-bottom: 20px; }
        .order-card { border: 1px solid #eee; padding: 15px; border-radius: 12px; margin-bottom: 15px; }
        .order-status { font-weight: bold; color: #c00020; }
        .btn { display: inline-block; padding: 10px 20px; background: #c00020; color: #fff; text-decoration: none; border-radius: 8px; margin-top: 10px; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Привет, <?= htmlspecialchars($u['name']) ?>!</h1>
        <p>Ваш номер: <?= htmlspecialchars($u['phone'] ?? 'Не указан') ?></p>
        
        <h2 style="margin-top: 30px;">Ваши заказы</h2>
        <?php if (empty($orders)): ?>
            <p>У вас пока нет заказов. <a href="/index.html" class="btn">Перейти в меню</a></p>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <div class="order-card">
                    <div style="display:flex; justify-content:space-between;">
                        <b>Заказ #<?= $o['id'] ?></b>
                        <span class="order-status"><?= $o['status'] ?></span>
                    </div>
                    <p style="margin: 5px 0;">Дата: <?= $o['created_at'] ?></p>
                    <p style="margin: 5px 0;">Состав: <?= htmlspecialchars($o['product'] ?? 'Не указан') ?></p>
                    <p style="margin: 5px 0;">Сумма: <?= $o['comment'] ?? '' ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; display: flex; align-items: center; gap: 15px;">
            <a href="/admin/logout.php" class="btn" style="background: #f0eae2; color: #2a2220; padding: 10px 20px; text-decoration: none; border-radius: 8px;">Выйти</a>
            
            <?php if (in_array($u['role'], ['admin', 'manager', 'editor'])): ?>
                <a href="/admin/index.php" class="btn" style="margin-top: 0; background: #c00020;">Админ-панель</a>
            <?php else: ?>
                <!-- Для обычных пользователей ссылка на вход/регистрацию не нужна, так как они уже авторизованы. 
                     Если вы хотите, чтобы они могли переключаться на другие аккаунты, оставьте ссылку на выход. -->
            <?php endif; ?>
        </div>
    </div>
</body>
</html>