<?php
$requireRole = 'doctor';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'doctor_profile.css';
include __DIR__ . '/../includes/header.php';

if (empty($user_id)) {
    header("Location: /BumbleCare/pages/login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        u.full_name,
        u.email,
        u.created_at,
        u.profile_image,
        u.phone_number,
        d.license_number,
        d.specialty,
        d.experience_years,
        d.certification,
        d.education,
        d.gender,
        d.date_of_birth,
        d.id_code,
        d.about,
        d.clinic_id, 
        c.name AS clinic_name
    FROM users u
    LEFT JOIN doctors d ON u.user_id = d.user_id
    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$doctor) {
    echo "<p>Помилка: користувач не знайдений.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = realpath(__DIR__ . '/../assets/images/uploads/') . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file = $_FILES['profile_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (in_array($ext, $allowed)) {
        if (!empty($doctor['profile_image']) && strpos($doctor['profile_image'], 'default_avatar.png') === false) {
            $oldPath = $upload_dir . basename($doctor['profile_image']);
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $path = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            $db_path = '/BumbleCare/assets/images/uploads/' . $new_name;
            $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?")->execute([$db_path, $user_id]);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
?>

<main class="doctor-profile">
  <div class="bc-container profile-wrapper">

    <div class="tabs">
      <button class="tab active">Особиста інформація</button>
      <button class="tab">Мої відгуки</button>
    </div>

    <div class="tab-content active" id="tab-info">

      <div class="profile-header">
        <div class="profile-photo">
          <form method="POST" enctype="multipart/form-data" class="photo-upload-form" id="photoForm">
            <label for="photo-upload" class="photo-label">
              <img 
                src="<?= htmlspecialchars($doctor['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
                alt="Фото профілю" 
                class="profile-img"
                id="profileImagePreview"
              >
            </label>
            <input 
              type="file" 
              name="profile_image" 
              id="photo-upload" 
              accept=".jpg,.jpeg,.png"
              onchange="document.getElementById('photoForm').submit()"
            >
          </form>

          <div class="profile-info">
            <h2><?= htmlspecialchars($doctor['full_name']) ?></h2>
            
            <p class="profile-detail">
              <strong>Спеціальність:</strong>
              <?= htmlspecialchars($doctor['specialty'] ?? '—') ?>
            </p>

            <p class="profile-detail">
              <strong>Стаж:</strong>
              <?= htmlspecialchars($doctor['experience_years'] ?? '—') ?>
              <?= isset($doctor['experience_years']) && $doctor['experience_years'] !== '' ? ' років' : '' ?>
            </p>
          </div>

        </div>

        <div class="profile-actions">
          <a href="#" class="btn blue">Змінити пароль</a>
          <a href="/BumbleCare/handlers/logout.php" class="btn red">Вийти з акаунту</a>
        </div>
      </div>

      
      <!-- ФОРМА ІНФОРМАЦІЇ -->
      <?php
        $clinics = [];
        try {
          $stmt = $pdo->query("SELECT clinic_id, name FROM clinics ORDER BY name ASC");
          $clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
          $clinics = [];
        }
        ?>

        <form class="doctor-info-form">
          <div class="form-grid">

            <div class="form-group">
              <label>ПІБ</label>
              <input type="text" name="full_name" value="<?= htmlspecialchars($doctor['full_name']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Клініка</label>
              <select name="clinic_id" disabled>
                <option value=""></option>
                <?php foreach ($clinics as $clinic): ?>
                  <option value="<?= $clinic['clinic_id'] ?>"
                    <?= ($doctor['clinic_id'] ?? null) == $clinic['clinic_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($clinic['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Пошта</label>
              <input type="text" name="email" value="<?= htmlspecialchars($doctor['email']) ?>" disabled>
            </div>

            <div class="form-group">
              <label>Спеціальність</label>
              <input type="text" name="specialty" value="<?= htmlspecialchars($doctor['specialty'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Дата народження</label>
              <input type="date" name="date_of_birth" value="<?= htmlspecialchars($doctor['date_of_birth'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Вік</label>
              <input type="text" name="age" value="<?= $doctor['date_of_birth'] ? date_diff(date_create($doctor['date_of_birth']), date_create('today'))->y : '' ?>" disabled>
            </div>

            <div class="form-group">
              <label>Номер телефону</label>
              <input type="text" name="phone_number" value="<?= htmlspecialchars($doctor['phone_number'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Стаж</label>
              <input type="text" name="experience_years" value="<?= htmlspecialchars($doctor['experience_years'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Ідентифікаційний код</label>
              <input type="text" name="id_code" value="<?= htmlspecialchars($doctor['id_code'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Атестація</label>
              <input type="text" name="certification" value="<?= htmlspecialchars($doctor['certification'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Освіта</label>
              <input type="text" name="education" value="<?= htmlspecialchars($doctor['education'] ?? '') ?>" disabled>
            </div>

            <div class="form-group">
              <label>Стать</label>
              <select name="gender" disabled>
                <option value=""></option>
                <option value="Чоловіча" <?= ($doctor['gender'] ?? '') === 'Чоловіча' ? 'selected' : '' ?>>Чоловіча</option>
                <option value="Жіноча" <?= ($doctor['gender'] ?? '') === 'Жіноча' ? 'selected' : '' ?>>Жіноча</option>
              </select>
            </div>
          </div>

          <div class="form-group full-width">
            <label>Про мене</label>
            <textarea name="about" disabled><?= htmlspecialchars($doctor['about'] ?? '') ?></textarea>
          </div>

          <button type="button" class="btn blue" id="editDoctorInfo">Редагувати</button>
        </form>

    </div>
    

    <!-- "Мої відгуки" -->
    <div class="tab-content hidden" id="tab-reviews">

      <?php
      $sort = $_GET['sort'] ?? 'date_desc';
      $orderBy = match ($sort) {
          'rating_asc'  => 'r.rating ASC',
          'rating_desc' => 'r.rating DESC',
          'date_asc'    => 'r.created_at ASC',
          default       => 'r.created_at DESC',
      };

      $stmt_avg = $pdo->prepare("
          SELECT ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(*) AS total_reviews
          FROM reviews r
          WHERE r.doctor_id = (SELECT doctor_id FROM doctors WHERE user_id = ?)
      ");
      $stmt_avg->execute([$user_id]);
      $avg_data = $stmt_avg->fetch(PDO::FETCH_ASSOC);
      $avg_rating = $avg_data['avg_rating'] ?? 0;
      $total_reviews = $avg_data['total_reviews'] ?? 0;

      $stmt = $pdo->prepare("
          SELECT r.rating, r.comment, r.created_at,
                u.full_name, u.profile_image
          FROM reviews r
          LEFT JOIN patients p ON r.patient_id = p.patient_id
          LEFT JOIN users u ON p.user_id = u.user_id
          WHERE r.doctor_id = (SELECT doctor_id FROM doctors WHERE user_id = ?)
          ORDER BY $orderBy
      ");
      $stmt->execute([$user_id]);
      $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <div class="reviews-header">
        <div class="average-rating">
          <span class="rating-number"><?= htmlspecialchars($avg_rating) ?></span>
          <div class="stars-container" data-rating="<?= htmlspecialchars($avg_rating) ?>"></div>

          <span class="rating-count">(<?= $total_reviews ?> відгуків)</span>
        </div>

        <form class="sort-form" onsubmit="return false;">
          <label for="sort">Сортувати:</label>
          <select id="sort" name="sort">
            <option value="date_desc" <?= $sort==='date_desc'?'selected':'' ?>>Новіші спочатку</option>
            <option value="date_asc" <?= $sort==='date_asc'?'selected':'' ?>>Старіші спочатку</option>
            <option value="rating_desc" <?= $sort==='rating_desc'?'selected':'' ?>>Від більшої оцінки</option>
            <option value="rating_asc" <?= $sort==='rating_asc'?'selected':'' ?>>Від меншої оцінки</option>
          </select>
        </form>
      </div>

      <div id="reviews-container">
        <?php
        if ($reviews):
            foreach ($reviews as $review): ?>
              <div class="review-card">
                <div class="review-header">
                  <div class="review-user">
                    <img src="<?= htmlspecialchars($review['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" alt="Фото" class="review-avatar">
                    <div class="review-meta">
                      <p class="review-name"><?= htmlspecialchars($review['full_name']) ?></p>
                      <p class="review-date"><?= date('d.m.y H:i', strtotime($review['created_at'])) ?></p>
                    </div>
                  </div>
                  <div class="review-stars" data-rating="<?= htmlspecialchars($review['rating']) ?>"></div>

                </div>

                <div class="review-body">
                  <p><?= htmlspecialchars($review['comment']) ?></p>
                </div>
              </div>
        <?php
            endforeach;
        else:
            echo "<p class='no-reviews'>Поки що відгуків немає.</p>";
        endif;
        ?>
      </div>
    </div>

  </div>

  <!-- МОДАЛЬНЕ ВІКНО ЗМІНИ ПАРОЛЮ -->
  <div id="change-password-modal" class="modal hidden">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Зміна паролю</h2>

      <form id="change-password-form" class="register-form" novalidate>

        <div class="form-group password-group">
          <label for="current_password">Поточний пароль</label>
          <div class="password-input-wrapper">
            <input 
              type="password" 
              id="current_password" 
              name="current_password" 
              required 
              placeholder="current-password">
            <button type="button" class="toggle-password" aria-label="Показати пароль">
              <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
            </button>
          </div>
        </div>

        <div class="form-group password-group">
          <label for="new_password">Новий пароль</label>
          <div class="password-input-wrapper">
            <input 
              type="password" 
              id="new_password" 
              name="new_password" 
              minlength="6" 
              required 
              placeholder="new-password">
            <button type="button" class="toggle-password" aria-label="Показати пароль">
              <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
            </button>
          </div>
        </div>

        <div class="form-group password-group">
          <label for="confirm_password">Підтвердження пароля</label>
          <div class="password-input-wrapper">
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              minlength="6" 
              required 
              placeholder="confirm-password">
            <button type="button" class="toggle-password" aria-label="Показати пароль">
              <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
            </button>
          </div>
        </div>

        <button type="submit" class="btn-approve-main">Зберегти</button>
      </form>

    </div>
  </div>

</main>

<script src="/BumbleCare/assets/js/doctor_profile.js"></script>
<script src="/BumbleCare/assets/js/change_password.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
