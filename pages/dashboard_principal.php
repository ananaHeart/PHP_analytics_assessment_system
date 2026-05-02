<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

// Security Check: Only Principal
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'principal') {
    header("Location: login.php");
    exit();
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

// 1. Fetch Quick Stats
$active_ay_id = get_active_academic_year_id($conn);
$count_students = 0;
if ($active_ay_id > 0) {
    $student_count_stmt = $conn->prepare("SELECT COUNT(DISTINCT student_id) AS total FROM student_enrollment WHERE academic_year_id = ?");
    $student_count_stmt->bind_param("i", $active_ay_id);
    $student_count_stmt->execute();
    $count_students = $student_count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
}
$count_teachers = $conn->query("SELECT COUNT(*) as total FROM user WHERE role = 'teacher' AND status = 'active'")->fetch_assoc()['total'] ?? 0;
$count_classes = $conn->query("SELECT COUNT(*) as total FROM class")->fetch_assoc()['total'] ?? 0;
$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
$count_assessments = $conn->query("SELECT COUNT(*) as total FROM `$test_table`")->fetch_assoc()['total'] ?? 0;
$has_created_at = column_exists($conn, $test_table, 'created_at');
$has_test_date = column_exists($conn, $test_table, 'test_date');
$recent_order_by = $has_created_at ? "t.created_at DESC" : ($has_test_date ? "t.test_date DESC, t.test_id DESC" : "t.test_id DESC");
$recent_date_field = $has_created_at ? "t.created_at AS activity_date" : ($has_test_date ? "t.test_date AS activity_date" : "NULL AS activity_date");
$recent_assessments = $conn->query("
    SELECT t.test_name, $recent_date_field, s.subject_name, sec.section_name, g.grade_level_name
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    ORDER BY $recent_order_by
    LIMIT 5
");

// Check for success/error messages
$msg = $_GET['assign'] ?? $_GET['msg'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Dashboard - SMART</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <!-- Use the external CSS we made -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-header {
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(44, 62, 80, 0.05);
        }

        .stat-card h3 {
            color: var(--text-gray);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .stat-card p {
            font-size: 34px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1;
        }

        .stat-card.highlight-warn {
            border-left: 5px solid #F1C40F;
        }

        .panel-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            align-items: start;
        }
        .badge-pending {
            background: #fff5f5;
            color: #e74c3c;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .dashboard-card {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(44, 62, 80, 0.05);
            padding: 26px;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .recent-table th,
        .recent-table td {
            padding: 14px 0;
            border-bottom: 1px solid #EDF2F7;
            text-align: left;
            vertical-align: top;
        }

        .recent-table th {
            color: var(--text-gray);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .recent-table tbody tr:last-child td {
            border-bottom: none;
        }

        .recent-name {
            font-weight: 700;
            color: var(--text-dark);
        }

        .recent-meta {
            display: block;
            margin-top: 4px;
            color: var(--text-gray);
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .panel-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    
    <div class="dashboard-header">
        <h1 style="color: var(--text-dark);">Welcome back, Principal <?php echo $_SESSION['user']['last_name']; ?></h1>
        <p style="color: var(--text-gray);">Here is a summary of the school's activities today.</p>
    </div>

    <?php if($msg == 'success' || (($_GET['upload'] ?? '') === 'success')): ?>
        <div style="background: #EBFBEE; color: var(--primary-green); padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600;">
            ✅ Action processed successfully!
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Students</h3>
            <p><?php echo $count_students; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Teachers</h3>
            <p><?php echo $count_teachers; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Classes</h3>
            <p><?php echo $count_classes; ?></p>
        </div>
        <div class="stat-card highlight-warn">
            <h3>Active Assessments</h3>
            <p><?php echo $count_assessments; ?></p>
        </div>
    </div>

    <div class="panel-grid">
        <div class="dashboard-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px;">
                <div>
                    <h2 style="font-size: 18px;">Recent Activity</h2>
                    <p style="font-size: 14px; color: var(--text-gray); margin-top: 6px;">Latest assessment records across classes.</p>
                </div>
                <span class="badge-pending" style="background:#EBFBEE; color:var(--primary-green);"><?php echo $count_assessments; ?> total</span>
            </div>

            <?php if($recent_assessments && $recent_assessments->num_rows > 0): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Assessment</th>
                            <th style="text-align:right;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($activity = $recent_assessments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="recent-name"><?php echo htmlspecialchars($activity['test_name']); ?></span>
                                    <span class="recent-meta"><?php echo htmlspecialchars($activity['subject_name'] . " - " . $activity['grade_level_name'] . " (" . $activity['section_name'] . ")"); ?></span>
                                </td>
                                <td style="text-align:right; color:var(--text-gray);">
                                    <?php echo !empty($activity['activity_date']) ? htmlspecialchars($activity['activity_date']) : '-'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text-gray); font-style: italic; margin-top: 18px;">No assessment activity recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

</body>
</html>


