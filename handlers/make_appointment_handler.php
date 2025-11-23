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

    function roundTimeToNextSlot($time)
    {
        list($h, $m) = explode(':', $time);
        $h = (int)$h;
        $m = (int)$m;

        if ($m == 0 || $m == 20 || $m == 40) {
            return sprintf("%02d:%02d", $h, $m);
        }

        if ($m < 20)      $m = 20;
        else if ($m < 40) $m = 40;
        else {
            $m = 0;
            $h = ($h + 1) % 24;
        }

        return sprintf("%02d:%02d", $h, $m);
    }

    function generateSlots($start, $end) {
        $slots = [];
        $roundedStart = roundTimeToNextSlot($start);
        list($h, $m) = explode(':', $roundedStart);
        $cur = strtotime("$h:$m");
        $endT = strtotime($end);
        while ($cur + 20 * 60 <= $endT) {
            $slots[] = date('H:i', $cur);
            $cur += 20 * 60;
        }
        return $slots;
    }

    $result = [];
    $now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));

    foreach ($schedules as $s) {
        $date = $s['work_date'];
        $slots = generateSlots($s['start_time'], $s['end_time']);

        foreach ($slots as $slot) {
            $slotDateTime = DateTime::createFromFormat('Y-m-d H:i', "$date $slot", new DateTimeZone('Europe/Kyiv'));
            
            if (!$slotDateTime) {
                continue;
            }
            if ($slotDateTime < $now) {
                continue;
            }

            $isBusy = in_array($slot, $busy[$date] ?? []);
            $result[$date][] = [
                'time' => $slot,
                'busy' => $isBusy
            ];
        }
    }

    $result = array_filter($result);
    echo json_encode(['slots' => $result]);
    exit;
}

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
    
    if (new DateTime($appointment_time) < new DateTime()) {
        echo json_encode(['error' => 'past_slot']);
        exit;
    }

    $check = $pdo->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE doctor_id = ? AND appointment_time = ? AND status IN ('booked', 'completed')
    ");
    $check->execute([$doctor_id, $appointment_time]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['error' => 'slot_busy']);
        exit;
    }
   
    $nowKyiv = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
    $currentTime = $nowKyiv->format('Y-m-d H:i:s');

    $activeCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments
        WHERE doctor_id = ? 
        AND patient_id = ? 
        AND status = 'booked'
        AND appointment_time > ?
    ");
    $activeCheck->execute([$doctor_id, $patient_id, $currentTime]);

    if ($activeCheck->fetchColumn() > 0) {
        echo json_encode(['error' => 'already_booked']);
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
