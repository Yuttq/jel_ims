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

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id FROM technicians WHERE user_id = :uid LIMIT 1');
$stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$techRow = $stmt->fetch(PDO::FETCH_ASSOC);
$technicianId = $techRow ? (int) $techRow['id'] : 0;

$assignedCount = 0;
$ongoingCount = 0;
$completedCount = 0;

if ($technicianId > 0) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE technician_id = :tid AND status = :s');
    $stmt->bindParam(':tid', $technicianId, PDO::PARAM_INT);
    $assigned = 'Assigned';
    $stmt->bindParam(':s', $assigned, PDO::PARAM_STR);
    $stmt->execute();
    $assignedCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE technician_id = :tid AND status = :s');
    $stmt->bindParam(':tid', $technicianId, PDO::PARAM_INT);
    $ongoing = 'Ongoing';
    $stmt->bindParam(':s', $ongoing, PDO::PARAM_STR);
    $stmt->execute();
    $ongoingCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE technician_id = :tid AND status = :s');
    $stmt->bindParam(':tid', $technicianId, PDO::PARAM_INT);
    $completed = 'Completed';
    $stmt->bindParam(':s', $completed, PDO::PARAM_STR);
    $stmt->execute();
    $completedCount = (int) $stmt->fetchColumn();
}

$pageTitle = 'Technician Dashboard — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Technician Dashboard</h1>

<?php if ($technicianId === 0): ?>
<p>No technician profile is linked to this account.</p>
<?php endif; ?>

<table>
    <tr>
        <th>Metric</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Assigned Bookings</td>
        <td><?php echo htmlspecialchars((string) $assignedCount, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Ongoing Services</td>
        <td><?php echo htmlspecialchars((string) $ongoingCount, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Completed Services</td>
        <td><?php echo htmlspecialchars((string) $completedCount, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
