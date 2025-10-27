<?php
  session_start();
  $page_css = 'login.css';
  include __DIR__.'/../includes/header.php';
  $error = $_SESSION['error'] ?? '';
  $formData = $_SESSION['form_data'] ?? ['email' => ''];
?>

<main class="login-page">
  <div class="bc-container login-wrapper">

    <div class="login-form-block">
      <h1>Увійти</h1>

      <form class="login-form" action="/BumbleCare/handlers/login_handler.php" method="post">
        <div class="form-group">
          <label for="email">Email</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($formData['email']); ?>"
            placeholder="<?= ($error === 'invalid_credentials') ? 'Невірний email або пароль' : 'yourmagesticuser@gmail.com'; ?>"
            class="<?= ($error === 'invalid_credentials') ? 'input-error' : ''; ?>"
            required
          >
        </div>

        <div class="form-group password-group">
          <label for="password">Пароль</label>
          
          <div class="password-input-wrapper <?= ($error === 'invalid_credentials') ? 'input-error' : ''; ?>">
            <input 
              type="password" 
              id="password" 
              name="password" 
              placeholder="<?= ($error === 'invalid_credentials') ? 'Невірний email або пароль' : 'super-secret-password'; ?>"
              required
            >
            <button type="button" class="toggle-password" aria-label="Показати пароль">
              <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
            </button>
          </div>

        </div>

        <div class="form-options"> 
          <div class="remember">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Запам’ятати мене</label>
          </div>
          <a href="#" class="forgot">Видалити пароль</a>
        </div>

        <button type="submit" class="btn-login-main">Увійти</button>

        <div class="social-login">
          <button type="button" class="btn-social google">
            <img src="/BumbleCare/assets/images/google-icon.png" alt="Google"> Увійти з Google
          </button>
          <button type="button" class="btn-social facebook">
            <img src="/BumbleCare/assets/images/facebook-icon.png" alt="Facebook"> Увійти з FaceBook
          </button>
        </div>

        <p class="register-hint">
          Новий тут? <a href="/BumbleCare/pages/register.php">Зареєструйся тут</a>
        </p>

      </form>
      <?php
      unset($_SESSION['error'], $_SESSION['form_data']);
      ?>
    </div>

    <div class="login-image">
      <img src="/BumbleCare/assets/images/emerald-block.png" alt="BumbleCare Bee">
    </div>
  </div>
</main>
<script src="/BumbleCare/assets/js/toggle_password.js"></script>

<?php include __DIR__.'/../includes/footer.php'; ?>
