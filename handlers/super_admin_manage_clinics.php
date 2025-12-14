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

function handleClinicPhotoUpload(PDO $pdo, int $clinic_id): ?string {
  if (
    !isset($_FILES['clinic_photo']) ||
    $_FILES['clinic_photo']['error'] !== UPLOAD_ERR_OK
  ) {
    return null;
  }

  $uploadDir = __DIR__ . '/../assets/images/clinics/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  $file = $_FILES['clinic_photo'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png'];

  if (!in_array($ext, $allowed, true)) {
    return null;
  }

  $stmt = $pdo->prepare("SELECT image_url FROM clinics WHERE clinic_id = ?");
  $stmt->execute([$clinic_id]);
  $oldPath = $stmt->fetchColumn();

	if ($oldPath && strpos($oldPath, 'default_clinic') === false) {
		$oldFile = $uploadDir . basename($oldPath);
		if (file_exists($oldFile)) {
			unlink($oldFile);
		}
	}

  $fileName = "clinic_{$clinic_id}_" . time() . "." . $ext;
  $targetPath = $uploadDir . $fileName;

  if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    return null;
  }

  return "/BumbleCare/assets/images/clinics/" . $fileName;
}

function clinicExists($pdo, $clinic_id) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM clinics WHERE clinic_id = ?");
  $stmt->execute([$clinic_id]);
  return $stmt->fetchColumn() > 0;
}

if ($action === 'add') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');

  if (!$name || !$city || !$address || !$phone || !$email) {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  $check = $pdo->prepare("SELECT clinic_id FROM clinics WHERE email = ?");
  $check->execute([$email]);
  if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Клініка з таким email вже існує']);
    exit;
  }

  $stmt = $pdo->prepare("
    INSERT INTO clinics (name, description, city, address, phone, email)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
	$stmt->execute([$name, $description, $city, $address, $phone, $email]);

	$clinic_id = (int)$pdo->lastInsertId();

	$photoPath = handleClinicPhotoUpload($pdo, $clinic_id);
	if ($photoPath) {
		$upd = $pdo->prepare("
			UPDATE clinics
			SET image_url = ?
			WHERE clinic_id = ?
		");
		$upd->execute([$photoPath, $clinic_id]);
	}

  echo json_encode([
    'status' => 'success',
    'clinic_id' => $clinic_id,
    'name' => $name,
    'message' => 'Клініку додано'
  ]);
  exit;
}

if ($action === 'get') {
  $clinic_id = $_POST['clinic_id'] ?? null;

  if (!$clinic_id || !clinicExists($pdo, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Клініку не знайдено']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT clinic_id, name, description, city, address, phone, email, image_url
    FROM clinics
    WHERE clinic_id = ?
  ");
  $stmt->execute([$clinic_id]);
  $clinic = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$clinic) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка отримання даних']);
    exit;
  }

  echo json_encode([
    'status' => 'success',
    'clinic' => $clinic
  ]);
  exit;
}

if ($action === 'edit') {
  $clinic_id = $_POST['clinic_id'] ?? null;

  if (!$clinic_id || !clinicExists($pdo, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Клініку не знайдено']);
    exit;
  }

  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');

  if (!$name || !$city || !$address || !$phone || !$email) {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  $check = $pdo->prepare("
    SELECT clinic_id
    FROM clinics
    WHERE email = ? AND clinic_id <> ?
  ");
  $check->execute([$email, $clinic_id]);
  if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Email вже використовується іншою клінікою']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE clinics
    SET name = ?, description = ?, city = ?, address = ?, phone = ?, email = ?
    WHERE clinic_id = ?
  ");
  $stmt->execute([$name, $description, $city, $address, $phone, $email, $clinic_id]);

	$photoPath = handleClinicPhotoUpload($pdo, (int)$clinic_id);
	if ($photoPath) {
		$upd = $pdo->prepare("
			UPDATE clinics
			SET image_url = ?
			WHERE clinic_id = ?
		");
		$upd->execute([$photoPath, $clinic_id]);
	}

  echo json_encode([
    'status' => 'success',
    'message' => 'Дані клініки оновлено'
  ]);
  exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
exit;
