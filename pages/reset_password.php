<?php
require_once __DIR__ . '/../includes/db_connect.php';
$page_css = 'reset_password.css';
include __DIR__ . '/../includes/header.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
  echo "<script>
    showPopupMessage('Недійсне посилання для відновлення.', 'error');
    setTimeout(() => window.location.href = '/BumbleCare/index.php', 2000);
  </script>";
  exit;
}

$stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
  echo "<script>
    showPopupMessage('Недійсний або використаний токен.', 'error');
    setTimeout(() => window.location.href = '/BumbleCare/index.php', 2000);
  </script>";
  exit;
}

$now = new DateTime('now', new DateTimeZone('Europe/Kyiv'));
$expiresAt = new DateTime($reset['expires_at'], new DateTimeZone('Europe/Kyiv'));

if ($expiresAt < $now) {
  echo "<script>
    showPopupMessage('Посилання прострочене.', 'error');
    setTimeout(() => window.location.href = '/BumbleCare/index.php', 2000);
  </script>";
  exit;
}
?>

<main class="reset-page">
  <div class="reset-container">
    <h1>Скидання паролю</h1>
    <p>Введіть новий пароль для свого облікового запису.</p>

    <form class="reset-form" action="/BumbleCare/handlers/reset_password_handler.php" method="POST">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="form-group">
        <label for="new_password">Новий пароль</label>
        <div class="password-input-wrapper">
          <input type="password" id="new_password" name="new_password" placeholder="Введіть новий пароль" required>
          <button type="button" class="toggle-password" aria-label="Показати пароль">
            <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Підтвердьте пароль</label>
        <div class="password-input-wrapper">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Повторіть пароль" required>
          <button type="button" class="toggle-password" aria-label="Показати пароль">
            <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
          </button>
        </div>
      </div>

      <button type="submit" class="btn-reset">Оновити пароль</button>
    </form>
  </div>
</main>

<script src="/BumbleCare/assets/js/reset_password_submit.js"></script>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
