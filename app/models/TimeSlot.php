<?php

require_once __DIR__ . '/../config/Database.php';

class TimeSlot
{
    private $conn;

    public $id;
    public $time_value;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @return array
     */
    public function getAllTimeSlots()
    {
        $sql = 'SELECT id, time_value FROM time_slots ORDER BY id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
