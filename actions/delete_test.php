<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header('Location: ../pages/login.php');
    exit();
}

$teacher_id = (int) $_SESSION['user']['user_id'];
$test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($test_id <= 0 || $class_id <= 0) {
    die('Invalid request.');
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

$owner_stmt = $conn->prepare('SELECT class_id FROM class WHERE class_id = ? AND user_id = ? LIMIT 1');
$owner_stmt->bind_param('ii', $class_id, $teacher_id);
$owner_stmt->execute();
$owner_res = $owner_stmt->get_result();
if (!$owner_res || $owner_res->num_rows === 0) {
    die('Access denied.');
}

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
if (!table_exists($conn, $test_table)) {
    die('Assessment table not found.');
}

$test_check_stmt = $conn->prepare("SELECT test_id FROM `$test_table` WHERE test_id = ? AND class_id = ? LIMIT 1");
$test_check_stmt->bind_param('ii', $test_id, $class_id);
$test_check_stmt->execute();
$test_check_res = $test_check_stmt->get_result();
if (!$test_check_res || $test_check_res->num_rows === 0) {
    die('Access denied.');
}

$conn->begin_transaction();

try {
    if (table_exists($conn, 'test_item_result') && table_exists($conn, 'test_part')) {
        $sql = 'DELETE tir FROM test_item_result tir INNER JOIN test_part tp ON tir.test_part_id = tp.test_part_id WHERE tp.test_id = ?';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $test_id);
            $stmt->execute();
        }
    }

    if (table_exists($conn, 'test_item_result') && table_exists($conn, 'test_result') && column_exists($conn, 'test_result', 'test_id')) {
        $sql = 'DELETE tir FROM test_item_result tir INNER JOIN test_result tr ON tir.test_result_id = tr.test_result_id WHERE tr.test_id = ?';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $test_id);
            $stmt->execute();
        }
    }

    if (table_exists($conn, 'test_result') && column_exists($conn, 'test_result', 'test_id')) {
        $stmt = $conn->prepare('DELETE FROM test_result WHERE test_id = ?');
        $stmt->bind_param('i', $test_id);
        $stmt->execute();
    }

    if (table_exists($conn, 'test_part')) {
        $stmt = $conn->prepare('DELETE FROM test_part WHERE test_id = ?');
        $stmt->bind_param('i', $test_id);
        $stmt->execute();
    }

    $delete_test_stmt = $conn->prepare("DELETE FROM `$test_table` WHERE test_id = ? AND class_id = ?");
    $delete_test_stmt->bind_param('ii', $test_id, $class_id);
    $delete_test_stmt->execute();

    if ($delete_test_stmt->affected_rows <= 0) {
        throw new Exception('Delete failed.');
    }

    $conn->commit();
    header('Location: ../pages/view_tests.php?class_id=' . $class_id);
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    die('Failed to delete assessment.');
}
?>
