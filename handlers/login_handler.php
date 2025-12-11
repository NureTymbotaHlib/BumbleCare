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

    if ($user['status'] !== 'active') {
        $_SESSION['error'] = 'inactive';
        $_SESSION['form_data'] = ['email' => $email];
        header("Location: /BumbleCare/pages/login.php");
        exit;
    }

		if ($user['role'] === 'clinic_admin' || $user['role'] === 'super_admin') {
				$code = random_int(100000, 999999);
				$codeHash = password_hash($code, PASSWORD_DEFAULT);

				$expires = (new DateTime('now', new DateTimeZone('Europe/Kyiv')))
					->add(new DateInterval('PT5M'))
					->format('Y-m-d H:i:s');

				$stmt2 = $pdo->prepare("
						INSERT INTO two_factor_codes (user_id, code, expires_at)
						VALUES (?, ?, ?)
				");
				$stmt2->execute([$user['user_id'], $codeHash, $expires]);

				require_once __DIR__ . '/../includes/send_mail.php';
				sendEmail(
						$user['email'],
						$user['full_name'] ?? $user['email'],
						"Ваш код підтвердження входу | BumbleCare",
						"<p>Ваш код підтвердження: <b>{$code}</b></p><p>Дійсний 5 хвилин.</p>"
				);

				$_SESSION['2fa_user_id'] = $user['user_id'];

				header("Location: /BumbleCare/pages/verify_2fa.php");
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
