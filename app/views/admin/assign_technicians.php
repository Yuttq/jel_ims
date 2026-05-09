<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

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
<p>Read-only operational monitoring for Admin.</p>

<?php if (!empty($_GET['assigned'])): ?>
<p>Technician assigned.</p>
<?php endif; ?>

<?php if (!empty($_GET['cancelled'])): ?>
<p>Booking cancelled.</p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    if ($code === 'technician_busy') {
        $msg = 'Technician already assigned at this time.';
    } elseif ($code === 'assign_invalid') {
        $msg = 'Assignment request was invalid.';
    } elseif ($code === 'assign_state') {
        $msg = 'Only unassigned bookings without a technician can be assigned here.';
    } elseif ($code === 'assign_failed') {
        $msg = 'Assignment could not be saved.';
    } elseif ($code === 'cancel_invalid') {
        $msg = 'Cancellation request was invalid.';
    } elseif ($code === 'cancel_failed') {
        $msg = 'This booking cannot be cancelled in its current state.';
    } else {
        $msg = 'Request could not be completed.';
    }
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<h2>Pending Technician Assignments</h2>

<?php if (count($unassigned) === 0): ?>
<p>No pending technician assignments.</p>
<?php else: ?>
<table>
    <tr>
        <th>Booking ID</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Date</th>
        <th>Time Slot</th>
        <th>Assignment</th>
    </tr>
    <?php foreach ($unassigned as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['time_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span>Managed by Staff</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Booking Cancellation Monitoring</h2>

<?php if (count($cancellable) === 0): ?>
<p>No bookings eligible for cancellation.</p>
<?php else: ?>
<table>
    <tr>
        <th>Booking ID</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Date</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php foreach ($cancellable as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><span>Managed by Staff</span></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
