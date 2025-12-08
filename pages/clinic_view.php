<?php
$requireLogin = false;
$allowRoles   = ['patient', 'clinic_admin', 'super_admin'];
$allowGuest   = true;

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'clinic_view.css';
include __DIR__ . '/../includes/header.php';

$clinic_id = $_GET['clinic_id'] ?? null;

if (!$clinic_id) {
  echo "<p class='error'>Клініку не знайдено.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    c.clinic_id,
    c.name,
    c.description,
    c.city,
    c.address,
    c.phone,
    c.email,
    c.image_url,
    COALESCE(AVG(r.rating), 0) AS avg_rating,
    COUNT(r.review_id) AS reviews_count
  FROM clinics c
  LEFT JOIN doctors d ON c.clinic_id = d.clinic_id
  LEFT JOIN reviews r ON d.doctor_id = r.doctor_id AND r.status = 'approved'
  WHERE c.clinic_id = ?
  GROUP BY c.clinic_id
");
$stmt->execute([$clinic_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
  echo "<p class='error'>Клініку не знайдено.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$img = $clinic['image_url'] ?: '/BumbleCare/assets/images/default_clinic.jpg';

$addr = trim(($clinic['address'] ?? '') . ' ' . ($clinic['city'] ?? ''));
$map_query = urlencode($addr);
$map_src = $map_query
  ? "https://www.google.com/maps?q={$map_query}&output=embed"
  : null;
?>

<main class="clinic-view-page">
  <div class="bc-container clinic-view-wrapper">

    <div class="clinic-view-card">
      <div class="clinic-view-left">
        <img src="<?= htmlspecialchars($img) ?>" class="clinic-view-avatar" alt="Фото клініки">
				<div class="clinic-view-info">
					<p class="clinic-view-name"><?= htmlspecialchars($clinic['name']) ?></p>
					<p class="clinic-view-address">Адреса: <?= htmlspecialchars($clinic['address'] ?? '—') ?></p>
					<p class="clinic-view-city">Місто: <?= htmlspecialchars($clinic['city'] ?? '—') ?></p>
					<p class="clinic-view-phone">Телефон: <?= htmlspecialchars($clinic['phone'] ?? '—') ?></p>
					<p class="clinic-view-email">Email: <?= htmlspecialchars($clinic['email'] ?? '—') ?></p>
      	</div>
      </div>

      <div class="clinic-view-right">
        <div class="clinic-view-rating">
          <span class="rating-number"><?= round($clinic['avg_rating'], 1) ?></span>
          <div class="rating-stars" data-rating="<?= round($clinic['avg_rating'], 1) ?>"></div>
          <span class="rating-count">(<?= (int)$clinic['reviews_count'] ?> відгуків)</span>
        </div>

				<a href="/BumbleCare/pages/clinics.php" class="btn-back">Назад до всіх лікарень</a>
      </div>
    </div>

    <div class="clinic-view-extra">
      <div class="clinic-view-map">
        <?php if ($map_src): ?>
          <iframe
            src="<?= htmlspecialchars($map_src) ?>"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
          ></iframe>
        <?php else: ?>
          <div class="map-empty">Немає адреси для відображення карти.</div>
        <?php endif; ?>
      </div>

      <div class="clinic-view-description">
        <?= nl2br(htmlspecialchars($clinic['description'] ?? "Опис клініки буде додано пізніше.")) ?>
      </div>
    </div>

  </div>
</main>

<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
