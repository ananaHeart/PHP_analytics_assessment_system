<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user']['user_id'];
$selected_test = $_GET['test_id'] ?? null;

// 1. Get all tests created by this teacher
$tests = $conn->query("SELECT t.*, s.subject_name, sec.section_name 
                       FROM test t
                       JOIN class c ON t.class_id = c.class_id
                       JOIN subject s ON c.subject_id = s.subject_id
                       JOIN section sec ON c.section_id = sec.section_id
                       WHERE c.user_id = '$teacher_id'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="header">Test Results Summary</div>
<div class="container">
    <div class="card">
        <form method="GET">
            <label>Select Test to View:</label>
            <select name="test_id" onchange="this.form.submit()">
                <option value="">-- Choose Test --</option>
                <?php while($t = $tests->fetch_assoc()): ?>
                    <option value="<?php echo $t['test_id']; ?>" <?php echo ($selected_test == $t['test_id']) ? 'selected' : ''; ?>>
                        <?php echo $t['test_name'] . " - " . $t['subject_name'] . " (" . $t['section_name'] . ")"; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <?php if($selected_test): 
        // 1. NEW: Fetch test details (name, subject, section) for the header
        $info_query = $conn->query("SELECT t.test_name, s.subject_name, sec.section_name 
                                    FROM test t
                                    JOIN class c ON t.class_id = c.class_id
                                    JOIN subject s ON c.subject_id = s.subject_id
                                    JOIN section sec ON c.section_id = sec.section_id
                                    WHERE t.test_id = '$selected_test' LIMIT 1");
        $info = $info_query->fetch_assoc();

        // 2. Get the total possible points for this test
        $total_points_query = $conn->query("SELECT SUM(number_of_items * points_per_item) as max_score FROM test_part WHERE test_id = '$selected_test'");
        $max_score_data = $total_points_query->fetch_assoc();
        $max_score = $max_score_data['max_score'] ?? 0;

        // 3. Get student scores
        $results = $conn->query("SELECT s.first_name, s.last_name, tr.total_score, tr.checked_at 
                                FROM test_result tr
                                JOIN student s ON tr.student_id = s.student_id
                                WHERE tr.test_id = '$selected_test'
                                ORDER BY s.last_name ASC");
    ?>
    
    <div class="card">
        <!-- We now use $info correctly here -->
        <h3>Results for: <?php echo $info['test_name']; ?> (<?php echo $info['subject_name']; ?>)</h3>
        <p>Section: <?php echo $info['section_name']; ?> | Max Score: <?php echo $max_score; ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if($results->num_rows > 0): ?>
                    <?php while($r = $results->fetch_assoc()): 
                        $pct = ($max_score > 0) ? ($r['total_score'] / $max_score) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo $r['last_name'] . ", " . $r['first_name']; ?></td>
                        <td><?php echo $r['total_score']; ?></td>
                        <td><?php echo round($pct, 2); ?>%</td>
                        <td>
                            <strong style="color: <?php echo ($pct >= 75) ? '#27ae60' : '#e74c3c'; ?>">
                                <?php echo ($pct >= 75) ? 'PASSED' : 'LOW'; ?>
                            </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No scores encoded for this test yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <!-- CSV Export Link -->
            <a href="../actions/export_results.php?test_id=<?php echo $selected_test; ?>" 
               class="btn-primary" 
               style="background: #27ae60; text-decoration: none; padding: 10px 15px; border-radius: 5px; color: white; font-weight: bold;">
               Export to Excel (CSV)
            </a>

            <!-- Print Button -->
            <button onclick="window.print()" class="btn-primary" style="background: #34495e; padding: 10px 15px; border-radius: 5px; color: white; border: none; font-weight: bold; cursor: pointer;">
               Print PDF
            </button>
        </div>
    </div>
    <?php endif; ?>
    <br>
    <a href="dashboard_teacher.php">Back to Dashboard</a>
</div>
</body>
</html>