<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: ../pages/login.php");
    exit();
}

$teacher_id = $_SESSION['user']['user_id'];
$test_id = isset($_POST['test_id']) ? (int) $_POST['test_id'] : 0;
$class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
$test_name = trim($_POST['test_name'] ?? '');
$test_type = trim($_POST['test_type'] ?? '');
$test_date = trim($_POST['test_date'] ?? '');

if ($test_id <= 0 || $class_id <= 0 || $test_name === '') {
    die("Invalid input.");
}

function table_exists($conn, $table_name) {
    $safe_table = $conn->real_escape_string($table_name);
    $result = $conn->query("SHOW TABLES LIKE '$safe_table'");
    return $result && $result->num_rows > 0;
}

function column_exists($conn, $table_name, $column_name) {
    $safe_column = $conn->real_escape_string($column_name);
    $result = $conn->query("SHOW COLUMNS FROM `$table_name` LIKE '$safe_column'");
    return $result && $result->num_rows > 0;
}

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
$has_test_type = column_exists($conn, $test_table, 'test_type');
$has_test_date = column_exists($conn, $test_table, 'test_date');

$check_sql = "
    SELECT t.test_id
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    WHERE t.test_id = ? AND t.class_id = ? AND c.user_id = ?
";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("iii", $test_id, $class_id, $teacher_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    die("Assessment not found or access denied.");
}

if ($has_test_type && $has_test_date) {
    $update_sql = "UPDATE `$test_table` SET test_name = ?, test_type = ?, test_date = ? WHERE test_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $test_name, $test_type, $test_date, $test_id);
} elseif ($has_test_type) {
    $update_sql = "UPDATE `$test_table` SET test_name = ?, test_type = ? WHERE test_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $test_name, $test_type, $test_id);
} elseif ($has_test_date) {
    $update_sql = "UPDATE `$test_table` SET test_name = ?, test_date = ? WHERE test_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $test_name, $test_date, $test_id);
} else {
    $update_sql = "UPDATE `$test_table` SET test_name = ? WHERE test_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $test_name, $test_id);
}

if ($update_stmt->execute()) {
    $part_count_stmt = $conn->prepare('SELECT COUNT(*) AS part_count FROM test_part WHERE test_id = ?');
    $part_count_stmt->bind_param('i', $test_id);
    $part_count_stmt->execute();
    $part_count_row = $part_count_stmt->get_result()->fetch_assoc();
    $part_count = (int) ($part_count_row['part_count'] ?? 0);

    if ($part_count > 0) {
        header("Location: ../pages/view_assessment.php?test_id=" . $test_id);
    } else {
        header("Location: ../pages/manage_test_parts.php?test_id=" . $test_id);
    }
    exit();
}

header("Location: ../pages/edit_test.php?test_id=" . $test_id . "&msg=error");
exit();
?>

