<?php
$requireRole = 'patient';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'patient_profile.css';
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
        p.gender,
        p.identification_code AS id_code,
        p.social_status,
        p.insurance_number,
        p.city,
        p.address,
        p.medical_card AS medcard,
        p.date_of_birth
    FROM users u
    LEFT JOIN patients p ON u.user_id = p.user_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>Помилка: користувач не знайдений.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = realpath(__DIR__ . '/../assets/images/uploads/') . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['profile_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (in_array($ext, $allowed)) {
        if (!empty($user['profile_image']) && strpos($user['profile_image'], 'default_avatar.png') === false) {
            $upload_dir = realpath(__DIR__ . '/../assets/images/uploads/') . DIRECTORY_SEPARATOR;
            $old_filename = basename($user['profile_image']);
            $oldPath = $upload_dir . $old_filename;

            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $path = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            $db_path = '/BumbleCare/assets/images/uploads/' . $new_name;
            $update = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            $update->execute([$db_path, $user_id]);
            $user['profile_image'] = $db_path;
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $error = "Не вдалося зберегти файл.";
        }
    } else {
        $error = "Недопустимий формат файлу. Дозволені: jpg, jpeg, png.";
    }
}

?>
<main class="patient-profile">
  <div class="bc-container profile-wrapper">

    <div class="profile-header">
      <div class="profile-photo">
        <form method="POST" enctype="multipart/form-data" class="photo-upload-form" id="photoForm">
          <label for="photo-upload" class="photo-label">
            <img 
              src="<?= htmlspecialchars($user['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
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
          <h2><?= htmlspecialchars($user['full_name']) ?></h2>
          <p class="reg-date">Зареєстровано: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
        </div>

      </div>

      <div class="profile-actions">
        <a href="#" class="btn blue">Змінити пароль</a>
        <a href="/BumbleCare/handlers/logout.php" class="btn red">Вийти з акаунту</a>
      </div>
    </div>

    <div class="profile-cards">
      <a href="patient_info.php" id="open-info" class="profile-card">
        <img src="/BumbleCare/assets/icons/myinfo.png" alt="">
        <span>Особиста інформація</span>
      </a>
      <a href="patient_appointments.php" class="profile-card">
        <img src="/BumbleCare/assets/icons/myrecords.png" alt="" style="margin-left: 10px">
        <span>Мої записи</span>
      </a>
      <a href="patient_feedbacks.php" class="profile-card">
        <img src="/BumbleCare/assets/icons/myfeedbacks.png" alt="">
        <span>Мої відгуки</span>
      </a>
    </div>

    <?php if (!empty($error)): ?>
      <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

  </div>

  <!-- МОДАЛЬНОЕ ОКНО -->
<div id="patient-info-modal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Особиста інформація</h2>

    <form id="patient-info-form">
      <div class="form-grid">
        <div class="form-group">
          <label>ПІБ</label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" disabled>
        </div>
        <div class="form-group">
          <label>Стать</label>
          <select name="gender" disabled>
            <option value=""></option>
            <option value="Чоловіча" <?= ($user['gender'] ?? '') === 'Чоловіча' ? 'selected' : '' ?>>Чоловіча</option>
            <option value="Жіноча" <?= ($user['gender'] ?? '') === 'Жіноча' ? 'selected' : '' ?>>Жіноча</option>
          </select>
        </div>

        <div class="form-group">
          <label>Пошта</label>
          <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>
        <div class="form-group">
          <label>Номер телефону</label>
          <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" disabled>
        </div>

        <div class="form-group">
          <label>Дата народження</label>
          <input type="date" id="date_of_birth" name="date_of_birth" 
                value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
          <label>Вік</label>
          <input type="text" id="age" name="age" disabled>
        </div>

        <div class="form-group">
          <label>Ідентифікаційний код</label>
          <input type="text" name="id_code" value="<?= htmlspecialchars($user['id_code'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
          <label>Соціальний статус</label>
          <select name="social_status" disabled>
            <option value=""></option>
            <option value="особа з інвалідністю I групи" <?= ($user['social_status'] ?? '') === 'особа з інвалідністю I групи' ? 'selected' : '' ?>>особа з інвалідністю I групи</option>
            <option value="особа з інвалідністю II групи" <?= ($user['social_status'] ?? '') === 'особа з інвалідністю II групи' ? 'selected' : '' ?>>особа з інвалідністю II групи</option>
            <option value="особа з інвалідністю III групи" <?= ($user['social_status'] ?? '') === 'особа з інвалідністю III групи' ? 'selected' : '' ?>>особа з інвалідністю III групи</option>
            <option value="ветеран війни" <?= ($user['social_status'] ?? '') === 'ветеран війни' ? 'selected' : '' ?>>ветеран війни</option>
            <option value="дитина війни" <?= ($user['social_status'] ?? '') === 'дитина війни' ? 'selected' : '' ?>>дитина війни</option>
            <option value="учасник бойових дій" <?= ($user['social_status'] ?? '') === 'учасник бойових дій' ? 'selected' : '' ?>>учасник бойових дій</option>
            <option value="учасник ліквідації наслідків аварії на ЧАЕС" <?= ($user['social_status'] ?? '') === 'учасник ліквідації наслідків аварії на ЧАЕС' ? 'selected' : '' ?>>учасник ліквідації наслідків аварії на ЧАЕС</option>
            <option value="пенсіонер" <?= ($user['social_status'] ?? '') === 'пенсіонер' ? 'selected' : '' ?>>пенсіонер</option>
            <option value="студент" <?= ($user['social_status'] ?? '') === 'студент' ? 'selected' : '' ?>>студент</option>
            <option value="безробітний" <?= ($user['social_status'] ?? '') === 'безробітний' ? 'selected' : '' ?>>безробітний</option>
            <option value="працюючий" <?= ($user['social_status'] ?? '') === 'працюючий' ? 'selected' : '' ?>>працюючий</option>
            <option value="багатодітна сімʼя" <?= ($user['social_status'] ?? '') === 'багатодітна сімʼя' ? 'selected' : '' ?>>багатодітна сімʼя</option>
            <option value="внутрішньо переміщена особа" <?= ($user['social_status'] ?? '') === 'внутрішньо переміщена особа' ? 'selected' : '' ?>>внутрішньо переміщена особа</option>
          </select>
        </div>

        <div class="form-group">
          <label>Місто</label>
          <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
          <label>Номер страхового полісу</label>
          <input type="text" name="insurance_number" value="<?= htmlspecialchars($user['insurance_number'] ?? '') ?>" disabled>
        </div>

        <div class="form-group">
          <label>Адреса</label>
          <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
          <label>Медкарта</label>
          <input type="text" name="medcard" value="<?= htmlspecialchars($user['medcard'] ?? '') ?>" disabled>
        </div>
      </div>

      <button type="button" id="edit-btn" class="btn blue">Редагувати</button>
    </form>
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

<script src="/BumbleCare/assets/js/patient_profile.js"></script>
<script src="/BumbleCare/assets/js/change_password.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
