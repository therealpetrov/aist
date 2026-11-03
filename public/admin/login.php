<?php
require __DIR__ . '/../config/auth.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? ''); // телефон или логин
    $pass  = $_POST['password'] ?? '';

    // Ищем по логину ИЛИ по телефону
    $stmt = $pdo->prepare("
        SELECT u.*, r.code AS role
        FROM users u JOIN roles r ON r.id = u.role_id
        WHERE u.login = ? OR u.phone = ?
    ");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'login' => $user['login'],
            'name' => $user['name'],
            'role' => $user['role'],
            'phone' => $user['phone']
        ];
        
        // Редирект в зависимости от роли
        if (in_array($user['role'], ['admin', 'editor', 'manager'])) {
            header('Location: /admin/index.php');
        } else {
            header('Location: /index.html'); // или /profile.php
        }
        exit;
    }
    $error = 'Неверный номер/логин или пароль';
}
?>
<!-- HTML форма входа остаётся без изменений, но можно добавить ссылку на регистрацию -->
<!DOCTYPE html>
<html lang="ru"><head>
<meta charset="UTF-8"><title>Вход в админку</title>
<style>
body{font-family:Ubuntu,system-ui,sans-serif;background:#fbf7f1;display:grid;place-items:center;min-height:100vh;margin:0}
.box{background:#fff;padding:40px;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.12);width:360px}
h1{font-size:22px;margin:0 0 24px;color:#2a2220}
label{display:block;font-size:14px;margin-bottom:6px;color:#2a2220}
input{width:100%;padding:14px 16px;border:1px solid #ddd;border-radius:12px;font-size:15px;box-sizing:border-box;margin-bottom:16px}
input:focus{outline:none;border-color:#c00020;box-shadow:0 0 0 3px rgba(192,0,32,.1)}
button{width:100%;padding:14px;border:none;border-radius:12px;background:#c00020;color:#fff;font-size:16px;font-weight:700;cursor:pointer}
button:hover{background:#8a0017}
.err{color:#c00020;font-size:14px;margin-bottom:16px}
</style></head><body>
<form class="box" method="post">
  <h1>Вход в админ-панель</h1>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <label>Логин или Телефон</label>
  <input name="login" required autofocus>
  <label>Пароль</label>
  <input name="password" type="password" required>
  <button type="submit">Войти</button>
</form>
</body></html>