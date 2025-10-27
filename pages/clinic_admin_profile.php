<?php
$requireRole = 'clinic_admin';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'clinic_admin_profile.css';
include __DIR__ . '/../includes/header.php';

if (empty($user_id)) {
    header("Location: /BumbleCare/pages/login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        u.full_name,
        u.email,
        u.phone_number,
        u.profile_image,
        ca.clinic_id,
        c.name AS clinic_name
    FROM users u
    LEFT JOIN clinic_admins ca ON u.user_id = ca.user_id
    LEFT JOIN clinics c ON ca.clinic_id = c.clinic_id
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
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
        if (!empty($admin['profile_image']) && strpos($admin['profile_image'], 'default_avatar.png') === false) {
            $oldPath = $upload_dir . basename($admin['profile_image']);
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

<main class="clinic-admin-profile">
  <div class="bc-container profile-wrapper">

    <div class="profile-header">
      <div class="profile-photo">
        <form method="POST" enctype="multipart/form-data" class="photo-upload-form" id="photoForm">
          <label for="photo-upload" class="photo-label">
            <img 
              src="<?= htmlspecialchars($admin['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
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
          <h2><?= htmlspecialchars($admin['full_name']) ?></h2>
          <p class="profile-detail">
            <strong>Адміністратор клініки:</strong>
            <?= htmlspecialchars($admin['clinic_name'] ?? 'Без клініки') ?>
          </p>
        </div>
      </div>



      <div class="profile-actions">
        <a href="#" class="btn blue">Змінити пароль</a>
        <a href="/BumbleCare/handlers/logout.php" class="btn red">Вийти з акаунту</a>
      </div>
    </div>

    <form class="admin-info-form">
      <div class="form-grid">

        <div class="form-group">
          <label>ПІБ</label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" disabled>
        </div>

        <div class="form-group">
          <label>Пошта</label>
          <input type="text" name="email" value="<?= htmlspecialchars($admin['email']) ?>" disabled>
        </div>

        <div class="form-group">
          <label>Номер телефону</label>
          <input type="text" name="phone_number" value="<?= htmlspecialchars($admin['phone_number'] ?? '') ?>" disabled>
        </div>
      </div>
      <button type="button" class="btn blue" id="editAdminInfo">Редагувати</button>
    </form>

  </div>

  <!-- Модальне вікно зміни паролю -->
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

<script src="/BumbleCare/assets/js/clinic_admin_profile.js"></script>
<script src="/BumbleCare/assets/js/change_password.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
