<?php
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name  = mysqli_real_escape_string($conn, $_POST['last_name']);
    $gender     = $_POST['gender'];
    $date_birth = $_POST['date_birth'];
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check_email = $conn->query("SELECT user_id FROM user WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        header("Location: ../pages/register.php?error=exists");
        exit();
    }

    // Role is hardcoded as 'teacher', status as 'pending'
    $sql = "INSERT INTO user (first_name, last_name, gender, date_birth, email, password, role, status) 
            VALUES ('$first_name', '$last_name', '$gender', '$date_birth', '$email', '$password', 'teacher', 'pending')";

    if ($conn->query($sql)) {
        header("Location: ../pages/login.php?msg=registered_pending");
    } else {
        header("Location: ../pages/register.php?error=failed");
        exit();
    }
}
?>
