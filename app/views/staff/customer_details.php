<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$viewerRole = (int) $_SESSION['role_id'];
if ($viewerRole !== 1 && $viewerRole !== 2) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../config/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$keyword = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$sqlCustomers = 'SELECT u.id AS user_id, u.full_name, u.email, c.contact_number, c.address,
                        COUNT(b.id) AS total_bookings
                 FROM users u
                 INNER JOIN customers c ON c.user_id = u.id
                 LEFT JOIN bookings b ON b.user_id = u.id
                 WHERE (:kw = "" OR u.full_name LIKE :like_kw OR u.email LIKE :like_kw)
                 GROUP BY u.id, u.full_name, u.email, c.contact_number, c.address
                 ORDER BY u.full_name ASC';
$stmtCustomers = $pdo->prepare($sqlCustomers);
$like = '%' . $keyword . '%';
$stmtCustomers->bindParam(':kw', $keyword, PDO::PARAM_STR);
$stmtCustomers->bindParam(':like_kw', $like, PDO::PARAM_STR);
$stmtCustomers->execute();
$customerRows = $stmtCustomers->fetchAll(PDO::FETCH_ASSOC);

$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$historyRows = [];
if ($selectedUserId > 0) {
    $sqlHistory = 'SELECT b.id, b.booking_date, ts.time_value, s.service_name, b.status,
                          tu.full_name AS technician_name
                   FROM bookings b
                   INNER JOIN services s ON s.id = b.service_id
                   INNER JOIN time_slots ts ON ts.id = b.time_slot_id
                   LEFT JOIN technicians t ON t.id = b.technician_id
                   LEFT JOIN users tu ON tu.id = t.user_id
                   WHERE b.user_id = :uid
                   ORDER BY b.booking_date DESC, b.time_slot_id DESC';
    $stmtHistory = $pdo->prepare($sqlHistory);
    $stmtHistory->bindParam(':uid', $selectedUserId, PDO::PARAM_INT);
    $stmtHistory->execute();
    $historyRows = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Customer Details — JEL-IMS';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<main class="site-main">
<h1>Customer Details</h1>
<?php if ($viewerRole === 1): ?><p>Read-only mode for Admin oversight.</p><?php endif; ?>

<form method="get" action="">
    <label for="q">Search customers</label>
    <input id="q" type="text" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name or email">
    <button type="submit">Search</button>
</form>

<h2>Customer List</h2>
<?php if (count($customerRows) === 0): ?>
<p>No customers found.</p>
<?php else: ?>
<table>
    <tr><th>Name</th><th>Email</th><th>Contact</th><th>Address</th><th>Total Bookings</th><th>Action</th></tr>
    <?php foreach ($customerRows as $c): ?>
    <tr>
        <td><?php echo htmlspecialchars((string) ($c['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($c['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($c['contact_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($c['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo (int) ($c['total_bookings'] ?? 0); ?></td>
        <td><a href="?q=<?php echo urlencode($keyword); ?>&user_id=<?php echo (int) $c['user_id']; ?>">View Booking History</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if ($selectedUserId > 0): ?>
<h2>Booking History (Customer #<?php echo $selectedUserId; ?>)</h2>
<?php if (count($historyRows) === 0): ?>
<p>No booking history for this customer.</p>
<?php else: ?>
<table>
    <tr><th>Booking ID</th><th>Service</th><th>Date</th><th>Time Slot</th><th>Technician</th><th>Status</th></tr>
    <?php foreach ($historyRows as $h): ?>
    <?php $statusLabel = ((string) $h['status'] === 'Unassigned') ? 'Pending Technician Assignment' : (string) $h['status']; ?>
    <tr>
        <td><?php echo (int) $h['id']; ?></td>
        <td><?php echo htmlspecialchars((string) $h['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $h['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $h['time_value'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($h['technician_name'] ?? 'Not yet assigned'), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>

</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
