<?php
setcookie('access_token', '', time() - 3600, '/', '', false, true);

header("Location: /BumbleCare/pages/login.php");
exit;
