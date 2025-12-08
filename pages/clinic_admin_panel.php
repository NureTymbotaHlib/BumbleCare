<?php
$requireLogin = true;
$requireRole = 'clinic_admin';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'clinic_admin_panel.css';

$stmt = $pdo->prepare("
    SELECT 
        u.full_name,
        u.profile_image,
        ca.clinic_id,
        c.name AS clinic_name
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    JOIN clinics c ON ca.clinic_id = c.clinic_id
    WHERE ca.user_id = ?
");
$stmt->execute([$user_id]);
$adminClinic = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$adminClinic) {
    die("Помилка: не знайдено клініку адміністратора.");
}

$clinic_id = $adminClinic['clinic_id'];

if (
  $_SERVER['REQUEST_METHOD'] === 'POST'
  && isset($_FILES['clinic_photo'])
  && $_FILES['clinic_photo']['error'] === UPLOAD_ERR_OK
) {
  $upload_dir = __DIR__ . '/../assets/images/clinics/';

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }

  $file = $_FILES['clinic_photo'];
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $allowed = ['jpg', 'jpeg', 'png'];

  if (in_array($ext, $allowed, true)) {
    $old = $pdo->prepare("SELECT image_url FROM clinics WHERE clinic_id = ?");
    $old->execute([$clinic_id]);
    $current = $old->fetchColumn();

    if (!empty($current) && strpos($current, 'default_clinic') === false) {
      $oldPath = $upload_dir . basename($current);
      if (file_exists($oldPath)) {
        unlink($oldPath);
      }
    }

    $new_name = "clinic_{$clinic_id}_" . time() . "." . $ext;
    $path = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
      $db_path = "/BumbleCare/assets/images/clinics/" . $new_name;

      $update = $pdo->prepare("UPDATE clinics SET image_url = ? WHERE clinic_id = ?");
      $update->execute([$db_path, $clinic_id]);

      header("Location: " . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
}

$doctors_stmt = $pdo->prepare("
  SELECT d.doctor_id, u.full_name, u.email, u.status, d.specialty
  FROM doctors d
  JOIN users u ON d.user_id = u.user_id
  WHERE d.clinic_id = ?
    AND u.status = 'active'
");
$doctors_stmt->execute([$clinic_id]);
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

$clinic_stmt = $pdo->prepare("
  SELECT name, description, city, address, phone, email, image_url
  FROM clinics
  WHERE clinic_id = ?
");
$clinic_stmt->execute([$clinic_id]);
$clinic = $clinic_stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="clinic-admin-panel">
  <div class="bc-container panel-wrapper">
		<div class="tabs">
			<button class="tab active">Управління лікарями</button>
			<button class="tab">Управління відгуками</button>
			<button class="tab">Клініка</button>
		</div>

		<div class="panel-header">
			<div class="profile-photo">
				<img 
					src="<?= htmlspecialchars($adminClinic['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>"
					alt="Фото профілю"
					class="profile-img"
				>

				<div class="profile-info">
					<h2><?= htmlspecialchars($adminClinic['full_name']) ?></h2>

					<p class="profile-detail">
						<strong>Адміністратор клініки:</strong>
						<?= htmlspecialchars($adminClinic['clinic_name']) ?>
					</p>
				</div>
			</div>
		</div>

		<div class="tab-content active" id="tab-doctors">
			<section class="panel-section">
				<h2>Додати лікаря</h2>
				<form id="addDoctorForm" class="panel-form">
					<div class="form-group">
						<label>Повне ім’я лікаря</label>
						<input type="text" name="full_name" placeholder="Введіть ПІБ лікаря">
					</div>
					<div class="form-group">
						<label>Email</label>
						<input type="email" name="email" placeholder="Введіть пошту лікаря">
					</div>
					<div class="form-group">
						<label>Початковий пароль</label>
						<div class="password-input-wrapper">
							<input type="password" name="password" placeholder="Введіть пароль">
							<button type="button" class="toggle-password" aria-label="Показати пароль">
								<img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon" alt="">
							</button>
						</div>
					</div>
					<div class="form-group">
						<label>Підтвердження пароля</label>
						<div class="password-input-wrapper">
							<input type="password" name="confirm_password" placeholder="Повторіть пароль">
							<button type="button" class="toggle-password" aria-label="Показати пароль">
								<img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon" alt="">
							</button>
						</div>
					</div>
					<div class="form-group">
						<label>Номер телефону</label>
						<input type="text" name="phone" placeholder="Введіть номер телефону лікаря">
					</div>
					<button type="submit" class="btn-submit">Додати лікаря</button>
				</form>
			</section>

			<section class="panel-section">
				<h2>Редагувати лікаря</h2>

				<form id="editDoctorForm" class="panel-form">
					<select name="doctor_id">
						<option value="">Оберіть лікаря...</option>
						<?php foreach ($doctors as $doc): ?>
								<option value="<?= $doc['doctor_id'] ?>">
									<?= htmlspecialchars($doc['full_name']) ?> (<?= $doc['specialty'] ?>)
								</option>
						<?php endforeach; ?>
					</select>

					<div class="form-group">
						<label>Повне ім’я лікаря</label>
						<input type="text" name="full_name" placeholder="ПІБ лікаря">
					</div>
					<div class="form-group">
						<label>Email</label>
						<input type="email" name="email" placeholder="Email лікаря">
					</div>
					<div class="form-group">
						<label>Номер телефону</label>
						<input type="text" name="phone" placeholder="Номер телефону">
					</div>
					<div class="form-group">
						<label>Спеціальність</label>
						<input type="text" name="specialty" placeholder="Спеціальність">
					</div>
					<div class="form-group">
						<label>Освіта</label>
						<input type="text" name="education" placeholder="Освіта">
					</div>
					<div class="form-group">
						<label>Стаж (років)</label>
						<input type="number" name="experience" placeholder="Кількість років">
					</div>
					<div class="form-group">
						<label>Номер ліцензії</label>
						<input type="text" name="license_number" placeholder="Номер ліцензії">
					</div>

					<div class="form-group">
						<label>Атестація</label>
						<input type="text" name="certification" placeholder="Кваліфікація / атестація">
					</div>

					<div class="form-group">
						<label>Стать</label>
						<select name="gender">
							<option value="">Стать</option>
							<option value="Чоловіча">Чоловіча</option>
							<option value="Жіноча">Жіноча</option>
						</select>
					</div>

					<div class="form-group">
						<label>Дата народження</label>
						<input type="date" name="date_of_birth" id="dayInput">
					</div>

					<div class="form-group">
						<label>Ідентифікаційний код</label>
						<input type="text" name="id_code" placeholder="Ідентифікаційний код">
					</div>

					<div class="form-group">
						<label>Про лікаря</label>
						<textarea name="about" placeholder="Коротка інформація про лікаря"></textarea>
					</div>

					<button type="submit" class="btn-submit">Оновити лікаря</button>
				</form>
			</section>

			<section class="panel-section">
				<h2>Деактивувати лікаря</h2>

				<form id="deactivateDoctorForm" class="panel-form">
					<select name="doctor_id">
						<option value="">Оберіть лікаря...</option>
						<?php foreach ($doctors as $doc): ?>
							<?php if ($doc['status'] === 'active'): ?>
								<option value="<?= $doc['doctor_id'] ?>">
									<?= htmlspecialchars($doc['full_name']) ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<button type="submit" class="btn-submit btn-red">Деактивувати</button>
				</form>
			</section>
		</div>

		<div class="tab-content hidden" id="tab-reviews">
			<section class="panel-section">
				<h2>Управління відгуками</h2>

				<div class="reviews-filter-block">
					<form id="reviewsFilterForm" class="reviews-filter-form">
						<input type="hidden" name="clinic_id" value="<?= $clinic_id ?>">

						<div class="filter-grid">
							<div class="filter-col">
								<label>Пошук лікаря</label>
								<div class="doctor-input-wrapper">
									<input type="text" name="doctor_query" placeholder="ПІБ лікаря">
									<button type="button" id="clearDoctorQuery" class="btn-clear" style="display:none;">Скинути</button>
								</div>
							</div>

							<div class="filter-col">
								<label>Статус</label>
								<select name="status">
									<option value="pending">Очікує</option>
									<option value="approved">Схвалені</option>
									<option value="rejected">Відхилені</option>
									<option value="hidden">Приховані</option>
								</select>
							</div>
						</div>

						<div class="controls-bar">
							<div class="buttons-block">
								<button type="submit" class="btn-apply">Застосувати</button>
								<button type="button" class="btn-reset" id="resetReviewsFilters">Скинути</button>
							</div>
							<div class="sort-bar">
								<label>Сортувати:</label>
								<select name="sort" id="reviewsSortSelect">
									<option value="date_desc">Новіші</option>
									<option value="date_asc">Старіші</option>
									<option value="rating_desc">Вища оцінка</option>
									<option value="rating_asc">Нижча оцінка</option>
								</select>
							</div>
						</div>

					</form>
				</div>

				<div id="reviewsResultsContainer" class="reviews-results"></div>
			</section>
		</div>

		<div class="tab-content hidden" id="tab-clinic">
			<section class="panel-section">
				<h2>Інформація про клініку</h2>

				<div class="form-group">
					<label>Фото клініки</label>
					<form method="POST" enctype="multipart/form-data" class="clinic-photo-form" id="clinicPhotoForm">
						<label for="clinic-photo-upload" class="clinic-photo-label">
							<img src="<?= htmlspecialchars($clinic['image_url'] ?? '/BumbleCare/assets/images/default_clinic.jpg') ?>" alt="Фото клініки" class="clinic-photo-img" id="clinicPhotoPreview">
						</label>
						<input type="file" name="clinic_photo" id="clinic-photo-upload"	accept=".jpg,.jpeg,.png" onchange="document.getElementById('clinicPhotoForm').submit()">
					</form>
				</div>	

				<form id="editClinicForm" class="clinic-edit-form">
					<div class="form-group">
						<label>Назва клініки</label>
						<input type="text" name="name" placeholder="Назва клініки" value="<?= htmlspecialchars($clinic['name']) ?>" disabled>
					</div>
					<div class="form-group">
						<label>Опис клініки</label>
						<textarea name="description" placeholder="Опис клініки" disabled><?= htmlspecialchars($clinic['description']) ?></textarea>
					</div>
					<div class="form-group">
						<label>Місто</label>
						<input type="text" name="city" placeholder="Місто" value="<?= htmlspecialchars($clinic['city']) ?>" disabled>
					</div>
					<div class="form-group">
						<label>Адреса</label>
						<input type="text" name="address" placeholder="Адреса" value="<?= htmlspecialchars($clinic['address']) ?>" disabled>
					</div>
					<div class="form-group">
						<label>Номер телефону</label>
						<input type="text" name="phone" placeholder="Телефон" value="<?= htmlspecialchars($clinic['phone']) ?>" disabled>
					</div>
					<div class="form-group">
						<label>Email</label>
						<input type="text" name="email" placeholder="Email" value="<?= htmlspecialchars($clinic['email']) ?>" disabled>
					</div>
					<input type="hidden" name="clinic_id" value="<?= $clinic_id ?>">

					<button type="button" class="btn-submit blue" id="editClinicBtn">Редагувати</button>
				</form>
			</section>
		</div>

  </div>
</main>

<script src="/BumbleCare/assets/js/clinic_admin_panel.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>
<script src="/BumbleCare/assets/js/rating_stars.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
