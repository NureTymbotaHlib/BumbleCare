<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/send_mail.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$doctor_id = $_GET['doctor_id'] ?? $_POST['doctor_id'] ?? null;

if (!$doctor_id) {
  echo json_encode(['error' => 'missing doctor_id']);
  exit;
}

function mergeIntervals($intervals) {
  usort($intervals, fn($a, $b) => strcmp($a['start'], $b['start']));

  $merged = [];
  foreach ($intervals as $int) {
    if (!$merged) {
      $merged[] = $int;
      continue;
    }

    $last = &$merged[count($merged) - 1];

    if ($int['start'] <= $last['end']) {
      if ($int['end'] > $last['end']) {
        $last['end'] = $int['end'];
      }
    } else {
      $merged[] = $int;
    }
  }
  return $merged;
}

function roundTimeToNextSlot($time) {
  list($h, $m) = explode(':', $time);
  $h = (int)$h;
  $m = (int)$m;

  if ($m == 0 || $m == 20 || $m == 40) {
    return sprintf("%02d:%02d", $h, $m);
  }

  if ($m < 20) $m = 20;
  else if ($m < 40) $m = 40;
  else {
    $m = 0;
    $h = ($h + 1) % 24;
  }

  return sprintf("%02d:%02d", $h, $m);
}

