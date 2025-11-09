<?php
require_once __DIR__ . '/../includes/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$token   = trim($_POST['token'] ?? '');
$new     = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

if (!$token || !$new || !$confirm) {
  echo json_encode(['success' => false, 'error' => 'Усі поля обовʼязкові']);
  exit;
}

if ($new !== $confirm) {
  echo json_encode(['success' => false, 'error' => 'Новий пароль і підтвердження не співпадають']);
  exit;
}

if (mb_strlen($new, 'UTF-8') < 6) {
  echo json_encode(['success' => false, 'error' => 'Пароль має бути не менше 6 символів']);
  exit;
}

$stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
  echo json_encode(['success' => false, 'error' => 'Недійсний або використаний токен']);
  exit;
}

$currentTime = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
$expireTime  = new DateTime($reset['expires_at'], new DateTimeZone('Europe/Kyiv'));

if ($expireTime < $currentTime) {
  echo json_encode(['success' => false, 'error' => 'Посилання прострочене']);
  exit;
}

$new_hash = password_hash($new, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$new_hash, $reset['user_id']]);

$pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

echo json_encode(['success' => true]);
