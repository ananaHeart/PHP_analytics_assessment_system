<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $test_id    = $_POST['test_id'];
    $student_id = $_POST['student_id'];
    $class_id   = $_POST['class_id'];
    $raw_ans_post = $_POST['raw_ans']; 

    // For the Web version, we can just use a generic prefix or leave it null
    // But for the Mobile version, this will be the real UUID from the phone
    $mobile_uuid = $_POST['mobile_uuid'] ?? null; 

    $total_test_score = 0;
    $results_summary = [];

    // --- Scoring Logic (The same as we built earlier) ---
    foreach ($raw_ans_post as $part_id => $student_input) {
        $stmt = $conn->prepare("SELECT answer_key, points_per_item FROM test_part WHERE test_part_id = ?");
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $part_data = $stmt->get_result()->fetch_assoc();

        $key_array = array_map('trim', explode(',', $part_data['answer_key']));
        $student_array = array_map('trim', explode(',', $student_input));

        $part_score = 0;
        foreach ($key_array as $index => $correct_val) {
            if (isset($student_array[$index]) && strcasecmp($student_array[$index], $correct_val) == 0) {
                $part_score += $part_data['points_per_item'];
            }
        }
        $total_test_score += $part_score;
        $results_summary[$part_id] = $student_input;
    }

    $json_raw = json_encode($results_summary);
    
    // SMART SYNC LOGIC: 
    // We use "ON DUPLICATE KEY UPDATE"
    // If the mobile_uuid already exists, it will just UPDATE the score.
    // If it's a new entry, it will INSERT.
    $sql = "INSERT INTO test_result (test_id, student_id, mobile_uuid, total_score, raw_answers) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            total_score = VALUES(total_score), 
            raw_answers = VALUES(raw_answers),
            updated_at = CURRENT_TIMESTAMP";

    $insert = $conn->prepare($sql);
    $insert->bind_param("iisis", $test_id, $student_id, $mobile_uuid, $total_test_score, $json_raw);

    if ($insert->execute()) {
        header("Location: ../pages/score_entry.php?class_id=$class_id&test_id=$test_id&msg=success");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>