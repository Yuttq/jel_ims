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

$isReadOnly = ($viewerRole === 1);

require_once __DIR__ . '/../../models/Booking.php';
require_once __DIR__ . '/../../models/Technician.php';

$bookingModel = new Booking();
$technicianModel = new Technician();

$allBookings = $bookingModel->getAllBookings();
$unassigned = array_values(array_filter($allBookings, function ($b) {
    $tid = $b['technician_id'] ?? null;
    return isset($b['status']) && $b['status'] === 'Unassigned'
        && ($tid === null || $tid === '' || (int) $tid === 0);
}));
$cancellable = array_values(array_filter($allBookings, function ($b) {
    $st = isset($b['status']) ? (string) $b['status'] : '';
    return in_array($st, ['Unassigned', 'Assigned', 'Ongoing'], true);
}));
$technicians = $technicianModel->getAllTechnicians();

$pageTitle = 'Assign Technicians — JEL-IMS';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<main class="site-main">
<h1>Assign Technicians</h1>
<?php if ($isReadOnly): ?><p>Read-only mode for Admin oversight.</p><?php endif; ?>
<?php if (!empty($_GET['assigned'])): ?><p>Technician assignment completed successfully.</p><?php endif; ?>
<?php if (!empty($_GET['cancelled'])): ?><p>Booking cancellation completed successfully.</p><?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
<?php
$code = (string) $_GET['error'];
$map = [
    'technician_busy' => 'Technician already assigned at this time.',
    'assign_invalid' => 'Assignment request was invalid.',
    'assign_state' => 'Only unassigned bookings can be assigned.',
    'assign_failed' => 'Assignment could not be saved.',
    'cancel_invalid' => 'Cancellation request was invalid.',
    'cancel_failed' => 'This booking cannot be cancelled in its current state.',
];
?>
<p><?php echo htmlspecialchars($map[$code] ?? 'Request could not be completed.', ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<h2>Pending Technician Assignments</h2>
<?php if (count($unassigned) === 0): ?>
<p>No pending technician assignments.</p>
<?php else: ?>
<table>
    <tr><th>Booking ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Time Slot</th><th>Action</th></tr>
    <?php foreach ($unassigned as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['time_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?php if ($isReadOnly): ?>
            <span>Read-only</span>
            <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/TechnicianController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                <select name="technician_id" required>
                    <option value="">Select</option>
                    <?php foreach ($technicians as $t): ?>
                    <option value="<?php echo (int) $t['id']; ?>">
                        <?php echo htmlspecialchars((string) ($t['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_technician" value="1">Assign Technician</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Booking Cancellation Queue</h2>
<?php if (count($cancellable) === 0): ?>
<p>No bookings eligible for cancellation.</p>
<?php else: ?>
<table>
    <tr><th>Booking ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($cancellable as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?php if ($isReadOnly): ?>
            <span>Read-only</span>
            <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/BookingController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                <textarea name="cancellation_reason" rows="2" cols="28" required></textarea><br>
                <button type="submit" name="cancel_booking" value="1">Cancel Booking</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
