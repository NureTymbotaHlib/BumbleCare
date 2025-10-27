<?php
require_once __DIR__ . '/jwt_utils.php';

$token = $_COOKIE['access_token'] ?? null;
$auth  = validate_jwt($token);

if (!$auth || empty($auth['user_id'])) {
    header("Location: /BumbleCare/pages/login.php");
    exit;
}

$user_id   = (int)$auth['user_id'];
$user_role = $auth['role'] ?? 'guest';

if (isset($requireRole) && $requireRole !== $user_role) {
    header("Location: /BumbleCare/pages/forbidden.php");
    exit;
}
