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

<h2>Unassigned Bookings</h2>

<?php if (count($unassigned) === 0): ?>
<p>No unassigned bookings.</p>
<?php else: ?>
<table>
    <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Date</th>
        <th>Time</th>
        <th>Assign</th>
    </tr>
    <?php foreach ($unassigned as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['time_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/TechnicianController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                <label for="tech_<?php echo (int) $b['id']; ?>">Technician</label><br>
                <select id="tech_<?php echo (int) $b['id']; ?>" name="technician_id" required>
                    <option value="">Select</option>
                    <?php foreach ($technicians as $t): ?>
                    <option value="<?php echo (int) $t['id']; ?>">
                        <?php echo htmlspecialchars((string) ($t['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit" name="assign_technician" value="1">Assign</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Cancel Booking (Admin)</h2>

<?php if (count($cancellable) === 0): ?>
<p>No bookings eligible for cancellation.</p>
<?php else: ?>
<table>
    <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Date</th>
        <th>Status</th>
        <th>Cancel</th>
    </tr>
    <?php foreach ($cancellable as $b): ?>
    <tr>
        <td><?php echo (int) $b['id']; ?></td>
        <td><?php echo htmlspecialchars((string) ($b['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['service_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['booking_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($b['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/BookingController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                <label for="adm_reason_<?php echo (int) $b['id']; ?>">Reason</label><br>
                <textarea id="adm_reason_<?php echo (int) $b['id']; ?>" name="cancellation_reason" rows="2" cols="28" required></textarea><br>
                <button type="submit" name="cancel_booking" value="1">Cancel booking</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
