<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'principal') {
    header("Location: login.php"); exit();
}

// Fetch Pending Teachers
$pending = $conn->query("SELECT * FROM user WHERE role = 'teacher' AND status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Accounts - SMART</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .tabs {
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #E2E8F0;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 5px;
            cursor: pointer;
            color: var(--text-gray);
            font-weight: 600;
            text-decoration: none;
        }

        .tab.active {
            color: var(--primary-green);
            border-bottom: 2px solid var(--primary-green);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .teacher-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .teacher-table thead th {
            background: #F8FAFC;
            color: #1F2937;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #E5EAF0;
        }

        .teacher-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #EEF2F7;
            color: #1F2937;
            vertical-align: middle;
        }

        .teacher-table tbody tr:hover {
            background: #FAFCFF;
        }

        .teacher-name {
            font-weight: 700;
        }

        .teacher-email {
            color: #334155;
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .action-cell .btn-green {
            margin-top: 0;
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 8px;
            text-decoration: none;
            line-height: 1.2;
        }

        .btn-reject {
            display: inline-block;
            background: #fff;
            color: #E74C3C;
            border: 1px solid #E74C3C;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<div class="admin-layout">
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 30px;">
        <h1 style="margin-top: 10px;">Teacher Accounts</h1>
        <p style="color: var(--text-gray);">Manage and monitor all educator access within the system.</p>
    </div>

    <!-- Success Message Pop-up -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'approved'): ?>
        <script>alert("✅ Teacher account has been approved successfully!");</script>
    <?php endif; ?>

    <div class="card">
        <div class="tabs">
            <a href="#" class="tab active">Pending Requests</a>
            <a href="#" class="tab">Approved</a>
            <a href="#" class="tab">Rejected</a>
        </div>

        <div class="table-wrap">
            <table class="teacher-table">
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pending->num_rows > 0): ?>
                        <?php while($row = $pending->fetch_assoc()): ?>
                        <tr>
                            <td class="teacher-name"><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                            <td class="teacher-email"><?php echo $row['email']; ?></td>
                            <td><?php echo ucfirst($row['gender']); ?></td>
                            <td>
                                <div class="action-cell">
                                    <a href="../actions/approve_teacher.php?id=<?php echo $row['user_id']; ?>" class="btn-green">Approve</a>
                                    <a href="#" class="btn-reject">Reject</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" align="center" style="padding: 40px; color: var(--text-gray);">No pending requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

</body>
</html>
