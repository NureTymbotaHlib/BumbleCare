<?php
$requireLogin = true;
$requireRole = 'super_admin';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/db_connect.php';

$page_css = 'super_admin_panel.css';

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
    SELECT ca.admin_id, u.full_name, c.name AS clinic_name
    FROM clinic_admins ca
    JOIN users u ON ca.user_id = u.user_id
    JOIN clinics c ON ca.clinic_id = c.clinic_id
    WHERE u.status = 'active'
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
        <button class="tab">Управління користувачами</button>
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
        <h2>Управління лікарями</h2>
        <p>Тут буде можливість додавати, редагувати та деактивувати лікарів.</p>
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
              <option value="">Оберіть клініку...</option>

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

          <select name="admin_id">
            <option value="">Оберіть адміністратора...</option>

            <?php foreach ($admins as $a): ?>
              <option value="<?= $a['admin_id'] ?>">
                <?= htmlspecialchars($a['full_name']) ?> — <?= htmlspecialchars($a['clinic_name']) ?>
              </option>
            <?php endforeach; ?>

          </select>

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
              <option value="">Оберіть клініку...</option>
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
          <select name="admin_id">
            <option value="">Оберіть адміністратора...</option>

            <?php foreach ($admins as $a): ?>
              <option value="<?= $a['admin_id'] ?>">
                <?= htmlspecialchars($a['full_name']) ?>
              </option>
            <?php endforeach; ?>

          </select>

          <button type="submit" class="btn-submit btn-red">
            Деактивувати
          </button>
        </form>
      </section>

    </div>


    <div class="tab-content hidden" id="tab-reviews">
      <section class="panel-section">
        <h2>Управління відгуками</h2>
        <p>Тут супер-адмін бачитиме всі відгуки.</p>
      </section>
    </div>

    <div class="tab-content hidden" id="tab-users">
      <section class="panel-section">
        <h2>Управління користувачами</h2>
        <p>Тут можна буде переглядати всіх користувачів, їх ролі та статуси.</p>
      </section>
    </div>

    <div class="tab-content hidden" id="tab-clinics">
      <section class="panel-section">
        <h2>Управління клініками</h2>
        <p>Тут супер-адмін зможе створювати клініки, прив’язувати адміністраторів тощо.</p>
      </section>
    </div>

  </div>
</main>

<script src="/BumbleCare/assets/js/super_admin_panel.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
