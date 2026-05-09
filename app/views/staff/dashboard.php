<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 2) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$sqlToday = 'SELECT COUNT(*) FROM bookings WHERE booking_date = CURRENT_DATE';
$todayBookings = (int) $pdo->query($sqlToday)->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = :s');
$ongoingLabel = 'Ongoing';
$stmt->bindParam(':s', $ongoingLabel, PDO::PARAM_STR);
$stmt->execute();
$ongoingServices = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = :s');
$unassignedLabel = 'Unassigned';
$stmt->bindParam(':s', $unassignedLabel, PDO::PARAM_STR);
$stmt->execute();
$unassignedBookings = (int) $stmt->fetchColumn();

$pageTitle = 'Staff Dashboard — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Staff Dashboard</h1>
<p>Operational control center for technician and booking coordination.</p>

<table>
    <tr>
        <th>Metric</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Today's Bookings</td>
        <td><?php echo htmlspecialchars((string) $todayBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Ongoing Services</td>
        <td><?php echo htmlspecialchars((string) $ongoingServices, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Unassigned Bookings</td>
        <td><?php echo htmlspecialchars((string) $unassignedBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>

<p>
    <a href="manage_technicians.php">Manage Technicians</a> |
    <a href="view_bookings.php">View Bookings</a> |
    <a href="assign_technicians.php">Assign Technicians</a> |
    <a href="customer_details.php">Customer Details</a>
</p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
