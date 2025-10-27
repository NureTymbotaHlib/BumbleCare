<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Получаем данные
$current = trim($_POST['current_password'] ?? '');
$new = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

// Проверки
if (!$current || !$new || !$confirm) {
    echo json_encode(['success' => false, 'error' => 'Усі поля обовʼязкові']);
    exit;
}
if ($new !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'Новий пароль і підтвердження не співпадають']);
    exit;
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'error' => 'Пароль має бути не менше 6 символів']);
    exit;
}

// Проверяем текущий пароль
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($current, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Неправильний поточний пароль']);
    exit;
}

// Обновляем хэш
$new_hash = password_hash($new, PASSWORD_DEFAULT);
$update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$update->execute([$new_hash, $user_id]);

echo json_encode(['success' => true]);
?>
