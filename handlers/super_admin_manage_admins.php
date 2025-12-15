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

function clinicExists($pdo, $clinic_id) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM clinics WHERE clinic_id = ?");
  $stmt->execute([$clinic_id]);
  return $stmt->fetchColumn() > 0;
}

function adminExists($pdo, $admin_id) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM clinic_admins WHERE admin_id = ?");
  $stmt->execute([$admin_id]);
  return $stmt->fetchColumn() > 0;
}

if ($action === 'add') {
  $full = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $pw = trim($_POST['password'] ?? '');
  $conf = trim($_POST['confirm_password'] ?? '');
  $clinic_id = trim($_POST['clinic_id'] ?? '');

  if (!$full || !$email || !$phone || !$pw || !$conf || !$clinic_id) {
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

  if (!clinicExists($pdo, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Клініка не знайдена']);
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
    VALUES (?, ?, ?, ?, 'clinic_admin', 'active', ?)
  ");
  $stmt->execute([$full, $email, $hash, $phone, $img]);

  $user_id = $pdo->lastInsertId();

  $stmt = $pdo->prepare("
    INSERT INTO clinic_admins (user_id, clinic_id)
    VALUES (?, ?)
  ");
  $stmt->execute([$user_id, $clinic_id]);

  $admin_id = $pdo->lastInsertId();

  $c = $pdo->prepare("SELECT name FROM clinics WHERE clinic_id = ?");
  $c->execute([$clinic_id]);
  $clinic_name = $c->fetchColumn();

  echo json_encode([
    'status' => 'success',
    'admin_id' => $admin_id,
    'clinic_name' => $clinic_name,
    'message' => 'Адміністратора додано'
  ]);
  exit;
}

if ($action === 'get_admin') {
  $admin_id = $_POST['admin_id'] ?? null;

  if (!$admin_id || !adminExists($pdo, $admin_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратора не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.full_name, u.email, u.phone_number AS phone, u.status, ca.clinic_id
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    WHERE ca.admin_id = ?
  ");
  $stmt->execute([$admin_id]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$admin || $admin['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратора деактивовано']);
    exit;
  }

  echo json_encode(['status' => 'success', 'admin' => $admin]);
  exit;
}

if ($action === 'edit') {
  $admin_id = $_POST['admin_id'] ?? null;

  if (!$admin_id || !adminExists($pdo, $admin_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратора не знайдено']);
    exit;
  }

  $full = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $clinic_id = trim($_POST['clinic_id'] ?? '');

  if (!$full || !$email || !$phone || !$clinic_id) {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if (!clinicExists($pdo, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Клініка не знайдена']);
    exit;
  }

	if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
	}

  $stmt = $pdo->prepare("SELECT user_id FROM clinic_admins WHERE admin_id = ?");
  $stmt->execute([$admin_id]);
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

  $stmt = $pdo->prepare("
    UPDATE clinic_admins
    SET clinic_id = ?
    WHERE admin_id = ?
  ");
  $stmt->execute([$clinic_id, $admin_id]);

  echo json_encode(['status' => 'success', 'message' => 'Дані адміністратора оновлено']);
  exit;
}

if ($action === 'deactivate') {
  $admin_id = $_POST['admin_id'] ?? null;

  if (!$admin_id || !adminExists($pdo, $admin_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратора не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.user_id, u.status
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    WHERE ca.admin_id = ?
  ");
  $stmt->execute([$admin_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка роботи з користувачем']);
    exit;
  }

  if ($row['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратор вже деактивований']);
    exit;
  }

  $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
  $stmt->execute([$row['user_id']]);

  echo json_encode(['status' => 'success', 'message' => 'Адміністратора деактивовано']);
  exit;
}

if ($action === 'activate') {
  $admin_id = $_POST['admin_id'] ?? null;

  if (!$admin_id || !adminExists($pdo, $admin_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратора не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.user_id, u.status
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    WHERE ca.admin_id = ?
  ");
  $stmt->execute([$admin_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка користувача']);
    exit;
  }

  if ($row['status'] === 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Адміністратор вже активний']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE users
    SET status = 'active'
    WHERE user_id = ?
  ");
  $stmt->execute([$row['user_id']]);

  echo json_encode(['status' => 'success', 'message' => 'Адміністратора активовано']);
  exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
exit;
