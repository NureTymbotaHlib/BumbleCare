<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!$auth || $auth['role'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Доступ дозволено лише пацієнтам.']);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$doctor_id      = $_POST['doctor_id'] ?? null;
$rating         = $_POST['rating'] ?? null;
$comment        = trim($_POST['comment'] ?? '');

if (!$appointment_id || !$doctor_id || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Не всі поля заповнені.']);
    exit;
}

$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Не вдалося знайти пацієнта.']);
    exit;
}

$stmt = $pdo->prepare("SELECT doctor_id, status FROM appointments WHERE appointment_id = ? AND patient_id = ?");
$stmt->execute([$appointment_id, $patient_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) {
    echo json_encode(['success' => false, 'message' => 'Цей прийом не належить вашому обліковому запису.']);
    exit;
}

if ($appt['status'] !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'Ви можете залишити відгук лише після завершення прийому.']);
    exit;
}

$stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE appointment_id = ?");
$stmt->execute([$appointment_id]);
if ($stmt->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'Відгук для цього прийому вже існує.']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO reviews (appointment_id, patient_id, doctor_id, rating, comment, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$success = $stmt->execute([$appointment_id, $patient_id, $doctor_id, $rating, $comment]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Не вдалося додати відгук.']);
}
