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
    c.city AS clinic_city,
    r.review_id,
    r.rating,
    r.comment AS review_comment
  FROM appointments a
  LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
  LEFT JOIN users u ON d.user_id = u.user_id
  LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
  LEFT JOIN reviews r ON r.appointment_id = a.appointment_id
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
        $apptTime = new DateTime($a['appointment_time'], new DateTimeZone('Europe/Kyiv'));
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
        } elseif ($status === 'cancelled_by_doctor') {
          $cardClass = 'cancelled';
          $label = 'Скасовано лікарем';
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
            <?php if (!empty($a['review_id'])): ?>
              <button
                class="btn-view-review"
                data-rating="<?= htmlspecialchars($a['rating']) ?>"
                data-comment="<?= htmlspecialchars($a['review_comment']) ?>"
              >
                Переглянути відгук
              </button>
            <?php else: ?>
              <button
                class="btn-review"
                data-id="<?= $a['appointment_id'] ?>" 
                data-doctor="<?= $a['doctor_id'] ?>"
              >
                Залишити відгук
              </button>
            <?php endif; ?>
            <button 
              class="btn-result" 
              onclick="window.location.href='/BumbleCare/pages/appointment_view_result.php?appointment_id=<?= $a['appointment_id'] ?>'"
            >
              Переглянути результати
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

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

  <div id="reviewModal" class="modal hidden">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Залишити відгук</h2>

      <form id="reviewForm">
        <input type="hidden" name="appointment_id" id="review_appointment_id">
        <input type="hidden" name="doctor_id" id="review_doctor_id">

        <div class="rating-block">
            <label>Оцініть прийом:</label>
          <div class="rating-select" id="starContainer">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star" data-value="<?= $i ?>">★</span>
            <?php endfor; ?>
          </div>
        </div>
        
        <input type="hidden" name="rating" id="review_rating" value="0">

        <div class="comment-block">
          <label for="review_comment">Ваш відгук:</label>
          <textarea id="review_comment" name="comment" placeholder="Напишіть свій відгук..." required></textarea>
        </div>
        
        <div class="modal-actions">
          <button type="submit" class="btn yes">Надіслати</button>
          <button type="button" class="btn no" id="cancelReview">Скасувати</button>
        </div>
      </form>
    </div>
  </div>

  <div id="viewReviewModal" class="modal hidden">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2> Ваш відгук</h2>
      <div class="doctor-rating">
        <span class="rating-number" id="viewRatingNumber"></span>
        <div class="rating-stars" id="viewRatingStars" data-rating="0"></div>
      </div>
      <p id="viewReviewComment" class="review-comment"></p>
    </div>
  </div>

</main>

<script src="/BumbleCare/assets/js/patient_appointments.js"></script>
<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
