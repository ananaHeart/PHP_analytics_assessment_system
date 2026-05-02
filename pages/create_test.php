<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php"); exit();
}

$teacher_id = $_SESSION['user']['user_id'];
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$pre_selected_class = $class_id > 0 ? $class_id : null;

// Fetch assigned classes for the dropdown
$classes = $conn->query("
    SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name 
    FROM class c
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE c.user_id = '$teacher_id'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Assessment - SMART</title>
    <link rel="stylesheet" href="/assessment_system/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; }
        label { font-weight: 600; font-size: 14px; margin-bottom: 8px; color: var(--text-gray); }
        input, select { padding: 12px; border: 1px solid #E2E8F0; border-radius: 10px; font-size: 14px; outline: none; }
        input:focus, select:focus { border-color: var(--primary-green); }
    </style>
</head>
<body class="teacher-layout">

    <nav class="top-nav">
        <div style="font-weight:800; font-size:18px;">
            <span style="color:var(--primary-green);">🎓</span> SMART Assessment System
        </div>
        <a href="<?php echo $class_id > 0 ? "view_tests.php?class_id=" . $class_id : "dashboard_teacher.php"; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to Assessments</a>
    </nav>

    <div class="teacher-container" style="max-width: 600px;">
        <div class="card">
            <h1 style="font-size: 24px; margin-bottom: 10px;">Create New Assessment</h1>
            <p style="color:var(--text-gray); font-size: 14px; margin-bottom: 30px;">Set up the basic information for your test.</p>

            <form action="../actions/save_test.php" method="POST">
                <?php if ($class_id > 0): ?>
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Target Class</label>
                    <select name="<?php echo $class_id > 0 ? 'class_id_display' : 'class_id'; ?>" <?php echo $class_id > 0 ? 'disabled' : ''; ?> required>
                        <option value="">-- Choose Class --</option>
                        <?php while($row = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $row['class_id']; ?>" <?php echo ($pre_selected_class == $row['class_id']) ? 'selected' : ''; ?>>
                                <?php echo $row['subject_name'] . " - " . $row['grade_level_name'] . " (" . $row['section_name'] . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Test Name</label>
                    <input type="text" name="test_name" placeholder="e.g. 1st Quarter Examination" required>
                </div>

                <div class="form-group">
                    <label>Assessment Type</label>
                    <select name="test_type">
                        <option>Quiz</option>
                        <option>Exam</option>
                        <option>Diagnostic</option>
                        <option>Long Test</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date Conducted</label>
                    <input type="date" name="test_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="btn-green" style="width: 100%; margin-top: 10px;">
                    Next: Setup Test Parts →
                </button>
            </form>
        </div>
    </div>

</body>
</html>

