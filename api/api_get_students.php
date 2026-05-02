<?php
// No session_start here because Mobile Apps use different auth, 
// but for now, we'll keep it simple.
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

// Tell the browser/mobile app that this is JSON data
header('Content-Type: application/json');

if (isset($_GET['class_id'])) {
    $class_id = mysqli_real_escape_string($conn, $_GET['class_id']);
    $class_enrollment_match = class_enrollment_join_condition('c', 'se');

    // Fetch students enrolled in the section connected to this class
    $sql = "SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.student_LRN, s.gender 
            FROM student s
            JOIN student_enrollment se ON s.student_id = se.student_id
            JOIN class c ON c.class_id = '$class_id' AND $class_enrollment_match
            ORDER BY s.last_name ASC";

    $result = $conn->query($sql);
    $students = [];

    while($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    // Send the data as a JSON list
    echo json_encode([
        "status" => "success",
        "total" => count($students),
        "data" => $students
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "No class_id provided"
    ]);
}
?>
