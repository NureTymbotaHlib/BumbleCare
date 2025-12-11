<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

if (!isset($_SESSION['2fa_user_id'])) {
  echo json_encode(['success' => false, 'error' => 'Сесія завершена. Увійдіть знову.']);
  exit;
}

$user_id = $_SESSION['2fa_user_id'];
$code = trim($_POST['code'] ?? '');

if (!$code) {
  echo json_encode(['success' => false, 'error' => 'Введіть код']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT code, expires_at
  FROM two_factor_codes
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 1
");
$stmt->execute([$user_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record || !password_verify($code, $record['code'])) {
  echo json_encode(['success' => false, 'error' => 'Невірний код']);
  exit;
}

$currentTime = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
$expireTime = new DateTime($record['expires_at'], new DateTimeZone('Europe/Kyiv'));

if ($expireTime < $currentTime) {
  echo json_encode(['success' => false, 'error' => 'Код прострочений']);
  exit;
}

$stmt2 = $pdo->prepare("
  SELECT role
  FROM users
  WHERE user_id = ?
");
$stmt2->execute([$user_id]);
$user = $stmt2->fetch(PDO::FETCH_ASSOC);

$token = generate_jwt([
  'user_id' => $user_id,
  'role' => $user['role']
]);

setcookie('access_token', $token, [
  'expires' => time() + 7 * 24 * 60 * 60,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax'
]);

$pdo->prepare("
  DELETE FROM two_factor_codes
  WHERE user_id = ?
")->execute([$user_id]);

unset($_SESSION['2fa_user_id']);

echo json_encode([
  'success' => true,
  'redirect' => ($user['role'] === 'super_admin')
    ? '/BumbleCare/pages/super_admin_profile.php'
    : '/BumbleCare/pages/clinic_admin_profile.php'
]);

exit;
