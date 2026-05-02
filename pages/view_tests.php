<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = (int) $_SESSION['user']['user_id'];
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

if ($class_id <= 0) {
    die("Invalid class.");
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

$class_student_count = class_student_count_sql('c');

$teacher_classes = $conn->query("
    SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name,
           $class_student_count AS std_count
    FROM class c
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE c.user_id = '$teacher_id'
    ORDER BY s.subject_name, g.grade_level_name, sec.section_name
");

$class_stmt = $conn->prepare("
    SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name,
           $class_student_count AS std_count
    FROM class c
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE c.class_id = ? AND c.user_id = ?
");
$class_stmt->bind_param("ii", $class_id, $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$class_info = $class_result->fetch_assoc();

if (!$class_info) {
    die("Class not found or access denied.");
}

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
$has_created_at = column_exists($conn, $test_table, 'created_at');
$has_test_date = column_exists($conn, $test_table, 'test_date');

$order_by = $has_created_at ? "created_at DESC" : ($has_test_date ? "test_date DESC, test_id DESC" : "test_id DESC");
$sql = "
    SELECT t.*,
           (SELECT COUNT(*) FROM test_part tp WHERE tp.test_id = t.test_id) AS part_count,
           (SELECT COUNT(DISTINCT se.student_id)
              FROM student_enrollment se
             WHERE se.section_id = c.section_id
               AND se.academic_year_id = c.academic_year_id) AS total_students,
           (SELECT COUNT(DISTINCT tr.student_id)
              FROM test_result tr
              JOIN student_enrollment se_checked ON se_checked.student_id = tr.student_id
             WHERE tr.test_id = t.test_id
               AND se_checked.section_id = c.section_id
               AND se_checked.academic_year_id = c.academic_year_id) AS checked_students
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    WHERE t.class_id = ?
    ORDER BY $order_by
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $class_id);
$stmt->execute();
$tests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessments - SMART</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body.teacher-layout {
            background: #f5f6fa;
        }

        .workspace-shell {
            max-width: 1080px;
            margin: 0 auto;
        }

        .workspace-header {
            margin-bottom: 20px;
        }

        .workspace-title {
            margin: 0;
            color: #1f2937;
            font-size: 30px;
            line-height: 1.2;
        }

        .workspace-subtitle {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .class-context {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .context-item {
            background: #fff;
            border: 1px solid #e5eaf0;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            color: #1f2937;
        }

        .context-label {
            color: #6b7280;
            font-weight: 700;
            margin-right: 4px;
        }

        .workspace-tabs {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .tab-pill {
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #dfe6ef;
            color: #6b7280;
            background: #fff;
        }

        .tab-pill.active {
            color: #16a34a;
            border-color: #bfe9c8;
            background: #e8fbe8;
        }

        .toolbar-card,
        .assessment-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 20px;
        }

        .toolbar-card {
            margin-bottom: 16px;
        }

        .toolbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .class-selector-wrap {
            min-width: 280px;
            flex: 1;
        }

        .class-selector-label {
            display: block;
            font-size: 12px;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .class-selector {
            width: 100%;
            max-width: 520px;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #d8dee9;
            padding: 0 12px;
            color: #1f2937;
        }

        .create-assessment-btn {
            min-width: 190px;
            margin-top: 0;
            text-align: center;
        }

        .assessment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .assessment-table th,
        .assessment-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            white-space: nowrap;
        }

        .assessment-table th {
            background: #f7f9fc;
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .assessment-name {
            font-weight: 700;
            color: #1f2937;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }

        .status-badge.status-incomplete {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-badge.status-completed {
            background: #16a34a;
            color: #fff;
        }

        .action-cell {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            align-items: center;
        }

        .btn-sm {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            line-height: 1.2;
        }

        .btn-view {
            color: #16a34a;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
        }

        .btn-edit {
            color: #fff;
            background: #34C759;
        }

        .btn-check {
            color: #0f766e;
            border: 1px solid #99f6e4;
            background: #f0fdfa;
        }

        .btn-recheck {
            color: #166534;
            border: 1px solid #86efac;
            background: #f0fdf4;
        }

        .btn-delete {
            color: #fff;
            background: #ef4444;
        }

        .btn-setup {
            color: #92400e;
            border: 1px solid #fed7aa;
            background: #fff7ed;
        }

        .empty-state {
            padding: 38px 16px;
            text-align: center;
            color: #6b7280;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 900px) {
            .workspace-title {
                font-size: 25px;
            }

            .action-cell {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body class="teacher-layout">

    <nav class="top-nav">
        <div class="brand-lockup" style="font-weight:800; font-size:18px;">
            <img src="../assets/img/smart-logo.svg" alt="SMART Assessment System" class="brand-logo">
            <span>SMART Assessment System</span>
        </div>
        <a href="dashboard_teacher.php" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to My Classes</a>
    </nav>

    <div class="teacher-container">
        <div class="workspace-shell">
            <div class="workspace-header">
                <h1 class="workspace-title">Grade Level: <?php echo htmlspecialchars($class_info['grade_level_name']); ?></h1>
                <p class="workspace-subtitle">Section: <?php echo htmlspecialchars($class_info['section_name']); ?> • Subject: <?php echo htmlspecialchars($class_info['subject_name']); ?></p>
                <div class="class-context">
                    <span class="context-item"><span class="context-label">Grade Level:</span><?php echo htmlspecialchars($class_info['grade_level_name']); ?></span>
                    <span class="context-item"><span class="context-label">Section:</span><?php echo htmlspecialchars($class_info['section_name']); ?></span>
                    <span class="context-item"><span class="context-label">Subject:</span><?php echo htmlspecialchars($class_info['subject_name']); ?></span>
                    <span class="context-item"><span class="context-label">Students:</span><?php echo (int) ($class_info['std_count'] ?? 0); ?></span>
                </div>
                <div class="workspace-tabs">
                    <span class="tab-pill active">Assessments</span>
                    <a href="teacher_analytics.php?class_id=<?php echo $class_id; ?>" class="tab-pill">Analytics</a>
                </div>
            </div>

            <div class="toolbar-card">
                <div class="toolbar-row">
                    <div class="class-selector-wrap">
                        <label for="classSelector" class="class-selector-label">Choose Class Assignment</label>
                        <select id="classSelector" class="class-selector" onchange="if(this.value){ window.location.href='view_tests.php?class_id=' + this.value; }">
                            <?php if ($teacher_classes && $teacher_classes->num_rows > 0): ?>
                                <?php while($c = $teacher_classes->fetch_assoc()): ?>
                                    <option value="<?php echo (int) $c['class_id']; ?>" <?php echo ((int)$c['class_id'] === $class_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars('Grade Level: ' . $c['grade_level_name'] . ' | Section: ' . $c['section_name'] . ' | Subject: ' . $c['subject_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <a href="create_test.php?class_id=<?php echo $class_id; ?>" class="btn-green create-assessment-btn">Create Assessment</a>
                </div>
            </div>

            <div class="assessment-card">
                <div class="table-responsive">
                    <?php if ($tests->num_rows > 0): ?>
                        <table class="assessment-table">
                            <thead>
                                <tr>
                                    <th>Assessment</th>
                                    <th>Date Created</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $tests->fetch_assoc()): ?>
                                    <?php
                                        $part_count = (int) ($row['part_count'] ?? 0);
                                        $total_students = (int) ($row['total_students'] ?? 0);
                                        $checked_students = (int) ($row['checked_students'] ?? 0);
                                        $is_complete = $part_count > 0;
                                        $is_completed = $is_complete && $total_students > 0 && $checked_students >= $total_students;
                                        if ($has_created_at && !empty($row['created_at'])) {
                                            $display_date = $row['created_at'];
                                        } elseif ($has_test_date && !empty($row['test_date'])) {
                                            $display_date = $row['test_date'];
                                        } else {
                                            $display_date = '-';
                                        }
                                        if ($part_count === 0) {
                                            $status_value = 'Incomplete Setup';
                                            $status_class = 'status-incomplete';
                                        } elseif ($is_completed) {
                                            $status_value = 'Completed';
                                            $status_class = 'status-completed';
                                        } else {
                                            $status_value = 'Active';
                                            $status_class = '';
                                        }
                                        $view_href = $part_count > 0
                                            ? 'view_assessment.php?test_id=' . (int) $row['test_id']
                                            : 'manage_test_parts.php?test_id=' . (int) $row['test_id'];
                                        $check_href = $part_count > 0
                                            ? 'check_assessment.php?test_id=' . (int) $row['test_id']
                                            : 'manage_test_parts.php?test_id=' . (int) $row['test_id'];
                                        $check_label = $part_count === 0 ? 'Setup Required' : ($is_completed ? 'Recheck Scores' : 'Check Scores');
                                        $check_class = $part_count === 0 ? 'btn-setup' : ($is_completed ? 'btn-recheck' : 'btn-check');
                                    ?>
                                    <tr>
                                        <td><span class="assessment-name"><?php echo htmlspecialchars($row['test_name']); ?></span></td>
                                        <td><?php echo htmlspecialchars($display_date); ?></td>
                                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_value); ?></span></td>
                                        <td>
                                            <div class="action-cell">
                                                <a href="<?php echo htmlspecialchars($view_href); ?>" class="btn-sm btn-view"><?php echo $part_count > 0 ? 'View' : 'Setup Parts'; ?></a>
                                                <a href="<?php echo htmlspecialchars($check_href); ?>" class="btn-sm <?php echo $check_class; ?>"><?php echo $check_label; ?></a>
                                                <a href="edit_test.php?test_id=<?php echo (int)$row['test_id']; ?>" class="btn-sm btn-edit">Edit</a>
                                                <a href="../actions/delete_test.php?test_id=<?php echo (int)$row['test_id']; ?>&class_id=<?php echo $class_id; ?>"
                                                   class="btn-sm btn-delete"
                                                   onclick="return confirm('Are you sure you want to delete this assessment?');">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="empty-state">No assessments yet for this class.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
