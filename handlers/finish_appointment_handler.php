<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode(["error" => "invalid_method"]);
  exit;
}

$appointment_id = $_POST["appointment_id"] ?? null;
$doctor_comment = trim($_POST["doctor_comment"] ?? '');
$treatment_program = trim($_POST["treatment_program"] ?? '');
$follow_up = trim($_POST["follow_up_recommendation"] ?? '');

if (!$appointment_id) {
  echo json_encode(["error" => "missing_id"]);
  exit;
}

if ($doctor_comment === '' || $treatment_program === '' || $follow_up === '') {
  echo json_encode(["error" => "empty_fields"]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT a.appointment_id
  FROM appointments a
  JOIN doctors d ON a.doctor_id = d.doctor_id
  WHERE a.appointment_id = ?
  AND d.user_id = ?
  AND a.status = 'booked'
");
$stmt->execute([$appointment_id, $user_id]);

if (!$stmt->fetch()) {
  echo json_encode(["error" => "access_denied"]);
  exit;
}

$update = $pdo->prepare("
  UPDATE appointments
  SET 
    doctor_comment = ?,
    treatment_program = ?,
    follow_up_recommendation = ?,
    status = 'completed'
  WHERE appointment_id = ?
");

$update->execute([
  $doctor_comment,
  $treatment_program,
  $follow_up,
  $appointment_id
]);

echo json_encode(["success" => true]);
exit;
