<?php

require_once __DIR__ . '/../../controllers/ReportController.php';
jel_ims_reports_require_admin();

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$sqlWorkload = 'SELECT u.full_name AS technician_name,
                       COUNT(b.id) AS total_assigned_jobs,
                       COALESCE(SUM(CASE WHEN b.status = \'Ongoing\' THEN 1 ELSE 0 END), 0) AS ongoing_jobs,
                       COALESCE(SUM(CASE WHEN b.status = \'Completed\' THEN 1 ELSE 0 END), 0) AS completed_jobs
                FROM technicians t
                INNER JOIN users u ON t.user_id = u.id
                LEFT JOIN bookings b ON b.technician_id = t.id
                GROUP BY t.id, u.full_name
                ORDER BY u.full_name ASC';

$workloadStmt = $pdo->query($sqlWorkload);
$workloadRows = $workloadStmt ? $workloadStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'Technician Workload — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Technician Workload Report</h1>

<table>
    <tr>
        <th>Technician Name</th>
        <th>Total Assigned Jobs</th>
        <th>Ongoing Jobs</th>
        <th>Completed Jobs</th>
    </tr>
    <?php foreach ($workloadRows as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['technician_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['total_assigned_jobs'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['ongoing_jobs'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['completed_jobs'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="reports.php">Reports</a></p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
