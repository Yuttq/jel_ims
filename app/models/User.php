<?php

require_once __DIR__ . '/../config/Database.php';

class User
{
    private $conn;

    public $id;
    public $full_name;
    public $email;
    public $password;
    public $role_id;
    public $status;
    public $created_at;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * @param string $plainPassword Stored using password_hash()
     * @return string|false Last insert id as string, or false on failure
     */
    public function createUser($full_name, $email, $plainPassword, $role_id, $status = 'Active')
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql = 'INSERT INTO users (full_name, email, password, role_id, status)
                VALUES (:full_name, :email, :password, :role_id, :status)';
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Same as {@see createUser} but executes on `$pdo` so multiple writes share one transaction.
     *
     * @param string $plainPassword Stored using password_hash()
     * @return string|false Last insert id as string, or false on failure
     */
    public function createUserOnConnection(\PDO $pdo, $full_name, $email, $plainPassword, $role_id, $status = 'Active')
    {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql = 'INSERT INTO users (full_name, email, password, role_id, status)
                VALUES (:full_name, :email, :password, :role_id, :status)';
        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $pdo->lastInsertId();
        }

        return false;
    }

    /**
     * @param int $id
     * @return array|false
     */
    public function getUserById($id)
    {
        $sql = 'SELECT id, full_name, email, password, role_id, status, created_at
                FROM users WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * @param string $email
     * @return array|false
     */
    public function getUserByEmail($email)
    {
        $sql = 'SELECT id, full_name, email, password, role_id, status, created_at
                FROM users WHERE email = :email LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * @return array
     */
    public function getAllUsers()
    {
        $sql = 'SELECT id, full_name, email, password, role_id, status, created_at
                FROM users ORDER BY id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin user list: no password column (never expose hashes in UI).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUsersForAdminList()
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.role_id, u.status, u.created_at,
                       r.role_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                ORDER BY u.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin edit form: excludes password/hash from the SELECT.
     *
     * @return array<string, mixed>|false
     */
    public function getUserForAdminEdit($id)
    {
        $sql = 'SELECT u.id, u.full_name, u.email, u.role_id, u.status, u.created_at,
                       r.role_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * @param int $excludeUserId
     */
    public function existsOtherUserWithEmail($email, $excludeUserId): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email AND id <> :exclude_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':exclude_id', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $status Use exact DB value ('Active').
     */
    public function countActiveUsersWithRole($role_id, $status = 'Active'): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role_id = :role_id AND status = :status';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Soft delete / restore only. Values must be exactly Active or Inactive (case-sensitive).
     *
     * @param int $user_id
     * @param string $status 'Active'|'Inactive'
     * @return bool
     */
    public function setUserStatus($user_id, $status)
    {
        if ($status !== 'Active' && $status !== 'Inactive') {
            return false;
        }

        $sql = 'UPDATE users SET status = :status WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @param int $id
     * @param string|null $plainPassword If non-null and non-empty, password is re-hashed
     * @return bool
     */
    public function updateUser($id, $full_name, $email, $role_id, $status, $plainPassword = null)
    {
        if ($plainPassword !== null && $plainPassword !== '') {
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET full_name = :full_name, email = :email, role_id = :role_id,
                    status = :status, password = :password WHERE id = :id';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hash, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        }

        $sql = 'UPDATE users SET full_name = :full_name, email = :email, role_id = :role_id,
                status = :status WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteUser($id)
    {
        $sql = 'DELETE FROM users WHERE id = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
