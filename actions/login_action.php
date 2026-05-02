<?php
session_start();
include '../config/db.php';

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];

$result = $conn->query("SELECT * FROM user WHERE email='$email'");

if ($result->num_rows == 0) {
    // Redirect back with error code
    header("Location: ../pages/login.php?error=notfound");
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    header("Location: ../pages/login.php?error=wrongpass");
    exit();
}

if ($user['status'] != 'active') {
    // This is the part you wanted: Redirect back for pending accounts
    header("Location: ../pages/login.php?error=pending");
    exit();
}

$_SESSION['user'] = $user;
session_write_close();

if ($user['role'] == 'principal') {
    header("Location: ../pages/dashboard_principal.php");
} else {
    header("Location: ../pages/dashboard_teacher.php");
}
exit();
?>