<?php
$requireLogin = true;
$allowRoles   = ['doctor'];

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'doctor_schedule.css';
include __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.profile_image, c.name AS clinic_name 
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
        WHERE d.user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo "<p class='error'>Лікаря не знайдено.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$doctor_id = $doctor['doctor_id'];
?>

<main class="schedule-page">
  <div class="bc-container schedule-container">

    <div class="doctor-card">
      <img src="<?= htmlspecialchars($doctor['profile_image']) ?>" class="doctor-avatar">

      <div class="doctor-info">
        <p class="doctor-name"><?= htmlspecialchars($doctor['full_name']) ?></p>
        <p class="doctor-specialty"><?= htmlspecialchars($doctor['specialty']) ?></p>
        <p class="doctor-clinic"><?= htmlspecialchars($doctor['clinic_name']) ?></p>
      </div>
    </div>

    <div class="add-interval">
      <h2>Додати робочий інтервал</h2>

      <div class="interval-form">
        <label>
          Дата:
          <input type="date" id="workDate">
        </label>

        <label>
          Час початку:
          <input type="time" id="startTime">
        </label>

        <label>
          Час завершення:
          <input type="time" id="endTime">
        </label>

        <button id="addIntervalBtn" class="add-btn">Додати робочий час</button>
      </div>
    </div>

    <div class="slots-section">
      <h2>Існуючі слоти для запису</h2>
      <div id="slotsContainer" class="slots-container">
        <p class="loading">Завантаження...</p>
      </div>
    </div>

  </div>

	<div id="freeSlotModal" class="modal hidden">
		<div class="modal-content">
			<span class="close-btn">&times;</span>

			<h2>Ви впевнені,що хочете заблокувати даний час для прийому?</h2>
			<p>На цей час неможливо буде записатися</p>

			<div class="modal-actions">
				<button class="btn yes" id="confirmFree">Так</button>
				<button class="btn no" id="cancelFree">Ні</button>
			</div>
		</div>
	</div>

	<div id="busySlotModal" class="modal hidden">
		<div class="modal-content">
			<span class="close-btn">&times;</span>

			<h2>Ви впевнені,що хочете заблокувати даний час для прийому?</h2>
			<p><strong>Увага!</strong></p>
			<p>На цей час вже записаний пацієнт!</p>
			<p>Якщо ви заблокуєте даний слот, пацієнт отримає електронне сповіщення про скасування прийому!</p>

			<div class="modal-actions">
				<button class="btn yes" id="confirmBusy">Так</button>
				<button class="btn no" id="cancelBusy">Ні</button>
			</div>
		</div>
	</div>

</main>

<script>
  const DOCTOR_ID = <?= (int)$doctor_id ?>;
</script>

<script src="/BumbleCare/assets/js/doctor_schedule.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
