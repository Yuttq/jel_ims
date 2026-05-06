<?php

require_once __DIR__ . '/../../controllers/ReportController.php';
jel_ims_reports_require_admin();

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$sqlFrequency = 'SELECT u.full_name AS customer_name,
                        COUNT(b.id) AS total_bookings,
                        MAX(b.booking_date) AS last_booking_date
                 FROM users u
                 INNER JOIN bookings b ON b.user_id = u.id
                 GROUP BY u.id, u.full_name
                 ORDER BY total_bookings DESC';

$frequencyStmt = $pdo->query($sqlFrequency);
$frequencyRows = $frequencyStmt ? $frequencyStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Reports — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Reports</h1>

<ul>
    <li><a href="reports.php#customer-frequency">Customer Frequency Report</a></li>
    <li><a href="technician_workload.php">Technician Workload Report</a></li>
    <li><a href="rfm_report.php">RFM Analysis Report</a></li>
</ul>

<h2 id="customer-frequency">Customer Frequency Report</h2>

<table>
    <tr>
        <th>Customer Name</th>
        <th>Total Bookings</th>
        <th>Last Booking Date</th>
    </tr>
    <?php foreach ($frequencyRows as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['total_bookings'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['last_booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
