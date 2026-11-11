<?php
session_start();

// Полная очистка сессии
$_SESSION = [];

// Удаляем cookie сессии, если она есть
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Уничтожаем сессию на сервере
session_destroy();

// Редирект на главную
header('Location: /index.html');
exit;