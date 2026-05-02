<?php
session_start();
include '../config/db.php';

// Security: Only teachers or principals can export
if (!isset($_SESSION['user'])) {
    die("Access denied.");
}

if (isset($_GET['test_id'])) {
    $test_id = mysqli_real_escape_string($conn, $_GET['test_id']);

    // 1. Fetch Test and Class info for the filename
    $info_sql = "SELECT t.test_name, s.subject_name, sec.section_name 
                 FROM test t
                 JOIN class c ON t.class_id = c.class_id
                 JOIN subject s ON c.subject_id = s.subject_id
                 JOIN section sec ON c.section_id = sec.section_id
                 WHERE t.test_id = '$test_id' LIMIT 1";
    $info = $conn->query($info_sql)->fetch_assoc();

    $filename = "Results_" . $info['test_name'] . "_" . $info['section_name'] . ".csv";

    // 2. Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // 3. Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // 4. Set the column headers for the Excel file
    fputcsv($output, array('LRN', 'Last Name', 'First Name', 'Gender', 'Total Score', 'Date Encoded'));

    // 5. Fetch the student results
    $results_sql = "SELECT s.student_LRN, s.last_name, s.first_name, s.gender, tr.total_score, tr.checked_at 
                    FROM test_result tr
                    JOIN student s ON tr.student_id = s.student_id
                    WHERE tr.test_id = '$test_id'
                    ORDER BY s.last_name ASC";
    
    $query = $conn->query($results_sql);

    while ($row = $query->fetch_assoc()) {
        fputcsv($output, array(
            $row['student_LRN'],
            $row['last_name'],
            $row['first_name'],
            ucfirst($row['gender']),
            $row['total_score'],
            date('Y-m-d', strtotime($row['checked_at']))
        ));
    }

    fclose($output);
    exit();
} else {
    echo "No test selected for export.";
}
?>