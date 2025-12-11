<?php
session_start();

if (!isset($_SESSION['2fa_user_id'])) {
  header("Location: /BumbleCare/pages/login.php");
  exit;
}

$page_css = 'verify_2fa.css';
include __DIR__ . '/../includes/header.php';
?>

<main class="verify-page">
  <div class="verify-container">
    <h1>Підтвердження входу</h1>
    <p>Ми надіслали на вашу пошту одноразовий код.</p>

    <form class="verify-form" action="/BumbleCare/handlers/verify_2fa_handler.php" method="POST">
      <div class="form-group">
        <label for="code">Код підтвердження</label>
        <input
          type="text"
          id="code"
          name="code"
          maxlength="6"
          placeholder="******"
          class="code-input"
          required
        >
      </div>

      <button type="submit" class="btn-verify">Увійти</button>
    </form>
  </div>
</main>

<script src="/BumbleCare/assets/js/verify_2fa.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
