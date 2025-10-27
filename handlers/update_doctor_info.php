<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($user_role !== 'doctor') {
    echo json_encode(['success' => false, 'error' => 'Доступ заборонено']);
    exit;
}

$fields = [
    'clinic_id' => isset($_POST['clinic_id']) && $_POST['clinic_id'] !== '' ? (int)$_POST['clinic_id'] : null,
    'specialty' => trim($_POST['specialty'] ?? ''),
    'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
    'phone_number' => trim($_POST['phone_number'] ?? ''),
    'experience_years' => trim($_POST['experience_years'] ?? ''),
    'id_code' => trim($_POST['id_code'] ?? ''),
    'certification' => trim($_POST['certification'] ?? ''),
    'education' => trim($_POST['education'] ?? ''),
    'gender' => trim($_POST['gender'] ?? ''),
    'about' => trim($_POST['about'] ?? '')
];

$errors = [];

if (!empty($fields['phone_number']) && !preg_match('/^[0-9\-\+\s\(\)]{7,20}$/', $fields['phone_number'])) {
    $errors[] = "Некоректний номер телефону";
}

if (!empty($fields['id_code']) && !preg_match('/^[0-9]{8,12}$/', $fields['id_code'])) {
    $errors[] = "Ідентифікаційний код повинен містити лише цифри (8–12)";
}

if (!empty($fields['date_of_birth'])) {
    $date = strtotime($fields['date_of_birth']);
    if (!$date || $date > time()) {
        $errors[] = "Некоректна дата народження";
    }
}

if ($fields['experience_years'] !== '') {
    if (!ctype_digit($fields['experience_years']) || (int)$fields['experience_years'] < 0 || (int)$fields['experience_years'] > 80) {
        $errors[] = "Стаж повинен бути числом від 0 до 80";
    } else {
        $fields['experience_years'] = (int)$fields['experience_years'];
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

    $check = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $check->execute([$user_id]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE doctors SET
                clinic_id = ?,
                specialty = ?,
                experience_years = ?,
                certification = ?,
                education = ?,
                gender = ?,
                date_of_birth = ?,
                id_code = ?,
                about = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $fields['clinic_id'],
            $fields['specialty'],
            $fields['experience_years'],
            $fields['certification'],
            $fields['education'],
            $fields['gender'],
            $fields['date_of_birth'],
            $fields['id_code'],
            $fields['about'],
            $user_id
        ]);

    } else {
        $stmt = $pdo->prepare("
            INSERT INTO doctors
                (user_id, clinic_id, specialty, experience_years, certification, education, gender, date_of_birth, id_code, about)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $fields['clinic_id'],
            $fields['specialty'],
            $fields['experience_years'],
            $fields['certification'],
            $fields['education'],
            $fields['gender'],
            $fields['date_of_birth'],
            $fields['id_code'],
            $fields['about']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Помилка при збереженні: ' . $e->getMessage()]);
}
?>
