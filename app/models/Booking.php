<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Technician.php';

class Booking
{
    private $conn;

    public $id;
    public $user_id;
    public $technician_id;
    public $service_id;
    public $booking_date;
    public $time_slot_id;
    public $status;
    public $cancellation_reason;
    public $rescheduled_from_booking_id;
    public $created_by;
    public $updated_by;
    public $updated_at;
    public $created_at;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @param string $booking_date Y-m-d
     * @return string|false
     */
    public function createBooking(
        $user_id,
        $service_id,
        $booking_date,
        $time_slot_id,
        $technician_id = null,
        $status = 'Unassigned',
        $cancellation_reason = null,
        $rescheduled_from_booking_id = null,
        $created_by = null,
        $updated_by = null
    ) {
        $sql = 'INSERT INTO bookings (
                    user_id, technician_id, service_id, booking_date, time_slot_id, status,
                    cancellation_reason, rescheduled_from_booking_id, created_by, updated_by
                ) VALUES (
                    :user_id, :technician_id, :service_id, :booking_date, :time_slot_id, :status,
                    :cancellation_reason, :rescheduled_from_booking_id, :created_by, :updated_by
                )';

        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
        $stmt->bindParam(':booking_date', $booking_date, PDO::PARAM_STR);
        $stmt->bindParam(':time_slot_id', $time_slot_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        if ($technician_id === null) {
            $stmt->bindValue(':technician_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        }

        if ($cancellation_reason === null) {
            $stmt->bindValue(':cancellation_reason', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':cancellation_reason', $cancellation_reason, PDO::PARAM_STR);
        }

        if ($rescheduled_from_booking_id === null) {
            $stmt->bindValue(':rescheduled_from_booking_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':rescheduled_from_booking_id', $rescheduled_from_booking_id, PDO::PARAM_INT);
        }

        if ($created_by === null) {
            $stmt->bindValue(':created_by', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
        }

        if ($updated_by === null) {
            $stmt->bindValue(':updated_by', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * True if this user already has any booking on the given date and time slot.
     *
     * @param string $booking_date Y-m-d
     */
    public function userHasBookingForSlot($user_id, $booking_date, $time_slot_id)
    {
        $sql = 'SELECT COUNT(*) FROM bookings
                WHERE user_id = :user_id
                  AND booking_date = :booking_date
                  AND time_slot_id = :time_slot_id
                  AND status NOT IN (\'Cancelled\', \'No-Show\')';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':booking_date', $booking_date, PDO::PARAM_STR);
        $stmt->bindParam(':time_slot_id', $time_slot_id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function getBookingsByUser($user_id)
    {
        $sql = 'SELECT b.id, b.user_id, b.technician_id, b.service_id, b.booking_date, b.time_slot_id,
                       b.status, b.cancellation_reason, b.rescheduled_from_booking_id,
                       b.created_by, b.updated_by, b.updated_at, b.created_at,
                       s.service_name, ts.time_value
                FROM bookings b
                INNER JOIN services s ON b.service_id = s.id
                INNER JOIN time_slots ts ON b.time_slot_id = ts.id
                WHERE b.user_id = :user_id
                ORDER BY b.booking_date DESC, b.time_slot_id DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getAllBookings()
    {
        $sql = 'SELECT b.id, b.user_id, b.technician_id, b.service_id, b.booking_date, b.time_slot_id,
                       b.status, b.cancellation_reason, b.rescheduled_from_booking_id,
                       b.created_by, b.updated_by, b.updated_at, b.created_at,
                       cu.full_name AS customer_name,
                       s.service_name,
                       ts.time_value,
                       tu.full_name AS technician_name
                FROM bookings b
                INNER JOIN users cu ON b.user_id = cu.id
                INNER JOIN services s ON b.service_id = s.id
                INNER JOIN time_slots ts ON b.time_slot_id = ts.id
                LEFT JOIN technicians t ON b.technician_id = t.id
                LEFT JOIN users tu ON t.user_id = tu.id
                ORDER BY b.booking_date DESC, b.time_slot_id DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $booking_id
     * @return array|false
     */
    public function getBookingById($booking_id)
    {
        $booking_id = (int) $booking_id;
        if ($booking_id < 1) {
            return false;
        }

        $sql = 'SELECT id, user_id, technician_id, service_id, booking_date, time_slot_id,
                       status, cancellation_reason, rescheduled_from_booking_id,
                       created_by, updated_by, updated_at, created_at
                FROM bookings WHERE id = :id LIMIT 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: false;
    }

    /**
     * @param int $booking_id
     * @param string $status
     * @return bool
     */
    public function updateBookingStatus($booking_id, $status)
    {
        $sql = 'UPDATE bookings SET status = :status WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @param int $booking_id
     * @param string $newStatus
     * @param int $updated_by
     * @return bool
     */
    public function updateBookingStatusWithAudit($booking_id, $newStatus, $updated_by)
    {
        $booking_id = (int) $booking_id;
        $updated_by = (int) $updated_by;

        $sql = 'UPDATE bookings SET status = :status, updated_at = CURRENT_TIMESTAMP, updated_by = :updated_by
                WHERE id = :id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
        $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);

        try {
            if (!$stmt->execute()) {
                return false;
            }

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cancel an open booking (Unassigned, Assigned, or Ongoing).
     *
     * @param int $booking_id
     * @param string $reason
     * @param int $updated_by
     * @return bool
     */
    public function cancelBooking($booking_id, $reason, $updated_by)
    {
        $booking_id = (int) $booking_id;
        $updated_by = (int) $updated_by;

        $booking = $this->getBookingById($booking_id);
        if (!$booking) {
            return false;
        }

        $st = (string) $booking['status'];
        if (!in_array($st, ['Unassigned', 'Assigned', 'Ongoing'], true)) {
            return false;
        }

        $status = 'Cancelled';
        $sql = 'UPDATE bookings SET status = :status, cancellation_reason = :reason,
                updated_at = CURRENT_TIMESTAMP, updated_by = :updated_by
                WHERE id = :id AND status IN (\'Unassigned\', \'Assigned\', \'Ongoing\')';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);

        try {
            if (!$stmt->execute()) {
                return false;
            }

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * @param int $booking_id
     * @param int $technician_id
     * @param string $status
     * @param int|null $updated_by
     * @return bool
     */
    public function assignTechnician($booking_id, $technician_id, $status = 'Assigned', $updated_by = null)
    {
        $sql = 'UPDATE bookings SET technician_id = :technician_id, status = :status,
                updated_at = CURRENT_TIMESTAMP, updated_by = :updated_by
                WHERE id = :id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);

        if ($updated_by === null) {
            $stmt->bindValue(':updated_by', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':updated_by', $updated_by, PDO::PARAM_INT);
        }

        try {
            if (!$stmt->execute()) {
                return false;
            }

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Assign the lowest-workload skilled technician with no time conflict; otherwise leave Unassigned.
     *
     * @param int $booking_id
     * @return bool True if a technician was assigned
     */
    /**
     * @param int|null $updated_by_user_id Session user performing implicit assignment (e.g. customer submit)
     * @return bool True if a technician was assigned
     */
    public function autoAssignTechnician($booking_id, $updated_by_user_id = null)
    {
        $booking_id = (int) $booking_id;

        $this->conn->beginTransaction();

        try {
            $booking = $this->findBookingForAssignment($booking_id);

            if (!$booking) {
                $this->conn->rollBack();
                return false;
            }

            $terminal = ['Cancelled', 'Completed', 'No-Show'];
            if (in_array((string) $booking['status'], $terminal, true)) {
                $this->conn->commit();
                return false;
            }

            if ($booking['technician_id'] !== null && $booking['technician_id'] !== '') {
                $this->conn->commit();
                return true;
            }

            $service_id = (int) $booking['service_id'];
            $booking_date = (string) $booking['booking_date'];
            $time_slot_id = (int) $booking['time_slot_id'];

            $technicianModel = new Technician();
            $skilled = $technicianModel->getTechniciansByService($service_id);

            $ranked = [];

            foreach ($skilled as $row) {
                $tid = (int) $row['id'];

                if ($this->technicianHasSlotConflict($tid, $booking_date, $time_slot_id, $booking_id)) {
                    continue;
                }

                $ranked[] = [
                    'id' => $tid,
                    'load' => $this->countTechnicianWorkload($tid),
                ];
            }

            usort($ranked, function ($a, $b) {
                if ($a['load'] === $b['load']) {
                    return $a['id'] - $b['id'];
                }

                return $a['load'] - $b['load'];
            });

            foreach ($ranked as $item) {
                if ($this->assignTechnician($booking_id, $item['id'], 'Assigned', $updated_by_user_id)) {
                    $this->conn->commit();
                    return true;
                }
            }

            $this->conn->commit();
            return false;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            error_log('Booking::autoAssignTechnician: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int $booking_id
     * @return array|false
     */
    private function findBookingForAssignment($booking_id)
    {
        $sql = 'SELECT id, technician_id, service_id, booking_date, time_slot_id, status
                FROM bookings WHERE id = :id LIMIT 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: false;
    }

    /**
     * Bookings in Assigned or Ongoing for this technician.
     *
     * @param int $technician_id
     */
    private function countTechnicianWorkload($technician_id)
    {
        $sql = 'SELECT COUNT(*) FROM bookings
                WHERE technician_id = :technician_id
                  AND status IN (\'Assigned\', \'Ongoing\')';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Another booking already occupies this technician at date + slot (excluding current booking).
     *
     * @param int $exclude_booking_id
     */
    private function technicianHasSlotConflict($technician_id, $booking_date, $time_slot_id, $exclude_booking_id)
    {
        $sql = 'SELECT COUNT(*) FROM bookings
                WHERE technician_id = :technician_id
                  AND booking_date = :booking_date
                  AND time_slot_id = :time_slot_id
                  AND id <> :exclude_id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        $stmt->bindParam(':booking_date', $booking_date, PDO::PARAM_STR);
        $stmt->bindParam(':time_slot_id', $time_slot_id, PDO::PARAM_INT);
        $stmt->bindParam(':exclude_id', $exclude_booking_id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Bookings that block technician deactivation (operations still in flight).
     *
     * @param int $technician_id technicians.id
     * @return int
     */
    public function countTechnicianBusyBookings($technician_id)
    {
        $technician_id = (int) $technician_id;
        $sql = 'SELECT COUNT(*) FROM bookings
                WHERE technician_id = :technician_id
                  AND status IN (\'Assigned\', \'Ongoing\')';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':technician_id', $technician_id, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * True if the technician already has another booking for this date and slot.
     *
     * @param int $exclude_booking_id Usually the booking being assigned (ignored in the check)
     */
    public function isTechnicianSlotTaken($technician_id, $booking_date, $time_slot_id, $exclude_booking_id)
    {
        return $this->technicianHasSlotConflict(
            (int) $technician_id,
            (string) $booking_date,
            (int) $time_slot_id,
            (int) $exclude_booking_id
        );
    }
}
