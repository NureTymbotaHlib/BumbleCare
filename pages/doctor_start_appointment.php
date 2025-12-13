<?php
$requireLogin = true;
$allowRoles   = ['doctor'];

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'doctor_start_appointment.css';
include __DIR__ . '/../includes/header.php';

$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    echo "<p class='error'>Прийом не знайдено.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

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
        p.patient_id,
        p.date_of_birth,
        p.gender,
        p.insurance_number,
        p.medical_card,
        u.full_name,
        u.profile_image
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE a.appointment_id = ? AND a.doctor_id = ?
");
$stmt->execute([$appointment_id, $doctor_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) {
  echo "<p class='error'>Прийом не знайдено або у вас немає доступу.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

if ($appt['status'] !== 'booked') {
  echo "<p class='error'>Неможливо провести цей прийом, оскільки він не є запланованим.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
$start = new DateTime($appt['appointment_time'], new DateTimeZone('Europe/Kyiv'));
$end   = (clone $start)->modify('+20 minutes');

if ($end < $now) {
  echo "<p class='error'>Неможливо розпочати прийом, оскільки час прийому вже минув.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$age = '—';
if (!empty($appt['date_of_birth']) && $appt['date_of_birth'] !== '0000-00-00') {
    $dob = new DateTime($appt['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y . " років";
}

$startTime = new DateTime($appt['appointment_time']);
$endTime = (clone $startTime)->modify('+20 minutes');
?>

<main class="doctor-start-page">
  <div class="bc-container doctor-start-wrapper">

    <div class="appointment-header">
      <div class="patient-info-block">
        <img 
          src="<?= htmlspecialchars($appt['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>"
          class="patient-avatar"
          alt="Фото пацієнта"
        >
        <div class="patient-info">
          <p class="patient-name"><strong>Пацієнт:</strong> <?= htmlspecialchars($appt['full_name']) ?></p>
          <p class="appointment-date"><strong>Дата прийому:</strong> <?= $startTime->format('d.m.Y') ?></p>
          <p class="appointment-time"><strong>Час прийому:</strong> <?= $startTime->format('H:i') ?> - <?= $endTime->format('H:i') ?></p>
        </div>
      </div>

			<div class="header-right">
				<button class="btn-back">Назад до всіх прийомів</button>
			</div>

    </div>

    <div class="patient-extra-info">
      <h3>Медична інформація пацієнта</h3>

      <div class="extra-grid">
        <p><strong>Вік:</strong> <?= htmlspecialchars($age) ?></p>
        <p><strong>Стать:</strong> <?= htmlspecialchars($appt['gender'] ?: '—') ?></p>
        <p><strong>Страховий номер:</strong> <?= htmlspecialchars($appt['insurance_number'] ?: '—') ?></p>
        <p>
          <strong>Медична карта:</strong>
          <?php if (!empty($appt['medical_card'])): ?>
            <a
              href="<?= htmlspecialchars($appt['medical_card']) ?>"
              target="_blank"
              class="medical-card-link"
            >
              <?= htmlspecialchars(basename($appt['medical_card'])) ?>
            </a>
          <?php else: ?>
            —
          <?php endif; ?>
        </p>
      </div>
    </div>

    <div class="appointment-form">
      <div class="form-section">
        <h3>Коментар лікаря</h3>
        <textarea name="doctor_comment" class="input textarea" placeholder="Ваш коментар..."><?= htmlspecialchars($appt['doctor_comment'] ?? '') ?></textarea>
      </div>

      <div class="form-section">
        <h3>Програма лікування</h3>
        <textarea name="treatment_program" class="input textarea" placeholder="Опишіть програму..."><?= htmlspecialchars($appt['treatment_program'] ?? '') ?></textarea>
      </div>

      <div class="form-section">
        <h3>Рекомендований повторний візит</h3>
        <input 
          type="text" 
					name="follow_up_recommendation"
          class="input text-input"
          placeholder="Наприклад: через 2 тижні"
          value="<?= htmlspecialchars($appt['follow_up_recommendation'] ?? '') ?>"
        >
      </div>
      <button class="btn-finish" data-appt="<?= $appointment_id ?>">Позначити прийом як завершений</button>
    </div>

  </div>

	<!-- модалки -->
	<div id="exitModal" class="modal hidden">
		<div class="modal-content">
			<span class="close-btn">&times;</span>

			<h2>Ви впевнені, що хочете закрити проведення прийому?</h2>
			<div class="app-info-block">
				<p><strong>Пацієнт:</strong> <?= htmlspecialchars($appt['full_name']) ?></p>
				<p><strong>Обрана дата:</strong> <?= $startTime->format('d.m.Y') ?></p>
				<p><strong>Час прийому:</strong> <?= $startTime->format('H:i') ?> - <?= $endTime->format('H:i') ?></p>
			</div>
			<p>Введена в поля інформація буде втрачена</p>

			<div class="modal-actions">
				<button class="btn yes" id="confirmExit">Так</button>
				<button class="btn no" id="cancelExit">Ні</button>
			</div>
		</div>
	</div>

	<div id="finishModal" class="modal hidden">
		<div class="modal-content">
			<span class="close-btn">&times;</span>

			<h2>Ви впевнені, що хочете завершити прийом?</h2>
			<div class="app-info-block">
				<p><strong>Пацієнт:</strong> <?= htmlspecialchars($appt['full_name']) ?></p>
				<p><strong>Обрана дата:</strong> <?= $startTime->format('d.m.Y') ?></p>
				<p><strong>Час прийому:</strong> <?= $startTime->format('H:i') ?> - <?= $endTime->format('H:i') ?></p>
			</div>
			<p>Прийом буде позначено як завершений</p>

			<div class="modal-actions">
				<button class="btn yes" id="confirmFinish">Так</button>
				<button class="btn no" id="cancelFinish">Ні</button>
			</div>
		</div>
	</div>
</main>

<script src="/BumbleCare/assets/js/doctor_start_appointment.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>