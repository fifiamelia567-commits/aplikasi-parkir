
<?php
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['id_user'])) {
    header('Location: /aplikasi-parkir/auth/login.php');
    exit;
}

redirect_role();
exit;
