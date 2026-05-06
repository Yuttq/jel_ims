<?php

require_once __DIR__ . '/../config/Database.php';

class Technician
{
    private $conn;

    public $id;
    public $user_id;
    public $availability_status;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @return string|false
     */
    public function createTechnician($user_id, $availability_status = 'Available')
    {
        $sql = 'INSERT INTO technicians (user_id, availability_status)
                VALUES (:user_id, :availability_status)';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':availability_status', $availability_status, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function createTechnicianOnConnection(\PDO $pdo, $user_id, $availability_status = 'Available')
    {
        $user_id = (int) $user_id;
        $sql = 'INSERT INTO technicians (user_id, availability_status)
                VALUES (:user_id, :availability_status)';
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':availability_status', $availability_status, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }

        return false;
    }

    /**
     * @param array<int, int> $service_ids Deduped list of existing service IDs
     * @return bool True if every INSERT succeeded (non-empty `$service_ids`)
     */
    public function insertTechnicianSkillsOnConnection(\PDO $pdo, $technician_id, array $service_ids)
    {
        $technician_id = (int) $technician_id;
        $sql = 'INSERT INTO technician_skills (technician_id, service_id)
                VALUES (:technician_id, :service_id)';
        $stmt = $pdo->prepare($sql);

        foreach ($service_ids as $service_id) {
            $sid = (int) $service_id;
            $stmt->bindValue(':technician_id', $technician_id, PDO::PARAM_INT);
            $stmt->bindValue(':service_id', $sid, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $user_id users.id
     * @return array<string, mixed>|false
     */
    public function getTechnicianByUserId($user_id)
    {
        $user_id = (int) $user_id;
        $sql = 'SELECT id, user_id, availability_status FROM technicians WHERE user_id = :user_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * Technicians with linked user rows (for displaying names).
     *
     * @return array
     */
    public function getAllTechnicians()
    {
        $sql = 'SELECT t.id, t.user_id, t.availability_status,
                       u.full_name, u.email, u.status AS user_status
                FROM technicians t
                INNER JOIN users u ON t.user_id = u.id
                ORDER BY t.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getAvailableTechnicians()
    {
        $status = 'Available';
        $sql = 'SELECT t.id, t.user_id, t.availability_status,
                       u.full_name, u.email, u.status AS user_status
                FROM technicians t
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.availability_status = :availability_status
                ORDER BY t.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':availability_status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Technicians who have a technician_skills row for the given service.
     *
     * @param int $service_id
     * @return array
     */
    public function getTechniciansByService($service_id)
    {
        $sql = 'SELECT t.id, t.user_id, t.availability_status,
                       u.full_name, u.email, u.status AS user_status
                FROM technicians t
                INNER JOIN technician_skills ts ON ts.technician_id = t.id AND ts.service_id = :service_id
                INNER JOIN users u ON t.user_id = u.id
                ORDER BY t.id ASC';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
