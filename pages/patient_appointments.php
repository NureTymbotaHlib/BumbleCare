<?php
$requireLogin = true;
$allowRoles   = ['patient'];
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'patient_appointments.css';
include __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare("
  SELECT 
    a.appointment_id,
    a.appointment_time,
    a.status,
    a.doctor_comment,
    d.doctor_id,
    u.full_name AS doctor_name,
    u.profile_image,
    c.name AS clinic_name,
    c.city AS clinic_city
  FROM appointments a
  LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
  LEFT JOIN users u ON d.user_id = u.user_id
  LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
  WHERE a.patient_id = (
      SELECT patient_id FROM patients WHERE user_id = ?
  )
	ORDER BY
		CASE
			WHEN a.status = 'booked' AND a.appointment_time >= NOW() THEN 1
			WHEN a.status = 'completed' THEN 2
			ELSE 3
		END,
		CASE
			WHEN a.status = 'booked' AND a.appointment_time >= NOW() THEN a.appointment_time
			ELSE NULL
		END ASC,
		a.appointment_time DESC
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
?>

<main class="appointments-page">
  <div class="bc-container patient-appointments-wrapper">
		<div class="back-block">
			<button class="btn-back" onclick="window.location.href='/BumbleCare/pages/patient_profile.php'">
				Назад до мого профілю
			</button>
		</div>

    <?php if (!$appointments): ?>
      <p>У вас ще немає записів.</p>
    <?php else: ?>
      <?php foreach ($appointments as $a): 
        $apptTime = new DateTime($a['appointment_time']);
        $endTime = (clone $apptTime)->modify('+20 minutes');
				$isPastEnd = $endTime < $now;
        $status = $a['status'];
        $cardClass = '';
        $label = '';

        if ($status === 'booked' && !$isPastEnd) {
          $cardClass = 'planned';
          $label = 'Заплановано';
        } elseif ($status === 'cancelled') {
          $cardClass = 'cancelled';
          $label = 'Скасовано';
        } elseif ($status === 'completed') {
          $cardClass = 'completed';
          $label = 'Завершено';
        } elseif ($status === 'booked' && $isPastEnd) {
          $cardClass = 'missed';
          $label = 'Не зʼявився';
        }
      ?>
      <div class="appointment-card <?= $cardClass ?>">
        <div class="appointment-left">
          <img src="<?= htmlspecialchars($a['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" alt="Фото лікаря" class="doctor-avatar">
          <div class="appointment-info">
            <p class="doctor-name"><strong>Лікар:</strong> <?= htmlspecialchars($a['doctor_name']) ?></p>
            <p class="clinic"><strong>Клініка:</strong> <?= htmlspecialchars($a['clinic_name']) ?></p>
            <p class="appointment-date"><strong>Обрана дата:</strong> <?= $apptTime->format('d.m.Y') ?></p>
            <p class="appointment-time"><strong>Час прийому:</strong> <?= $apptTime->format('H:i') ?> - <?= $apptTime->modify('+20 minutes')->format('H:i') ?></p>
          </div>
        </div>

        <div class="appointment-right">
          <p class="status"><?= $label ?></p>
          <?php if ($status === 'booked' && !$isPastEnd): ?>
              <button 
								class="btn-cancel" 
								data-id="<?= $a['appointment_id'] ?>"
								data-doctor="<?= htmlspecialchars($a['doctor_name']) ?>"
								data-clinic="<?= htmlspecialchars($a['clinic_name']) ?>"
								data-date="<?= $apptTime->format('d.m.Y') ?>"
								data-time="<?= $apptTime->format('H:i') ?> - <?= $endTime->format('H:i') ?>"
							>
								Скасувати бронювання
							</button>
          <?php elseif ($status === 'completed'): ?>
            <button
							class="btn-review" 
							data-id="<?= $a['appointment_id'] ?>" 
							data-doctor="<?= $a['doctor_id'] ?>"
						>
							Залишити відгук
						</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

	<!-- МОДАЛЬНЕ ВІКНО СКАСУВАННЯ ЗАПИСУ -->
	<div id="cancelModal" class="modal hidden">
		<div class="modal-content">
			<span class="close-btn">&times;</span>
			<h2>Скасування запису</h2>

			<p id="cancelInfo"></p>
			<p>Ви впевнені, що хочете скасувати бронювання?</p>

			<div class="modal-actions">
				<button class="btn yes">Так</button>
				<button class="btn no">Ні</button>
			</div>
		</div>
	</div>

</main>

<script src="/BumbleCare/assets/js/patient_appointments.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
