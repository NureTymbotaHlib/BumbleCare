<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'check_auth') {
    echo json_encode([
        'isLoggedIn' => $isLoggedIn,
        'user_id'    => $user_id,
        'role'       => $user_role,
    ]);
    exit;
}

// Отримання слотів для лікаря
if ($action === 'get_slots') {
    $doctor_id = $_GET['doctor_id'] ?? null;

    if (!$doctor_id) {
        echo json_encode(['error' => 'Відсутній ID лікаря']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT work_date, start_time, end_time
        FROM doctor_schedules
        WHERE doctor_id = ?
        ORDER BY work_date ASC, start_time ASC
    ");
    $stmt->execute([$doctor_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$schedules) {
        echo json_encode(['slots' => []]);
        exit;
    }

    $appt = $pdo->prepare("
        SELECT DATE(appointment_time) AS work_date, 
               DATE_FORMAT(appointment_time, '%H:%i') AS start_time
        FROM appointments
        WHERE doctor_id = ? 
          AND status IN ('booked', 'completed')
    ");
    $appt->execute([$doctor_id]);
    $busy = [];
    foreach ($appt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $busy[$b['work_date']][] = $b['start_time'];
    }

    function generateSlots($start, $end) {
        $slots = [];
        $cur = strtotime($start);
        $endT = strtotime($end);
        while ($cur + 20 * 60 <= $endT) {
            $slots[] = date('H:i', $cur);
            $cur += 20 * 60;
        }
        return $slots;
    }

    $result = [];
    foreach ($schedules as $s) {
        $date = $s['work_date'];
        $slots = generateSlots($s['start_time'], $s['end_time']);

        foreach ($slots as $slot) {
            $isBusy = in_array($slot, $busy[$date] ?? []);
            $result[$date][] = [
                'time' => $slot,
                'busy' => $isBusy
            ];
        }
    }

    echo json_encode(['slots' => $result]);
    exit;
}

// Створення запису
if ($action === 'create_appointment') {
    if (!$isLoggedIn || $user_role !== 'patient') {
        echo json_encode(['error' => 'Користувач не авторизований']);
        exit;
    }

    $doctor_id = $_POST['doctor_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $time = $_POST['time'] ?? null;

    if (!$doctor_id || !$date || !$time) {
        echo json_encode(['error' => 'Неповні дані']);
        exit;
    }

    $patientQuery = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $patientQuery->execute([$user_id]);
    $patient_id = $patientQuery->fetchColumn();

    if (!$patient_id) {
        echo json_encode(['error' => 'Не знайдено пацієнта']);
        exit;
    }

    $appointment_time = "$date $time:00";

    $check = $pdo->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE doctor_id = ? AND appointment_time = ? AND status IN ('booked', 'completed')
    ");
    $check->execute([$doctor_id, $appointment_time]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['error' => 'slot_busy']);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO appointments (doctor_id, patient_id, appointment_time, status)
        VALUES (?, ?, ?, 'booked')
    ");
    $insert->execute([$doctor_id, $patient_id, $appointment_time]);

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Невідома дія']);
exit;
