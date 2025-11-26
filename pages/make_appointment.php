<?php
$requireLogin = false;
$allowRoles   = ['patient'];
$allowGuest   = true;

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'make_appointment.css';
include __DIR__ . '/../includes/header.php';

$doctor_id = $_GET['doctor_id'] ?? null;
if (!$doctor_id) {
  echo "<p class='error'>Лікаря не знайдено.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$stmt = $pdo->prepare("
    SELECT 
        u.full_name, u.profile_image, d.specialty,
        c.name AS clinic_name, c.city AS clinic_city
    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    LEFT JOIN clinics c ON d.clinic_id = c.clinic_id
    WHERE d.doctor_id = ?
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
  echo "<p class='error'>Лікаря не знайдено.</p>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}
?>

<main class="make-appointment-page">
  <div class="bc-container doctor-profile">

    <div class="doctor-main">
      <div class="doctor-left">
        <img 
          src="<?= htmlspecialchars($doctor['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>" 
          alt="Фото лікаря"
          class="doctor-avatar"
        >
        <div class="doctor-info">
          <p class="doctor-name"><?= htmlspecialchars($doctor['full_name']) ?></p>
          <p class="doctor-speciality"><strong>Спеціальність:</strong> <?= htmlspecialchars($doctor['specialty'] ?? '—') ?></p>
          <p class="doctor-clinic"><strong>Місце роботи:</strong> <?= htmlspecialchars($doctor['clinic_name']) ?> (<?= htmlspecialchars($doctor['clinic_city']) ?>)</p>
        </div>
      </div>

      <div class="doctor-right">
        <button class="btn-back" onclick="window.location.href='/BumbleCare/pages/search.php'">Назад до вибору лікаря</button>
      </div>
    </div>

    <div class="schedule-section">
      <h2>Оберіть дату та час прийому</h2>
      <div id="slotsContainer" class="slots-container">
        <p class="loading">Завантаження слотів...</p>
      </div>
    </div>

  </div>
</main>

<div id="confirmModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Підтвердження запису</h2>
    <p id="confirmInfo"></p>
    <p>Ви впевнені, що хочете створити бронювання?</p>

    <div class="modal-actions">
      <button class="btn yes">Так</button>
      <button class="btn no">Ні</button>
    </div>
  </div>
</div>

<div id="loginModal" class="modal hidden">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Запис до лікаря</h2>
    <p>Для того, щоб створити запис, увійдіть у систему</p>
    <a href="/BumbleCare/pages/login.php" class="btn-login">Перейти на сторінку авторизації</a>
  </div>
</div>


<script src="/BumbleCare/assets/js/make_appointment.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
