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

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$userId = (int) $_SESSION['user_id'];

$sqlUpcoming = 'SELECT COUNT(*) FROM bookings
                WHERE user_id = :uid
                  AND booking_date >= CURRENT_DATE
                  AND status IN (\'Unassigned\', \'Assigned\', \'Ongoing\')';

$stmt = $pdo->prepare($sqlUpcoming);
$stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$upcomingBookings = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :uid AND status = :s');
$stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
$completed = 'Completed';
$stmt->bindParam(':s', $completed, PDO::PARAM_STR);
$stmt->execute();
$completedBookings = (int) $stmt->fetchColumn();

$pageTitle = 'Customer Dashboard — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Customer Dashboard</h1>

<table>
    <tr>
        <th>Metric</th>
        <th>Value</th>
    </tr>
    <tr>
        <td>Upcoming Bookings</td>
        <td><?php echo htmlspecialchars((string) $upcomingBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <td>Completed Bookings</td>
        <td><?php echo htmlspecialchars((string) $completedBookings, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
