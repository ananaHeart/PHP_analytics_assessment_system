<?php
session_start();
include '../config/db.php';

// Security: Only Principal can delete assignments
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'principal') {
    header("Location: ../pages/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $class_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Perform the deletion
    $sql = "DELETE FROM class WHERE class_id = '$class_id'";

    if ($conn->query($sql)) {
        header("Location: ../pages/assign_class.php?msg=deleted");
    } else {
        header("Location: ../pages/assign_class.php?msg=error");
    }
} else {
    header("Location: ../pages/assign_class.php");
}
exit();
?>
