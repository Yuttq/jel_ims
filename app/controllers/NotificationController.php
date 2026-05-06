<?php

require_once __DIR__ . '/../models/Notification.php';

class NotificationController
{
    /** @var Notification */
    private $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    /**
     * @param int $user_id
     * @param int|null $booking_id
     * @return string|false
     */
    public function createNotification($user_id, $booking_id, $message, $type)
    {
        return $this->notifications->createNotification($user_id, $booking_id, $message, $type);
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getUserNotifications($user_id)
    {
        return $this->notifications->getUserNotifications($user_id);
    }

    /**
     * @param int $notification_id
     * @param int $user_id
     * @return bool
     */
    public function markNotificationAsRead($notification_id, $user_id)
    {
        return $this->notifications->markAsRead($notification_id, $user_id);
    }
}

if (
    PHP_SAPI_NAME() !== 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && basename($_SERVER['SCRIPT_FILENAME']) === 'NotificationController.php'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['mark_notification_read'])
) {
    session_start();

    if (empty($_SESSION['user_id'])) {
        header('Location: ../views/auth/login.php');
        exit;
    }

    $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;

    if ($notificationId > 0) {
        $notificationController = new NotificationController();
        $notificationController->markNotificationAsRead($notificationId, (int) $_SESSION['user_id']);
    }

    header('Location: ../views/layouts/notifications.php');
    exit;
}
