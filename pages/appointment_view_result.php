<?php
$requireLogin = true;
$allowRoles   = ['doctor', 'patient'];

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'appointment_view_result.css';
include __DIR__ . '/../includes/header.php';

$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
  echo "<p class='error'>Прийом не знайдено.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

if ($user_role === 'doctor') {
  $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $doctor_id = $stmt->fetchColumn();

  if (!$doctor_id) {
    echo "<p class='error'>Помилка доступу.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT 
      a.*,
      u.full_name AS patient_name,
      u.profile_image,
			p.insurance_number,
 			p.medical_card
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE a.appointment_id = ?
      AND a.doctor_id = ?
      AND a.status = 'completed'
  ");
  $stmt->execute([$appointment_id, $doctor_id]);

} elseif ($user_role === 'patient') {
  $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $patient_id = $stmt->fetchColumn();

  if (!$patient_id) {
    echo "<p class='error'>Помилка доступу.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT 
			a.*,
			u.full_name AS doctor_name,
			u.profile_image,
			d.specialty,
			c.name AS clinic_name,
			c.city AS clinic_city
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN users u ON d.user_id = u.user_id
		LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
    WHERE a.appointment_id = ?
      AND a.patient_id = ?
      AND a.status = 'completed'
  ");
  $stmt->execute([$appointment_id, $patient_id]);

} else {
  echo "<p class='error'>У вас немає доступу.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) {
  echo "<p class='error'>Неможливо переглянути результат. Прийом не був проведений або не належить вам.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$startTime = new DateTime($appt['appointment_time']);
$endTime = (clone $startTime)->modify('+20 minutes');

$isDoctor = ($user_role === 'doctor');

$displayName = $isDoctor ? $appt['patient_name'] : $appt['doctor_name'];
$nameLabel   = $isDoctor ? "Пацієнт" : "Лікар";

$avatar = htmlspecialchars($appt['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png');
?>

<main class="result-page">
  <div class="bc-container result-wrapper">

    <div class="result-header">
      <div class="person-info-block">
        <img 
          src="<?= $avatar ?>" 
          class="person-avatar" 
          alt="Фото"
        >

        <div class="person-info">
          <p class="person-name">
            <strong><?= $nameLabel ?>:</strong>
            <?= htmlspecialchars($displayName) ?>
          </p>

					<?php if (!$isDoctor): ?>
						<p class="doctor-speciality"><strong>Спеціальність лікаря:</strong> <?= htmlspecialchars($appt['specialty']) ?></p>
						<p class="clinic">
							<strong>Клініка:</strong> 
							<?= htmlspecialchars($appt['clinic_name']) ?> (<?= htmlspecialchars($appt['clinic_city']) ?>)
						</p>
					<?php endif; ?>

					<?php if ($isDoctor): ?>
						<p class="insurance-number"><strong>Страховий номер пацієнта:</strong> 
							<?= htmlspecialchars($appt['insurance_number'] ?: '—') ?>
						</p>
						<p class="medical-card"><strong>Медична карта:</strong> 
							<?= nl2br(htmlspecialchars($appt['medical_card'] ?: '—')) ?>
						</p>
					<?php endif; ?>

          <p class="appointment-date">
            <strong>Дата:</strong> <?= $startTime->format('d.m.Y') ?>
          </p>

          <p class="appointment-time">
            <strong>Час:</strong>
            <?= $startTime->format('H:i') ?>–<?= $endTime->format('H:i') ?>
          </p>
        </div>
      </div>

			<div class="header-right">
				<?php if ($isDoctor): ?>
					<button class="btn-back" onclick="window.location.href='/BumbleCare/pages/doctor_appointments.php'">
						Назад до всіх прийомів
					</button>
				<?php else: ?>
					<button class="btn-back" onclick="window.location.href='/BumbleCare/pages/patient_appointments.php'">
						Назад до моїх записів
					</button>
				<?php endif; ?>
			</div>
    </div>

    <div class="result-content">

      <div class="result-section">
        <h3>Коментар лікаря</h3>
        <div class="result-box">
          <?= nl2br(htmlspecialchars($appt['doctor_comment'] ?: '—')) ?>
        </div>
      </div>

      <div class="result-section">
        <h3>Програма лікування</h3>
        <div class="result-box">
          <?= nl2br(htmlspecialchars($appt['treatment_program'] ?: '—')) ?>
        </div>
      </div>

      <div class="result-section">
        <h3>Рекомендований повторний візит</h3>
        <div class="result-box">
          <?= nl2br(htmlspecialchars($appt['follow_up_recommendation'] ?: '—')) ?>
        </div>
      </div>

    </div>

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
