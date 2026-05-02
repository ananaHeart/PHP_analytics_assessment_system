<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
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

$teacher_id = (int) $_SESSION['user']['user_id'];
$class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
$requested_test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
$requested_part_id = isset($_GET['part_id']) ? (int) $_GET['part_id'] : 0;
$requested_competency_id = isset($_GET['competency_id']) ? (int) $_GET['competency_id'] : 0;

if ($class_id <= 0) {
    die('Invalid class.');
}

$class_student_count = class_student_count_sql('c');
$class_enrollment_match = class_enrollment_join_condition('c', 'se');

$class_stmt = $conn->prepare(" 
    SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name,
           $class_student_count AS std_count
    FROM class c
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE c.class_id = ? AND c.user_id = ?
    LIMIT 1
");
$class_stmt->bind_param('ii', $class_id, $teacher_id);
$class_stmt->execute();
$class_info = $class_stmt->get_result()->fetch_assoc();

if (!$class_info) {
    die('Access denied.');
}

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
$has_created_at = column_exists($conn, $test_table, 'created_at');
$has_test_date = column_exists($conn, $test_table, 'test_date');

$order_by = $has_created_at ? "created_at DESC" : ($has_test_date ? "test_date DESC, test_id DESC" : "test_id DESC");
$tests_sql = "SELECT test_id, test_name";
if ($has_created_at) {
    $tests_sql .= ", created_at";
}
if ($has_test_date) {
    $tests_sql .= ", test_date";
}
$tests_sql .= " FROM `$test_table` WHERE class_id = ? AND EXISTS (SELECT 1 FROM test_part tp WHERE tp.test_id = `$test_table`.test_id) ORDER BY $order_by";
$tests_stmt = $conn->prepare($tests_sql);
$tests_stmt->bind_param('i', $class_id);
$tests_stmt->execute();
$tests_res = $tests_stmt->get_result();

$tests = [];
$test_ids = [];
while ($t = $tests_res->fetch_assoc()) {
    $tests[] = $t;
    $test_ids[] = (int) $t['test_id'];
}

$selected_test_id = 0;
if (!empty($tests)) {
    if ($requested_test_id > 0 && in_array($requested_test_id, $test_ids, true)) {
        $selected_test_id = $requested_test_id;
    } else {
        $selected_test_id = (int) $tests[0]['test_id'];
    }
}

$parts = [];
$selected_part = null;
$selected_part_id = 0;
$item_stats_map = [];
$lms_rows = [];
$results_count = 0;

if ($selected_test_id > 0) {
    $parts_stmt = $conn->prepare(" 
        SELECT tp.test_part_id, tp.part_order, tp.part_type, tp.number_of_items, tp.points_per_item, tp.competency_id, ct.competency_name
        FROM test_part tp
        LEFT JOIN competency_tags ct ON tp.competency_id = ct.competency_id
        WHERE tp.test_id = ?
        ORDER BY tp.test_part_id ASC
    ");
    $parts_stmt->bind_param('i', $selected_test_id);
    $parts_stmt->execute();
    $parts_res = $parts_stmt->get_result();

    $part_ids = [];
    while ($p = $parts_res->fetch_assoc()) {
        $p['test_part_id'] = (int) $p['test_part_id'];
        $p['number_of_items'] = (int) $p['number_of_items'];
        $p['points_per_item'] = (int) $p['points_per_item'];
        $p['competency_id'] = (int) ($p['competency_id'] ?? 0);
        $parts[] = $p;
        $part_ids[] = $p['test_part_id'];
    }

    if (!empty($parts)) {
        if ($requested_part_id > 0 && in_array($requested_part_id, $part_ids, true)) {
            $selected_part_id = $requested_part_id;
        } else {
            $selected_part_id = (int) $parts[0]['test_part_id'];
        }

        foreach ($parts as $p) {
            if ((int) $p['test_part_id'] === $selected_part_id) {
                $selected_part = $p;
                break;
            }
        }
    }

    $results_count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT tr.test_result_id) AS total_results
        FROM test_result tr
        JOIN class c ON c.class_id = ?
        JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
        WHERE tr.test_id = ?
    ");
    $results_count_stmt->bind_param('ii', $class_id, $selected_test_id);
    $results_count_stmt->execute();
    $results_count_row = $results_count_stmt->get_result()->fetch_assoc();
    $results_count = (int) ($results_count_row['total_results'] ?? 0);

    if ($selected_part_id > 0 && $results_count > 0) {
        $item_stmt = $conn->prepare(" 
            SELECT
                tir.item_number,
                COUNT(DISTINCT tir.item_result_id) AS total_checked,
                COALESCE(SUM(tir.is_correct), 0) AS correct_count,
                ROUND((COALESCE(SUM(tir.is_correct), 0) / COUNT(tir.item_result_id)) * 100, 2) AS success_rate
            FROM test_item_result tir
            JOIN test_result tr ON tir.test_result_id = tr.test_result_id
            JOIN class c ON c.class_id = ?
            JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
            WHERE tr.test_id = ?
              AND tir.test_part_id = ?
            GROUP BY tir.item_number
            ORDER BY tir.item_number ASC
        ");
        $item_stmt->bind_param('iii', $class_id, $selected_test_id, $selected_part_id);
        $item_stmt->execute();
        $item_res = $item_stmt->get_result();

        while ($r = $item_res->fetch_assoc()) {
            $item_number = (int) $r['item_number'];
            $item_stats_map[$item_number] = [
                'total_checked' => (int) $r['total_checked'],
                'correct_count' => (int) $r['correct_count'],
                'success_rate' => (float) $r['success_rate']
            ];
        }
    }

    if ($results_count > 0) {
        $lms_stmt = $conn->prepare(" 
            SELECT
                ct.competency_id,
                ct.competency_name,
                COUNT(DISTINCT tir.item_result_id) AS total_items_checked,
                COALESCE(SUM(tir.is_correct), 0) AS correct_count,
                ROUND((COALESCE(SUM(tir.is_correct), 0) / COUNT(tir.item_result_id)) * 100, 2) AS mastery_rate
            FROM test_item_result tir
            JOIN test_part tp ON tir.test_part_id = tp.test_part_id
            JOIN competency_tags ct ON tp.competency_id = ct.competency_id
            JOIN test_result tr ON tir.test_result_id = tr.test_result_id
            JOIN class c ON c.class_id = ?
            JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
            WHERE tr.test_id = ?
            GROUP BY ct.competency_id, ct.competency_name
            ORDER BY mastery_rate ASC
        ");
        $lms_stmt->bind_param('ii', $class_id, $selected_test_id);
        $lms_stmt->execute();
        $lms_res = $lms_stmt->get_result();

        while ($l = $lms_res->fetch_assoc()) {
            $l['competency_id'] = (int) $l['competency_id'];
            $l['total_items_checked'] = (int) $l['total_items_checked'];
            $l['correct_count'] = (int) $l['correct_count'];
            $l['mastery_rate'] = (float) $l['mastery_rate'];
            $lms_rows[] = $l;
        }
    }
}

$selected_competency = null;
$intervention_students = [];
if ($requested_competency_id > 0 && !empty($lms_rows) && $selected_test_id > 0) {
    foreach ($lms_rows as $lms) {
        if ((int) $lms['competency_id'] === $requested_competency_id) {
            $selected_competency = $lms;
            break;
        }
    }

    if ($selected_competency) {
        $students_stmt = $conn->prepare(" 
            SELECT
                s.student_id,
                CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                COALESCE(SUM(tir.is_correct), 0) AS correct_count,
                COUNT(tir.item_result_id) AS total_items,
                ROUND((COALESCE(SUM(tir.is_correct), 0) / COUNT(tir.item_result_id)) * 100, 2) AS student_mastery
            FROM test_result tr
            JOIN student s ON tr.student_id = s.student_id
            JOIN test_item_result tir ON tr.test_result_id = tir.test_result_id
            JOIN test_part tp ON tir.test_part_id = tp.test_part_id
            JOIN class c ON c.class_id = ?
            JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
            WHERE tr.test_id = ?
              AND tp.competency_id = ?
            GROUP BY s.student_id, s.first_name, s.last_name
            HAVING student_mastery < 75
            ORDER BY student_mastery ASC
        ");
        $students_stmt->bind_param('iii', $class_id, $selected_test_id, $requested_competency_id);
        $students_stmt->execute();
        $students_res = $students_stmt->get_result();

        while ($st = $students_res->fetch_assoc()) {
            $st['student_id'] = (int) $st['student_id'];
            $st['correct_count'] = (int) $st['correct_count'];
            $st['total_items'] = (int) $st['total_items'];
            $st['student_mastery'] = (float) $st['student_mastery'];
            $intervention_students[] = $st;
        }
    }
}

function fmt_rate($v) {
    return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Analytics - SMART</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body.teacher-layout { background: #f5f6fa; }
        .analytics-shell { max-width: 1160px; margin: 0 auto; }
        .analytics-header { margin-bottom: 16px; }
        .analytics-title { margin: 0; color: #1f2937; font-size: 30px; }
        .analytics-subtitle { margin: 8px 0 0; color: #6b7280; font-size: 14px; }
        .tabs { display: flex; gap: 10px; margin-top: 14px; }
        .tab-link { padding: 7px 12px; border-radius: 999px; border: 1px solid #dfe6ef; color: #6b7280; background: #fff; text-decoration: none; font-size: 12px; font-weight: 700; }
        .tab-link.active { color: #16a34a; border-color: #bfe9c8; background: #e8fbe8; }
        .grid { display: grid; grid-template-columns: 1.65fr 1fr; gap: 24px; align-items: start; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); padding: 20px; margin-bottom: 16px; }
        .card h3 { margin: 0 0 14px; color: #1f2937; font-size: 16px; letter-spacing: .02em; }
        .muted { color: #6b7280; }
        .assessment-select { width: 100%; height: 44px; border: 1px solid #d8dee9; border-radius: 10px; padding: 0 12px; color: #1f2937; }
        .part-pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .part-pill { display: inline-block; text-decoration: none; border: 1px solid #e5eaf0; border-radius: 12px; padding: 10px 12px; background: #f8fafc; min-width: 180px; }
        .part-pill .label { display: block; font-size: 12px; font-weight: 700; color: #1f2937; }
        .part-pill .meta { display: block; margin-top: 4px; color: #6b7280; font-size: 12px; }
        .part-pill.active { background: #34C759; border-color: #34C759; }
        .part-pill.active .label, .part-pill.active .meta { color: #fff; }
        .part-summary { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; margin-bottom: 14px; }
        .summary-box { background: #f7f9fc; border: 1px solid #e5eaf0; border-radius: 12px; padding: 10px; }
        .summary-label { color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .summary-value { color: #1f2937; margin-top: 6px; font-weight: 700; }
        .item-row { margin-bottom: 10px; }
        .item-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 13px; color: #1f2937; }
        .bar-track { width: 100%; height: 10px; border-radius: 999px; background: #e9eef4; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 999px; }
        .rate-good { background: #34C759; }
        .rate-mid { background: #f59e0b; }
        .rate-low { background: #ef4444; }
        .lms-list { display: grid; gap: 10px; }
        .lms-card { border: 1px solid #e5eaf0; border-radius: 14px; padding: 12px; background: #fff; }
        .lms-card.priority { border-left: 4px solid #ef4444; }
        .lms-card.mastered { border-left: 4px solid #34C759; }
        .lms-title { margin: 0; color: #1f2937; font-size: 14px; font-weight: 700; }
        .lms-meta { margin-top: 6px; color: #6b7280; font-size: 12px; }
        .badge { display: inline-block; margin-top: 8px; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge.priority { background: #fee2e2; color: #b91c1c; }
        .badge.mastered { background: #dcfce7; color: #166534; }
        .btn-view { display: inline-block; margin-top: 10px; text-decoration: none; border-radius: 8px; padding: 8px 12px; font-size: 12px; font-weight: 700; border: 1px solid #d8dee9; color: #1f2937; background: #fff; }
        .empty { color: #6b7280; font-style: italic; margin: 0; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 999; padding: 16px; }
        .modal { width: min(760px, 100%); background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); overflow: hidden; }
        .modal-head { display:flex; justify-content:space-between; align-items:center; padding:16px 18px; border-bottom: 1px solid #e5eaf0; }
        .modal-title { margin:0; font-size:16px; color:#1f2937; }
        .modal-close { text-decoration:none; color:#6b7280; font-weight:700; font-size:18px; }
        .modal-body { padding: 16px 18px; }
        .competency-chip { display:inline-block; background:#fee2e2; color:#b91c1c; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; margin-bottom:12px; }
        .student-row { background:#fff; border:1px solid #e5eaf0; border-radius:12px; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .student-name { color:#1f2937; font-weight:600; }
        .student-score { color:#ef4444; font-weight:700; }
        @media (max-width: 1024px) {
            .grid { grid-template-columns: 1fr; }
            .part-summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="teacher-layout">

<nav class="top-nav">
    <div class="brand-lockup" style="font-weight:800; font-size:18px;">
        <img src="../assets/img/smart-logo.svg" alt="SMART Assessment System" class="brand-logo">
        <span>SMART Assessment System</span>
    </div>
    <a href="view_tests.php?class_id=<?php echo $class_id; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to Class Assessments</a>
</nav>

<div class="teacher-container">
    <div class="analytics-shell">
        <div class="analytics-header">
            <h1 class="analytics-title"><?php echo htmlspecialchars($class_info['grade_level_name'] . ' - ' . $class_info['section_name']); ?> Class Analytics</h1>
            <p class="analytics-subtitle">Performance insights for your assigned class</p>
            <div class="tabs">
                <a class="tab-link" href="view_tests.php?class_id=<?php echo $class_id; ?>">Classes</a>
                <span class="tab-link active">Analytics</span>
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="card">
                    <h3>ASSESSMENT PART DETAILS</h3>
                    <?php if ($selected_test_id <= 0): ?>
                        <p class="empty">No assessment selected.</p>
                    <?php elseif (empty($parts)): ?>
                        <p class="empty">No assessment parts configured yet.</p>
                    <?php else: ?>
                        <div class="part-pills">
                            <?php foreach ($parts as $idx => $part): ?>
                                <?php
                                    $pid = (int) $part['test_part_id'];
                                    $is_active = $pid === $selected_part_id;
                                    $label = !empty($part['part_order']) ? $part['part_order'] : ('Part ' . ($idx + 1));
                                ?>
                                <a class="part-pill <?php echo $is_active ? 'active' : ''; ?>" href="teacher_analytics.php?class_id=<?php echo $class_id; ?>&test_id=<?php echo $selected_test_id; ?>&part_id=<?php echo $pid; ?>">
                                    <span class="label"><?php echo htmlspecialchars($label); ?></span>
                                    <span class="meta"><?php echo htmlspecialchars($part['part_type'] ?: 'Part Type'); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <?php if ($selected_test_id <= 0): ?>
                        <p class="empty">No assessment selected.</p>
                    <?php elseif (empty($parts)): ?>
                        <p class="empty">No assessment parts configured yet.</p>
                    <?php elseif ($results_count <= 0): ?>
                        <p class="empty">No assessment results available yet. Check or encode student scores first.</p>
                    <?php elseif (!$selected_part): ?>
                        <p class="empty">No part selected.</p>
                    <?php else: ?>
                        <h3><?php echo strtoupper(htmlspecialchars(($selected_part['part_order'] ?: 'PART') . ': ' . ($selected_part['part_type'] ?: 'PART TYPE'))); ?></h3>
                        <div class="part-summary">
                            <div class="summary-box">
                                <div class="summary-label">Number of Items</div>
                                <div class="summary-value"><?php echo (int) $selected_part['number_of_items']; ?></div>
                            </div>
                            <div class="summary-box">
                                <div class="summary-label">Points per Item</div>
                                <div class="summary-value"><?php echo (int) $selected_part['points_per_item']; ?></div>
                            </div>
                            <div class="summary-box">
                                <div class="summary-label">Competency Tag</div>
                                <div class="summary-value"><?php echo htmlspecialchars($selected_part['competency_name'] ?: 'Not tagged'); ?></div>
                            </div>
                        </div>

                        <?php
                            $num_items = (int) $selected_part['number_of_items'];
                            $has_any = false;
                            for ($i = 1; $i <= $num_items; $i++):
                                $stat = $item_stats_map[$i] ?? ['total_checked' => 0, 'correct_count' => 0, 'success_rate' => 0.0];
                                $rate = (float) $stat['success_rate'];
                                if ($stat['total_checked'] > 0) { $has_any = true; }
                                $rate_class = ($rate >= 75) ? 'rate-good' : (($rate >= 50) ? 'rate-mid' : 'rate-low');
                        ?>
                            <div class="item-row">
                                <div class="item-head">
                                    <span>ITEM <?php echo $i; ?></span>
                                    <span><?php echo fmt_rate($rate); ?>%</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill <?php echo $rate_class; ?>" style="width: <?php echo max(0, min(100, $rate)); ?>%;"></div>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <?php if (!$has_any): ?>
                            <p class="empty" style="margin-top: 8px;">No item analysis available yet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3>SELECTED ASSESSMENT</h3>
                    <?php if (empty($tests)): ?>
                        <p class="empty">No assessment selected.</p>
                    <?php else: ?>
                        <form method="GET" action="teacher_analytics.php">
                            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                            <select name="test_id" class="assessment-select" onchange="this.form.submit()">
                                <?php foreach ($tests as $t): ?>
                                    <?php
                                        $date_text = '';
                                        if ($has_created_at && !empty($t['created_at'])) {
                                            $date_text = ' (' . $t['created_at'] . ')';
                                        } elseif ($has_test_date && !empty($t['test_date'])) {
                                            $date_text = ' (' . $t['test_date'] . ')';
                                        }
                                    ?>
                                    <option value="<?php echo (int) $t['test_id']; ?>" <?php echo ((int)$t['test_id'] === $selected_test_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['test_name'] . $date_text); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3>LEAST MASTERED SKILLS</h3>
                    <?php if ($selected_test_id <= 0): ?>
                        <p class="empty">No assessment selected.</p>
                    <?php elseif ($results_count <= 0): ?>
                        <p class="empty">No assessment results available yet. Check or encode student scores first.</p>
                    <?php elseif (empty($lms_rows)): ?>
                        <p class="empty">No competency mastery data available yet.</p>
                    <?php else: ?>
                        <div class="lms-list">
                            <?php foreach ($lms_rows as $lms): ?>
                                <?php
                                    $rate = (float) $lms['mastery_rate'];
                                    $priority = $rate < 75;
                                ?>
                                <div class="lms-card <?php echo $priority ? 'priority' : 'mastered'; ?>">
                                    <p class="lms-title"><?php echo htmlspecialchars($lms['competency_name']); ?></p>
                                    <p class="lms-meta">Mastery Rate: <?php echo fmt_rate($rate); ?>%</p>
                                    <span class="badge <?php echo $priority ? 'priority' : 'mastered'; ?>"><?php echo $priority ? 'Priority for Remediation' : 'Mastered'; ?></span>
                                    <br>
                                    <a class="btn-view" href="teacher_analytics.php?class_id=<?php echo $class_id; ?>&test_id=<?php echo $selected_test_id; ?>&part_id=<?php echo $selected_part_id; ?>&competency_id=<?php echo (int) $lms['competency_id']; ?>">View Students</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($selected_competency): ?>
    <div class="modal-overlay" id="interventionModal">
        <div class="modal">
            <div class="modal-head">
                <h3 class="modal-title">STUDENTS REQUIRING INTERVENTION</h3>
                <a class="modal-close" href="teacher_analytics.php?class_id=<?php echo $class_id; ?>&test_id=<?php echo $selected_test_id; ?>&part_id=<?php echo $selected_part_id; ?>">×</a>
            </div>
            <div class="modal-body">
                <span class="competency-chip"><?php echo htmlspecialchars($selected_competency['competency_name']); ?></span>

                <?php if (!empty($intervention_students)): ?>
                    <?php foreach ($intervention_students as $st): ?>
                        <div class="student-row">
                            <span class="student-name"><?php echo htmlspecialchars($st['student_name']); ?></span>
                            <span class="student-score"><?php echo (int) $st['correct_count']; ?>/<?php echo (int) $st['total_items']; ?> (<?php echo fmt_rate($st['student_mastery']); ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty">No students require intervention for this competency.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const overlay = document.getElementById('interventionModal');
            if (!overlay) return;
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    window.location.href = 'teacher_analytics.php?class_id=<?php echo $class_id; ?>&test_id=<?php echo $selected_test_id; ?>&part_id=<?php echo $selected_part_id; ?>';
                }
            });
        })();
    </script>
<?php endif; ?>

</body>
</html>
