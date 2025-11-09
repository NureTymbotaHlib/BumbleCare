<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';
header('Content-Type: application/json');

if (!$isLoggedIn || $user_role !== 'patient') {
  echo json_encode(['error' => 'Доступ заборонено']);
  exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null;

if ($action === 'cancel' && $id) {
  $stmt = $pdo->prepare("
    UPDATE appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    SET a.status = 'cancelled'
    WHERE a.appointment_id = ? AND p.user_id = ?
  ");
  $stmt->execute([$id, $user_id]);
  echo json_encode(['success' => true]);
  exit;
}

echo json_encode(['error' => 'Невідома дія']);
