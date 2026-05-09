<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../controllers/NotificationController.php';

$notificationController = new NotificationController();
$sessionUserId = (int) $_SESSION['user_id'];
$sessionRoleId = (int) $_SESSION['role_id'];
$allNotifications = $notificationController->getUserNotifications($sessionUserId);

$unread = [];
$read = [];

foreach ($allNotifications as $row) {
    if (isset($row['status']) && strcasecmp((string) $row['status'], 'Unread') === 0) {
        $unread[] = $row;
    } else {
        $read[] = $row;
    }
}

$pageTitle = 'Notifications — JEL-IMS';

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

?>
<main class="site-main">

<h1>Notifications</h1>

<h2>Unread Notifications</h2>

<?php if (count($unread) === 0): ?>
<p>No unread notifications.</p>
<?php else: ?>
<table>
    <tr>
        <th>Message</th>
        <th>Type</th>
        <th>Booking</th>
        <th>Created</th>
        <th>Action</th>
        <th>Details</th>
    </tr>
    <?php foreach ($unread as $n): ?>
    <tr>
        <td><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $n['type'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($n['booking_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $n['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/NotificationController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="notification_id" value="<?php echo (int) $n['id']; ?>">
                <button type="submit" name="mark_notification_read" value="1">Mark as read</button>
            </form>
        </td>
        <td>
            <?php if ($sessionRoleId === 3 && !empty($n['booking_id'])): ?>
            <a href="<?php echo htmlspecialchars($jel_ims_web_root . '/app/views/technician/booking_details.php?booking_id=' . (int) $n['booking_id'], ENT_QUOTES, 'UTF-8'); ?>">Open booking</a>
            <?php else: ?>
            <span>N/A</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Read Notifications</h2>

<?php if (count($read) === 0): ?>
<p>No read notifications.</p>
<?php else: ?>
<table>
    <tr>
        <th>Message</th>
        <th>Type</th>
        <th>Booking</th>
        <th>Created</th>
        <th>Status</th>
    </tr>
    <?php foreach ($read as $n): ?>
    <tr>
        <td><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $n['type'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($n['booking_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $n['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $n['status'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>
