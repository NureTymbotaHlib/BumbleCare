<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';

$doctor_id = $_GET['doctor_id'] ?? null;
if (!$doctor_id || !is_numeric($doctor_id)) {
  http_response_code(400);
  echo "<h2>Невірний запит</h2>";
  exit;
}

$sql = "
SELECT 
  d.doctor_id, d.license_number, d.specialty, d.phone_number AS doctor_phone,
  d.experience_years, d.certification, d.education, d.gender,
  d.date_of_birth, d.id_code, d.about, d.work_schedule,
  u.full_name, u.profile_image,
  c.name AS clinic_name, c.city AS clinic_city,
  COALESCE(AVG(r.rating), 0) AS avg_rating,
  COUNT(r.review_id) AS reviews_count
FROM doctors d
JOIN users u ON d.user_id = u.user_id
LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
LEFT JOIN reviews r ON d.doctor_id = r.doctor_id
WHERE d.doctor_id = ?
GROUP BY d.doctor_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
  http_response_code(404);
  echo "<h2>Лікаря не знайдено</h2>";
  exit;
}

$age = null;
if (!empty($doctor['date_of_birth'])) {
  $age = date_diff(date_create($doctor['date_of_birth']), date_create('today'))->y;
}

$review_sql = "
SELECT 
  r.review_id, r.rating, r.comment, r.created_at,
  u.full_name, u.profile_image
FROM reviews r
JOIN patients p ON r.patient_id = p.patient_id
JOIN users u ON p.user_id = u.user_id
WHERE r.doctor_id = ?
ORDER BY r.created_at DESC
";
$review_stmt = $pdo->prepare($review_sql);
$review_stmt->execute([$doctor_id]);
$reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_css = 'doctor_view.css';
include __DIR__ . '/../includes/header.php';
?>

<main class="doctor-view-page">
  <div class="bc-container doctor-profile">

    <div class="doctor-main">
      <div class="doctor-left">
        <img src="<?= htmlspecialchars($doctor['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" alt="Фото лікаря" class="doctor-avatar"
        >
        <div class="doctor-info">
          <p class="doctor-name"><?= htmlspecialchars($doctor['full_name']) ?></p>
					<div class="doctor-desc">
						<p><strong>Спеціальність:</strong> <?= htmlspecialchars($doctor['specialty'] ?? '—') ?></p>
						<p><strong>Місце роботи:</strong> <?= htmlspecialchars($doctor['clinic_name']) ?> (<?= htmlspecialchars($doctor['clinic_city']) ?>)</p>
						<p><strong>Стаж:</strong> <?= htmlspecialchars($doctor['experience_years'] ?? '—') ?> років</p>
						<p><strong>Вік:</strong> <?= htmlspecialchars($age ?? '—') ?> років</p>
						<p><strong>Стать:</strong> <?= htmlspecialchars($doctor['gender'] ?? '—') ?></p>
					</div>
      	</div>
      </div>

			<div class="doctor-right">
				<button class="btn-back" onclick="window.history.back()">Назад до вибору лікаря</button>
				<div class="doctor-rating">
					<span class="rating-number"><?= round($doctor['avg_rating'], 1) ?></span>
					<div class="rating-stars" data-rating="<?= round($doctor['avg_rating'], 1) ?>"></div>
					<span class="rating-count">(<?= $doctor['reviews_count'] ?>)</span>
				</div>
			</div>
    </div>

    <div class="doctor-about">
      <h2>Про мене</h2>
      <p><?= nl2br(htmlspecialchars($doctor['about'] ?? 'Інформація відсутня')) ?></p>
    </div>

    <div class="doctor-reviews-section">
      <div class="reviews-header">
        <h2>Відгуки пацієнтів</h2>

        <form class="sort-form" onsubmit="return false;">
          <label for="sort">Сортувати:</label>
          <select id="sort" name="sort">
            <option value="date_desc">Новіші спочатку</option>
            <option value="date_asc">Старіші спочатку</option>
            <option value="rating_desc">Від більшої оцінки</option>
            <option value="rating_asc">Від меншої оцінки</option>
          </select>
        </form>
      </div>

      <div id="reviews-container">
        <?php if ($reviews && count($reviews) > 0): ?>
          <?php foreach ($reviews as $review): ?>
            <div class="review-card">
              <div class="review-header">
                <div class="review-user">
                  <img src="<?= htmlspecialchars($review['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" alt="Фото" class="review-avatar">
                  <div class="review-meta">
                    <p class="review-name"><?= htmlspecialchars($review['full_name']) ?></p>
                    <p class="review-date"><?= date('d.m.y H:i', strtotime($review['created_at'])) ?></p>
                  </div>
                </div>
                <div class="doctor-rating">
                  <span class="rating-number"><?= round($review['rating'], 1) ?></span>
                  <div class="rating-stars" data-rating="<?= round($review['rating'], 1) ?>"></div>
                </div>
              </div>

              <div class="review-body">
                <p><?= htmlspecialchars($review['comment']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-reviews">У даного лікаря відгуків поки що немає.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<script src="/BumbleCare/assets/js/doctor_view.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
