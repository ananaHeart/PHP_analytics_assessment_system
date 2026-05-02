<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user']['user_id'];
$test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;

if ($test_id <= 0) {
    die("Invalid test.");
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

$sql = "
    SELECT t.*
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    WHERE t.test_id = ? AND c.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $test_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$test = $result->fetch_assoc();

if (!$test) {
    die("Assessment not found or access denied.");
}

$class_id = (int) $test['class_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assessment - SMART</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
        <a href="view_assessment.php?test_id=<?php echo (int) $test['test_id']; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to Assessment</a>
    </nav>

    <div class="teacher-container" style="max-width: 600px;">
        <div class="card">
            <h1 style="font-size:24px; margin-bottom:10px;">Edit Assessment</h1>
            <p style="color:var(--text-gray); font-size:14px; margin-bottom:30px;">Update assessment details below.</p>

            <form action="../actions/update_test.php" method="POST">
                <input type="hidden" name="test_id" value="<?php echo (int) $test['test_id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

                <div class="form-group">
                    <label>Test Name</label>
                    <input type="text" name="test_name" required value="<?php echo htmlspecialchars($test['test_name']); ?>">
                </div>

                <?php if ($has_test_type): ?>
                    <div class="form-group">
                        <label>Assessment Type</label>
                        <select name="test_type">
                            <?php
                                $types = ['Quiz', 'Exam', 'Diagnostic', 'Long Test'];
                                foreach ($types as $type):
                            ?>
                                <option value="<?php echo $type; ?>" <?php echo (($test['test_type'] ?? '') === $type) ? 'selected' : ''; ?>>
                                    <?php echo $type; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($has_test_date): ?>
                    <div class="form-group">
                        <label>Date Conducted</label>
                        <input type="date" name="test_date" value="<?php echo htmlspecialchars($test['test_date'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-green" style="width:100%; margin-top:10px;">
                    Save Changes
                </button>
            </form>

            <a href="manage_test_parts.php?test_id=<?php echo (int) $test['test_id']; ?>" class="btn-green" style="display:block; text-align:center; background:#2C3E50; width:100%; margin-top:12px;">
                Manage Test Parts
            </a>
        </div>
    </div>

</body>
</html>
