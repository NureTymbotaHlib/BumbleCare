<?php
require_once __DIR__ . '/jwt_utils.php';

$token = $_COOKIE['access_token'] ?? null;
$auth  = validate_jwt($token);

$isLoggedIn = $auth && !empty($auth['user_id']);
$user_id    = $isLoggedIn ? (int)$auth['user_id'] : null;
$user_role  = $auth['role'] ?? 'guest';


if (isset($requireLogin) && $requireLogin && !$isLoggedIn) {
    header("Location: /BumbleCare/pages/login.php");
    exit;
}

if (isset($requireRole)) {
    if (!$isLoggedIn || $user_role !== $requireRole) {
        header("Location: /BumbleCare/pages/forbidden.php");
        exit;
    }
}

if (isset($allowRoles) && is_array($allowRoles)) {
    $allowGuest = $allowGuest ?? false;

    if ($isLoggedIn) {
        if (!in_array($user_role, $allowRoles, true)) {
            header("Location: /BumbleCare/pages/forbidden.php");
            exit;
        }
    } else {
        if (!$allowGuest) {
            header("Location: /BumbleCare/pages/login.php");
            exit;
        }
    }
}
