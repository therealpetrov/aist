<?php
require __DIR__ . '/../config/auth.php';
require_login();
$u = current_user();
?>
<!DOCTYPE html>
<html lang="ru"><head>
<meta charset="UTF-8"><title>Админ-панель · Аист №1</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Ubuntu,system-ui,sans-serif;background:#fbf7f1;color:#2a2220}
.top{background:#2a2220;color:#fff;padding:16px 28px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:17px}
.top a{color:#ffc438;text-decoration:none;font-size:14px}
.wrap{max-width:1100px;margin:28px auto;padding:0 20px}
.tabs{display:flex;gap:8px;margin-bottom:20px}
.tabs button{padding:10px 20px;border:none;border-radius:12px;background:#fff;color:#2a2220;cursor:pointer;font-weight:600;font-size:14px}
.tabs button.active{background:#c00020;color:#fff}
.card{background:#fff;border-radius:20px;padding:24px;box-shadow:0 10px 40px rgba(42,34,32,.08)}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{padding:12px 10px;text-align:left;border-bottom:1px solid #f0eae2}
th{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6b6560}
select,input{padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit}
.btn{padding:8px 14px;border:none;border-radius:8px;background:#c00020;color:#fff;cursor:pointer;font-size:13px;font-weight:600}
.btn.ghost{background:#f0eae2;color:#2a2220}
.btn:hover{opacity:.9}
.add-form{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:20px}
.badge{padding:3px 10px;border-radius:50px;font-size:12px;font-weight:600}
.b-admin{background:#ffe0e4;color:#c00020}
.b-manager{background:#fff3d4;color:#8a6d00}
.b-user{background:#e6f0ff;color:#0044a0}
.status{padding:3px 10px;border-radius:50px;font-size:12px;background:#f0eae2}
.err{color:#c00020;font-size:13px}
</style></head><body>
<div class="top">
  <b>Админ-панель · Аист №1</b>
  <span><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>) ·
    <a href="/admin/logout.php">Выйти</a></span>
</div>
<div class="wrap">
  <div class="tabs">
    <?php if (in_array($u['role'], ['admin', 'manager'])): ?>
      <button data-tab="orders" class="active">Заказы</button>
    <?php endif; ?>
    
    <?php if ($u['role'] === 'admin'): ?>
      <button data-tab="users">Пользователи и роли</button>
    <?php endif; ?>
    
    <?php if ($u['role'] === 'admin' || $u['role'] === 'editor'): ?>
      <button data-tab="news" class="<?= $u['role'] === 'editor' ? 'active' : '' ?>">Новости</button>
    <?php endif; ?>
  </div>

  <div id="tab-orders" class="card" <?= !in_array($u['role'], ['admin', 'manager']) ? 'style="display:none"' : '' ?>>
    <h3 style="margin-bottom:16px">Заявки с сайта</h3>
    <table><thead><tr>
      <th>#</th><th>Дата</th><th>Имя</th><th>Телефон</th><th>Заказ / коммент</th><th>Статус</th>
    </tr></thead><tbody id="ordersBody"></tbody></table>
  </div>

  <?php if ($u['role'] === 'admin'): ?>
  <div id="tab-users" class="card" style="display:none">
    <h3 style="margin-bottom:16px">Пользователи</h3>
    <div class="add-form">
      <input id="nLogin" placeholder="Логин">
      <input id="nPhone" placeholder="Телефон">
      <input id="nName" placeholder="Имя">
      <input id="nPass" placeholder="Пароль" type="text">
      <select id="nRole"></select>
      <button class="btn" onclick="addUser()">Добавить</button>
    </div>
    <div id="userErr" class="err"></div>
    <table><thead><tr>
      <th>ID</th><th>Логин</th><th>Имя</th><th>Роль</th><th></th>
    </tr></thead><tbody id="usersBody"></tbody></table>
  </div>
  <?php endif; ?>
    <?php if ($u['role'] === 'admin' || $u['role'] === 'editor'): ?>
    <div id="tab-news" class="card" style="display:none">
    <h3 style="margin-bottom:16px">Управление новостями</h3>
    <div style="display:grid;grid-template-columns:1fr;gap:10px;margin-bottom:20px;">
      <!-- ДОБАВЛЕНО: скрытое поле для ID при редактировании -->
      <input type="hidden" id="newsId">
      
      <input id="newsTitle" placeholder="Заголовок новости" style="padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-family:inherit;">
      <textarea id="newsContent" placeholder="Текст новости" style="padding:10px;border:1px solid #ddd;border-radius:8px;font-family:inherit;min-height:100px;resize:vertical;"></textarea>
      
      <!-- Поле для фото -->
      <input type="file" id="newsImage" accept="image/*" style="padding:10px; border:1px solid #ddd; border-radius:8px; font-family:inherit;">
      
      <div style="display:flex; gap:10px;">
        <button class="btn" onclick="saveNews()" style="justify-self:start;">Опубликовать</button>
        <!-- ДОБАВЛЕНО: кнопка отмены редактирования (скрыта по умолчанию) -->
        <button class="btn ghost" id="cancelEditBtn" onclick="cancelEdit()" style="display:none;">Отменить редактирование</button>
      </div>
    </div>
    <table><thead><tr>
      <th>ID</th><th>Дата</th><th>Заголовок</th><th>Автор</th><th></th>
    </tr></thead>
    <tbody id="newsBody"></tbody></table>
  </div>
  <?php endif; ?>
</div>

<script>
const CURRENT_ID = <?= (int)$u['id'] ?>;
const IS_ADMIN = <?= $u['role'] === 'admin' ? 'true' : 'false' ?>;

document.querySelectorAll('.tabs button').forEach(b => {
  b.onclick = () => {
    document.querySelectorAll('.tabs button').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    ['orders','users','news'].forEach(t => {
      const el = document.getElementById('tab-'+t);
      if (el) el.style.display = (t === b.dataset.tab) ? 'block' : 'none';
    });
  };
});

async function api(url, opts = {}) {
  const r = await fetch(url, {
    headers: {'Content-Type':'application/json'},
    ...opts
  });
  return r.json();
}

// ---------- Заказы ----------
async function loadOrders() {
  const {orders} = await api('/admin/orders.php');
  document.getElementById('ordersBody').innerHTML = orders.map(o => `
    <tr>
      <td>${o.id}</td>
      <td>${o.created_at}</td>
      <td>${esc(o.name)}</td>
      <td>${esc(o.phone)}</td>
      <td>${esc(o.product || '')}${o.comment ? '<br><small>' + esc(o.comment) + '</small>' : ''}</td>
      <td>
        <select onchange="setStatus(${o.id}, this.value)">
          ${['new','processing','done','canceled'].map(s =>
            `<option value="${s}" ${s===o.status?'selected':''}>${statusName(s)}</option>`).join('')}
        </select>
      </td>
    </tr>`).join('') || '<tr><td colspan="6">Заказов пока нет</td></tr>';
}
function statusName(s){return {new:'Новый',processing:'В работе',done:'Выполнен',canceled:'Отменён'}[s]||s}
function esc(s){return (s||'').replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]))}

async function setStatus(id, status) {
  await api('/admin/orders.php?id='+id, {method:'PATCH', body: JSON.stringify({status})});
}

// ---------- Пользователи ----------
let ROLES = [];
async function loadUsers() {
  if (!IS_ADMIN) return;
  const data = await api('/admin/users.php');
  ROLES = data.roles;
  const sel = document.getElementById('nRole');
  if (sel) sel.innerHTML = ROLES.map(r => `<option value="${r.code}">${r.title}</option>`).join('');

  document.getElementById('usersBody').innerHTML = data.users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td>${esc(u.login)}</td>
      <td>${esc(u.name)}</td>
      <td>
        <select onchange="setRole(${u.id}, this.value)">
          ${ROLES.map(r => `<option value="${r.code}" ${r.code===u.role?'selected':''}>${r.title}</option>`).join('')}
        </select>
      </td>
      <td>${u.id === CURRENT_ID ? '<span class="status">это вы</span>' :
        `<button class="btn ghost" onclick="delUser(${u.id})">Удалить</button>`}</td>
    </tr>`).join('');
}

async function addUser() {
  const err = document.getElementById('userErr');
  err.textContent = '';
  const body = {
    login: document.getElementById('nLogin').value.trim(),
    phone: document.getElementById('nPhone').value.trim(), // ДОБАВЛЕНО: берем телефон
    name: document.getElementById('nName').value.trim(),
    password: document.getElementById('nPass').value,
    role: document.getElementById('nRole').value,
  };
  
  const r = await api('/admin/users.php', {method:'POST', body: JSON.stringify(body)});
  
  if (r.error) { 
    err.textContent = r.error; 
    return; 
  }
  
  // Очищаем поля после успешного добавления
  document.getElementById('nLogin').value = '';
  document.getElementById('nPhone').value = ''; // ДОБАВЛЕНО: очищаем телефон
  document.getElementById('nName').value = '';
  document.getElementById('nPass').value = '';
  
  loadUsers();
}

async function setRole(id, role) {
  await api('/admin/users.php?id='+id, {method:'PATCH', body: JSON.stringify({role})});
}
async function delUser(id) {
  if (!confirm('Удалить пользователя?')) return;
  const r = await api('/admin/users.php?id='+id, {method:'DELETE'});
  if (r.error) alert(r.error);
  loadUsers();
}

loadOrders();
loadUsers();

// ---------- Новости ----------
async function loadNews() {
  if (!document.getElementById('newsBody')) return; 
  
  const data = await api('/admin/news.php');
  if (!data.news) return;

  document.getElementById('newsBody').innerHTML = data.news.map(n => `
    <tr>
      <td>${n.id}</td>
      <td>${new Date(n.created_at).toLocaleDateString('ru-RU')}</td>
      <td>${esc(n.title)}</td>
      <td>${esc(n.author_name || '—')}</td>
      <td>
        <!-- ДОБАВЛЕНО: Кнопка редактирования -->
        <button class="btn ghost" onclick="editNews(${n.id})" style="margin-right:5px;">Ред.</button>
        <button class="btn ghost" onclick="delNews(${n.id})">Удалить</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="5">Новостей пока нет</td></tr>';
}

// ДОБАВЛЕНО: Функция заполнения формы для редактирования
async function editNews(id) {
  // Получаем все новости, чтобы найти нужную
  const data = await api('/admin/news.php');
  const newsItem = data.news.find(n => n.id === id);
  
  if (newsItem) {
    document.getElementById('newsId').value = newsItem.id;
    document.getElementById('newsTitle').value = newsItem.title;
    document.getElementById('newsContent').value = newsItem.content;
    
    // Меняем кнопки
    document.querySelector('button[onclick="saveNews()"]').textContent = 'Сохранить изменения';
    document.getElementById('cancelEditBtn').style.display = 'inline-block';
    
    // Прокручиваем к форме
    document.getElementById('tab-news').scrollIntoView({ behavior: 'smooth' });
  }
}

// ДОБАВЛЕНО: Функция отмены редактирования
function cancelEdit() {
  document.getElementById('newsId').value = '';
  document.getElementById('newsTitle').value = '';
  document.getElementById('newsContent').value = '';
  document.getElementById('newsImage').value = '';
  document.querySelector('button[onclick="saveNews()"]').textContent = 'Опубликовать';
  document.getElementById('cancelEditBtn').style.display = 'none';
}

// ОБНОВЛЕНО: Функция сохранения (теперь и создает, и обновляет)
async function saveNews() {
  const id = document.getElementById('newsId').value;
  const title = document.getElementById('newsTitle').value.trim();
  const content = document.getElementById('newsContent').value.trim();
  const imageFile = document.getElementById('newsImage').files[0];
  
  if (!title || !content) return alert('Заполните заголовок и текст');
  
  const formData = new FormData();
  formData.append('title', title);
  formData.append('content', content);
  if (imageFile) formData.append('image', imageFile);
  
  // Если есть ID, значит это обновление (PUT), иначе создание (POST)
  const method = id ? 'POST' : 'POST'; // PHP обычно не парсит PUT с FormData, поэтому используем POST с флагом _method
  
  if (id) {
    formData.append('_method', 'PUT'); // Эмулируем PUT
    formData.append('id', id);
  }

  const r = await fetch('/admin/news.php', {
    method: 'POST', // Всегда отправляем POST, а PHP разберется по _method
    body: formData
  });
  const data = await r.json();
  
  if (data.error) return alert(data.error);
  
  cancelEdit(); // Очищаем поля и возвращаем кнопки
  loadNews();
}

async function delNews(id) {
  if (!confirm('Удалить новость?')) return;
  const r = await api('/admin/news.php?id='+id, {method:'DELETE'});
  if (r.error) alert(r.error);
  loadNews();
}

// Вызываем загрузку при старте
loadNews();
</script>
</body></html>