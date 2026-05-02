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

$teacher_id = (int) $_SESSION['user']['user_id'];
$test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : (int) ($_POST['test_id'] ?? 0);
$selected_student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

if ($test_id <= 0) {
    die("Invalid assessment.");
}

$test_table = table_exists($conn, 'tests') ? 'tests' : 'test';

$assessment_sql = "
    SELECT t.test_id, t.class_id, t.test_name,
           s.subject_name, sec.section_name, g.grade_level_name,
           (SELECT COUNT(*) FROM test_part tp WHERE tp.test_id = t.test_id) AS part_count
    FROM `$test_table` t
    JOIN class c ON t.class_id = c.class_id
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    WHERE t.test_id = ? AND c.user_id = ?
    LIMIT 1
";
$assessment_stmt = $conn->prepare($assessment_sql);
$assessment_stmt->bind_param("ii", $test_id, $teacher_id);
$assessment_stmt->execute();
$assessment = $assessment_stmt->get_result()->fetch_assoc();

if (!$assessment) {
    die("Assessment not found or access denied.");
}

if ((int) ($assessment['part_count'] ?? 0) <= 0) {
    header("Location: manage_test_parts.php?test_id=" . $test_id);
    exit();
}

$class_id = (int) $assessment['class_id'];
$class_enrollment_match = class_enrollment_join_condition('c', 'se');

