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

$doctors_stmt = $pdo->prepare("
  SELECT d.doctor_id, u.full_name, u.email, u.status, d.specialty
  FROM doctors d
  JOIN users u ON d.user_id = u.user_id
  WHERE d.clinic_id = ?
    AND u.status = 'active'
");
$doctors_stmt->execute([$clinic_id]);
$doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);

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

					<input type="text" name="full_name" placeholder="ПІБ лікаря">
					<input type="email" name="email" placeholder="Email лікаря">
					<input type="text" name="phone" placeholder="Номер телефону">
					<input type="text" name="specialty" placeholder="Спеціальність">
					<input type="text" name="education" placeholder="Освіта">
					<input type="number" name="experience" placeholder="Стаж (років)">
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
				<p>Управління відгуками</p>
		</div>

		<div class="tab-content hidden" id="tab-clinic">
				<p>Керування клінікою</p>
		</div>

  </div>
</main>

<script src="/BumbleCare/assets/js/clinic_admin_panel.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
