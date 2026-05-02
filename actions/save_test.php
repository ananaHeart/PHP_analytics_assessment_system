<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: ../pages/login.php");
    exit();
}

$class_id = $_POST['class_id'] ?? '';
$test_name = $_POST['test_name'] ?? '';
$test_type = $_POST['test_type'] ?? '';
$test_date = $_POST['test_date'] ?? '';

if ($class_id === '' || trim($test_name) === '') {
    die('Invalid input.');
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

$teacher_id = (int) $_SESSION['user']['user_id'];
$class_stmt = $conn->prepare("SELECT class_id FROM class WHERE class_id = ? AND user_id = ? LIMIT 1");
$class_stmt->bind_param("ii", $class_id, $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
if (!$class_result || $class_result->num_rows === 0) {
    die('Class not found or access denied.');
}

$columns = ['class_id', 'test_name'];
$values = [
    "'" . $conn->real_escape_string($class_id) . "'",
    "'" . $conn->real_escape_string($test_name) . "'"
];

if (column_exists($conn, $test_table, 'test_type')) {
    $columns[] = 'test_type';
    $values[] = "'" . $conn->real_escape_string($test_type) . "'";
}

if (column_exists($conn, $test_table, 'test_date')) {
    $columns[] = 'test_date';
    $values[] = "'" . $conn->real_escape_string($test_date) . "'";
}

$sql = "INSERT INTO `$test_table` (" . implode(', ', $columns) . ")
        VALUES (" . implode(', ', $values) . ")";

if ($conn->query($sql)) {
    $test_id = $conn->insert_id;
    header("Location: ../pages/manage_test_parts.php?test_id=" . $test_id);
    exit();
} else {
    header("Location: ../pages/create_test.php?class_id=" . urlencode((string) $class_id) . "&msg=error");
    exit();
}
?>
