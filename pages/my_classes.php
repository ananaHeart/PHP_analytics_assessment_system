<?php
session_start();

// Canonical class-selection page for teacher assessment flow is dashboard_teacher.php.
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

header("Location: dashboard_teacher.php");
exit();
?>
