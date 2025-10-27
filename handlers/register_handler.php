<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password-confirm'];

    $_SESSION['form_data'] = [
        'fullname' => $full_name,
        'email' => $email
    ];

    if ($password !== $password_confirm) {
        $_SESSION['error'] = 'password_mismatch';
        header("Location: /BumbleCare/pages/register.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = 'email_exists';
        unset($_SESSION['form_data']);
        header("Location: /BumbleCare/pages/register.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status)
                               VALUES (:full_name, :email, :password_hash, 'patient', 'inactive')");
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':password_hash' => $password_hash
        ]);

        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO patients (user_id) VALUES (:user_id)");
        $stmt->execute([':user_id' => $user_id]);

        $pdo->commit();

        $token = generate_jwt([
            'user_id' => (int)$user_id,
            'role'    => 'patient'
        ]);

        setcookie(
            'access_token',
            $token,
            [
                'expires'  => time() + JWT_EXPIRATION,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        unset($_SESSION['error'], $_SESSION['form_data']);

        header("Location: /BumbleCare/pages/patient_profile.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Помилка під час реєстрації: " . $e->getMessage());
    }

} else {
    header("Location: /BumbleCare/pages/register.php");
    exit;
}
?>
