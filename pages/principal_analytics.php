<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

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

function execute_prepared_query($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    if ($types !== '' && !empty($params)) {
        $bind = array_merge([$types], $params);
        $refs = [];
        foreach ($bind as $k => $v) {
            $refs[$k] = &$bind[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    return $stmt->get_result();
}

function build_filter_clause($grade_level_id, $section_id, $teacher_id, $subject_id) {
    $clause = '';
    $types = '';
    $params = [];

    if ($grade_level_id > 0) {
        $clause .= ' AND gl.grade_level_id = ?';
        $types .= 'i';
        $params[] = $grade_level_id;
    }

    if ($section_id > 0) {
        $clause .= ' AND sec.section_id = ?';
        $types .= 'i';
        $params[] = $section_id;
    }

    if ($teacher_id > 0) {
        $clause .= ' AND u.user_id = ?';
        $types .= 'i';
        $params[] = $teacher_id;
    }

    if ($subject_id > 0) {
        $clause .= ' AND subj.subject_id = ?';
        $types .= 'i';
        $params[] = $subject_id;
    }

    return ['clause' => $clause, 'types' => $types, 'params' => $params];
}

$grade_level_id = isset($_GET['grade_level_id']) ? (int) $_GET['grade_level_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int) $_GET['section_id'] : 0;
$teacher_id = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';
$has_test_date = column_exists($conn, $test_table, 'test_date');
$has_created_at = column_exists($conn, $test_table, 'created_at');

$grade_levels = [];
$grade_levels_res = $conn->query('SELECT grade_level_id, grade_level_name FROM grade_level ORDER BY grade_level_name ASC');
if ($grade_levels_res) {
    while ($row = $grade_levels_res->fetch_assoc()) {
        $row['grade_level_id'] = (int) $row['grade_level_id'];
        $grade_levels[] = $row;
    }
}

$sections = [];
$sections_by_grade = [];
$sections_res = $conn->query("
    SELECT s.section_id, s.section_name, s.grade_level_id, g.grade_level_name
    FROM section s
    JOIN grade_level g ON s.grade_level_id = g.grade_level_id
    ORDER BY g.grade_level_name ASC, s.section_name ASC
");
if ($sections_res) {
    while ($row = $sections_res->fetch_assoc()) {
        $row['section_id'] = (int) $row['section_id'];
        $row['grade_level_id'] = (int) $row['grade_level_id'];
        $sections[] = $row;
        $sections_by_grade[$row['grade_level_id']][] = $row['section_id'];
    }
}

$teachers = [];
$teachers_by_section = [];
$teachers_res = $conn->query("
    SELECT DISTINCT c.section_id, u.user_id, u.first_name, u.last_name
    FROM class c
    JOIN academic_year ay ON c.academic_year_id = ay.academic_year_id
    JOIN user u ON c.user_id = u.user_id
    WHERE ay.status = 'Active'
      AND u.role = 'teacher'
    ORDER BY u.last_name ASC, u.first_name ASC
");
if ($teachers_res) {
    while ($row = $teachers_res->fetch_assoc()) {
        $row['section_id'] = (int) $row['section_id'];
        $row['user_id'] = (int) $row['user_id'];
        $teachers[] = $row;
        $teachers_by_section[$row['section_id']][] = $row['user_id'];
    }
}

$subjects = [];
$subjects_by_section_teacher = [];
$subjects_res = $conn->query("
    SELECT DISTINCT c.section_id, c.user_id, s.subject_id, s.subject_name
    FROM class c
    JOIN academic_year ay ON c.academic_year_id = ay.academic_year_id
    JOIN subject s ON c.subject_id = s.subject_id
    WHERE ay.status = 'Active'
    ORDER BY s.subject_name ASC
");
if ($subjects_res) {
    while ($row = $subjects_res->fetch_assoc()) {
        $row['section_id'] = (int) $row['section_id'];
        $row['user_id'] = (int) $row['user_id'];
        $row['subject_id'] = (int) $row['subject_id'];
        $subjects[] = $row;
        $subjects_by_section_teacher[$row['section_id']][$row['user_id']][] = $row['subject_id'];
    }
}

if ($grade_level_id > 0) {
    $valid_sections = $sections_by_grade[$grade_level_id] ?? [];
    if ($section_id > 0 && !in_array($section_id, $valid_sections, true)) {
        $section_id = 0;
        $teacher_id = 0;
        $subject_id = 0;
    }
}

if ($section_id <= 0) {
    $teacher_id = 0;
    $subject_id = 0;
} else {
    $valid_teachers = $teachers_by_section[$section_id] ?? [];
    if ($teacher_id > 0 && !in_array($teacher_id, $valid_teachers, true)) {
        $teacher_id = 0;
        $subject_id = 0;
    }
}

if ($teacher_id <= 0) {
    $subject_id = 0;
} else {
    $valid_subjects = $subjects_by_section_teacher[$section_id][$teacher_id] ?? [];
    if ($subject_id > 0 && !in_array($subject_id, $valid_subjects, true)) {
        $subject_id = 0;
    }
}

$filters = build_filter_clause($grade_level_id, $section_id, $teacher_id, $subject_id);
$filter_clause = $filters['clause'];
$filter_types = $filters['types'];
$filter_params = $filters['params'];
$class_enrollment_match = class_enrollment_join_condition('c', 'se');

$common_joins = "
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level gl ON sec.grade_level_id = gl.grade_level_id
    JOIN subject subj ON c.subject_id = subj.subject_id
    JOIN user u ON c.user_id = u.user_id
";
$completed_test_clause = " AND EXISTS (SELECT 1 FROM test_part tpc WHERE tpc.test_id = t.test_id)";

$total_assessment_sql = "SELECT COUNT(DISTINCT t.test_id) AS total_assessments $common_joins WHERE 1=1 $filter_clause $completed_test_clause";
$total_assessment_res = execute_prepared_query($conn, $total_assessment_sql, $filter_types, $filter_params);
$total_assessments = 0;
if ($total_assessment_res) {
    $row = $total_assessment_res->fetch_assoc();
    $total_assessments = (int) ($row['total_assessments'] ?? 0);
}

$results_count_sql = "
    SELECT COUNT(DISTINCT tr.test_result_id) AS total_results
    FROM test_result tr
    JOIN `$test_table` t ON tr.test_id = t.test_id
    JOIN class c ON t.class_id = c.class_id
    JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level gl ON sec.grade_level_id = gl.grade_level_id
    JOIN subject subj ON c.subject_id = subj.subject_id
    JOIN user u ON c.user_id = u.user_id
    WHERE 1=1 $filter_clause $completed_test_clause
";
$results_count_res = execute_prepared_query($conn, $results_count_sql, $filter_types, $filter_params);
$total_results = 0;
if ($results_count_res) {
    $row = $results_count_res->fetch_assoc();
    $total_results = (int) ($row['total_results'] ?? 0);
}

$lms_sql = "
    SELECT
        ct.competency_name,
        COUNT(DISTINCT tir.item_result_id) AS total_checked,
        COALESCE(SUM(tir.is_correct), 0) AS correct_count,
        ROUND((COALESCE(SUM(tir.is_correct), 0) / COUNT(tir.item_result_id)) * 100, 2) AS mastery_rate
    FROM test_item_result tir
    JOIN test_part tp ON tir.test_part_id = tp.test_part_id
    JOIN competency_tags ct ON tp.competency_id = ct.competency_id
    JOIN test_result tr ON tir.test_result_id = tr.test_result_id
    JOIN `$test_table` t ON tr.test_id = t.test_id
    JOIN class c ON t.class_id = c.class_id
    JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level gl ON sec.grade_level_id = gl.grade_level_id
    JOIN subject subj ON c.subject_id = subj.subject_id
    JOIN user u ON c.user_id = u.user_id
    WHERE 1=1 $filter_clause $completed_test_clause
    GROUP BY ct.competency_id, ct.competency_name
    ORDER BY mastery_rate ASC
    LIMIT 5
";
$lms_res = execute_prepared_query($conn, $lms_sql, $filter_types, $filter_params);
$lms_rows = [];
if ($lms_res) {
    while ($row = $lms_res->fetch_assoc()) {
        $row['total_checked'] = (int) $row['total_checked'];
        $row['correct_count'] = (int) $row['correct_count'];
        $row['mastery_rate'] = (float) $row['mastery_rate'];
        $lms_rows[] = $row;
    }
}

$activity_date_expr = $has_test_date ? 't.test_date' : ($has_created_at ? 't.created_at' : 'NULL');
$activity_order = $has_test_date ? 't.test_date DESC, t.test_id DESC' : ($has_created_at ? 't.created_at DESC, t.test_id DESC' : 't.test_id DESC');
$activity_group_by = 't.test_id, t.test_name, u.first_name, u.last_name';
if ($has_test_date) {
    $activity_group_by .= ', t.test_date';
} elseif ($has_created_at) {
    $activity_group_by .= ', t.created_at';
}

$activity_sql = "
    SELECT
        t.test_name,
        CONCAT(u.first_name, ' ', u.last_name) AS teacher_name,
        $activity_date_expr AS activity_date,
        COUNT(DISTINCT CASE WHEN se.student_enrollment_id IS NOT NULL THEN tr.test_result_id END) AS result_count
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    JOIN user u ON c.user_id = u.user_id
    LEFT JOIN test_result tr ON t.test_id = tr.test_id
    LEFT JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level gl ON sec.grade_level_id = gl.grade_level_id
    JOIN subject subj ON c.subject_id = subj.subject_id
    WHERE 1=1 $filter_clause $completed_test_clause
    GROUP BY $activity_group_by
    ORDER BY $activity_order
    LIMIT 5
";
$activity_res = execute_prepared_query($conn, $activity_sql, $filter_types, $filter_params);
$activities = [];
if ($activity_res) {
    while ($row = $activity_res->fetch_assoc()) {
        $row['result_count'] = (int) $row['result_count'];
        $activities[] = $row;
    }
}

$trend_order = $has_test_date ? 't.test_date ASC, t.test_id ASC' : ($has_created_at ? 't.created_at ASC, t.test_id ASC' : 't.test_id ASC');
$trend_group_by = 't.test_id, t.test_name';
if ($has_test_date) {
    $trend_group_by .= ', t.test_date';
} elseif ($has_created_at) {
    $trend_group_by .= ', t.created_at';
}

$trend_sql = "
    SELECT
        t.test_name,
        ROUND(AVG((tr.total_score / totals.total_items) * 100), 2) AS average_percentage
    FROM test_result tr
    JOIN `$test_table` t ON tr.test_id = t.test_id
    JOIN class c ON t.class_id = c.class_id
    JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level gl ON sec.grade_level_id = gl.grade_level_id
    JOIN subject subj ON c.subject_id = subj.subject_id
    JOIN user u ON c.user_id = u.user_id
    JOIN (
        SELECT test_id, SUM(number_of_items * points_per_item) AS total_items
        FROM test_part
        GROUP BY test_id
    ) totals ON t.test_id = totals.test_id
    WHERE totals.total_items > 0 $filter_clause $completed_test_clause
    GROUP BY $trend_group_by
    ORDER BY $trend_order
";
$trend_res = execute_prepared_query($conn, $trend_sql, $filter_types, $filter_params);
$trend_labels = [];
$trend_values = [];
if ($trend_res) {
    while ($row = $trend_res->fetch_assoc()) {
        $trend_labels[] = $row['test_name'];
        $trend_values[] = (float) $row['average_percentage'];
    }
}

function rate_class($rate) {
    $rate = (float) $rate;
    if ($rate < 50) {
        return 'rate-low';
    }
    if ($rate < 75) {
        return 'rate-mid';
    }
    return 'rate-good';
}

function fmt_rate($rate) {
    return rtrim(rtrim(number_format((float) $rate, 2, '.', ''), '0'), '.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Assessment Analytics - SMART</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #f5f6fa; }
        .analytics-shell { max-width: 1160px; margin: 0 auto; }
        .analytics-header { margin-bottom: 14px; }
        .analytics-title { margin: 0; color: #1f2937; font-size: 30px; }
        .analytics-subtitle { margin: 8px 0 0; color: #6b7280; font-size: 14px; }
        .card { background: #ffffff; border-radius: 16px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); padding: 20px; margin-bottom: 18px; }
        .filters-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; }
        .filter-label { display: block; margin-bottom: 8px; color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .filter-select { width: 100%; height: 44px; border: 1px solid #d8dee9; border-radius: 10px; padding: 0 12px; color: #1f2937; }
        .summary-pill { margin-top: 12px; display: inline-block; background: #e8fbe8; color: #166534; border-radius: 999px; padding: 8px 12px; font-size: 13px; font-weight: 700; }
        .main-grid { display: grid; grid-template-columns: 1.65fr 1fr; gap: 24px; align-items: start; }
        .card-title { margin: 0 0 12px; font-size: 16px; color: #1f2937; letter-spacing: .02em; }
        .lms-row { margin-bottom: 12px; }
        .lms-head { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 6px; font-size: 13px; color: #1f2937; }
        .bar-track { width: 100%; height: 10px; border-radius: 999px; background: #e9edf3; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 999px; }
        .rate-good { background: #34C759; }
        .rate-mid { background: #f59e0b; }
        .rate-low { background: #ef4444; }
        .activity-list { display: grid; gap: 10px; }
        .activity-item { border: 1px solid #e5eaf0; border-radius: 12px; padding: 12px; }
        .activity-name { margin: 0; font-weight: 700; color: #1f2937; }
        .activity-meta { margin-top: 6px; font-size: 12px; color: #6b7280; }
        .status-badge { display: inline-block; margin-top: 8px; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .status-recorded { background: #dcfce7; color: #166534; }
        .status-pending { background: #fee2e2; color: #b91c1c; }
        .empty-state { margin: 0; color: #6b7280; font-style: italic; }
        .chart-wrap { min-height: 320px; }
        .reset-link { margin-left: 8px; color: #34C759; text-decoration: none; font-weight: 700; font-size: 13px; }
        @media (max-width: 1060px) {
            .filters-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .main-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .filters-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="analytics-shell">
        <div class="analytics-header">
            <h1 class="analytics-title">School Assessment Analytics</h1>
            <p class="analytics-subtitle">School-wide performance insights based on recorded assessment results</p>
        </div>

        <div class="card">
            <form method="GET" action="principal_analytics.php">
                <div class="filters-grid">
                    <div>
                        <label class="filter-label" for="gradeLevelFilter">Grade Level</label>
                        <select class="filter-select" id="gradeLevelFilter" name="grade_level_id">
                            <option value="">All Grade Levels</option>
                            <?php foreach ($grade_levels as $g): ?>
                                <option value="<?php echo (int) $g['grade_level_id']; ?>" <?php echo ((int)$g['grade_level_id'] === $grade_level_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($g['grade_level_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" for="sectionFilter">Section</label>
                        <select class="filter-select" id="sectionFilter" name="section_id">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $s): ?>
                                <option value="<?php echo (int) $s['section_id']; ?>" <?php echo ((int)$s['section_id'] === $section_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['section_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" for="teacherFilter">Teacher</label>
                        <select class="filter-select" id="teacherFilter" name="teacher_id">
                            <option value="">All Teachers</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo (int) $t['user_id']; ?>" <?php echo ((int)$t['user_id'] === $teacher_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['last_name'] . ', ' . $t['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" for="subjectFilter">Subject</label>
                        <select class="filter-select" id="subjectFilter" name="subject_id">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?php echo (int) $sub['subject_id']; ?>" <?php echo ((int)$sub['subject_id'] === $subject_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <button type="submit" class="btn-green" style="margin-top:0;">Apply Filters</button>
                    <a class="reset-link" href="principal_analytics.php">Reset</a>
                </div>
            </form>

            <span class="summary-pill">Total Assessment: <?php echo $total_assessments; ?></span>
        </div>

        <div class="main-grid">
            <div class="card">
                <h3 class="card-title">Least Mastered Skills Breakdown</h3>

                <?php if ($total_results <= 0): ?>
                    <p class="empty-state">No recorded assessment results yet.</p>
                <?php elseif (empty($lms_rows)): ?>
                    <p class="empty-state">No least mastered skills available yet.</p>
                <?php else: ?>
                    <?php foreach ($lms_rows as $row): ?>
                        <div class="lms-row">
                            <div class="lms-head">
                                <span><?php echo htmlspecialchars($row['competency_name']); ?></span>
                                <span><?php echo fmt_rate($row['mastery_rate']); ?>%</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill <?php echo rate_class($row['mastery_rate']); ?>" style="width: <?php echo max(0, min(100, (float)$row['mastery_rate'])); ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="card-title">Teacher Assessment Activity</h3>

                <?php if ($total_assessments <= 0): ?>
                    <p class="empty-state">No assessments found for selected filters.</p>
                <?php elseif (empty($activities)): ?>
                    <p class="empty-state">No assessment activity available yet.</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($activities as $act): ?>
                            <?php $is_recorded = $act['result_count'] > 0; ?>
                            <div class="activity-item">
                                <p class="activity-name"><?php echo htmlspecialchars($act['test_name']); ?></p>
                                <div class="activity-meta">Teacher: <?php echo htmlspecialchars($act['teacher_name']); ?></div>
                                <div class="activity-meta">Date: <?php echo !empty($act['activity_date']) ? htmlspecialchars($act['activity_date']) : '-'; ?></div>
                                <span class="status-badge <?php echo $is_recorded ? 'status-recorded' : 'status-pending'; ?>"><?php echo $is_recorded ? 'Recorded' : 'Pending'; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Assessment Trends</h3>

            <?php if ($total_results <= 0): ?>
                <p class="empty-state">No assessment trend data available yet.</p>
            <?php elseif (empty($trend_labels)): ?>
                <p class="empty-state">No assessment trend data available yet.</p>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="assessmentTrendsChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
    (function() {
        const sections = <?php echo json_encode(array_values($sections)); ?>;
        const teachers = <?php echo json_encode(array_values($teachers)); ?>;
        const subjects = <?php echo json_encode(array_values($subjects)); ?>;

        const gradeLevelFilter = document.getElementById('gradeLevelFilter');
        const sectionFilter = document.getElementById('sectionFilter');
        const teacherFilter = document.getElementById('teacherFilter');
        const subjectFilter = document.getElementById('subjectFilter');

        if (!gradeLevelFilter || !sectionFilter || !teacherFilter || !subjectFilter) {
            return;
        }

        function setOptions(select, items, selectedValue, allLabel, valueKey, labelBuilder) {
            const desired = String(selectedValue || '');
            const fragment = document.createDocumentFragment();
            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = allLabel;
            fragment.appendChild(allOption);

            items.forEach(item => {
                const option = document.createElement('option');
                option.value = String(item[valueKey]);
                option.textContent = labelBuilder(item);
                if (option.value === desired) {
                    option.selected = true;
                }
                fragment.appendChild(option);
            });

            select.innerHTML = '';
            select.appendChild(fragment);
        }

        function filteredSections() {
            const gradeId = parseInt(gradeLevelFilter.value || '0', 10);
            return sections.filter(section => gradeId <= 0 || section.grade_level_id === gradeId);
        }

        function filteredTeachers() {
            const sectionId = parseInt(sectionFilter.value || '0', 10);
            const seen = new Set();
            return teachers.filter(teacher => {
                if (sectionId > 0 && teacher.section_id !== sectionId) {
                    return false;
                }
                const key = String(teacher.user_id);
                if (seen.has(key)) {
                    return false;
                }
                seen.add(key);
                return true;
            });
        }

        function filteredSubjects() {
            const sectionId = parseInt(sectionFilter.value || '0', 10);
            const teacherId = parseInt(teacherFilter.value || '0', 10);
            const seen = new Set();
            return subjects.filter(subject => {
                if (sectionId > 0 && subject.section_id !== sectionId) {
                    return false;
                }
                if (teacherId > 0 && subject.user_id !== teacherId) {
                    return false;
                }
                const key = String(subject.subject_id);
                if (seen.has(key)) {
                    return false;
                }
                seen.add(key);
                return true;
            });
        }

        function syncFilters() {
            const currentSection = sectionFilter.value;
            const currentTeacher = teacherFilter.value;
            const currentSubject = subjectFilter.value;

            const nextSections = filteredSections();
            const validSectionIds = new Set(nextSections.map(item => String(item.section_id)));
            const nextSection = validSectionIds.has(currentSection) ? currentSection : '';
            setOptions(sectionFilter, nextSections, nextSection, 'All Sections', 'section_id', item => item.section_name);

            const nextTeachers = filteredTeachers();
            const validTeacherIds = new Set(nextTeachers.map(item => String(item.user_id)));
            const nextTeacher = validTeacherIds.has(currentTeacher) ? currentTeacher : '';
            setOptions(teacherFilter, nextTeachers, nextTeacher, 'All Teachers', 'user_id', item => item.last_name + ', ' + item.first_name);

            const nextSubjects = filteredSubjects();
            const validSubjectIds = new Set(nextSubjects.map(item => String(item.subject_id)));
            const nextSubject = validSubjectIds.has(currentSubject) ? currentSubject : '';
            setOptions(subjectFilter, nextSubjects, nextSubject, 'All Subjects', 'subject_id', item => item.subject_name);
        }

        gradeLevelFilter.addEventListener('change', syncFilters);
        sectionFilter.addEventListener('change', syncFilters);
        teacherFilter.addEventListener('change', syncFilters);

        syncFilters();
    })();
</script>

<?php if (!empty($trend_labels)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('assessmentTrendsChart');
        if (!ctx) return;

        const labels = <?php echo json_encode(array_values($trend_labels)); ?>;
        const data = <?php echo json_encode(array_values($trend_values)); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Score %',
                    data: data,
                    backgroundColor: [
                        '#34C759', '#52d676', '#6ae08d', '#82e7a3', '#9aefba', '#b2f4d0', '#c9f8e1'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        ticks: {
                            callback: (value) => value + '%'
                        },
                        grid: {
                            color: '#EEF2F7'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    })();
</script>
<?php endif; ?>
</body>
</html>
