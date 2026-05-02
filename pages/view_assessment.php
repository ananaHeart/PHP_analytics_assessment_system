<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = (int) $_SESSION['user']['user_id'];
$test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;

if ($test_id <= 0) {
    die("Invalid assessment.");
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
$has_created_at = column_exists($conn, $test_table, 'created_at');
$class_student_count = class_student_count_sql('c');

$test_sql = "
    SELECT t.test_id, t.class_id, t.test_name" .
    ($has_test_type ? ", t.test_type" : "") .
    ($has_test_date ? ", t.test_date" : "") .
    ($has_created_at ? ", t.created_at" : "") . ",
           s.subject_name, sec.section_name, g.grade_level_name,
           $class_student_count AS std_count,
           (SELECT COUNT(*) FROM test_part tp WHERE tp.test_id = t.test_id) AS part_count
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE t.test_id = ? AND c.user_id = ?
    LIMIT 1
";

$test_stmt = $conn->prepare($test_sql);
$test_stmt->bind_param("ii", $test_id, $teacher_id);
$test_stmt->execute();
$test_result = $test_stmt->get_result();
$assessment = $test_result->fetch_assoc();

if (!$assessment) {
    die("Assessment not found or access denied.");
}

if ((int) ($assessment['part_count'] ?? 0) <= 0) {
    header("Location: manage_test_parts.php?test_id=" . $test_id);
    exit();
}

$class_id = (int) $assessment['class_id'];

$parts_stmt = $conn->prepare("
    SELECT tp.test_part_id, tp.part_order, tp.part_type, tp.number_of_items, tp.points_per_item, tp.answer_key,
           ct.competency_name
    FROM test_part tp
    LEFT JOIN competency_tags ct ON tp.competency_id = ct.competency_id
    WHERE tp.test_id = ?
    ORDER BY tp.test_part_id ASC
");
$parts_stmt->bind_param("i", $test_id);
$parts_stmt->execute();
$parts_result = $parts_stmt->get_result();

$total_parts = 0;
$total_items = 0;
$parts = [];
while ($part = $parts_result->fetch_assoc()) {
    $parts[] = $part;
    $total_parts++;
    $total_items += (int) ($part['number_of_items'] ?? 0);
}

$date_display = '-';
if ($has_created_at && !empty($assessment['created_at'])) {
    $date_display = $assessment['created_at'];
} elseif ($has_test_date && !empty($assessment['test_date'])) {
    $date_display = $assessment['test_date'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Assessment - SMART</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body.teacher-layout {
            background: #f5f6fa;
        }

        .assessment-shell {
            max-width: 1080px;
            margin: 0 auto;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .back-link {
            color: #6b7280;
            text-decoration: none;
            font-weight: 600;
        }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        .btn-edit {
            background: #34C759;
        }

        .btn-delete {
            background: #ef4444;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 22px;
            margin-bottom: 16px;
        }

        .title {
            margin: 0;
            font-size: 30px;
            color: #1f2937;
        }

        .subtitle {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .meta-item {
            background: #f7f9fc;
            border-radius: 12px;
            padding: 12px;
        }

        .meta-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .meta-value {
            color: #1f2937;
            font-weight: 700;
            margin-top: 6px;
            font-size: 14px;
        }

        .parts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .parts-table th,
        .parts-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #eef1f5;
            text-align: left;
            vertical-align: top;
        }

        .parts-table th {
            background: #f7f9fc;
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .part-name {
            font-weight: 700;
            color: #1f2937;
        }

        .answer-preview {
            max-width: 280px;
            color: #4b5563;
            white-space: normal;
            word-break: break-word;
            font-family: monospace;
            font-size: 12px;
        }

        .empty-note {
            color: #6b7280;
            margin: 0;
            padding: 6px 0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 900px) {
            .meta-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body class="teacher-layout">

    <nav class="top-nav">
        <div style="font-weight:800; font-size:18px;">
            <span style="color:var(--primary-green);">🎓</span> SMART Assessment System
        </div>
        <a href="view_tests.php?class_id=<?php echo $class_id; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to Assessments</a>
    </nav>

    <div class="teacher-container">
        <div class="assessment-shell">
            <div class="top-row">
                <a class="back-link" href="view_tests.php?class_id=<?php echo $class_id; ?>">← Back to selected class assessment list</a>
                <div class="action-group">
                    <a href="edit_test.php?test_id=<?php echo $test_id; ?>" class="btn-action btn-edit">Edit Assessment</a>
                    <a href="../actions/delete_test.php?test_id=<?php echo $test_id; ?>&class_id=<?php echo $class_id; ?>"
                       class="btn-action btn-delete"
                       onclick="return confirm('Are you sure you want to delete this assessment?');">Delete Assessment</a>
                </div>
            </div>

            <div class="card">
                <h1 class="title"><?php echo htmlspecialchars($assessment['test_name']); ?></h1>
                <p class="subtitle">Assessment Preview</p>

                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-label">Assessment Type</div>
                        <div class="meta-value"><?php echo htmlspecialchars($assessment['test_type'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Date Created</div>
                        <div class="meta-value"><?php echo htmlspecialchars($date_display); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Total Parts</div>
                        <div class="meta-value"><?php echo $total_parts; ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Total Items</div>
                        <div class="meta-value"><?php echo $total_items; ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0; margin-bottom:14px; color:#1f2937;">Class Information</h3>
                <div class="meta-grid" style="margin-top:0;">
                    <div class="meta-item">
                        <div class="meta-label">Grade Level</div>
                        <div class="meta-value"><?php echo htmlspecialchars($assessment['grade_level_name']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Section</div>
                        <div class="meta-value"><?php echo htmlspecialchars($assessment['section_name']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Subject</div>
                        <div class="meta-value"><?php echo htmlspecialchars($assessment['subject_name']); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Total Students</div>
                        <div class="meta-value"><?php echo (int) ($assessment['std_count'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0; margin-bottom:14px; color:#1f2937;">Assessment Parts</h3>
                <?php if (!empty($parts)): ?>
                    <div class="table-responsive">
                        <table class="parts-table">
                            <thead>
                                <tr>
                                    <th>Part</th>
                                    <th>Type</th>
                                    <th>Competency Tag</th>
                                    <th>Items</th>
                                    <th>Points/Item</th>
                                    <th>Answer Key Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parts as $part): ?>
                                    <tr>
                                        <td class="part-name"><?php echo htmlspecialchars($part['part_order'] ?: ('Part #' . $part['test_part_id'])); ?></td>
                                        <td><?php echo htmlspecialchars($part['part_type'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($part['competency_name'] ?: 'Not tagged'); ?></td>
                                        <td><?php echo (int) ($part['number_of_items'] ?? 0); ?></td>
                                        <td><?php echo (int) ($part['points_per_item'] ?? 0); ?></td>
                                        <td class="answer-preview"><?php echo htmlspecialchars($part['answer_key'] ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-note">No test parts added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
