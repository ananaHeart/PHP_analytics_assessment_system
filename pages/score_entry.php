<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user']['user_id'];
$class_enrollment_match = class_enrollment_join_condition('c', 'se');

// Get assigned classes
$classes = $conn->query("SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name 
                         FROM class c
                         JOIN subject s ON c.subject_id = s.subject_id
                         JOIN section sec ON c.section_id = sec.section_id
                         JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
                         WHERE c.user_id = '$teacher_id'");

$selected_class = $_GET['class_id'] ?? null;
$selected_test  = $_GET['test_id'] ?? null;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Score Encoding</title>
    <link rel="stylesheet" href="/assessment_system/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="teacher-layout">
<div class="header">Score Encoding (Manual Entry)</div>
<div class="container">
    
    <div class="card">
        <form method="GET">
            <label>Class:</label>
            <select name="class_id" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php while($c = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo ($selected_class == $c['class_id']) ? 'selected' : ''; ?>>
                        <?php echo $c['subject_name'] . " (" . $c['section_name'] . ")"; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <?php if($selected_class): 
            $tests = $conn->query("SELECT * FROM test WHERE class_id = '$selected_class'");
        ?>
        <form method="GET">
            <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
            <label>Test:</label>
            <select name="test_id" onchange="this.form.submit()">
                <option value="">-- Select Test --</option>
                <?php while($t = $tests->fetch_assoc()): ?>
                    <option value="<?php echo $t['test_id']; ?>" <?php echo ($selected_test == $t['test_id']) ? 'selected' : ''; ?>>
                        <?php echo $t['test_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

        <?php if($selected_test): 
        // Get students in the section
        $students = $conn->query("SELECT DISTINCT s.* FROM student s 
                                 JOIN student_enrollment se ON s.student_id = se.student_id 
                                 JOIN class c ON c.class_id = '$selected_class' AND $class_enrollment_match
                                 ORDER BY s.last_name, s.first_name");
        
        // Get test parts
        $parts = $conn->query("SELECT * FROM test_part WHERE test_id = '$selected_test'");
    ?>
    <div class="card">
        <form action="../actions/save_score.php" method="POST">
            <input type="hidden" name="test_id" value="<?php echo $selected_test; ?>">
            <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">

            <label>Student Name:</label>
            <select name="student_id" required>
                <option value="">-- Choose Student --</option>
                <?php while($s = $students->fetch_assoc()): ?>
                    <option value="<?php echo $s['student_id']; ?>">
                        <?php echo $s['last_name'] . ", " . $s['first_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <hr>
            <h3>Input Points per Competency:</h3>

           <!-- Inside the while loop of test parts in score_entry.php -->
<?php while($p = $parts->fetch_assoc()): ?>
    <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: #fff;">
        <label>
            <strong><?php echo $p['part_order']; ?>:</strong> <?php echo $p['Competency_tag']; ?> 
            <br>
            <small style="color: #7f8c8d;">
                Items: <?php echo $p['number_of_items']; ?> | 
                Points per item: <?php echo $p['points_per_item']; ?>
            </small>
        </label>
        
        <p style="font-size: 12px; color: #2c3e50; margin-bottom: 5px;">Type student answers (separate by comma):</p>
        <input type="text" 
               name="raw_ans[<?php echo $p['test_part_id']; ?>]" 
               placeholder="e.g. A, B, C, D..."
               required 
               style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
<?php endwhile; ?>

            <button type="submit" class="btn-upload" style="width: 100%; margin-top: 10px;">Save Student Score</button>
        </form>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
