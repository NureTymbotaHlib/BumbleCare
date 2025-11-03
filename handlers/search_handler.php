<?php
require_once __DIR__ . '/../includes/db_connect.php';

$query = $_GET['query'] ?? '';
$specialty = $_GET['specialty'] ?? '';
$city = $_GET['city'] ?? '';
$clinic_id = $_GET['clinic_id'] ?? '';
$sort = $_GET['sort'] ?? 'default';

$sql = "
SELECT d.doctor_id, u.full_name, d.specialty,
       c.name AS clinic_name, c.city AS clinic_city,
       COALESCE(AVG(r.rating), 0) AS avg_rating,
       COUNT(r.review_id) AS reviews_count,
       u.profile_image
FROM doctors d
JOIN users u ON d.user_id = u.user_id
LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
LEFT JOIN reviews r ON d.doctor_id = r.doctor_id
WHERE u.status = 'active'
";
$params = [];

if ($query) {
  $sql .= " AND (u.full_name LIKE ? OR d.specialty LIKE ?)";
  $params[] = "%$query%";
  $params[] = "%$query%";
}
if ($specialty) {
  $sql .= " AND d.specialty = ?";
  $params[] = $specialty;
}
if ($city) {
  $sql .= " AND c.city = ?";
  $params[] = $city;
}
if ($clinic_id) {
  $sql .= " AND c.clinic_id = ?";
  $params[] = $clinic_id;
}

$sql .= " GROUP BY d.doctor_id ";

switch ($sort) {
  case 'rating':  $sql .= " ORDER BY avg_rating DESC"; break;
  case 'reviews': $sql .= " ORDER BY reviews_count DESC"; break;
  default:        $sql .= " ORDER BY u.full_name ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($doctors):
  foreach ($doctors as $doc): ?>
    <div class="doctor-card">
      <div class="doctor-header">
        <div class="doctor-user">
          <img 
            src="<?= htmlspecialchars($doc['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
            alt="Фото лікаря" 
            class="doctor-avatar"
          >
          <div class="doctor-meta">
            <p class="doctor-name"><?= htmlspecialchars($doc['full_name']) ?></p>
            <p class="doctor-spec"><?= htmlspecialchars($doc['specialty'] ?? '—') ?></p>
            <p class="doctor-clinic">
              <?= htmlspecialchars($doc['clinic_name'] ?? '—') ?>
              (<?= htmlspecialchars($doc['clinic_city'] ?? '—') ?>)
            </p>
            <a href="/BumbleCare/pages/doctor_view.php?doctor_id=<?= $doc['doctor_id'] ?>" class="btn-details">Детальніше</a>
          </div>
        </div>
        <div class="doctor-right-part">
            <div class="doctor-rating">
            <span class="rating-number"><?= round($doc['avg_rating'], 1) ?></span>
            <div class="rating-stars" data-rating="<?= round($doc['avg_rating'], 1) ?>"></div>
            <span class="rating-count">(<?= $doc['reviews_count'] ?>)</span>
            </div>
            <a href="/BumbleCare/pages/make_appointment.php?doctor_id=<?= $doc['doctor_id'] ?>" class="btn-appointment">Записатись на прийом</a>
        </div>
      </div>

    </div>
  <?php endforeach;
else: ?>
  <p class="no-results">Лікарів не знайдено.</p>
<?php endif; ?>
