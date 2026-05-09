<?php

session_start();

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../config/Database.php';

function jel_ims_technician_require_login(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
        header('Location: ../views/auth/login.php?error=session');
        exit;
    }
}

function jel_ims_technician_resolve_technician_id(int $userId): int
{
    $db = new Database();
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare('SELECT id FROM technicians WHERE user_id = :uid LIMIT 1');
    $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int) $row['id'] : 0;
}

function jel_ims_technician_status_transition_allowed(string $current, string $next): bool
{
    $map = [
        'Assigned' => ['Ongoing', 'Completed', 'No-Show'],
        'Ongoing' => ['Completed', 'No-Show'],
    ];

    return isset($map[$current]) && in_array($next, $map[$current], true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/technician/dashboard.php');
    exit;
}

jel_ims_technician_require_login();

$sessionUserId = (int) $_SESSION['user_id'];
$sessionRole = (int) $_SESSION['role_id'];

$bookingModel = new Booking();

if (!empty($_POST['assign_technician'])) {
    if ($sessionRole !== 2) {
        header('Location: ../views/auth/login.php?error=forbidden');
        exit;
    }

    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $technician_id = isset($_POST['technician_id']) ? (int) $_POST['technician_id'] : 0;

    if ($booking_id < 1 || $technician_id < 1) {
        header('Location: ../views/staff/assign_technicians.php?error=assign_invalid');
        exit;
    }

    $booking = $bookingModel->getBookingById($booking_id);

    if (!$booking) {
        header('Location: ../views/staff/assign_technicians.php?error=assign_invalid');
        exit;
    }

    if ((string) $booking['status'] !== 'Unassigned') {
        header('Location: ../views/staff/assign_technicians.php?error=assign_state');
        exit;
    }

    $existingTech = $booking['technician_id'];
    if ($existingTech !== null && $existingTech !== '' && (int) $existingTech > 0) {
        header('Location: ../views/staff/assign_technicians.php?error=assign_state');
        exit;
    }

    if ($bookingModel->isTechnicianSlotTaken(
        $technician_id,
        (string) $booking['booking_date'],
        (int) $booking['time_slot_id'],
        $booking_id
    )) {
        header('Location: ../views/staff/assign_technicians.php?error=technician_busy');
        exit;
    }

    if (!$bookingModel->assignTechnician($booking_id, $technician_id, 'Assigned', $sessionUserId)) {
        header('Location: ../views/staff/assign_technicians.php?error=assign_failed');
        exit;
    }

    header('Location: ../views/staff/assign_technicians.php?assigned=1');
    exit;
}

if (!empty($_POST['update_booking_status'])) {
    if ($sessionRole !== 1 && $sessionRole !== 3) {
        header('Location: ../views/auth/login.php?error=forbidden');
        exit;
    }

    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $newStatus = isset($_POST['status']) ? trim((string) $_POST['status']) : '';

    $allowed = ['No-Show', 'Ongoing', 'Completed'];

    if ($booking_id < 1 || !in_array($newStatus, $allowed, true)) {
        header('Location: ../views/technician/update_status.php?error=invalid');
        exit;
    }

    $booking = $bookingModel->getBookingById($booking_id);

    if (!$booking) {
        header('Location: ../views/technician/update_status.php?error=invalid');
        exit;
    }

    if ($sessionRole === 3) {
        $techId = jel_ims_technician_resolve_technician_id($sessionUserId);

        if ($techId < 1 || (int) $booking['technician_id'] !== $techId) {
            header('Location: ../views/technician/update_status.php?error=forbidden');
            exit;
        }
    }

    $current = (string) $booking['status'];

    if (strcasecmp($current, $newStatus) === 0) {
        header('Location: ../views/technician/update_status.php?error=transition');
        exit;
    }

    if (!jel_ims_technician_status_transition_allowed($current, $newStatus)) {
        header('Location: ../views/technician/update_status.php?error=transition');
        exit;
    }

    if (!$bookingModel->updateBookingStatusWithAudit($booking_id, $newStatus, $sessionUserId)) {
        header('Location: ../views/technician/update_status.php?error=save');
        exit;
    }

    if ($newStatus === 'No-Show' && strcasecmp($current, 'No-Show') !== 0) {
        $customerModel = new Customer();
        $customerModel->incrementNoShowCountForUser((int) $booking['user_id']);
    }

    header('Location: ../views/technician/update_status.php?updated=1');
    exit;
}

header('Location: ../views/technician/dashboard.php');
exit;
