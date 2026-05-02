<?php
session_start();
include '../config/db.php';

// Security check: Principal only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'principal') {
    header("Location: login.php");
    exit();
}

$upload_status = $_GET['upload'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Import - SMART</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .import-container {
            border: 2px dashed #3ACF49;
            background: #f9fff9;
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            margin-top: 20px;
        }

        .notice {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .notice.success {
            background: #ebfbee;
            color: #166534;
            border: 1px solid #bfe9c8;
        }

        .notice.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 30px;">
        <small style="color: var(--text-gray);">Principal Portal / Class Records</small>
        <h1 style="margin-top: 10px;">Smart Import (SF1)</h1>
        <p style="color: var(--text-gray);">Upload the student master list to automatically populate the system. For demo mode, reset old student and assessment data first, then upload the 5-students-per-section CSV.</p>
    </div>

    <?php if ($upload_status === 'invalid_file'): ?>
        <div class="notice error">Please choose a valid CSV file before uploading.</div>
    <?php elseif ($upload_status === 'missing_year'): ?>
        <div class="notice error">No active academic year is configured.</div>
    <?php elseif ($upload_status === 'failed'): ?>
        <div class="notice error">Upload failed. Please try again.</div>
    <?php endif; ?>

    <div class="card">
        <div class="import-container">
            <div style="font-size: 50px; margin-bottom: 20px;">📄</div>
            <h3>Upload Student List</h3>
            <p style="color: var(--text-gray); margin-bottom: 30px;">Please select the CSV file containing student records.</p>
            
            <form action="../actions/smart_upload_action.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required style="margin-bottom: 20px;"><br>
                <button type="submit" name="upload_btn" class="btn-green">Process and Enroll Students</button>
            </form>
            
            <div style="margin-top: 30px; text-align: left; font-size: 13px; color: var(--text-gray); background: #fff; padding: 15px; border-radius: 10px; border: 1px solid #eee;">
                <strong>CSV Format Reminder:</strong><br>
                Column 1: LRN | Column 2: First Name | Column 3: Last Name | Column 4: Gender | Column 5: Grade | Column 6: Section
            </div>
        </div>
    </div>
</div>
</div>

</body>
</html>
