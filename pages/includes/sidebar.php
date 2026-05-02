<?php
$role = $_SESSION['user']['role'];
$initials = substr($_SESSION['user']['first_name'], 0, 1) . substr($_SESSION['user']['last_name'], 0, 1);
$current_page = basename($_SERVER['PHP_SELF']);

function nav_is_active($current_page, $pages) {
    return in_array($current_page, $pages, true) ? ' active' : '';
}
?>

<div class="sidebar">
    <div class="logo-section">
        <div class="logo-mark">S</div>
        <span>SMART <br><small>ASSESSMENT SYSTEM</small></span>
    </div>

    <div class="nav-menu">
        <?php if($role == 'principal'): ?>
            <a href="dashboard_principal.php" class="nav-item<?php echo nav_is_active($current_page, ['dashboard_principal.php']); ?>">Dashboard</a>
            <a href="manage_teachers.php" class="nav-item<?php echo nav_is_active($current_page, ['manage_teachers.php']); ?>">Manage Teachers</a>
            <a href="smart_import.php" class="nav-item<?php echo nav_is_active($current_page, ['smart_import.php']); ?>">Import Students (SF1)</a>
            <a href="assign_class.php" class="nav-item<?php echo nav_is_active($current_page, ['assign_class.php']); ?>">Assign Classes</a>
            <a href="principal_analytics.php" class="nav-item<?php echo nav_is_active($current_page, ['principal_analytics.php']); ?>">Analytics</a>
        <?php else: ?>
            <a href="dashboard_teacher.php" class="nav-item<?php echo nav_is_active($current_page, ['dashboard_teacher.php', 'my_classes.php', 'view_tests.php', 'view_assessment.php', 'create_test.php', 'edit_test.php', 'manage_test_parts.php']); ?>">My Classes</a>
            <a href="teacher_analytics.php" class="nav-item<?php echo nav_is_active($current_page, ['teacher_analytics.php']); ?>">Analytics</a>
        <?php endif; ?>
    </div>

    <div class="user-profile">
        <div class="avatar"><?php echo strtoupper($initials); ?></div>
        <div class="user-info">
            <div class="user-name"><?php echo $_SESSION['user']['first_name']; ?></div>
            <div class="user-role"><?php echo ucfirst($role); ?></div>
        </div>
    </div>
    
    <a href="../actions/logout.php" class="logout-link<?php echo nav_is_active($current_page, ['logout.php']); ?>">Logout</a>
</div>

