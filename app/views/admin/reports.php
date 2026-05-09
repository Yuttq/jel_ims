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

$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$pendingServices = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('Unassigned','Assigned','Ongoing')")->fetchColumn();
$completedServices = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Completed'")->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 4")->fetchColumn();
$totalTechnicians = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();

$monthlyRows = $pdo->query(
    "SELECT DATE_FORMAT(booking_date, '%Y-%m') AS ym, COUNT(*) AS total
     FROM bookings
     GROUP BY DATE_FORMAT(booking_date, '%Y-%m')
     ORDER BY ym ASC
     LIMIT 12"
)->fetchAll(PDO::FETCH_ASSOC);

$workloadRows = $pdo->query(
    "SELECT u.full_name AS technician_name, COUNT(b.id) AS total_jobs
     FROM technicians t
     INNER JOIN users u ON u.id = t.user_id
     LEFT JOIN bookings b ON b.technician_id = t.id
     GROUP BY t.id, u.full_name
     ORDER BY total_jobs DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Reports — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Reports</h1>

<table>
    <tr><th>Total Bookings</th><th>Pending Services</th><th>Completed Services</th><th>Total Customers</th><th>Total Technicians</th></tr>
    <tr>
        <td><?php echo $totalBookings; ?></td>
        <td><?php echo $pendingServices; ?></td>
        <td><?php echo $completedServices; ?></td>
        <td><?php echo $totalCustomers; ?></td>
        <td><?php echo $totalTechnicians; ?></td>
    </tr>
</table>

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

<h2>Simple Visual Summary</h2>
<style>
.report-chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
    margin: 12px 0 20px;
}
.report-chart-card {
    border: 1px solid #d5dbe3;
    background: #fff;
    border-radius: 8px;
    padding: 10px 12px;
}
.report-chart-card h3 {
    margin: 0 0 8px;
    font-size: 15px;
}
.report-chart-wrap {
    position: relative;
    height: 250px;
    max-height: 250px;
}
@media (max-width: 640px) {
    .report-chart-wrap {
        height: 220px;
        max-height: 220px;
    }
}
</style>

<div class="report-chart-grid">
    <section class="report-chart-card">
        <h3>Completed vs Pending</h3>
        <div class="report-chart-wrap">
            <canvas id="statusPie"></canvas>
        </div>
    </section>
    <section class="report-chart-card">
        <h3>Monthly Booking Trend</h3>
        <div class="report-chart-wrap">
            <canvas id="monthlyLine"></canvas>
        </div>
    </section>
    <section class="report-chart-card">
        <h3>Technician Workload</h3>
        <div class="report-chart-wrap">
            <canvas id="workloadBar"></canvas>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const pendingServices = <?php echo (int) $pendingServices; ?>;
const completedServices = <?php echo (int) $completedServices; ?>;
const monthlyLabels = <?php echo json_encode(array_map(function ($r) { return $r['ym']; }, $monthlyRows)); ?>;
const monthlyTotals = <?php echo json_encode(array_map(function ($r) { return (int) $r['total']; }, $monthlyRows)); ?>;
const workloadLabels = <?php echo json_encode(array_map(function ($r) { return $r['technician_name']; }, $workloadRows)); ?>;
const workloadTotals = <?php echo json_encode(array_map(function ($r) { return (int) $r['total_jobs']; }, $workloadRows)); ?>;

new Chart(document.getElementById('statusPie'), {
    type: 'pie',
    data: {
        labels: ['Pending', 'Completed'],
        datasets: [{ data: [pendingServices, completedServices] }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('monthlyLine'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{ label: 'Monthly Bookings', data: monthlyTotals, fill: false }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('workloadBar'), {
    type: 'bar',
    data: {
        labels: workloadLabels,
        datasets: [{ label: 'Technician Workload', data: workloadTotals }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
