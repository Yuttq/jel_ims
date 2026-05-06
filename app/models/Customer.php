<?php

require_once __DIR__ . '/../config/Database.php';

class Customer
{
    private $conn;

    public $id;
    public $user_id;
    public $contact_number;
    public $address;
    public $no_show_count;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @return string|false
     */
    public function createCustomer($user_id, $contact_number, $address)
    {
        $sql = 'INSERT INTO customers (user_id, contact_number, address)
                VALUES (:user_id, :contact_number, :address)';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * @param int $user_id
     * @return array|false
     */
    public function getCustomerByUserId($user_id)
    {
        $sql = 'SELECT id, user_id, contact_number, address, no_show_count
                FROM customers WHERE user_id = :user_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * @param int $id Customer primary key
     * @return bool
     */
    public function updateCustomer($id, $contact_number, $address, $no_show_count = null)
    {
        if ($no_show_count !== null) {
            $sql = 'UPDATE customers SET contact_number = :contact_number, address = :address,
                    no_show_count = :no_show_count WHERE id = :id';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
            $stmt->bindParam(':address', $address, PDO::PARAM_STR);
            $stmt->bindParam(':no_show_count', $no_show_count, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        }

        $sql = 'UPDATE customers SET contact_number = :contact_number, address = :address WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':contact_number', $contact_number, PDO::PARAM_STR);
        $stmt->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @param int $user_id customers.user_id
     * @return bool
     */
    public function incrementNoShowCountForUser($user_id)
    {
        $sql = 'UPDATE customers SET no_show_count = no_show_count + 1 WHERE user_id = :user_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
