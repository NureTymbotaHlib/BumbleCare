<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($user_role !== 'super_admin') {
    echo json_encode(['success' => false, 'error' => 'Доступ заборонено']);
    exit;
}

$phone = trim($_POST['phone_number'] ?? '');
$errors = [];

if ($phone !== '' && !preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $phone)) {
    $errors[] = "Некоректний номер телефону";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode('. ', $errors)]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET phone_number = ? WHERE user_id = ?");
    $stmt->execute([$phone, $user_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Помилка при збереженні: ' . $e->getMessage()]);
}
