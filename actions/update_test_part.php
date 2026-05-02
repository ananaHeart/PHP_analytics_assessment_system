<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header('Location: ../pages/login.php');
    exit();
}

$teacher_id = (int) $_SESSION['user']['user_id'];
$test_part_id = isset($_POST['test_part_id']) ? (int) $_POST['test_part_id'] : 0;
$test_id = isset($_POST['test_id']) ? (int) $_POST['test_id'] : 0;
$part_order = trim($_POST['part_order'] ?? '');
$part_type = trim($_POST['part_type'] ?? '');
$number_of_items = isset($_POST['number_of_items']) ? (int) $_POST['number_of_items'] : 0;
$points_per_item = isset($_POST['points_per_item']) ? (int) $_POST['points_per_item'] : 0;
$competency_id = isset($_POST['competency_id']) ? (int) $_POST['competency_id'] : 0;
$answer_key = trim($_POST['answer_key'] ?? '');

if ($test_part_id <= 0 || $test_id <= 0 || $part_order === '' || $part_type === '' || $number_of_items <= 0 || $points_per_item <= 0 || $competency_id <= 0 || $answer_key === '') {
    die('Invalid input.');
}

$check_sql = "
    SELECT tp.test_part_id
    FROM test_part tp
    JOIN test t ON tp.test_id = t.test_id
    JOIN class c ON t.class_id = c.class_id
    WHERE tp.test_part_id = ? AND tp.test_id = ? AND c.user_id = ?
    LIMIT 1
";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('iii', $test_part_id, $test_id, $teacher_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if (!$check_result || $check_result->num_rows === 0) {
    die('Access denied.');
}

$update_sql = 'UPDATE test_part SET competency_id = ?, part_order = ?, part_type = ?, number_of_items = ?, points_per_item = ?, answer_key = ? WHERE test_part_id = ? AND test_id = ?';
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param('issiisii', $competency_id, $part_order, $part_type, $number_of_items, $points_per_item, $answer_key, $test_part_id, $test_id);

if ($update_stmt->execute()) {
    header('Location: ../pages/manage_test_parts.php?test_id=' . $test_id);
    exit();
}

header('Location: ../pages/manage_test_parts.php?test_id=' . $test_id . '&msg=error');
exit();
?>
