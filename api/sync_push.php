<?php
// No session_start needed for APIs; we usually use tokens later.
include '../config/db.php';

// Tell the mobile app we only speak JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
    exit();
}

// React Native sends a JSON Body. We read it like this:
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['results']) || !is_array($input['results'])) {
    echo json_encode(["status" => "error", "message" => "Invalid data format"]);
    exit();
}

$results = $input['results']; // This is the array of student scores
$processed_count = 0;

// Use a transaction to make it "Atomic" (Point 4 in your notes)
// If one fails, we can stop the whole batch to keep data clean.
$conn->begin_transaction();

try {
    // Prepare the SQL once for better performance
    $sql = "INSERT INTO test_result (test_id, student_id, mobile_uuid, total_score, raw_answers) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            total_score = VALUES(total_score), 
            raw_answers = VALUES(raw_answers),
            updated_at = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($sql);

    foreach ($results as $row) {
        // Data from the Mobile App
        $test_id     = $row['test_id'];
        $student_id  = $row['student_id'];
        $mobile_uuid = $row['mobile_uuid'];
        $total_score = $row['total_score'];
        $raw_json    = json_encode($row['raw_answers']); // The "A,B,C" string or JSON

        $stmt->bind_param("iisis", $test_id, $student_id, $mobile_uuid, $total_score, $raw_json);
        $stmt->execute();
        $processed_count++;
    }

    // If everything is okay, save to database
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Sync complete",
        "synced_rows" => $processed_count
    ]);

} catch (Exception $e) {
    // If something goes wrong, undo everything in this batch
    $conn->rollback();
    echo json_encode([
        "status" => "error", 
        "message" => "Sync failed: " . $e->getMessage()
    ]);
}
?>
