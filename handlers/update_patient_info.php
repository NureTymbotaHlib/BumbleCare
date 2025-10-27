<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($user_role !== 'patient') {
    echo json_encode(['success' => false, 'error' => 'Доступ заборонено']);
    exit;
}

$fields = [
    'phone_number' => trim($_POST['phone_number'] ?? ''),
    'gender' => trim($_POST['gender'] ?? ''),
    'identification_code' => trim($_POST['identification_code'] ?? ''),
    'social_status' => trim($_POST['social_status'] ?? ''),
    'insurance_number' => trim($_POST['insurance_number'] ?? ''),
    'city' => trim($_POST['city'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
    'medical_card' => trim($_POST['medical_card'] ?? ''),
    'date_of_birth' => trim($_POST['date_of_birth'] ?? '')
];

$errors = [];

if (!empty($fields['phone_number']) && !preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $fields['phone_number'])) {
    $errors[] = "Некоректний номер телефону";
}

if (!empty($fields['identification_code']) && !preg_match('/^[0-9]{8,12}$/', $fields['identification_code'])) {
    $errors[] = "Ідентифікаційний код повинен містити лише цифри (8–12)";
}

if (!empty($fields['insurance_number']) && !preg_match('/^[A-Za-z0-9]{5,20}$/', $fields['insurance_number'])) {
    $errors[] = "Номер страхового полісу повинен містити лише цифри та літери";
}

if (!empty($fields['date_of_birth'])) {
    $date = strtotime($fields['date_of_birth']);
    if (!$date || $date > time()) {
        $errors[] = "Некоректна дата народження";
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode('. ', $errors)]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE users SET phone_number = ? WHERE user_id = ?");
    $stmt->execute([$fields['phone_number'], $user_id]);

    $check = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $check->execute([$user_id]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE patients SET 
                gender = ?, 
                identification_code = ?, 
                social_status = ?, 
                insurance_number = ?, 
                city = ?, 
                address = ?, 
                medical_card = ?, 
                date_of_birth = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $fields['gender'],
            $fields['identification_code'],
            $fields['social_status'],
            $fields['insurance_number'],
            $fields['city'],
            $fields['address'],
            $fields['medical_card'],
            $fields['date_of_birth'],
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO patients 
            (user_id, gender, identification_code, social_status, insurance_number, city, address, medical_card, date_of_birth)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $fields['gender'],
            $fields['identification_code'],
            $fields['social_status'],
            $fields['insurance_number'],
            $fields['city'],
            $fields['address'],
            $fields['medical_card'],
            $fields['date_of_birth']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Помилка при збереженні: ' . $e->getMessage()]);
}
?>
