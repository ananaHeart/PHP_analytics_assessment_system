<?php
session_start();
include '../config/db.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Update status to active
    $sql = "UPDATE user SET status = 'active' WHERE user_id = '$user_id'";

    if ($conn->query($sql)) {
        // FIXED: Redirect to manage_teachers.php instead of sections.php
        header("Location: ../pages/manage_teachers.php?msg=approved");
    } else {
        echo "Error: " . $conn->error;
    }
    exit();
}
?>