if ($action === "get") {
  $now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));

  $stmt = $pdo->prepare("
    SELECT work_date, start_time, end_time
    FROM doctor_schedules
    WHERE doctor_id = ?
    ORDER BY work_date, start_time
  ");
  $stmt->execute([$doctor_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

  $result = [];

  foreach ($rows as $r) {
    $date = $r['work_date'];
    $start = $r['start_time'];
    $end = $r['end_time'];

    $roundedStart = roundTimeToNextSlot($start);

    $cur = strtotime("$date $roundedStart");
    $endT = strtotime("$date $end");

    while ($cur + 20 * 60 <= $endT) {
      $slotTime = date("H:i", $cur);

      $slotDT = DateTime::createFromFormat(
        'Y-m-d H:i',
        "$date $slotTime",
        new DateTimeZone('Europe/Kyiv')
      );

      if ($slotDT >= $now) {
        $isBusy = in_array($slotTime, $busy[$date] ?? []);
        $result[$date][] = [
          'time' => $slotTime,
          'busy' => $isBusy
        ];
      }

      $cur += 20 * 60;
    }
  }

  $result = array_filter($result);
  echo json_encode(['slots' => $result]);
  exit;
}

if ($action === "add") {
  $date = $_POST['date'];
  $start = $_POST['start'];
  $end = $_POST['end'];

  if ($start >= $end) {
    echo json_encode(['error' => 'Невірний час']);
    exit;
  }

  $now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
  $intervalStart = DateTime::createFromFormat(
    'Y-m-d H:i',
    "$date $start",
    new DateTimeZone('Europe/Kyiv')
  );

  if (!$intervalStart) {
    echo json_encode(['error' => 'Невірний формат дати/часу']);
    exit;
  }

  if ($intervalStart < $now) {
    echo json_encode(['error' => 'Неможливо додати інтервал у минуле']);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT start_time, end_time
    FROM doctor_schedules
    WHERE doctor_id = ? AND work_date = ?
    ORDER BY start_time
  ");
  $stmt->execute([$doctor_id, $date]);
  $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $intervals = [];
  foreach ($existing as $ex) {
    $intervals[] = [
      'start' => $ex['start_time'],
      'end' => $ex['end_time']
    ];
  }

  $intervals[] = ['start' => $start, 'end' => $end];
  $merged = mergeIntervals($intervals);

  $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ? AND work_date = ?")
      ->execute([$doctor_id, $date]);

  $insert = $pdo->prepare("
    INSERT INTO doctor_schedules (doctor_id, work_date, start_time, end_time)
    VALUES (?, ?, ?, ?)
  ");

  foreach ($merged as $m) {
    $insert->execute([$doctor_id, $date, $m['start'], $m['end']]);
  }

  echo json_encode(['success' => true]);
  exit;
}

if ($action === "delete") {
  $date = $_POST['date'];
  $time = $_POST['time'];
  $busy = isset($_POST['busy']) ? (int)$_POST['busy'] : 0;

  if ($busy === 1) {
    $stmt = $pdo->prepare("
      SELECT appointment_id
      FROM appointments
      WHERE doctor_id = ?
        AND DATE(appointment_time) = ?
        AND DATE_FORMAT(appointment_time, '%H:%i') = ?
        AND status = 'booked'
    ");
    $stmt->execute([$doctor_id, $date, $time]);
    $apptId = $stmt->fetchColumn();

    if ($apptId) {
      $pdo->prepare("
        UPDATE appointments
        SET status = 'cancelled_by_doctor'
        WHERE appointment_id = ?
      ")->execute([$apptId]);

      $info = $pdo->prepare("
        SELECT
          u.email,
          u.full_name AS patient_name,
          doc.full_name AS doctor_name,
          c.name AS clinic_name,
          c.city AS clinic_city,
          a.appointment_time
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users doc ON d.user_id = doc.user_id
        JOIN clinics c ON d.clinic_id = c.clinic_id
        WHERE a.appointment_id = ?
      ");
      $info->execute([$apptId]);
      $data = $info->fetch(PDO::FETCH_ASSOC);

      if ($data) {
        $apptDate = (new DateTime($data['appointment_time'], new DateTimeZone('Europe/Kyiv')))
          ->format('d.m.Y H:i');

        $subject = "Скасування прийому | BumbleCare";

        $html = "
          <h2>Вітаємо, {$data['patient_name']}!</h2>

          <p>Ваш запис було <b>скасовано лікарем</b>. Вибачаємося за незручності.</p>

          <h3>Деталі прийому:</h3>
          <p><b>Лікар:</b> {$data['doctor_name']}</p>
          <p><b>Клініка:</b> {$data['clinic_name']} ({$data['clinic_city']})</p>
          <p><b>Дата та час:</b> {$apptDate}</p>

          <p>Будь ласка, оберіть інший зручний час або іншого лікаря у системі BumbleCare.</p>

          <hr>
          <small>© " . date('Y') . " BumbleCare</small>
        ";

        $sent = sendEmail($data['email'], $data['patient_name'], $subject, $html);
      }
    }
  }

  $stmt = $pdo->prepare("
    SELECT start_time, end_time
    FROM doctor_schedules
    WHERE doctor_id = ? AND work_date = ?
  ");
  $stmt->execute([$doctor_id, $date]);
  $intervals = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $removeStart = strtotime("$date $time");
  $removeEnd   = $removeStart + 20 * 60;

  $newIntervals = [];

  foreach ($intervals as $int) {
    $intStart = strtotime("$date {$int['start_time']}");
    $intEnd   = strtotime("$date {$int['end_time']}");

    if ($removeStart < $intStart || $removeStart >= $intEnd) {
      $newIntervals[] = [
        'start' => $int['start_time'],
        'end'   => $int['end_time']
      ];
      continue;
    }

    if ($intStart < $removeStart) {
      $leftStart = $intStart;
      $leftEnd   = $removeStart;

      if ($leftEnd > $leftStart) {
        $newIntervals[] = [
          'start' => date("H:i", $leftStart),
          'end'   => date("H:i", $leftEnd)
        ];
      }
    }

    if ($removeEnd < $intEnd) {
      $rightStart = $removeEnd;
      $rightEnd   = $intEnd;

      if ($rightEnd > $rightStart) {
        $newIntervals[] = [
          'start' => date("H:i", $rightStart),
          'end'   => date("H:i", $rightEnd)
        ];
      }
    }
  }

  $pdo->prepare("
    DELETE FROM doctor_schedules
    WHERE doctor_id = ? AND work_date = ?
  ")->execute([$doctor_id, $date]);

  if (empty($newIntervals)) {
    echo json_encode(['success' => true]);
    exit;
  }

  $insert = $pdo->prepare("
    INSERT INTO doctor_schedules (doctor_id, work_date, start_time, end_time)
    VALUES (?, ?, ?, ?)
  ");

  foreach ($newIntervals as $m) {
    $insert->execute([$doctor_id, $date, $m['start'], $m['end']]);
  }

  echo json_encode(['success' => true]);
  exit;
}

echo json_encode(['error' => 'unknown action']);