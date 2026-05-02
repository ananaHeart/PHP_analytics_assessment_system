<?php
include '../config/db.php';

$test_part_id = $_POST['test_part_id'];
$student_name = $_POST['student_name'];
$student_answers = $_POST['student_answers'];

// get answer key
$result = $conn->query("
    SELECT answer_key, number_of_items 
    FROM test_part 
    WHERE test_part_id = $test_part_id
");

$data = $result->fetch_assoc();

$answer_key = $data['answer_key'];

// convert to arrays
$key_array = explode(",", $answer_key);
$student_array = explode(",", $student_answers);

// scoring
$score = 0;

for ($i = 0; $i < count($key_array); $i++) {
    if (isset($student_array[$i]) && trim($student_array[$i]) == trim($key_array[$i])) {
        $score++;
    }
}

// save result
$sql = "INSERT INTO test_result 
(test_part_id, student_name, student_answers, score)
VALUES 
('$test_part_id', '$student_name', '$student_answers', '$score')";

$conn->query($sql);

// output
echo "<h2>Score: $score / " . count($key_array) . "</h2>";
?>