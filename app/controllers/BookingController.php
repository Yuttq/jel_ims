<?php

session_start();

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/../config/Database.php';

function jel_ims_booking_require_customer_session(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
        header('Location: ../views/auth/login.php?error=session');
        exit;
    }

    if ((int) $_SESSION['role_id'] !== 4) {
        header('Location: ../views/auth/login.php?error=forbidden');
        exit;
    }
}

function jel_ims_booking_require_authenticated(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
        header('Location: ../views/auth/login.php?error=session');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/customer/create_booking.php');
    exit;
}

$bookingModel = new Booking();

if (!empty($_POST['create_booking'])) {
    jel_ims_booking_require_customer_session();

    $service_id = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
    $time_slot_id = isset($_POST['time_slot_id']) ? (int) $_POST['time_slot_id'] : 0;
    $booking_date = isset($_POST['booking_date']) ? trim((string) $_POST['booking_date']) : '';

    if ($service_id < 1 || $time_slot_id < 1 || $booking_date === '') {
        header('Location: ../views/customer/create_booking.php?error=invalid');
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
        header('Location: ../views/customer/create_booking.php?error=invalid');
        exit;
    }

    if ($booking_date < date('Y-m-d')) {
        header('Location: ../views/customer/create_booking.php?error=past_date');
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];

    if ($bookingModel->userHasBookingForSlot($user_id, $booking_date, $time_slot_id)) {
        header('Location: ../views/customer/create_booking.php?error=duplicate');
        exit;
    }

    $created_by = $user_id;
    $newId = $bookingModel->createBooking(
        $user_id,
        $service_id,
        $booking_date,
        $time_slot_id,
        null,
        'Unassigned',
        null,
        null,
        $created_by,
        null
    );

    if ($newId === false) {
        header('Location: ../views/customer/create_booking.php?error=save');
        exit;
    }

    $bookingIdInt = (int) $newId;

    $notificationController = new NotificationController();
    $notificationController->createNotification(
        $user_id,
        $bookingIdInt,
        'Your service booking was received and is pending assignment.',
        'Booking Created'
    );

    $dbNotify = new Database();
    $pdoNotify = $dbNotify->getConnection();
    $stmtAdmins = $pdoNotify->prepare('SELECT id FROM users WHERE role_id = :rid');
    $adminRoleId = 1;
    $stmtAdmins->bindParam(':rid', $adminRoleId, PDO::PARAM_INT);
    $stmtAdmins->execute();

    while ($adminId = $stmtAdmins->fetchColumn()) {
        $notificationController->createNotification(
            (int) $adminId,
            $bookingIdInt,
            'A new customer booking has been submitted.',
            'Booking Created'
        );
    }

    $bookingModel->autoAssignTechnician($bookingIdInt, $user_id);

    $stmtAssigned = $pdoNotify->prepare(
        'SELECT t.user_id AS technician_user_id
         FROM bookings b
         INNER JOIN technicians t ON b.technician_id = t.id
         WHERE b.id = :bid AND b.technician_id IS NOT NULL'
    );
    $stmtAssigned->bindParam(':bid', $bookingIdInt, PDO::PARAM_INT);
    $stmtAssigned->execute();
    $assignedRow = $stmtAssigned->fetch(PDO::FETCH_ASSOC);

    if ($assignedRow && !empty($assignedRow['technician_user_id'])) {
        $technicianUserId = (int) $assignedRow['technician_user_id'];

        $notificationController->createNotification(
            $user_id,
            $bookingIdInt,
            'A technician has been assigned to your booking.',
            'Technician Assigned'
        );

        $notificationController->createNotification(
            $technicianUserId,
            $bookingIdInt,
            'You have been assigned a new booking.',
            'Technician Assigned'
        );
    }

    header('Location: ../views/customer/booking_history.php?created=1');
    exit;
}

if (!empty($_POST['cancel_booking'])) {
    jel_ims_booking_require_authenticated();

    $roleId = (int) $_SESSION['role_id'];
    $sessionUserId = (int) $_SESSION['user_id'];

    if ($roleId !== 1 && $roleId !== 4) {
        header('Location: ../views/auth/login.php?error=forbidden');
        exit;
    }

    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $reason = isset($_POST['cancellation_reason']) ? trim((string) $_POST['cancellation_reason']) : '';

    if ($booking_id < 1 || $reason === '') {
        $target = $roleId === 4
            ? '../views/customer/booking_history.php?error=cancel_invalid'
            : '../views/admin/assign_technicians.php?error=cancel_invalid';
        header('Location: ' . $target);
        exit;
    }

    $booking = $bookingModel->getBookingById($booking_id);

    if (!$booking) {
        $target = $roleId === 4
            ? '../views/customer/booking_history.php?error=cancel_invalid'
            : '../views/admin/assign_technicians.php?error=cancel_invalid';
        header('Location: ' . $target);
        exit;
    }

    if ($roleId === 4 && (int) $booking['user_id'] !== $sessionUserId) {
        header('Location: ../views/customer/booking_history.php?error=cancel_forbidden');
        exit;
    }

    if (!$bookingModel->cancelBooking($booking_id, $reason, $sessionUserId)) {
        $target = $roleId === 4
            ? '../views/customer/booking_history.php?error=cancel_failed'
            : '../views/admin/assign_technicians.php?error=cancel_failed';
        header('Location: ' . $target);
        exit;
    }

    $target = $roleId === 4
        ? '../views/customer/booking_history.php?cancelled=1'
        : '../views/admin/assign_technicians.php?cancelled=1';
    header('Location: ' . $target);
    exit;
}

header('Location: ../views/customer/create_booking.php');
exit;
