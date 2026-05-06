<?php

require_once __DIR__ . '/../config/Database.php';

class Notification
{
    private $conn;

    public $id;
    public $user_id;
    public $booking_id;
    public $message;
    public $type;
    public $status;
    public $created_at;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @param int|null $booking_id
     * @return string|false
     */
    public function createNotification($user_id, $booking_id, $message, $type, $status = 'Unread')
    {
        $sql = 'INSERT INTO notifications (user_id, booking_id, message, type, status)
                VALUES (:user_id, :booking_id, :message, :type, :status)';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        if ($booking_id === null) {
            $stmt->bindValue(':booking_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getUserNotifications($user_id)
    {
        $sql = 'SELECT id, user_id, booking_id, message, type, status, created_at
                FROM notifications
                WHERE user_id = :user_id
                ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $notification_id
     * @param int $user_id Ensures notifications are scoped to the logged-in user
     * @param string $status Typically "Read"
     * @return bool
     */
    public function markAsRead($notification_id, $user_id, $status = 'Read')
    {
        $sql = 'UPDATE notifications SET status = :status
                WHERE id = :id AND user_id = :user_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
