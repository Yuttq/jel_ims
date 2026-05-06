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
$trow = $stmt->fetch(PDO::FETCH_ASSOC);
$technicianId = $trow ? (int) $trow['id'] : 0;

$bookRows = [];

if ($technicianId > 0) {
    $sql = 'SELECT b.id, b.booking_date, b.status, s.service_name, ts.time_value
            FROM bookings b
            INNER JOIN services s ON b.service_id = s.id
            INNER JOIN time_slots ts ON b.time_slot_id = ts.id
            WHERE b.technician_id = :tid AND b.status IN (\'Assigned\', \'Ongoing\')
            ORDER BY b.booking_date ASC, b.time_slot_id ASC';
    $q = $pdo->prepare($sql);
    $q->bindParam(':tid', $technicianId, PDO::PARAM_INT);
    $q->execute();
    $bookRows = $q->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Update Status — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Update Status</h1>

<?php if (!empty($_GET['updated'])): ?>
<p>Booking status updated.</p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    $map = [
        'invalid' => 'Request was invalid.',
        'forbidden' => 'You cannot update this booking.',
        'transition' => 'That status change is not allowed from the current state.',
        'save' => 'Update could not be saved.',
    ];
    $msg = isset($map[$code]) ? $map[$code] : 'Something went wrong.';
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($technicianId === 0): ?>
<p>No technician profile is linked to this account.</p>
<?php elseif (count($bookRows) === 0): ?>
<p>No assigned or ongoing bookings.</p>
<?php else: ?>
<table>
    <tr>
        <th>ID</th>
        <th>Service</th>
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
        <th>Update</th>
    </tr>
    <?php foreach ($bookRows as $br): ?>
    <tr>
        <td><?php echo (int) $br['id']; ?></td>
        <td><?php echo htmlspecialchars((string) $br['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $br['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $br['time_value'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $br['status'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/TechnicianController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $br['id']; ?>">
                <label for="st_<?php echo (int) $br['id']; ?>">New status</label><br>
                <select id="st_<?php echo (int) $br['id']; ?>" name="status" required>
                    <option value="">Select</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Completed">Completed</option>
                    <option value="No-Show">No-Show</option>
                </select><br>
                <button type="submit" name="update_booking_status" value="1">Save</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
