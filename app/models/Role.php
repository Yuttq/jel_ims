<?php

require_once __DIR__ . '/../config/Database.php';

class Role
{
    private $conn;

    public $id;
    public $role_name;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @return array
     */
    public function getAllRoles()
    {
        $sql = 'SELECT id, role_name FROM roles ORDER BY id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
