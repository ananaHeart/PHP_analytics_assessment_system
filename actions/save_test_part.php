<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_id = (int) $_SESSION['user']['user_id'];
    $test_id = isset($_POST['test_id']) ? (int) $_POST['test_id'] : 0;
    $competency_id = isset($_POST['competency_id']) ? (int) $_POST['competency_id'] : 0;
    $part_order = mysqli_real_escape_string($conn, trim($_POST['part_order'] ?? ''));
    $part_type = mysqli_real_escape_string($conn, trim($_POST['part_type'] ?? ''));
    $number_of_items = isset($_POST['number_of_items']) ? (int) $_POST['number_of_items'] : 0;
    $points_per_item = isset($_POST['points_per_item']) ? (int) $_POST['points_per_item'] : 0;
    $answer_key = mysqli_real_escape_string($conn, trim($_POST['answer_key'] ?? ''));
    $action = $_POST['action'] ?? 'add';

    if ($test_id <= 0 || $competency_id <= 0 || $part_order === '' || $part_type === '' || $number_of_items <= 0 || $points_per_item <= 0 || $answer_key === '') {
        die('Invalid input.');
    }

    $test_stmt = $conn->prepare("
        SELECT t.class_id
        FROM test t
        JOIN class c ON t.class_id = c.class_id
        WHERE t.test_id = ? AND c.user_id = ?
        LIMIT 1
    ");
    $test_stmt->bind_param('ii', $test_id, $teacher_id);
    $test_stmt->execute();
    $test = $test_stmt->get_result()->fetch_assoc();

    if (!$test) {
        die('Assessment not found or access denied.');
    }

    $class_id = (int) $test['class_id'];

    $sql = "INSERT INTO test_part (test_id, competency_id, part_order, part_type, number_of_items, points_per_item, answer_key) 
            VALUES ('$test_id', '$competency_id', '$part_order', '$part_type', '$number_of_items', '$points_per_item', '$answer_key')";

    if ($conn->query($sql)) {
        if ($action == 'finish') {
            header("Location: ../pages/view_tests.php?class_id=$class_id");
        } else {
            header("Location: ../pages/manage_test_parts.php?test_id=$test_id");
        }
        exit();
    } else {
        header("Location: ../pages/manage_test_parts.php?test_id=$test_id&msg=error");
        exit();
    }
}
?>
