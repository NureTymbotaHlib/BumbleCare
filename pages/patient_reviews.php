<?php
$requireLogin = true;
$allowRoles   = ['patient'];
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'patient_reviews.css';
include __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT 
    r.review_id,
    r.rating,
    r.comment,
    r.created_at,
    r.appointment_id,
    a.appointment_time,
    d.doctor_id,
    d.specialty,
    u.full_name AS doctor_name,
    u.profile_image,
    c.name AS clinic_name
  FROM reviews r
  LEFT JOIN doctors d ON r.doctor_id = d.doctor_id
  LEFT JOIN users u ON d.user_id = u.user_id
  LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
  LEFT JOIN appointments a ON r.appointment_id = a.appointment_id
  WHERE r.patient_id = ?
  ORDER BY r.created_at DESC
");
$stmt->execute([$patient_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="reviews-page">
  <div class="bc-container reviews-wrapper">
    <div class="back-block">
      <button class="btn-back" onclick="window.location.href='/BumbleCare/pages/patient_profile.php'">
        Назад до мого профілю
      </button>
    </div>

    <?php if (!$reviews): ?>
      <p>Ви ще не залишали відгуків.</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): 
        $apptTime = $r['appointment_time'] ? new DateTime($r['appointment_time']) : null;
      ?>
      <div class="review-card">
        <div class="review-left">
          <img 
            src="<?= htmlspecialchars($r['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
            alt="Фото лікаря" 
            class="doctor-avatar"
          >
          <div class="review-info">
            <p class="doctor-name"><strong>Лікар:</strong> <?= htmlspecialchars($r['doctor_name']) ?></p>
            <p class="specialty"><strong>Спеціальність:</strong> <?= htmlspecialchars($r['specialty'] ?? '—') ?></p>
            <p class="clinic"><strong>Клініка:</strong> <?= htmlspecialchars($r['clinic_name']) ?></p>
            <?php if ($apptTime): ?>
              <p class="appointment-date"><strong>Дата прийому:</strong> <?= $apptTime->format('d.m.Y H:i') ?></p>
            <?php endif; ?>
            <p class="review-date"><strong>Дата відгуку:</strong> <?= (new DateTime($r['created_at']))->format('d.m.Y H:i') ?></p>
          </div>
        </div>

        <div class="review-right">
          <div class="doctor-rating">
            <span class="rating-number"><?= round($r['rating'], 1) ?></span>
            <div class="rating-stars" data-rating="<?= round($r['rating'], 1) ?>"></div>
          </div>
          <p class="comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
