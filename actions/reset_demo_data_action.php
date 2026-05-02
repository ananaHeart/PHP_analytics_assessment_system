<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'principal') {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_reset']) || $_POST['confirm_reset'] !== 'yes') {
    header("Location: ../pages/smart_import.php?reset=invalid");
    exit();
}

$queries = [
    "DELETE FROM test_item_result",
    "DELETE FROM test_result",
    "DELETE FROM test_part",
    "DELETE FROM test",
    "DELETE FROM class",
    "DELETE FROM student_enrollment",
    "DELETE FROM student"
];

$conn->begin_transaction();

try {
    foreach ($queries as $sql) {
        if (!$conn->query($sql)) {
            throw new Exception($conn->error);
        }
    }

    $conn->commit();
    header("Location: ../pages/smart_import.php?reset=success");
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    header("Location: ../pages/smart_import.php?reset=error");
    exit();
}
?>
