<?php
session_start();
require __DIR__ . '/config/db.php';

// Если уже авторизован — редирект в ЛК или на главную
if (isset($_SESSION['user'])) {
    header('Location: /profile.php');
    exit;
}

$error = null;
$success = null;

// ============ ОБРАБОТКА ВХОДА ============
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$login || !$pass) {
        $error = 'Введите логин/телефон и пароль';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, r.code AS role
            FROM users u JOIN roles r ON r.id = u.role_id
            WHERE u.login = ? OR u.phone = ?
        ");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'login' => $user['login'],
                'name'  => $user['name'],
                'role'  => $user['role'],
                'phone' => $user['phone'],
            ];

            // Редирект в зависимости от роли
            if (in_array($user['role'], ['admin', 'editor', 'manager'], true)) {
                // Служебные роли — в админ-панель
                header('Location: /admin/index.php');
            } else {
                // Обычные клиенты — в личный кабинет
                header('Location: /profile.php');
            }
            exit;
        } else {
            $error = 'Неверный логин/телефон или пароль';
        }
    }
}

// ============ ОБРАБОТКА РЕГИСТРАЦИИ ============
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $login = trim($_POST['login'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$login || !$name || !$pass) {
        $error = 'Заполните логин, имя и пароль';
    } elseif (mb_strlen($pass) < 6) {
        $error = 'Пароль должен быть не короче 6 символов';
    } else {
        // Роль по умолчанию — user
        $roleId = $pdo->query("SELECT id FROM roles WHERE code = 'user'")->fetchColumn();
        if (!$roleId) {
            $error = 'Роль "user" не найдена в БД';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (login, phone, password_hash, name, role_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $login,
                    $phone ?: null,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $name,
                    $roleId,
                ]);

                // Автоматически логиним
                $_SESSION['user'] = [
                    'id'    => $pdo->lastInsertId(),
                    'login' => $login,
                    'name'  => $name,
                    'role'  => 'user',
                    'phone' => $phone,
                ];
                header('Location: /profile.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Логин или телефон уже заняты';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход и регистрация · Аист №1</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Ubuntu',system-ui,sans-serif;
  background:#fbf7f1;color:#2a2220;
  min-height:100vh;display:grid;place-items:center;
  padding:20px;
}
.card{
  background:#fff;padding:40px;border-radius:24px;
  box-shadow:0 20px 60px rgba(0,0,0,.12);
  width:100%;max-width:420px;
}
.logo{text-align:center;margin-bottom:24px}
.logo-mark{
  display:inline-grid;place-items:center;
  width:56px;height:56px;border-radius:16px;
  background:#c00020;color:#fff;font-size:26px;font-weight:700;
  box-shadow:0 8px 24px rgba(192,0,32,.3);margin-bottom:10px;
}
.logo h1{font-size:22px;color:#2a2220}
.logo p{font-size:13px;color:#6b6560;margin-top:4px}

.tabs{display:flex;gap:6px;background:#f0eae2;padding:6px;border-radius:14px;margin-bottom:24px}
.tabs button{
  flex:1;padding:10px;border:none;background:transparent;
  border-radius:10px;font-family:inherit;font-size:14px;font-weight:600;
  color:#6b6560;cursor:pointer;transition:.2s;
}
.tabs button.active{background:#fff;color:#c00020;box-shadow:0 2px 8px rgba(0,0,0,.08)}

.form-pane{display:none}
.form-pane.active{display:block}

label{display:block;font-size:13px;margin-bottom:6px;color:#2a2220;font-weight:500}
input{
  width:100%;padding:13px 16px;border:1px solid #ddd;border-radius:12px;
  font-size:15px;margin-bottom:14px;font-family:inherit;
}
input:focus{outline:none;border-color:#c00020;box-shadow:0 0 0 3px rgba(192,0,32,.1)}
button.submit{
  width:100%;padding:14px;border:none;border-radius:12px;
  background:#c00020;color:#fff;font-size:15px;font-weight:700;
  cursor:pointer;margin-top:6px;
}
button.submit:hover{background:#8a0017}

.err{background:#ffe0e4;color:#c00020;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px}

.back{display:block;text-align:center;margin-top:20px;font-size:13px;color:#6b6560}
.back a{color:#c00020;text-decoration:none;font-weight:600}
.back a:hover{text-decoration:underline}
</style>
</head>
<body>

<div class="card">
  <div class="logo">
    <div class="logo-mark">А</div>
    <h1>Аист №1</h1>
    <p>Вход и регистрация</p>
  </div>

  <div class="tabs">
    <button type="button" class="active" data-pane="login">Вход</button>
    <button type="button" data-pane="register">Регистрация</button>
  </div>

  <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- ========== ВХОД ========== -->
  <div class="form-pane active" id="pane-login">
    <form method="post">
      <input type="hidden" name="action" value="login">
      <label>Логин или телефон</label>
      <input name="login" required autofocus>
      <label>Пароль</label>
      <input name="password" type="password" required>
      <button type="submit" class="submit">Войти</button>
    </form>
  </div>

  <!-- ========== РЕГИСТРАЦИЯ ========== -->
  <div class="form-pane" id="pane-register">
    <form method="post">
      <input type="hidden" name="action" value="register">
      <label>Логин</label>
      <input name="login" required>
      <label>Телефон</label>
      <input name="phone" type="tel" placeholder="+7 (___) ___-__-__">
      <label>Имя</label>
      <input name="name" required>
      <label>Пароль (мин. 6 символов)</label>
      <input name="password" type="password" required minlength="6">
      <button type="submit" class="submit">Зарегистрироваться</button>
    </form>
  </div>

  <p class="back"><a href="/index.html">← На главную</a></p>
</div>

<script>
document.querySelectorAll('.tabs button').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.form-pane').forEach(p => p.classList.remove('active'));
    document.getElementById('pane-' + btn.dataset.pane).classList.add('active');
  });
});
</script>
</body>
</html>