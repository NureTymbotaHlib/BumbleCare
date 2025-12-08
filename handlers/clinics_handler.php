<?php
require_once __DIR__ . '/../includes/db_connect.php';

$query = $_GET['query'] ?? '';
$city  = $_GET['city'] ?? '';
$sort  = $_GET['sort'] ?? 'default';

$sql = "
SELECT 
  c.clinic_id,
  c.name,
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
WHERE 1
";

$params = [];

if ($query) {
  $sql .= " AND c.name LIKE ? ";
  $params[] = "%$query%";
}

if ($city) {
  $sql .= " AND c.city = ? ";
  $params[] = $city;
}

$sql .= " GROUP BY c.clinic_id ";

switch ($sort) {
  case 'rating':
    $sql .= " ORDER BY avg_rating DESC";
    break;
  case 'reviews':
    $sql .= " ORDER BY reviews_count DESC";
    break;
  default:
    $sql .= " ORDER BY c.name ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($clinics):
  foreach ($clinics as $cl):

    $img = $cl['image_url'] ?: '/BumbleCare/assets/images/default_clinic.jpg';
?>
    <div class="clinic-card">
      <div class="clinic-header">

        <div class="clinic-user">
          <img 
            src="<?= htmlspecialchars($img) ?>"
            class="clinic-avatar"
            alt="Фото клініки"
          >

          <div class="clinic-meta">
            <p class="clinic-name"><?= htmlspecialchars($cl['name']) ?></p>
            <p class="clinic-address">Адреса: <?= htmlspecialchars($cl['address']) ?></p>
            <p class="clinic-city">Місто: <?= htmlspecialchars($cl['city']) ?></p>
            <p class="clinic-phone">Телефон: <?= htmlspecialchars($cl['phone']) ?></p>
            <p class="clinic-email">Email: <?= htmlspecialchars($cl['email']) ?></p>
          </div>
        </div>

        <div class="clinic-right-part">
          <div class="clinic-rating">
            <span class="rating-number"><?= round($cl['avg_rating'], 1) ?></span>
            <div class="rating-stars" data-rating="<?= round($cl['avg_rating'], 1) ?>"></div>
            <span class="rating-count">(<?= $cl['reviews_count'] ?>)</span>
          </div>

          <a href="/BumbleCare/pages/clinic_view.php?clinic_id=<?= $cl['clinic_id'] ?>" class="btn-details">Детальніше</a>
        </div>

      </div>
    </div>
<?php
  endforeach;
else:
?>
  <p class="no-results">Клінік не знайдено.</p>
<?php endif; ?>
