<?php
// /admin/logout.php — keluar & balik ke login
require __DIR__.'/auth.php';
session_unset();
session_destroy();
header('Location: login.php');
exit;
