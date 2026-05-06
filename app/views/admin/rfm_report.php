<?php

require_once __DIR__ . '/../../controllers/ReportController.php';
jel_ims_reports_require_admin();

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$sqlRfm = 'SELECT full_name, frequency, recency_days, total_service_minutes
           FROM rfm_customer_analysis
           ORDER BY frequency DESC';

$rfmStmt = $pdo->query($sqlRfm);
$rfmRows = $rfmStmt ? $rfmStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$pageTitle = 'RFM Analysis — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>RFM Analysis Report</h1>

<table>
    <tr>
        <th>Full Name</th>
        <th>Frequency</th>
        <th>Recency Days</th>
        <th>Total Service Minutes</th>
    </tr>
    <?php foreach ($rfmRows as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['frequency'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['recency_days'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $row['total_service_minutes'], ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="reports.php">Reports</a></p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
