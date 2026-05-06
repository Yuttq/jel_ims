<?php

if (!isset($jel_ims_web_root)) {
    $jel_ims_script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $jel_ims_script_dir = dirname($jel_ims_script_name);
    $jel_ims_web_root = dirname(dirname(dirname($jel_ims_script_dir)));
    if ($jel_ims_web_root === '/' || $jel_ims_web_root === '.' || $jel_ims_web_root === '\\') {
        $jel_ims_web_root = '';
    }
}

$jel_ims_nav_role = 0;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['role_id'])) {
    $jel_ims_nav_role = (int) $_SESSION['role_id'];
}

$nb = $jel_ims_web_root;

?>
<aside class="site-sidebar" aria-label="Main navigation">
<nav class="site-sidebar__nav">
    <ul>
        <?php if ($jel_ims_nav_role === 0): ?>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/auth/login.php', ENT_QUOTES, 'UTF-8'); ?>">Login</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/auth/register.php', ENT_QUOTES, 'UTF-8'); ?>">Register</a></li>
        <?php endif; ?>

        <?php if ($jel_ims_nav_role === 1): ?>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/admin/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/admin/manage_users.php', ENT_QUOTES, 'UTF-8'); ?>">Manage Users</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/admin/assign_technicians.php', ENT_QUOTES, 'UTF-8'); ?>">Assign Technicians</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/admin/reports.php', ENT_QUOTES, 'UTF-8'); ?>">Reports</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/layouts/notifications.php', ENT_QUOTES, 'UTF-8'); ?>">Notifications</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/public/logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a></li>
        <?php endif; ?>

        <?php if ($jel_ims_nav_role === 2): ?>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/staff/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/layouts/notifications.php', ENT_QUOTES, 'UTF-8'); ?>">Notifications</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/public/logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a></li>
        <?php endif; ?>

        <?php if ($jel_ims_nav_role === 3): ?>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/technician/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/technician/update_status.php', ENT_QUOTES, 'UTF-8'); ?>">Update Status</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/layouts/notifications.php', ENT_QUOTES, 'UTF-8'); ?>">Notifications</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/public/logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a></li>
        <?php endif; ?>

        <?php if ($jel_ims_nav_role === 4): ?>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/customer/dashboard.php', ENT_QUOTES, 'UTF-8'); ?>">Dashboard</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/customer/create_booking.php', ENT_QUOTES, 'UTF-8'); ?>">Create Booking</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/customer/booking_history.php', ENT_QUOTES, 'UTF-8'); ?>">Booking History</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/app/views/layouts/notifications.php', ENT_QUOTES, 'UTF-8'); ?>">Notifications</a></li>
        <li><a href="<?php echo htmlspecialchars($nb . '/public/logout.php', ENT_QUOTES, 'UTF-8'); ?>">Logout</a></li>
        <?php endif; ?>
    </ul>
</nav>
</aside>
