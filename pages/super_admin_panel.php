<?php
$requireLogin = true;
$requireRole = 'super_admin';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = [
  'super_admin_panel.css',
  'manage_reviews_tab.css'
];

$stmt = $pdo->prepare("
    SELECT u.full_name, u.profile_image
    FROM users u
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$allClinics = $pdo->query("
    SELECT clinic_id, name 
    FROM clinics
")->fetchAll(PDO::FETCH_ASSOC);

$admins = $pdo->query("
    SELECT ca.admin_id, u.full_name, ca.clinic_id
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    WHERE u.status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

$doctors = $pdo->query("
  SELECT 
    d.doctor_id,
    u.full_name,
    u.status,
    d.specialty,
    d.clinic_id
  FROM doctors d
  JOIN users u ON d.user_id = u.user_id
  ORDER BY u.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$patients = $pdo->query("
  SELECT
    p.patient_id,
    u.full_name,
    u.status
  FROM patients p
  JOIN users u ON p.user_id = u.user_id
  ORDER BY u.full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="super-admin-panel">
  <div class="bc-container panel-wrapper">

    <div class="tabs">
      <div class="tab-block">
        <button class="tab active">Управління лікарями</button>
        <button class="tab">Управління адміністраторами клінік</button>
      </div>
      <div class="tab-block">
        <button class="tab">Управління відгуками</button>
        <button class="tab">Управління пацієнтами</button>
        <button class="tab">Управління клініками</button>
      </div>
    </div>

    <div class="panel-header">
      <div class="profile-photo">
        <img 
          src="<?= htmlspecialchars($admin['profile_image'] ?? '/BumbleCare/assets/images/default_avatar.png') ?>"
          alt="Фото профілю"
          class="profile-img"
        >

        <div class="profile-info">
          <h2><?= htmlspecialchars($admin['full_name']) ?></h2>

          <p class="profile-detail">
            <strong>Роль:</strong> Супер Адміністратор системи
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
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Підтвердження пароля</label>
            <div class="password-input-wrapper">
              <input type="password" name="confirm_password" placeholder="Повторіть пароль">
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Номер телефону</label>
            <input type="text" name="phone" placeholder="Введіть номер телефону лікаря">
          </div>

          <div class="form-group">
            <label>Клініка</label>
            <select name="clinic_id">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-submit">Додати лікаря</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Редагувати лікаря</h2>

        <form id="editDoctorForm" class="panel-form">

          <div class="form-group">
            <select id="editClinicSelect">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <select name="doctor_id" id="editDoctorSelect">
              <option value="" disabled selected hidden>Оберіть лікаря...</option>

              <?php foreach ($doctors as $doc): ?>
                <?php if ($doc['status'] === 'active'): ?>
                  <option
                    value="<?= $doc['doctor_id'] ?>"
                    data-clinic="<?= $doc['clinic_id'] ?>"
                  >
                  <?= htmlspecialchars($doc['full_name']) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>

            </select>
          </div>

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

          <div class="form-group">
            <label>Клініка де працює</label>
            <select name="clinic_id">
              <option value="" disabled selected hidden>Клініка лікаря...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-submit">Оновити лікаря</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Деактивувати лікаря</h2>

        <form id="deactivateDoctorForm" class="panel-form">

          <div class="form-group">
            <select id="deactivateClinicSelect">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <select name="doctor_id" id="deactivateDoctorSelect">
              <option value="" disabled selected hidden>Оберіть лікаря...</option>

              <?php foreach ($doctors as $doc): ?>
                <?php if ($doc['status'] === 'active'): ?>
                  <option
                    value="<?= $doc['doctor_id'] ?>"
                    data-clinic="<?= $doc['clinic_id'] ?>"
                  >
                    <?= htmlspecialchars($doc['full_name']) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>

            </select>
          </div>

          <button type="submit" class="btn-submit btn-red">
            Деактивувати
          </button>
        </form>
      </section>

    </div>

    <div class="tab-content hidden" id="tab-admins">
      <section class="panel-section">
        <h2>Додати адміністратора клініки</h2>

        <form id="addClinicAdminForm" class="panel-form">

          <div class="form-group">
            <label>Повне ім’я адміністратора</label>
            <input type="text" name="full_name" placeholder="Введіть ПІБ адміністратора">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Введіть пошту адміністратора">
          </div>

          <div class="form-group">
            <label>Початковий пароль</label>
            <div class="password-input-wrapper">
              <input type="password" name="password" placeholder="Введіть пароль">
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Підтвердження пароля</label>
            <div class="password-input-wrapper">
              <input type="password" name="confirm_password" placeholder="Повторіть пароль">
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Номер телефону</label>
            <input type="text" name="phone" placeholder="Введіть номер телефону адміністратора">
          </div>

          <div class="form-group">
            <label>Прив’язати до клініки</label>
            <select name="clinic_id">
              <option value="" disabled selected hidden>Оберіть клініку...</option>

              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>

            </select>
          </div>

          <button type="submit" class="btn-submit">Додати адміністратора</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Редагувати адміністратора клініки</h2>
        <form id="editClinicAdminForm" class="panel-form">
          <div class="form-group">
            <select id="editAdminClinicSelect">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <select name="admin_id" id="editAdminSelect">
              <option value="" disabled selected hidden>Оберіть адміністратора...</option>

              <?php foreach ($admins as $a): ?>
                <option
                  value="<?= $a['admin_id'] ?>"
                  data-clinic="<?= $a['clinic_id'] ?>"
                >
                  <?= htmlspecialchars($a['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Повне ім’я адміністратора</label>
            <input type="text" name="full_name" placeholder="ПІБ адміністратора">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Email адміністратора">
          </div>

          <div class="form-group">
            <label>Номер телефону</label>
            <input type="text" name="phone" placeholder="Номер телефону">
          </div>

          <div class="form-group">
            <label>Клініка</label>
            <select name="clinic_id">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-submit">Оновити адміністратора</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Деактивувати адміністратора</h2>

        <form id="deactivateClinicAdminForm" class="panel-form">
          <div class="form-group">
            <select id="deactivateAdminClinicSelect">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <select name="admin_id" id="deactivateAdminSelect">
              <option value="" disabled selected hidden>Оберіть адміністратора...</option>

              <?php foreach ($admins as $a): ?>
                <option
                  value="<?= $a['admin_id'] ?>"
                  data-clinic="<?= $a['clinic_id'] ?>"
                >
                  <?= htmlspecialchars($a['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-submit btn-red">
            Деактивувати
          </button>
        </form>
      </section>

    </div>


		<div class="tab-content hidden" id="tab-reviews">
			<section class="panel-section">
				<h2>Управління відгуками</h2>

				<div class="reviews-filter-block">
					<form id="reviewsFilterForm" class="reviews-filter-form">

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

    <div class="tab-content hidden" id="tab-users">
      <section class="panel-section">
        <h2>Додати пацієнта</h2>

        <form id="addPatientForm" class="panel-form">
          <div class="form-group">
            <label>Повне ім’я пацієнта</label>
            <input type="text" name="full_name" placeholder="Введіть ПІБ пацієнта">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Введіть пошту пацієнта">
          </div>

          <div class="form-group">
            <label>Початковий пароль</label>
            <div class="password-input-wrapper">
              <input type="password" name="password" placeholder="Введіть пароль">
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Підтвердження пароля</label>
            <div class="password-input-wrapper">
              <input type="password" name="confirm_password" placeholder="Повторіть пароль">
              <button type="button" class="toggle-password">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" class="eye-icon">
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>Номер телефону</label>
            <input type="text" name="phone" placeholder="Введіть номер телефону пацієнта">
          </div>

          <button type="submit" class="btn-submit">Додати пацієнта</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Редагувати пацієнта</h2>

        <form id="editPatientForm" class="panel-form">
          <div class="form-group">
            <select name="patient_id" id="editPatientSelect">
              <option value="" disabled selected hidden>Оберіть пацієнта...</option>

              <?php foreach ($patients as $p): ?>
                <?php if ($p['status'] === 'active'): ?>
                  <option value="<?= $p['patient_id'] ?>">
                    <?= htmlspecialchars($p['full_name']) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Повне ім’я пацієнта</label>
            <input type="text" name="full_name" placeholder="ПІБ пацієнта">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Email пацієнта">
          </div>

          <div class="form-group">
            <label>Номер телефону</label>
            <input type="text" name="phone" placeholder="Номер телефону">
          </div>

          <div class="form-group">
            <label>Дата народження</label>
            <input type="date" name="date_of_birth" placeholder="Оберіть дату">
          </div>

          <div class="form-group">
            <label>Стать</label>
            <select name="gender">
              <option value="">Оберіть стать</option>
              <option value="male">Чоловіча</option>
              <option value="female">Жіноча</option>
            </select>
          </div>

          <div class="form-group">
            <label>Місто</label>
            <input type="text" name="city" placeholder="Місто">
          </div>

          <div class="form-group">
            <label>Адреса</label>
            <input type="text" name="address" placeholder="Вулиця, будинок, квартира">
          </div>

          <div class="form-group">
            <label>Ідентифікаційний код</label>
            <input type="text" name="identification_code" placeholder="Ідентифікаційний код пацієнта">
          </div>

          <div class="form-group">
            <label>Соціальний статус</label>
            <select name="social_status">
              <option value="" disabled selected hidden>Оберіть статус...</option>
              <option value="особа з інвалідністю I групи">особа з інвалідністю I групи</option>
              <option value="особа з інвалідністю II групи">особа з інвалідністю II групи</option>
              <option value="особа з інвалідністю III групи">особа з інвалідністю III групи</option>
              <option value="ветеран війни">ветеран війни</option>
              <option value="дитина війни">дитина війни</option>
              <option value="учасник бойових дій">учасник бойових дій</option>
              <option value="учасник ліквідації наслідків аварії на ЧАЕС">учасник ліквідації наслідків аварії на ЧАЕС</option>
              <option value="пенсіонер">пенсіонер</option>
              <option value="студент">студент</option>
              <option value="безробітний">безробітний</option>
              <option value="працюючий">працюючий</option>
              <option value="багатодітна сімʼя">багатодітна сімʼя</option>
              <option value="внутрішньо переміщена особа">внутрішньо переміщена особа</option>
            </select>
          </div>

          <div class="form-group">
            <label>Страховий номер</label>
            <input type="text" name="insurance_number" placeholder="Номер страхового полісу">
          </div>

          <button type="submit" class="btn-submit">Оновити пацієнта</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Деактивувати пацієнта</h2>

        <form id="deactivatePatientForm" class="panel-form">
          <div class="form-group">
            <select name="patient_id" id="deactivatePatientSelect">
              <option value="" disabled selected hidden>Оберіть пацієнта...</option>

              <?php foreach ($patients as $p): ?>
                <?php if ($p['status'] === 'active'): ?>
                  <option value="<?= $p['patient_id'] ?>">
                    <?= htmlspecialchars($p['full_name']) ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn-submit btn-red">Деактивувати</button>
        </form>
      </section>
    </div>

    <div class="tab-content hidden" id="tab-clinics">
      <section class="panel-section">
        <h2>Додати клініку</h2>

        <form id="clinicAddForm" class="panel-form" enctype="multipart/form-data">
          <div class="form-group">
            <label>Назва клініки</label>
            <input type="text" name="name" placeholder="Назва клініки">
          </div>

          <div class="form-group">
            <label>Опис</label>
            <textarea name="description" placeholder="Опис клініки"></textarea>
          </div>

          <div class="form-group">
            <label>Місто</label>
            <input type="text" name="city" placeholder="Місто">
          </div>

          <div class="form-group">
            <label>Адреса</label>
            <input type="text" name="address" placeholder="Адреса">
          </div>

          <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone" placeholder="Телефон">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Email">
          </div>

          <div class="form-group">
            <label>Фото клініки</label>
            <input
              type="file"
              name="clinic_photo"
              id="addClinicPhotoInput"
              accept=".jpg,.jpeg,.png"
            >

            <div class="clinic-photo-preview">
              <img
                id="addClinicPhotoPreview"
                src="/BumbleCare/assets/images/default_clinic.jpg"
                alt="Превʼю фото клініки"
              >
            </div>
          </div>

          <button type="submit" class="btn-submit">Додати клініку</button>
        </form>
      </section>

      <section class="panel-section">
        <h2>Редагувати клініку</h2>

        <form id="clinicEditForm" class="panel-form" enctype="multipart/form-data">

          <div class="form-group">
            <select name="clinic_id" id="clinicEditSelect">
              <option value="" disabled selected hidden>Оберіть клініку...</option>
              <?php foreach ($allClinics as $cl): ?>
                <option value="<?= $cl['clinic_id'] ?>">
                  <?= htmlspecialchars($cl['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Назва клініки</label>
            <input type="text" name="name" placeholder="Назва клініки">
          </div>

          <div class="form-group">
            <label>Опис</label>
            <textarea name="description" placeholder="Опис клініки"></textarea>
          </div>

          <div class="form-group">
            <label>Місто</label>
            <input type="text" name="city" placeholder="Місто">
          </div>

          <div class="form-group">
            <label>Адреса</label>
            <input type="text" name="address" placeholder="Адреса">
          </div>

          <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone" placeholder="Телефон">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Email">
          </div>

          <div class="form-group">
            <label>Фото клініки</label>
            <input
              type="file"
              name="clinic_photo"
              id="editClinicPhotoInput"
              accept=".jpg,.jpeg,.png"
            >

            <div class="clinic-photo-preview">
              <img
                id="editClinicPhotoPreview"
                src="/BumbleCare/assets/images/default_clinic.jpg"
                alt="Поточне фото клініки"
              >
            </div>
          </div>

          <button type="submit" class="btn-submit">Оновити клініку</button>
        </form>
      </section>
    </div>

  </div>
</main>

<script src="/BumbleCare/assets/js/super_admin_panel.js"></script>
<script src="/BumbleCare/assets/js/rating_stars.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
