<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id    = $_POST['user_id'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];

    // Get the current Active academic year
    $ay_query = $conn->query("SELECT academic_year_id FROM academic_year WHERE status = 'Active' LIMIT 1");
    $ay = $ay_query->fetch_assoc();
    
    if (!$ay) {
        die("Error: No Active Academic Year found.");
    }
    $ay_id = $ay['academic_year_id'];

    // Check if duplicate assignment exists
    $check = $conn->query("SELECT class_id FROM class WHERE user_id = '$user_id' AND subject_id = '$subject_id' AND section_id = '$section_id' AND academic_year_id = '$ay_id'");

    if ($check->num_rows > 0) {
        header("Location: ../pages/assign_class.php?msg=exists");
        exit();
    }

    $sql = "INSERT INTO class (user_id, subject_id, section_id, academic_year_id) VALUES ('$user_id', '$subject_id', '$section_id', '$ay_id')";

    if ($conn->query($sql)) {
        header("Location: ../pages/assign_class.php?msg=success");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>