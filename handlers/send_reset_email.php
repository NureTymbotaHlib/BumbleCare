<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/send_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status' => 'error']);
  exit;
}

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
  echo json_encode(['status' => 'error']);
  exit;
}

$stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo json_encode(['status' => 'not_found']);
  exit;
}

$token = bin2hex(random_bytes(32));
$expires = (new DateTime('now', new DateTimeZone('Europe/Kyiv')))
  ->add(new DateInterval('PT30M'))
  ->format('Y-m-d H:i:s');

$stmt = $pdo->prepare("
  INSERT INTO password_resets (user_id, token, expires_at)
  VALUES (?, ?, ?)
");
$stmt->execute([$user['user_id'], $token, $expires]);

$resetLink = "http://localhost/BumbleCare/pages/reset_password.php?token=" . urlencode($token);

$subject = "Відновлення паролю | BumbleCare";

$html = "
  <h2>Привіт, {$user['full_name']}!</h2>
  <p>Ви запросили відновлення паролю у системі <b>BumbleCare</b>.</p>
  <p>Для скидання паролю перейдіть за посиланням нижче (дійсне 30 хвилин):</p>
  <p><a href='{$resetLink}'style='background:#668AD2;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;'>Скинути пароль</a></p>
  <p>Якщо ви не надсилали цей запит — просто ігноруйте повідомлення.</p>

  <hr>
  <small>© " . date('Y') . " BumbleCare</small>
";

$sent = sendEmail($email, $user['full_name'], $subject, $html);

if (!$sent) {
  echo json_encode(['status' => 'error']);
  exit;
}

echo json_encode(['status' => 'success']);
