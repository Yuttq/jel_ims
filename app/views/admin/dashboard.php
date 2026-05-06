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

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM bookings');
$totalBookings = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM technicians');
$totalTechnicians = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = :s');
$unassignedLabel = 'Unassigned';
$stmt->bindParam(':s', $unassignedLabel, PDO::PARAM_STR);
$stmt->execute();
$unassignedBookings = (int) $stmt->fetchColumn();

$sqlRecent = 'SELECT u.full_name AS customer_name, s.service_name, b.booking_date,
                     ts.time_value, b.status
              FROM bookings b
              INNER JOIN users u ON b.user_id = u.id
              INNER JOIN services s ON b.service_id = s.id
              INNER JOIN time_slots ts ON b.time_slot_id = ts.id
              ORDER BY b.booking_date DESC, b.created_at DESC
              LIMIT 25';

$recentStmt = $pdo->query($sqlRecent);
$recentRows = $recentStmt ? $recentStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Admin Dashboard — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Admin Dashboard</h1>

<table>
    <tr>
        <th>Metric</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Total Users</td>
        <td><?php echo htmlspecialchars((string) $totalUsers, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Total Bookings</td>
        <td><?php echo htmlspecialchars((string) $totalBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Total Technicians</td>
        <td><?php echo htmlspecialchars((string) $totalTechnicians, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Unassigned Bookings</td>
        <td><?php echo htmlspecialchars((string) $unassignedBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>

<h2>Recent Bookings</h2>

<table>
    <tr>
        <th>Customer Name</th>
        <th>Service Name</th>
        <th>Date</th>
        <th>Time Slot</th>
        <th>Status</th>
    </tr>
    <?php foreach ($recentRows as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['time_value'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
