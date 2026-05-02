<?php
session_start();
include '../config/db.php';
include_once '../config/enrollment_helpers.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php"); exit();
}

$name = $_SESSION['user']['first_name'] . " " . $_SESSION['user']['last_name'];
$teacher_id = (int) $_SESSION['user']['user_id'];
$class_student_count = class_student_count_sql('c');

$classes = $conn->query("
    SELECT c.class_id, s.subject_name, sec.section_name, g.grade_level_name,
           $class_student_count AS std_count,
           ay.year_name
    FROM class c
    JOIN subject s ON c.subject_id = s.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    JOIN grade_level g ON sec.grade_level_id = g.grade_level_id
    LEFT JOIN academic_year ay ON c.academic_year_id = ay.academic_year_id
    WHERE c.user_id = '$teacher_id'
    ORDER BY g.grade_level_name, sec.section_name, s.subject_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - SMART</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="stylesheet" href="/assessment_system/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body.teacher-layout {
            background: #f5f6fa;
        }

        .class-grid {
            margin-top: 8px;
        }

        .class-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .class-item-card {
            border-radius: 16px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .class-item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1);
        }

        .class-details {
            margin-top: 10px;
            display: grid;
            gap: 8px;
        }

        .detail-line {
            color: #1f2937;
            font-size: 14px;
        }

        .detail-label {
            color: #6b7280;
            font-weight: 700;
            margin-right: 4px;
        }

        .class-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
            margin: 16px 0 14px;
            padding-top: 10px;
            border-top: 1px solid #eef2f7;
            gap: 12px;
            flex-wrap: wrap;
        }

        .class-open-label {
            display: inline-block;
            color: #34C759;
            font-weight: 700;
            font-size: 13px;
        }

        .session-badge {
            background: #e8fbe8;
            color: #16a34a;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            margin-top: 8px;
        }
    </style>
</head>
<body class="teacher-layout">

    <nav class="top-nav">
        <div class="brand-lockup" style="font-weight:800; font-size:20px; color:var(--text-dark);">
            <img src="../assets/img/smart-logo.png" alt="SMART Assessment System" class="brand-logo">
            <span>SMART Assessment System</span>
        </div>
        <div style="display:flex; align-items:center; gap:20px;">
            <span style="font-weight:600;"><?php echo htmlspecialchars($name); ?></span>
            <a href="../actions/logout.php" style="color:#E74C3C; text-decoration:none; font-weight:600;">Logout</a>
        </div>
    </nav>

    <div class="teacher-container">
        <div class="profile-header-card">
            <div class="avatar-circle">👩‍🏫</div>
            <div>
                <h1 style="font-size:32px;"><?php echo htmlspecialchars($name); ?></h1>
                <p style="color:var(--text-gray);">Teacher Workspace</p>
                <span class="session-badge">Active Session</span>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:12px;">
            <h2 style="font-size:20px; margin:0;">Choose Class</h2>
        </div>

        <div class="class-grid">
            <?php if($classes && $classes->num_rows > 0): ?>
                <?php while($row = $classes->fetch_assoc()): ?>
                    <a class="class-card-link" href="view_tests.php?class_id=<?php echo (int) $row['class_id']; ?>">
                        <div class="class-item-card">
                            <div class="icon-box">📖</div>

                            <div class="class-details">
                                <div class="detail-line"><span class="detail-label">Grade Level:</span><?php echo htmlspecialchars($row['grade_level_name']); ?></div>
                                <div class="detail-line"><span class="detail-label">Section:</span><?php echo htmlspecialchars($row['section_name']); ?></div>
                                <div class="detail-line"><span class="detail-label">Subject:</span><?php echo htmlspecialchars($row['subject_name']); ?></div>
                            </div>

                            <div class="class-meta">
                                <span><span class="detail-label">Students:</span><?php echo (int) ($row['std_count'] ?? 0); ?></span>
                                <span><?php echo htmlspecialchars($row['year_name'] ?? 'Current SY'); ?></span>
                            </div>

                            <span class="class-open-label">Open Assessments →</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <p style="margin:0; color:var(--text-gray);">No classes assigned. Contact the Principal.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
