<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/check_auth.php';
header('Content-Type: application/json');

if ($user_role !== 'clinic_admin' && $user_role !== 'super_admin') {
  echo json_encode(['error' => 'Access denied']);
  exit;
}

$clinic_id = null;

if ($user_role === 'clinic_admin') {
  $stmt = $pdo->prepare("SELECT clinic_id FROM clinic_admins WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $clinic_id = $stmt->fetchColumn();

  if (!$clinic_id) {
    echo json_encode(['error' => 'Clinic not found']);
    exit;
  }
}

function reviewBelongsToClinic($pdo, $review_id, $clinic_id) {
  $stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM reviews r
    JOIN doctors d ON r.doctor_id = d.doctor_id
    WHERE r.review_id = ? AND d.clinic_id = ?
  ");
  $stmt->execute([$review_id, $clinic_id]);
  return $stmt->fetchColumn() > 0;
}

$action = $_POST['action'] ?? null;

if (!$action) {
  echo json_encode(['error' => 'No action specified']);
  exit;
}

if ($action === "update") {

  $review_id = $_POST['review_id'] ?? null;
  $status = $_POST['status'] ?? null;

  if (!$review_id || !$status) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
  }

  $allowed = ['pending', 'approved', 'rejected', 'hidden'];
  if (!in_array($status, $allowed, true)) {
    echo json_encode(['error' => 'Invalid status']);
    exit;
  }

    if ($user_role === 'clinic_admin' && !reviewBelongsToClinic($pdo, $review_id, $clinic_id)) {
    echo json_encode(['error' => 'You cannot modify this review']);
    exit;
  }

  $stmt = $pdo->prepare("
    UPDATE reviews
    SET status = ?
    WHERE review_id = ?
  ");
  $stmt->execute([$status, $review_id]);

  echo json_encode(['success' => true]);
  exit;
}

if ($action === "list") {
  $doctor_query = trim($_POST['doctor_query'] ?? '');
  $status = $_POST['status'] ?? 'pending';
  $sort = $_POST['sort'] ?? 'date_desc';

  $sql = "
    SELECT 
      r.review_id,
      r.rating,
      r.comment,
      r.created_at,
      r.status AS review_status,

      u.full_name AS patient_name,
      u.profile_image AS patient_image,

      d.doctor_id,
      du.full_name AS doctor_name,
      d.specialty,

      a.appointment_time,

      c.name AS clinic_name,
      c.city AS clinic_city

    FROM reviews r
    JOIN patients p ON r.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id

    JOIN doctors d ON r.doctor_id = d.doctor_id
    JOIN users du ON d.user_id = du.user_id

    JOIN appointments a ON r.appointment_id = a.appointment_id
    JOIN clinics c ON d.clinic_id = c.clinic_id

    WHERE 1
  ";

  $params = [];

  if ($user_role === 'clinic_admin') {
    $sql .= " AND d.clinic_id = ? ";
    $params[] = $clinic_id;
  }

  if ($doctor_query !== '') {
    $sql .= " AND du.full_name LIKE ? ";
    $params[] = "%$doctor_query%";
  }

  if ($status) {
    $sql .= " AND r.status = ? ";
    $params[] = $status;
  }

  switch ($sort) {
    case 'rating_asc':
      $sql .= " ORDER BY r.rating ASC";
      break;
    case 'rating_desc':
      $sql .= " ORDER BY r.rating DESC";
      break;
    case 'date_asc':
      $sql .= " ORDER BY r.created_at ASC";
      break;
    default:
      $sql .= " ORDER BY r.created_at DESC";
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

  ob_start();
?>

<?php if (!$reviews): ?>
  <p class='no-reviews'>Відгуків не знайдено.</p>
<?php else: ?>
  <?php foreach ($reviews as $r): ?>

    <?php
      $img = htmlspecialchars($r['patient_image'] ?? '/BumbleCare/assets/images/default_avatar.png');
      $patient = htmlspecialchars($r['patient_name']);
      $doctor = htmlspecialchars($r['doctor_name']);
      $specialty = htmlspecialchars($r['specialty']);
      $clinic = htmlspecialchars($r['clinic_name']);
      $city = htmlspecialchars($r['clinic_city']);
      $appointment_time = date('d.m.y H:i', strtotime($r['appointment_time']));
      $review_date = date('d.m.y H:i', strtotime($r['created_at']));
      $rating = round($r['rating'], 1);
      $comment = nl2br(htmlspecialchars($r['comment']));
      $id = $r['review_id'];
    ?>

    <div class="review-card">

      <div class="review-header">
        <div class="review-user">
          <img src="<?= $img ?>" class="review-avatar">

          <div class="review-meta">
            <p><strong>Пацієнт:</strong> <?= $patient ?></p>
            <p><strong>Лікар:</strong> <?= $doctor ?></p>
            <p><strong>Спеціальність:</strong> <?= $specialty ?></p>
            <p><strong>Клініка:</strong> <?= $clinic ?> (<?= $city ?>)</p>
            <p><strong>Дата прийому:</strong> <?= $appointment_time ?></p>
            <p><strong>Дата відгуку:</strong> <?= $review_date ?></p>
          </div>
        </div>

        <div class="review-rating">
          <span class="rating-number"><?= $rating ?></span>
          <div class="rating-stars" data-rating="<?= $rating ?>"></div>
        </div>
      </div>

      <div class="review-body">
        <p><?= $comment ?></p>
      </div>

      <div class="review-actions">
        <div class="review-actions">
          <?php if ($r['review_status'] === 'pending'): ?>
            <button class="btn-approve" data-id="<?= $id ?>">Схвалити</button>
            <button class="btn-reject" data-id="<?= $id ?>">Відхилити</button>

          <?php elseif ($r['review_status'] === 'approved'): ?>
            <button class="btn-hide" data-id="<?= $id ?>">Приховати</button>

          <?php elseif ($r['review_status'] === 'hidden'): ?>
            <button class="btn-approve" data-id="<?= $id ?>">Показати</button>

          <?php elseif ($r['review_status'] === 'rejected'): ?>
            <button class="btn-approve" data-id="<?= $id ?>">Схвалити</button>
          <?php endif; ?>
        </div>
      </div>
    </div>

  <?php endforeach; ?>
<?php endif; ?>

<?php
  $html = ob_get_clean();
  echo json_encode(['success' => true, 'html' => $html]);
  exit;
}
?>
