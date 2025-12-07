<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json');

if ($user_role !== 'clinic_admin') {
  echo json_encode(['success' => false, 'error' => 'Access denied']);
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
  echo json_encode(['success' => false, 'error' => 'Clinic not found']);
  exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update') {

  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');

  $stmt = $pdo->prepare("
    UPDATE clinics
    SET name = ?, description = ?, city = ?, address = ?, phone = ?, email = ?
    WHERE clinic_id = ?
  ");

  $ok = $stmt->execute([
    $name,
    $description,
    $city,
    $address,
    $phone,
    $email,
    $clinic_id
  ]);

  echo json_encode(['success' => $ok]);
  exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
exit;
