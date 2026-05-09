<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../models/Booking.php';

$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
$bookingModel = new Booking();
$detail = ($bookingId > 0)
    ? $bookingModel->getBookingDetailForTechnician($bookingId, (int) $_SESSION['user_id'])
    : false;

$pageTitle = 'Booking Details — JEL-IMS';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<main class="site-main">
<h1>Booking Details</h1>

<?php if ($detail === false): ?>
<p>Booking details are unavailable or you do not have access to this booking.</p>
<?php else: ?>
<table>
    <tr><th>Field</th><th>Value</th></tr>
    <tr><td>Booking ID</td><td><?php echo (int) $detail['id']; ?></td></tr>
    <tr><td>Customer Name</td><td><?php echo htmlspecialchars((string) $detail['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Customer Email</td><td><?php echo htmlspecialchars((string) $detail['customer_email'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Customer Contact</td><td><?php echo htmlspecialchars((string) ($detail['customer_contact'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Service Location</td><td><?php echo htmlspecialchars((string) ($detail['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Requested Service</td><td><?php echo htmlspecialchars((string) $detail['service_name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Appointment Date</td><td><?php echo htmlspecialchars((string) $detail['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Appointment Time</td><td><?php echo htmlspecialchars((string) $detail['time_value'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <tr><td>Booking Status</td><td><?php echo htmlspecialchars((string) $detail['status'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
</table>
<?php endif; ?>
</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
