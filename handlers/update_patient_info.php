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

$medicalCardPath = null;

if (
  isset($_FILES['medical_card_file']) &&
  $_FILES['medical_card_file']['error'] === UPLOAD_ERR_OK
) {
  $uploadDir = __DIR__ . '/../assets/images/medcards/';

  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  $file = $_FILES['medical_card_file'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['pdf', 'doc', 'docx', 'txt'];

  if (!in_array($ext, $allowed, true)) {
    echo json_encode([
      'success' => false,
      'error' => 'Недопустимий формат медичної карти'
    ]);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT medical_card
    FROM patients
    WHERE user_id = ?
  ");
  $stmt->execute([$user_id]);
  $oldMedicalCard = $stmt->fetchColumn();

  $filename = 'medcard_patient_' . $user_id . '_' . time() . '.' . $ext;
  $fullPath = $uploadDir . $filename;

  if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode([
      'success' => false,
      'error' => 'Не вдалося зберегти файл медкарти'
    ]);
    exit;
  }

  if (!empty($oldMedicalCard)) {
    $projectRoot = realpath(__DIR__ . '/..'); 
		$relativePath = str_replace('/BumbleCare', '', $oldMedicalCard);
		$oldPath = $projectRoot . $relativePath;
    if (file_exists($oldPath)) {
      unlink($oldPath);
    }
  }

  $medicalCardPath = '/BumbleCare/assets/images/medcards/' . $filename;
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
                medical_card = COALESCE(?, medical_card),
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
            $medicalCardPath,
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
            $medicalCardPath,
            $fields['date_of_birth']
        ]);
    }

    $pdo->commit();
    echo json_encode([
			'success' => true,
			'medical_card' => $medicalCardPath
		]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Помилка при збереженні: ' . $e->getMessage()]);
}
?>
