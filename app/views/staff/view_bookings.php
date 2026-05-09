<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$viewerRole = (int) $_SESSION['role_id'];
if ($viewerRole !== 1 && $viewerRole !== 2) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Booking.php';

$bookingModel = new Booking();
$rows = $bookingModel->getAllBookings();

$pageTitle = 'View Bookings — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>View Bookings</h1>

<?php if ($viewerRole === 1): ?>
<p>Read-only mode for Admin oversight.</p>
<?php endif; ?>

<?php if (count($rows) === 0): ?>
<p>No bookings found.</p>
<?php else: ?>
<table>
    <tr>
        <th>Booking ID</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Date</th>
        <th>Time Slot</th>
        <th>Technician</th>
        <th>Status</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <?php $statusLabel = ((string) ($r['status'] ?? '') === 'Unassigned') ? 'Pending Technician Assignment' : (string) ($r['status'] ?? ''); ?>
    <tr>
        <td><?php echo (int) $r['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($r['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($r['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($r['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($r['time_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($r['technician_name'] ?? 'Not yet assigned'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
