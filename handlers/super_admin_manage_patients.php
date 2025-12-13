<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json');

if ($user_role !== 'super_admin') {
  echo json_encode(['status' => 'error', 'message' => 'Access denied']);
  exit;
}

$action = $_POST['action'] ?? '';

if (!$action) {
  echo json_encode(['status' => 'error', 'message' => 'Missing action']);
  exit;
}

function patientExists($pdo, $patient_id) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE patient_id = ?");
  $stmt->execute([$patient_id]);
  return $stmt->fetchColumn() > 0;
}

if ($action === 'add') {
  $full = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $pw = trim($_POST['password'] ?? '');
  $conf = trim($_POST['confirm_password'] ?? '');

  if (!$full || !$email || !$phone || !$pw || !$conf) {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if ($pw !== $conf) {
    echo json_encode(['status' => 'error', 'message' => 'Паролі не співпадають']);
    exit;
  }

  if (mb_strlen($pw, 'UTF-8') < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Пароль має бути не менше 6 символів']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
  $chk->execute([$email]);
  if ($chk->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Користувач з такою поштою вже існує']);
    exit;
  }

  $hash = password_hash($pw, PASSWORD_DEFAULT);
  $img = '/BumbleCare/assets/images/default_avatar.png';

  $stmt = $pdo->prepare("
    INSERT INTO users (full_name, email, password_hash, phone_number, role, status, profile_image)
    VALUES (?, ?, ?, ?, 'patient', 'active', ?)
  ");
  $stmt->execute([$full, $email, $hash, $phone, $img]);

  $user_id = $pdo->lastInsertId();

  $stmt = $pdo->prepare("
    INSERT INTO patients (user_id)
    VALUES (?)
  ");
  $stmt->execute([$user_id]);

  $patient_id = $pdo->lastInsertId();

  echo json_encode([
    'status' => 'success',
    'patient_id' => $patient_id,
    'full_name' => $full,
    'message' => 'Пацієнта додано'
  ]);
  exit;
}

if ($action === 'get_patient') {
  $patient_id = $_POST['patient_id'] ?? null;

  if (!$patient_id || !patientExists($pdo, $patient_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Пацієнта не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.full_name, u.email, u.phone_number AS phone, u.status
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.patient_id = ?
  ");
  $stmt->execute([$patient_id]);
  $patient = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$patient || $patient['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Пацієнта деактивовано']);
    exit;
  }

  echo json_encode(['status' => 'success', 'patient' => $patient]);
  exit;
}

if ($action === 'edit') {
  $patient_id = $_POST['patient_id'] ?? null;

  if (!$patient_id || !patientExists($pdo, $patient_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Пацієнта не знайдено']);
    exit;
  }

  $full = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  if (!$full || !$email || !$phone) {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT user_id
    FROM patients
    WHERE patient_id = ?
  ");
  $stmt->execute([$patient_id]);
  $user_id = $stmt->fetchColumn();

  if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка отримання користувача']);
    exit;
  }

  $check = $pdo->prepare("
    SELECT user_id
    FROM users
    WHERE email = ? AND user_id <> ?
  ");
  $check->execute([$email, $user_id]);

  if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Email вже використовується іншим користувачем']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE users
    SET full_name = ?, email = ?, phone_number = ?
    WHERE user_id = ?
  ");
  $stmt->execute([$full, $email, $phone, $user_id]);

  echo json_encode(['status' => 'success', 'message' => 'Дані пацієнта оновлено']);
  exit;
}

if ($action === 'deactivate') {
  $patient_id = $_POST['patient_id'] ?? null;

  if (!$patient_id || !patientExists($pdo, $patient_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Пацієнта не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.user_id, u.status
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.patient_id = ?
  ");
  $stmt->execute([$patient_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка роботи з користувачем']);
    exit;
  }

  if ($row['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Пацієнт вже деактивований']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE users
    SET status = 'inactive'
    WHERE user_id = ?
  ");
  $stmt->execute([$row['user_id']]);

  echo json_encode(['status' => 'success', 'message' => 'Пацієнта деактивовано']);
  exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
exit;
