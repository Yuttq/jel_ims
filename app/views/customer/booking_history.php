<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 4) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Booking.php';

$bookingModel = new Booking();
$rows = $bookingModel->getBookingsByUser((int) $_SESSION['user_id']);

$pageTitle = 'Booking History — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Booking History</h1>

<?php if (!empty($_GET['created'])): ?>
<p>Your booking was created.</p>
<?php endif; ?>

<?php if (!empty($_GET['cancelled'])): ?>
<p>Your booking was cancelled.</p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
    $ec = (string) $_GET['error'];
    $ems = [
        'cancel_invalid' => 'Cancellation could not be processed.',
        'cancel_forbidden' => 'You cannot cancel this booking.',
        'cancel_failed' => 'This booking cannot be cancelled in its current state.',
    ];
    $em = isset($ems[$ec]) ? $ems[$ec] : 'Something went wrong.';
?>
<p><?php echo htmlspecialchars($em, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (count($rows) === 0): ?>
<p>No bookings yet.</p>
<?php else: ?>
<table>
    <tr>
        <th>Service Name</th>
        <th>Booking Date</th>
        <th>Time Slot</th>
        <th>Status</th>
        <th>Cancel</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <?php
        $st = (string) $r['status'];
        $canCancel = in_array($st, ['Unassigned', 'Assigned', 'Ongoing'], true);
    ?>
    <tr>
        <td><?php echo htmlspecialchars($r['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['time_value'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <?php if ($canCancel): ?>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/BookingController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $r['id']; ?>">
                <label for="reason_<?php echo (int) $r['id']; ?>">Reason</label><br>
                <textarea id="reason_<?php echo (int) $r['id']; ?>" name="cancellation_reason" rows="2" cols="28" required></textarea><br>
                <button type="submit" name="cancel_booking" value="1">Cancel booking</button>
            </form>
            <?php else: ?>
            <span>N/A</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
