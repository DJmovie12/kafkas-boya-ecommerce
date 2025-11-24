<?php
require_once 'includes/session.php';

// Oturumu kapat
session_destroy();

// Ana sayfaya yönlendir
header("Location: /index.php");
exit();
?>
