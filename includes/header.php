<?php
require_once __DIR__ . '/jwt_utils.php';

$token = $_COOKIE['access_token'] ?? null;
$auth = validate_jwt($token);

$isLoggedIn = $auth && !empty($auth['user_id']);
$user_role = $auth['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BumbleCare</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/BumbleCare/assets/css/header.css">

    <?php if (!empty($page_css)) : ?>
        <link rel="stylesheet" href="/BumbleCare/assets/css/<?= htmlspecialchars($page_css) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="/BumbleCare/assets/css/footer.css">
</head>
<body>

<header class="bc-header">
    <div class="bc-container bc-header__inner">
        <div class="bc-logo">
            <img src="/BumbleCare/assets/images/logo.png" alt="BumbleCare" class="bc-logo__img">
            <span class="bc-logo__text">
                <span>Bumble</span><span>Care</span>
            </span>
        </div>

    <nav class="bc-nav">
        <a href="/BumbleCare/index.php" class="bc-nav__link">Головна</a>
        <a href="#" class="bc-nav__link">Лікарні</a>

        <?php if ($user_role === 'doctor'): ?>
            <a href="#" class="bc-nav__link">Прийоми</a>
        <?php elseif ($user_role === 'clinic_admin'): ?>
            <a href="#" class="bc-nav__link">Панель управління</a>
        <?php elseif ($user_role === 'super_admin'): ?>
            <a href="#" class="bc-nav__link">Керування системою</a>
        <?php else: ?>
            <a href="#" class="bc-nav__link">Пошук</a>
        <?php endif; ?>
    </nav>

    <?php if ($isLoggedIn): ?>
        <?php
            $profile_link = match ($user_role) {
                'patient' => '/BumbleCare/pages/patient_profile.php',
                'doctor' => '/BumbleCare/pages/doctor_profile.php',
                'clinic_admin' => '/BumbleCare/pages/clinic_admin_profile.php',
                'super_admin' => '/BumbleCare/pages/super_admin_profile.php',
                default => '/BumbleCare/pages/login.php',
            };
        ?>
        <a href="<?= htmlspecialchars($profile_link) ?>" class="bc-login-btn">Мій профіль</a>
    <?php else: ?>
        <a href="/BumbleCare/pages/login.php" class="bc-login-btn">Увійти</a>
    <?php endif; ?>

    </div>
</header>
