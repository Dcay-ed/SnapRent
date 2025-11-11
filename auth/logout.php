<?php
// /admin/logout.php — keluar & balik ke login
require __DIR__.'/auth.php';

// Hancurkan session dengan benar
session_unset();
session_destroy();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect ke homepage
header('Location: ../index.php');
exit;