<?php
require_once __DIR__ . '/../includes/db_connect.php';

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$query     = trim($_GET['query'] ?? '');
$statusF   = $_GET['status'] ?? 'planned';
$dayF      = $_GET['day'] ?? '';

if (!$doctor_id) {
    echo '<p class="no-results">Невірний лікар.</p>';
    exit;
}

$sql = "
SELECT 
  a.appointment_id,
  a.appointment_time,
  a.status,
  a.doctor_comment,

  p.patient_id,
  u.full_name AS patient_name,
  u.profile_image AS patient_image,

  r.rating,
  r.comment AS review_comment

FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.patient_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN reviews r ON r.appointment_id = a.appointment_id
WHERE a.doctor_id = ?
";
$params = [$doctor_id];

if ($query !== '') {
    $sql .= " AND u.full_name LIKE ? ";
    $params[] = "%$query%";
}

if ($dayF !== '') {
    $sql .= " AND DATE(a.appointment_time) = ? ";
    $params[] = $dayF;
}


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
$today = new DateTime('today', new DateTimeZone('Europe/Kyiv'));

$appointments = [];

foreach ($result as $a) {
		$apptTime = new DateTime($a['appointment_time'], new DateTimeZone('Europe/Kyiv'));
    $endTime  = (clone $apptTime)->modify('+20 minutes');

    $dbStatus = $a['status'];
    $virtual  = null;

    if ($dbStatus === 'cancelled') continue;

    if ($dbStatus === 'booked' && $apptTime >= $today && $endTime >= $now) {
        $virtual = 'planned';
    }

    elseif ($dbStatus === 'booked' && $endTime < $now) {
        $virtual = 'missed';
    }

    elseif ($dbStatus === 'completed') {
        if ($apptTime >= $today) $virtual = 'completed';
        else $virtual = 'completed';
    }

    if (!$virtual) continue;

    if ($statusF === 'planned' && $virtual !== 'planned') continue;
    if ($statusF === 'completed' && $virtual !== 'completed') continue;
    if ($statusF === 'past' && !in_array($virtual, ['missed', 'completed'])) continue;
    if ($statusF === 'all') {

    }

    $a['_virtual_status'] = $virtual;
    $a['_end_time'] = $endTime;

    $appointments[] = $a;
}

if (!$appointments) {
    echo '<p class="no-results">Прийомів не знайдено.</p>';
    exit;
}

usort($appointments, function($a, $b) use ($statusF, $now) {
		$timeA = new DateTime($a['appointment_time'], new DateTimeZone('Europe/Kyiv'));
		$timeB = new DateTime($b['appointment_time'], new DateTimeZone('Europe/Kyiv'));

    if ($statusF === 'planned') {
        return $timeA <=> $timeB;
    }

    if ($statusF === 'completed') {
        return $timeB <=> $timeA;
    }

    if ($statusF === 'past') {
        return $timeB <=> $timeA;
    }

    $endA = $a['_end_time'];
    $endB = $b['_end_time'];

    $isFutureA = $endA >= $now;
    $isFutureB = $endB >= $now;

    if ($isFutureA && !$isFutureB) return -1;
    if (!$isFutureA && $isFutureB) return 1;

    if ($isFutureA && $isFutureB) {
        return $timeA <=> $timeB;
    }

    return $timeB <=> $timeA;
});

foreach ($appointments as $a):
		$apptTime = new DateTime($a['appointment_time'], new DateTimeZone('Europe/Kyiv'));
    $endTime  = $a['_end_time'];
    $cls = $a['_virtual_status'];

    $label = match ($cls) {
        'planned'   => 'Майбутній',
        'completed' => 'Завершено',
        'missed'    => 'Минулий - не зʼявився',
        default     => '—'
    };
?>
  <div class="appointment-card <?= htmlspecialchars($cls) ?>">
    <div class="appointment-left">
      <img 
        src="<?= htmlspecialchars($a['patient_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>"
        alt="Фото пацієнта"
        class="patient-avatar"
      >

      <div class="appointment-info">
        <p class="patient-name"><strong>Пацієнт:</strong> <?= htmlspecialchars($a['patient_name'] ?? '—') ?></p>
        <p class="appointment-date"><strong>Дата:</strong> <?= $apptTime->format('d.m.Y') ?></p>
        <p class="appointment-time"><strong>Час:</strong> <?= $apptTime->format('H:i') ?> - <?= $endTime->format('H:i') ?></p>
      </div>
    </div>

    <div class="appointment-right">
      <p class="status"><?= $label ?></p>

      <?php if ($cls === 'planned'): ?>
        <button class="btn-action btn-start" data-id="<?= (int)$a['appointment_id'] ?>">Розпочати прийом</button>

      <?php elseif ($cls === 'completed'): ?>
        <button class="btn-action btn-result" data-id="<?= (int)$a['appointment_id'] ?>">Переглянути результати</button>

      <?php endif; ?>
    </div>
  </div>

<?php endforeach; ?>
