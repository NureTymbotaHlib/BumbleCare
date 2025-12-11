<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json');

if ($user_role !== 'clinic_admin') {
  echo json_encode(['status' => 'error', 'message' => 'Access denied']);
  exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if (!$action) {
  echo json_encode(['status' => 'error', 'message' => 'Missing action']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT clinic_id
  FROM clinic_admins
  WHERE user_id = ?
");
$stmt->execute([$user_id]);
$clinic_id = $stmt->fetchColumn();

if (!$clinic_id) {
  echo json_encode(['status' => 'error', 'message' => 'Clinic not found']);
  exit;
}

function isDoctorFromClinic($pdo, $doctor_id, $clinic_id) {
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM doctors
    WHERE doctor_id = ? AND clinic_id = ?
  ");
  $stmt->execute([$doctor_id, $clinic_id]);
  return $stmt->fetchColumn() > 0;
}

if ($action === 'add') {

  $full_name = trim($_POST['full_name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $phone     = trim($_POST['phone'] ?? '');
  $password  = trim($_POST['password'] ?? '');
  $confirm   = trim($_POST['confirm_password'] ?? '');

  if ($full_name === '' || $email === '' || $phone === '' || $password === '' || $confirm === '') {
    echo json_encode(['status' => 'error', 'message' => 'Усі поля обовʼязкові']);
    exit;
  }

  if ($password !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'Паролі не співпадають']);
    exit;
  }

  if (mb_strlen($password, 'UTF-8') < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Пароль має бути не менше 6 символів']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
  $check->execute([$email]);
  if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Користувач з такою поштою вже існує']);
    exit;
  }

  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $default_img   = '/BumbleCare/assets/images/default_avatar.png';

  $stmt = $pdo->prepare("
    INSERT INTO users (full_name, email, password_hash, phone_number, role, status, profile_image)
    VALUES (?, ?, ?, ?, 'doctor', 'active', ?)
  ");
  $stmt->execute([$full_name, $email, $password_hash, $phone, $default_img]);

  $new_user_id = $pdo->lastInsertId();

  $stmt = $pdo->prepare("
    INSERT INTO doctors (user_id, clinic_id)
    VALUES (?, ?)
  ");
  $stmt->execute([$new_user_id, $clinic_id]);

  $new_doctor_id = $pdo->lastInsertId();

  echo json_encode(['status' => 'success','message' => 'Лікаря успішно додано','doctor_id' => $new_doctor_id]);
  exit;
}

if ($action === 'edit') {

  $doctor_id = $_POST['doctor_id'] ?? null;

  if (!$doctor_id || !isDoctorFromClinic($pdo, $doctor_id, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Лікар не знайдений або не належить вашій клініці']);
    exit;
  }

  $statusCheck = $pdo->prepare("
    SELECT u.status
    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.doctor_id = ?
  ");
  $statusCheck->execute([$doctor_id]);
  if ($statusCheck->fetchColumn() !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Лікар деактивований']);
    exit;
  }

  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $specialty = trim($_POST['specialty'] ?? '');
  $education = trim($_POST['education'] ?? '');
  $experience = trim($_POST['experience'] ?? '');
  $license_number = trim($_POST['license_number'] ?? '');
  $certification = trim($_POST['certification'] ?? '');
  $gender = trim($_POST['gender'] ?? '');
  $date_of_birth = trim($_POST['date_of_birth'] ?? '');
  $id_code = trim($_POST['id_code'] ?? '');
  $about = trim($_POST['about'] ?? '');

  $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE doctor_id = ?");
  $stmt->execute([$doctor_id]);
  $doctor_user_id = $stmt->fetchColumn();

  $check = $pdo->prepare("
    SELECT user_id 
    FROM users 
    WHERE email = ? AND user_id <> ?
  ");
  $check->execute([$email, $doctor_user_id]);

  if ($check->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Еmail вже використовується іншим користувачем']);
    exit;
  }

  if (!preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Некоректний номер телефону']);
    exit;
  }

  if (!$doctor_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка при оновленні']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE users
    SET full_name = ?, email = ?, phone_number = ?
    WHERE user_id = ?
  ");
  $stmt->execute([$full_name, $email, $phone, $doctor_user_id]);

  $stmt = $pdo->prepare("
    UPDATE doctors
    SET specialty = ?, 
      education = ?, 
      experience_years = ?,
      license_number = ?,
      certification = ?,
      gender = ?,
      date_of_birth = ?,
      id_code = ?,
      about = ?
    WHERE doctor_id = ?
  ");

  $stmt->execute([$specialty, $education, (int)$experience, $license_number, $certification, $gender, $date_of_birth, $id_code, $about, $doctor_id]);

  echo json_encode(['status' => 'success', 'message' => 'Дані лікаря оновлено']);
  exit;
}

if ($action === 'deactivate') {

  $doctor_id = $_POST['doctor_id'] ?? null;

  if (!$doctor_id || !isDoctorFromClinic($pdo, $doctor_id, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Лікар не знайдений або не належить вашій клініці']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT u.user_id, u.status
    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.doctor_id = ?
  ");
  $stmt->execute([$doctor_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Помилка при зміні статусу']);
    exit;
  }

  if ($row['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Лікар вже деактивований']);
    exit;
  }

  $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?")
      ->execute([$row['user_id']]);

  echo json_encode(['status' => 'success','message' => 'Лікаря деактивовано','doctor_id' => $doctor_id]);
  exit;
}

if ($action === 'get_doctor') {

  $doctor_id = $_POST['doctor_id'] ?? null;

  if (!$doctor_id || !isDoctorFromClinic($pdo, $doctor_id, $clinic_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Лікар не знайдений']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT 
      u.full_name,
      u.email,
      u.phone_number AS phone,
      u.status,
      d.specialty,
      d.education,
      d.experience_years AS experience,
      d.license_number,
      d.certification,
      d.gender,
      d.date_of_birth,
      d.id_code,
      d.about

    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    WHERE d.doctor_id = ?
  ");
  $stmt->execute([$doctor_id]);
  $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$doctor || $doctor['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'Лікар деактивований']);
    exit;
  }

  echo json_encode([
    'status' => 'success',
    'doctor' => $doctor
  ]);
  exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
exit;
