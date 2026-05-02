<?php
session_start();
include '../config/db.php';

// Security: Principal only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'principal') {
    header("Location: login.php"); exit();
}

// 1. Fetch Active Teachers
$teachers = $conn->query("SELECT user_id, first_name, last_name FROM user WHERE role = 'teacher' AND status = 'active'");

// 2. Fetch Subjects
$subjects = $conn->query("SELECT * FROM subject");

// 3. Fetch Sections with Grade Levels
$sections = $conn->query("SELECT s.section_id, s.section_name, g.grade_level_name FROM section s JOIN grade_level g ON s.grade_level_id = g.grade_level_id");

// 4. Fetch Current Assignments (The Table at the bottom)
$assignments = $conn->query("
    SELECT c.class_id, u.first_name, u.last_name, s.subject_name, sec.section_name, g.grade_level_name, ay.year_name
    FROM class c
    JOIN user u ON c.user_id = u.user_id
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    JOIN academic_year ay ON c.academic_year_id = ay.academic_year_id
    WHERE ay.status = 'Active'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Class Assignment</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header {
            margin-bottom: 30px;
        }

        .assignment-card,
        .table-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            width: 100%;
        }

        .assignment-card {
            margin-bottom: 32px;
        }

        .card-title {
            margin: 0 0 18px;
            color: var(--text-dark);
            font-size: 20px;
        }

        .assign-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1.2fr;
            gap: 16px;
            align-items: end;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-field label {
            color: var(--text-gray);
            font-size: 13px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            height: 48px;
            border-radius: 10px;
            border: 1px solid #d8dee9;
            padding: 0 14px;
            background: #ffffff;
            color: var(--text-dark);
            font-size: 14px;
        }

        .assign-btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 10px;
            background: #34C759;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            white-space: nowrap;
        }

        .table-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 16px;
        }

        .table-search {
            width: 280px;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #d8dee9;
            padding: 0 14px;
            color: var(--text-dark);
            font-size: 14px;
            margin-left: auto;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .assignments-table thead th {
            background: #f7f9fc;
            color: #1f2937;
            font-weight: 700;
        }

        .assignments-table th,
        .assignments-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #eef1f5;
            white-space: nowrap;
        }

        .assignments-table tbody tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            background: #e8fbe8;
            color: #16a34a;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
            line-height: 1;
            border: 1px solid transparent;
        }

        .edit-btn {
            background: #fff4e5;
            color: #f59e0b;
            border-color: #fde7c2;
        }

        .delete-btn {
            background: #fff1f2;
            color: #dc2626;
            border-color: #ffd6dc;
        }

        @media (max-width: 1100px) {
            .assign-form-grid {
                grid-template-columns: 1fr;
            }

            .table-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-search {
                width: 100%;
                max-width: 320px;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <small style="color: var(--text-gray);">Assessments / Teacher Class Assignment</small>
        <h1 style="margin-top: 10px;">Teacher Class Assignment</h1>
        <p style="color: var(--text-gray);">Manage and assign teachers to sections and subjects for the current academic year.</p>
    </div>

    <div class="assignment-card">
        <h3 class="card-title">Assignment Creation Section</h3>
        <form action="../actions/save_class.php" method="POST" class="assign-form-grid">
            <div class="form-field">
                <label for="teacherSelect">Teacher</label>
                <select id="teacherSelect" name="user_id" class="form-control" required>
                    <option value="">Select Teacher</option>
                    <?php while($t = $teachers->fetch_assoc()): ?>
                        <option value="<?php echo $t['user_id']; ?>"><?php echo $t['first_name']." ".$t['last_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="subjectSelect">Subject</label>
                <select id="subjectSelect" name="subject_id" class="form-control" required>
                    <option value="">Select Subject</option>
                    <?php while($s = $subjects->fetch_assoc()): ?>
                        <option value="<?php echo $s['subject_id']; ?>"><?php echo $s['subject_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="sectionSelect">Section</label>
                <select id="sectionSelect" name="section_id" class="form-control" required>
                    <option value="">Select Section</option>
                    <?php while($sec = $sections->fetch_assoc()): ?>
                        <option value="<?php echo $sec['section_id']; ?>">
                            <?php echo $sec['grade_level_name']." - ".$sec['section_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="assign-btn">Assign Teacher</button>
        </form>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h3 class="card-title" style="margin:0;">Assignments Table</h3>
            <input type="text" class="table-search" placeholder="Search assignments...">
        </div>

        <div class="table-responsive">
            <table class="assignments-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($assignments->num_rows > 0): ?>
                        <?php while($row = $assignments->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $row['first_name']." ".$row['last_name']; ?></strong></td>
                            <td><?php echo $row['subject_name']; ?></td>
                            <td><?php echo $row['grade_level_name']." - ".$row['section_name']; ?></td>
                            <td><?php echo $row['year_name']; ?></td>
                            <td><span class="status-badge">Active</span></td>
                            <td>
                                <div class="actions-cell">
                                    <a href="#" class="icon-btn edit-btn" aria-label="Edit assignment">✎</a>
                                    <a href="../actions/delete_class.php?id=<?php echo $row['class_id']; ?>"
                                       class="icon-btn delete-btn"
                                       aria-label="Delete assignment"
                                       onclick="return confirm('Are you sure you want to remove this teacher assignment?');">🗑</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" align="center">No assignments found for the active year.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <script>alert("✅ Assignment removed successfully.");</script>
<?php endif; ?>
</body>
</html>
