<?php
    session_start();
    $page_css = 'register.css';
    include __DIR__.'/../includes/header.php';
    $error = $_SESSION['error'] ?? null;
    $formData = $_SESSION['form_data'] ?? ['fullname' => '', 'email' => ''];
?>


<main class="register-page">
  <div class="bc-container register-wrapper">

    <div class="register-image">
      <img src="/BumbleCare/assets/images/emerald-block.png" alt="BumbleCare Bee">
    </div>

    <div class="register-form-block">
      <h1>Створити акаунт</h1>

      <form class="register-form" action="/BumbleCare/handlers/register_handler.php" method="post">

        <div class="form-row">
            <div class="form-group">
                <label for="fullname">ПІБ</label>
                <input 
                type="text" 
                id="fullname" 
                name="fullname" 
                placeholder="Іванов Ілля Іванович"
                value="<?php echo htmlspecialchars($formData['fullname']); ?>"
                required>
            </div>

            <div class="form-group password-group">
                <label for="password">Пароль</label>
                <div class="password-input-wrapper <?php echo ($error === 'password_mismatch') ? 'error' : ''; ?>">
                    <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="<?php echo ($error === 'password_mismatch') ? 'Паролі не збігаються!' : 'secret-password'; ?>">
                    <button type="button" class="toggle-password" aria-label="Показати пароль">
                    <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
                    </button>
                </div>
            </div>
            
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="email">Email</label>
            <input 
              type="email" 
              id="email" 
              name="email"
              class="<?php echo ($error === 'email_exists') ? 'error' : ''; ?>"
              placeholder="<?php echo ($error === 'email_exists') ? 'Ця пошта вже використана' : 'magesticuser@gmail.com'; ?>"
              value="<?php echo htmlspecialchars($formData['email']); ?>"
              required>
          </div>

            <div class="form-group password-group">
            <label for="password-confirm">Підтвердіть пароль</label>
            <div class="password-input-wrapper <?php echo ($error === 'password_mismatch') ? 'error' : ''; ?>">
                <input 
                type="password" 
                id="password-confirm" 
                name="password-confirm" 
                required
                placeholder="<?php echo ($error === 'password_mismatch') ? 'Паролі не збігаються!' : 'secret-password'; ?>">
                <button type="button" class="toggle-password" aria-label="Показати пароль">
                <img src="/BumbleCare/assets/icons/eye-closed.svg" alt="#" class="eye-icon">
                </button>
            </div>
            </div>

        </div>

        <div class="form-options">
          <div class="remember">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Запам’ятати мене</label>
          </div>
          <a href="#" class="forgot">Видалити пароль</a>
        </div>

        <button type="submit" class="btn-register-main">Зареєструватися</button>
      </form>
      <?php
        unset($_SESSION['error'], $_SESSION['form_data']);
      ?>

    </div>
  </div>
</main>

<script src="/BumbleCare/assets/js/toggle_password.js"></script>
<?php include __DIR__.'/../includes/footer.php'; ?>