// Unchecked students are the only selectable students in the dropdown.
$unchecked_stmt = $conn->prepare("
    SELECT DISTINCT st.student_id, st.first_name, st.last_name
    FROM student st
    JOIN student_enrollment se ON st.student_id = se.student_id
    JOIN class c ON c.class_id = ? AND $class_enrollment_match
    WHERE NOT EXISTS (
        SELECT 1
        FROM test_result tr
        WHERE tr.test_id = ?
          AND tr.student_id = st.student_id
    )
    ORDER BY st.last_name, st.first_name
");
$unchecked_stmt->bind_param("ii", $class_id, $test_id);
$unchecked_stmt->execute();
$unchecked_res = $unchecked_stmt->get_result();
$unchecked_students = [];
while ($s = $unchecked_res->fetch_assoc()) {
    $unchecked_students[] = $s;
}

$checked_stmt = $conn->prepare("
    SELECT DISTINCT tr.student_id, st.first_name, st.last_name, tr.total_score
    FROM test_result tr
    JOIN student st ON tr.student_id = st.student_id
    JOIN class c ON c.class_id = ?
    JOIN student_enrollment se ON se.student_id = tr.student_id AND $class_enrollment_match
    WHERE tr.test_id = ?
    ORDER BY st.last_name, st.first_name
");
$checked_stmt->bind_param("ii", $class_id, $test_id);
$checked_stmt->execute();
$checked_res = $checked_stmt->get_result();
$checked_students = [];
while ($cs = $checked_res->fetch_assoc()) {
    $checked_students[] = $cs;
}

$unchecked_lookup = [];
foreach ($unchecked_students as $student) {
    $unchecked_lookup[(int) $student['student_id']] = $student;
}

$checked_lookup = [];
foreach ($checked_students as $student) {
    $checked_lookup[(int) $student['student_id']] = $student;
}

if ($selected_student_id > 0 && !isset($unchecked_lookup[$selected_student_id]) && !isset($checked_lookup[$selected_student_id])) {
    $selected_student_id = 0;
}

$parts_stmt = $conn->prepare(" 
    SELECT tp.test_part_id, tp.part_order, tp.part_type, tp.number_of_items, tp.points_per_item, tp.answer_key, ct.competency_name
    FROM test_part tp
    LEFT JOIN competency_tags ct ON tp.competency_id = ct.competency_id
    WHERE tp.test_id = ?
    ORDER BY tp.test_part_id ASC
");
$parts_stmt->bind_param("i", $test_id);
$parts_stmt->execute();
$parts_res = $parts_stmt->get_result();

$parts = [];
$total_items = 0;
$max_score = 0;
while ($part = $parts_res->fetch_assoc()) {
    $part['number_of_items'] = (int) $part['number_of_items'];
    $part['points_per_item'] = (int) $part['points_per_item'];
    $total_items += $part['number_of_items'];
    $max_score += ($part['number_of_items'] * $part['points_per_item']);
    $parts[] = $part;
}

if (empty($parts)) {
    die("No test parts found. Please setup assessment parts first.");
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
    $msg = 'Scores saved successfully.';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'no_student') {
    $msg = 'Please select a student first before encoding scores.';
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'invalid_student') {
    $msg = 'Selected student is not valid for this class.';
}

$has_selected_student = $selected_student_id > 0;
$all_students_checked = empty($unchecked_students);
$selected_checked_student = $has_selected_student && isset($checked_lookup[$selected_student_id]) ? $checked_lookup[$selected_student_id] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_student_id = (int) ($_POST['student_id'] ?? 0);
    $states = $_POST['state'] ?? [];

    if ($selected_student_id <= 0) {
        header('Location: check_assessment.php?test_id=' . $test_id . '&msg=no_student');
        exit();
    }

    $student_check = $conn->prepare(" 
        SELECT st.student_id
        FROM student st
        JOIN student_enrollment se ON st.student_id = se.student_id
        JOIN class c ON c.class_id = ? AND $class_enrollment_match
        WHERE st.student_id = ?
        LIMIT 1
    ");
    $student_check->bind_param("ii", $class_id, $selected_student_id);
    $student_check->execute();
    $student_ok = $student_check->get_result()->fetch_assoc();
    if (!$student_ok) {
        header('Location: check_assessment.php?test_id=' . $test_id . '&msg=invalid_student');
        exit();
    }

    $score = 0;
    $summary = [];

    foreach ($parts as $part) {
        $pid = (int) $part['test_part_id'];
        $count = (int) $part['number_of_items'];
        $ppi = (int) $part['points_per_item'];
        $summary[$pid] = [];

        for ($i = 1; $i <= $count; $i++) {
            $value = '';
            if (isset($states[$pid]) && isset($states[$pid][$i])) {
                $value = (string) $states[$pid][$i];
            }

            if ($value === '1') {
                $score += $ppi;
                $summary[$pid][$i] = 1;
            } elseif ($value === '0') {
                $summary[$pid][$i] = 0;
            } else {
                $summary[$pid][$i] = null;
            }
        }
    }

    $conn->begin_transaction();

    try {
        $result_check = $conn->prepare('SELECT test_result_id FROM test_result WHERE test_id = ? AND student_id = ? LIMIT 1');
        $result_check->bind_param('ii', $test_id, $selected_student_id);
        $result_check->execute();
        $existing = $result_check->get_result()->fetch_assoc();

        $json_raw = json_encode($summary);

        if ($existing) {
            $test_result_id = (int) $existing['test_result_id'];
            $update_result = $conn->prepare('UPDATE test_result SET total_score = ?, raw_answers = ?, updated_at = CURRENT_TIMESTAMP WHERE test_result_id = ?');
            $update_result->bind_param('isi', $score, $json_raw, $test_result_id);
            $update_result->execute();
        } else {
            $insert_result = $conn->prepare('INSERT INTO test_result (test_id, student_id, mobile_uuid, total_score, raw_answers) VALUES (?, ?, NULL, ?, ?)');
            $insert_result->bind_param('iiis', $test_id, $selected_student_id, $score, $json_raw);
            $insert_result->execute();
            $test_result_id = (int) $conn->insert_id;
        }

        $delete_items = $conn->prepare('DELETE FROM test_item_result WHERE test_result_id = ?');
        $delete_items->bind_param('i', $test_result_id);
        $delete_items->execute();

        $insert_item = $conn->prepare('INSERT INTO test_item_result (test_part_id, test_result_id, item_number, is_correct) VALUES (?, ?, ?, ?)');

        foreach ($parts as $part) {
            $pid = (int) $part['test_part_id'];
            $count = (int) $part['number_of_items'];

            for ($i = 1; $i <= $count; $i++) {
                $value = '';
                if (isset($states[$pid]) && isset($states[$pid][$i])) {
                    $value = (string) $states[$pid][$i];
                }

                if ($value === '1' || $value === '0') {
                    $is_correct = ($value === '1') ? 1 : 0;
                    $insert_item->bind_param('iiii', $pid, $test_result_id, $i, $is_correct);
                    $insert_item->execute();
                }
            }
        }

        $conn->commit();
        header('Location: check_assessment.php?test_id=' . $test_id . '&msg=saved');
        exit();
    } catch (Throwable $e) {
        $conn->rollback();
        die('Failed to save score.');
    }
}

$prefill_states = [];
$current_score = 0;
if ($selected_student_id > 0) {
    $result_stmt = $conn->prepare('SELECT test_result_id, total_score FROM test_result WHERE test_id = ? AND student_id = ? LIMIT 1');
    $result_stmt->bind_param('ii', $test_id, $selected_student_id);
    $result_stmt->execute();
    $result_row = $result_stmt->get_result()->fetch_assoc();

    if ($result_row) {
        $current_score = (int) $result_row['total_score'];
        $test_result_id = (int) $result_row['test_result_id'];

        $items_stmt = $conn->prepare('SELECT test_part_id, item_number, is_correct FROM test_item_result WHERE test_result_id = ?');
        $items_stmt->bind_param('i', $test_result_id);
        $items_stmt->execute();
        $items_res = $items_stmt->get_result();

        while ($item = $items_res->fetch_assoc()) {
            $pid = (int) $item['test_part_id'];
            $inum = (int) $item['item_number'];
            $prefill_states[$pid][$inum] = ((int) $item['is_correct'] === 1) ? '1' : '0';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Assessment Scores - SMART</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body.teacher-layout { background: #f5f6fa; }
        .check-shell { max-width: 1120px; margin: 0 auto; }
        .page-header { margin-bottom: 14px; }
        .title { margin: 0; color: #1f2937; font-size: 28px; }
        .subtitle { margin: 8px 0 0; color: #6b7280; font-size: 14px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 24px rgba(15,23,42,0.06); padding: 20px; margin-bottom: 14px; }
        .top-row { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; align-items: start; }
        .label { display: block; font-size: 12px; color: #6b7280; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .03em; }
        .select { width: 100%; height: 44px; border: 1px solid #d8dee9; border-radius: 10px; padding: 0 12px; }
        .checked-panel { background: #f7f9fc; border: 1px solid #e8edf3; border-radius: 12px; padding: 12px; }
        .checked-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .checked-pill { display: inline-block; text-decoration: none; background: #e8fbe8; color: #166534; border: 1px solid #bfe9c8; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; }
        .checked-pill.is-active { background: #34C759; color: #fff; border-color: #34C759; }
        .checked-empty { margin: 0; color: #6b7280; font-size: 13px; }
        .score-box { background: #f7f9fc; border: 1px solid #e8edf3; border-radius: 12px; padding: 12px; text-align: right; }
        .score-box strong { color: #16a34a; font-size: 22px; }
        .part-card { border: 1px solid #e8edf3; border-radius: 12px; padding: 14px; margin-top: 12px; }
        .part-head { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
        .part-title { font-weight: 700; color: #1f2937; }
        .part-meta { color: #6b7280; font-size: 13px; }
        .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
        .item-box { background: #f9fafb; border: 1px solid #e7edf4; border-radius: 10px; padding: 10px; transition: background-color .15s ease, border-color .15s ease; }
        .item-box.state-none { background: #f9fafb; border-color: #d5dde6; }
        .item-box.state-correct { background: #f0fdf4; border-color: #34C759; }
        .item-box.state-wrong { background: #fef2f2; border-color: #ef4444; }
        .item-num { font-size: 12px; font-weight: 700; color: #6b7280; margin-bottom: 8px; }
        .item-status { margin-bottom: 8px; font-size: 12px; font-weight: 700; }
        .item-status.status-none { color: #6b7280; }
        .item-status.status-correct { color: #15803d; }
        .item-status.status-wrong { color: #b91c1c; }
        .item-actions { display: flex; gap: 8px; }
        .state-btn { border: none; border-radius: 8px; padding: 8px 10px; font-size: 12px; font-weight: 700; cursor: pointer; color: #fff; flex: 1; opacity: .72; transition: transform .08s ease, opacity .12s ease, box-shadow .12s ease; }
        .btn-none { background: #9ca3af; }
        .btn-correct { background: #34C759; }
        .btn-wrong { background: #ef4444; }
        .state-btn.is-active { opacity: 1; transform: translateY(-1px); box-shadow: 0 0 0 3px rgba(15,23,42,.14), inset 0 0 0 2px rgba(255,255,255,0.96); }
        .state-btn:disabled,
        .save-btn:disabled {
            background: #d1d5db;
            color: #f9fafb;
            cursor: not-allowed;
            opacity: .55;
            box-shadow: none;
            transform: none;
        }
        .save-wrap { margin-top: 16px; display: flex; justify-content: flex-end; }
        .save-btn { min-width: 180px; }
        .alert { background: #e8fbe8; border: 1px solid #bfe9c8; color: #166534; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-weight: 600; }
        .alert.warning { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
        .completion-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        .btn-secondary-link { display: inline-block; text-decoration: none; border-radius: 10px; padding: 11px 16px; font-weight: 700; border: 1px solid #d8dee9; color: #1f2937; background: #fff; }
        .selected-student-box { width: 100%; min-height: 44px; border: 1px solid #d8dee9; border-radius: 10px; padding: 11px 12px; color: #1f2937; background: #f7f9fc; box-sizing: border-box; }
        @media (max-width: 900px) { .top-row { grid-template-columns: 1fr; } .save-wrap { justify-content: stretch; } .save-btn { width: 100%; } }
    </style>
</head>
<body class="teacher-layout">

<nav class="top-nav">
    <div class="brand-lockup" style="font-weight:800; font-size:18px;">
        <img src="../assets/img/smart-logo.png" alt="SMART Assessment System" class="brand-logo">
        <span>SMART Assessment System</span>
    </div>
    <a href="view_tests.php?class_id=<?php echo $class_id; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to View Assessments</a>
</nav>

<div class="teacher-container">
    <div class="check-shell">
        <div class="page-header">
            <h1 class="title">Check / Encode Scores</h1>
            <p class="subtitle">
                <?php echo htmlspecialchars($assessment['test_name']); ?>
                | Grade Level: <?php echo htmlspecialchars($assessment['grade_level_name']); ?>
                | Section: <?php echo htmlspecialchars($assessment['section_name']); ?>
                | Subject: <?php echo htmlspecialchars($assessment['subject_name']); ?>
            </p>
        </div>

        <?php if ($msg !== ''): ?>
            <div class="alert <?php echo (isset($_GET['msg']) && $_GET['msg'] !== 'saved') ? 'warning' : ''; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if (!$has_selected_student && !$all_students_checked): ?>
            <div class="alert warning">Please select a student first before encoding scores.</div>
        <?php elseif (!$has_selected_student && $all_students_checked): ?>
            <div class="alert">All students have been checked for this assessment.</div>
            <div class="completion-actions" style="margin-bottom:12px;">
                <a href="teacher_analytics.php?class_id=<?php echo $class_id; ?>&test_id=<?php echo $test_id; ?>" class="btn-green" style="margin-top:0;">View Class Analytics</a>
                <a href="view_tests.php?class_id=<?php echo $class_id; ?>" class="btn-secondary-link">Back to Assessments</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="check_assessment.php?test_id=<?php echo $test_id; ?>" id="checkForm">
            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

            <div class="card">
                <div class="top-row">
                    <div class="checked-panel">
                        <label class="label" style="margin-bottom:10px;">Checked Students (Recheck)</label>
                        <?php if (!empty($checked_students)): ?>
                            <div class="checked-list">
                                <?php foreach ($checked_students as $cs): ?>
                                    <?php $sid = (int) $cs['student_id']; ?>
                                    <a class="checked-pill <?php echo $sid === $selected_student_id ? 'is-active' : ''; ?>" href="check_assessment.php?test_id=<?php echo $test_id; ?>&student_id=<?php echo $sid; ?>">
                                        <?php echo htmlspecialchars($cs['last_name'] . ', ' . $cs['first_name']); ?> (<?php echo (int) $cs['total_score']; ?>/<?php echo $max_score; ?>)
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="checked-empty">No checked students yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="score-box">
                        <div style="font-size:12px; color:#6b7280; font-weight:700; text-transform:uppercase;">Score</div>
                        <strong><span id="scoreValue"><?php echo $current_score; ?></span> / <?php echo $max_score; ?></strong>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <label for="studentSelect" class="label">Select Student</label>
                    <?php if ($all_students_checked && $selected_checked_student): ?>
                        <input type="hidden" name="student_id" value="<?php echo $selected_student_id; ?>">
                        <div class="selected-student-box">
                            <?php echo htmlspecialchars($selected_checked_student['last_name'] . ', ' . $selected_checked_student['first_name']); ?> (Recheck)
                        </div>
                    <?php elseif ($all_students_checked): ?>
                        <div class="selected-student-box">All students have been checked.</div>
                    <?php else: ?>
                        <select id="studentSelect" name="student_id" class="select" required>
                            <option value="">-- Select Student First --</option>
                            <?php if ($selected_checked_student): ?>
                                <option value="<?php echo $selected_student_id; ?>" selected>
                                    <?php echo htmlspecialchars($selected_checked_student['last_name'] . ', ' . $selected_checked_student['first_name'] . ' (Recheck)'); ?>
                                </option>
                            <?php endif; ?>
                            <?php foreach ($unchecked_students as $student): ?>
                                <option value="<?php echo (int) $student['student_id']; ?>" <?php echo ((int)$student['student_id'] === $selected_student_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <?php foreach ($parts as $part): ?>
                    <?php
                        $pid = (int) $part['test_part_id'];
                        $num_items = (int) $part['number_of_items'];
                        $ppi = (int) $part['points_per_item'];
                    ?>
                    <div class="part-card">
                        <div class="part-head">
                            <div>
                                <div class="part-title"><?php echo htmlspecialchars($part['part_order'] ?: ('Part #' . $pid)); ?> - <?php echo htmlspecialchars($part['part_type'] ?: 'Part'); ?></div>
                                <div class="part-meta">Competency: <?php echo htmlspecialchars($part['competency_name'] ?: 'Not tagged'); ?></div>
                            </div>
                            <div class="part-meta">Items: <?php echo $num_items; ?> | Points/Item: <?php echo $ppi; ?></div>
                        </div>

                        <div class="item-grid">
                            <?php for ($i = 1; $i <= $num_items; $i++): ?>
                                <?php $saved = $prefill_states[$pid][$i] ?? ''; ?>
                                <div class="item-box">
                                    <div class="item-num">Item <?php echo $i; ?></div>
                                    <input type="hidden" name="state[<?php echo $pid; ?>][<?php echo $i; ?>]" id="state-<?php echo $pid; ?>-<?php echo $i; ?>" value="<?php echo htmlspecialchars($saved); ?>" data-points="<?php echo $ppi; ?>" data-status-id="status-<?php echo $pid; ?>-<?php echo $i; ?>">
                                    <div class="item-status status-none" id="status-<?php echo $pid; ?>-<?php echo $i; ?>">Not checked</div>
                                    <div class="item-actions">
                                        <button type="button" class="state-btn btn-none" data-target="state-<?php echo $pid; ?>-<?php echo $i; ?>" data-value="" <?php echo $has_selected_student ? '' : 'disabled'; ?>>Reset</button>
                                        <button type="button" class="state-btn btn-correct" data-target="state-<?php echo $pid; ?>-<?php echo $i; ?>" data-value="1" <?php echo $has_selected_student ? '' : 'disabled'; ?>>✓ Correct</button>
                                        <button type="button" class="state-btn btn-wrong" data-target="state-<?php echo $pid; ?>-<?php echo $i; ?>" data-value="0" <?php echo $has_selected_student ? '' : 'disabled'; ?>>✕ Wrong</button>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="save-wrap">
                    <button type="submit" class="btn-green save-btn" <?php echo $has_selected_student ? '' : 'disabled'; ?>>Save Result</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const scoreValue = document.getElementById('scoreValue');
    const stateButtons = document.querySelectorAll('.state-btn');
    const studentSelect = document.getElementById('studentSelect');

    function updateActiveStates() {
        document.querySelectorAll('input[id^="state-"]').forEach(input => {
            const statusEl = document.getElementById(input.dataset.statusId || '');
            const itemBox = input.closest('.item-box');
            if (!itemBox) return;

            itemBox.classList.remove('state-none', 'state-correct', 'state-wrong');
            if (statusEl) {
                statusEl.classList.remove('status-none', 'status-correct', 'status-wrong');
            }

            if (input.value === '1') {
                itemBox.classList.add('state-correct');
                if (statusEl) {
                    statusEl.classList.add('status-correct');
                    statusEl.textContent = 'Correct';
                }
            } else if (input.value === '0') {
                itemBox.classList.add('state-wrong');
                if (statusEl) {
                    statusEl.classList.add('status-wrong');
                    statusEl.textContent = 'Wrong';
                }
            } else {
                itemBox.classList.add('state-none');
                if (statusEl) {
                    statusEl.classList.add('status-none');
                    statusEl.textContent = 'Not checked';
                }
            }
        });

        stateButtons.forEach(btn => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            btn.classList.toggle('is-active', input.value === btn.dataset.value);
        });
    }

    function recalcScore() {
        let score = 0;
        document.querySelectorAll('input[id^="state-"]').forEach(input => {
            if (input.value === '1') {
                score += parseInt(input.dataset.points || '0', 10);
            }
        });
        scoreValue.textContent = score;
    }

    stateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;
            const input = document.getElementById(this.dataset.target);
            if (!input) return;
            input.value = this.dataset.value;
            updateActiveStates();
            recalcScore();
        });
    });

    if (studentSelect) {
        studentSelect.addEventListener('change', function() {
            const sid = this.value;
            if (!sid) {
                window.location.href = 'check_assessment.php?test_id=<?php echo $test_id; ?>';
                return;
            }
            window.location.href = 'check_assessment.php?test_id=<?php echo $test_id; ?>&student_id=' + encodeURIComponent(sid);
        });
    }

    document.getElementById('checkForm').addEventListener('submit', function(e) {
        if (studentSelect && !studentSelect.value) {
            e.preventDefault();
            window.location.href = 'check_assessment.php?test_id=<?php echo $test_id; ?>&msg=no_student';
        }
    });

    updateActiveStates();
    recalcScore();
})();
</script>

</body>
</html>
