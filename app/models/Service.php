<?php

require_once __DIR__ . '/../config/Database.php';

class Service
{
    private $conn;

    public $id;
    public $service_name;
    public $estimated_duration_minutes;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @return array
     */
    public function getAllServices()
    {
        $sql = 'SELECT id, service_name, estimated_duration_minutes
                FROM services ORDER BY id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
