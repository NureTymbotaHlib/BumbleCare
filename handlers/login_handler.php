<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'invalid_credentials';
        unset($_SESSION['form_data']);
        header("Location: /BumbleCare/pages/login.php");
        exit;
    }

    $_SESSION['form_data'] = [
        'email' => $email
    ];

    $stmt = $pdo->prepare("SELECT user_id, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['error'] = 'invalid_credentials';
        unset($_SESSION['form_data']);
        header("Location: /BumbleCare/pages/login.php");
        exit;
    }

    $token = generate_jwt([
        'user_id' => (int)$user['user_id'],
        'role'    => $user['role']
    ]);


    setcookie('access_token', $token, [
        'expires'  => time() + (7 * 24 * 60 * 60),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    unset($_SESSION['error'], $_SESSION['form_data']);

    switch ($user['role']) {
        case 'patient':
            header("Location: /BumbleCare/pages/patient_profile.php");
            break;
        case 'doctor':
            header("Location: /BumbleCare/pages/doctor_profile.php");
            break;
        case 'clinic_admin':
            header("Location: /BumbleCare/pages/clinic_admin_profile.php");
            break;
        case 'super_admin':
            header("Location: /BumbleCare/pages/super_admin_profile.php");
            break;
        default:
            header("Location: /BumbleCare/pages/login.php");
            break;
    }
    exit;
}

header("Location: /BumbleCare/pages/login.php");
exit;
?>
