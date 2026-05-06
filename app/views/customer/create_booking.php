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

require_once __DIR__ . '/../../models/Service.php';
require_once __DIR__ . '/../../models/TimeSlot.php';

$serviceModel = new Service();
$timeSlotModel = new TimeSlot();
$services = $serviceModel->getAllServices();
$timeSlots = $timeSlotModel->getAllTimeSlots();

$pageTitle = 'Create Booking — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Create Booking</h1>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    if ($code === 'duplicate') {
        $msg = 'Booking already exists for this time slot.';
    } elseif ($code === 'past_date') {
        $msg = 'Booking date cannot be in the past.';
    } elseif ($code === 'invalid') {
        $msg = 'Please choose a service, date, and time slot.';
    } elseif ($code === 'save') {
        $msg = 'Booking could not be saved. Please try again.';
    } else {
        $msg = 'Request could not be completed.';
    }
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/BookingController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <div>
        <label for="service_id">Service Type</label><br>
        <select id="service_id" name="service_id" required>
            <option value="">Select a service</option>
            <?php foreach ($services as $s): ?>
            <option value="<?php echo (int) $s['id']; ?>">
                <?php echo htmlspecialchars($s['service_name'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="booking_date">Booking Date</label><br>
        <input type="date" id="booking_date" name="booking_date" required>
    </div>
    <div>
        <label for="time_slot_id">Time Slot</label><br>
        <select id="time_slot_id" name="time_slot_id" required>
            <option value="">Select a time</option>
            <?php foreach ($timeSlots as $t): ?>
            <option value="<?php echo (int) $t['id']; ?>">
                <?php echo htmlspecialchars($t['time_value'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <button type="submit" name="create_booking" value="1">Submit</button>
    </div>
</form>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
