<?php

class Database
{
    private $host = 'localhost';
    private $db_name = 'jel_ims';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    /**
     * @return PDO
     */
    public function getConnection()
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8';

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            error_log('JEL-IMS database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection could not be established.', 0, $e);
        }

        return $this->conn;
    }
}
