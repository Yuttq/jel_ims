<?php

session_start();

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Role.php';

$userModel = new User();
$customerModel = new Customer();
$roleModel = new Role();

/**
 * Resolve Customer role id from seeded roles table (name = Customer).
 *
 * @return int|null
 */
function jel_ims_customer_role_id(Role $roleModel)
{
    foreach ($roleModel->getAllRoles() as $row) {
        if (isset($row['role_name']) && $row['role_name'] === 'Customer') {
            return (int) $row['id'];
        }
    }

    return null;
}

/**
 * @param array $user Row from users table
 */
function jel_ims_redirect_for_role(array $user): void
{
    $roleId = (int) $user['role_id'];

    switch ($roleId) {
        case 1:
            header('Location: ../views/admin/dashboard.php');
            exit;
        case 2:
            header('Location: ../views/staff/dashboard.php');
            exit;
        case 3:
            header('Location: ../views/technician/dashboard.php');
            exit;
        case 4:
            header('Location: ../views/customer/dashboard.php');
            exit;
        default:
            header('Location: ../views/auth/login.php?error=invalid_role');
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/auth/login.php');
    exit;
}

// ----- Login -----
if (isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if ($email === '' || $password === '') {
        header('Location: ../views/auth/login.php?error=missing');
        exit;
    }

    $user = $userModel->getUserByEmail($email);

    if (!$user) {
        header('Location: ../views/auth/login.php?error=auth');
        exit;
    }

    if (isset($user['status']) && strcasecmp((string) $user['status'], 'Active') !== 0) {
        header('Location: ../views/auth/login.php?error=inactive');
        exit;
    }

    $stored = (string) $user['password'];
    $passwordOk = password_verify($password, $stored);

    // One-time upgrade: seed SQL may store plain text until re-imported with bcrypt.
    if (!$passwordOk && strncmp($stored, '$2y$', 4) !== 0 && strncmp($stored, '$2a$', 4) !== 0 && hash_equals($stored, $password)) {
        $userModel->updateUser(
            (int) $user['id'],
            (string) $user['full_name'],
            (string) $user['email'],
            (int) $user['role_id'],
            (string) $user['status'],
            $password
        );
        $passwordOk = true;
    }

    if (!$passwordOk) {
        header('Location: ../views/auth/login.php?error=auth');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role_id'] = (int) $user['role_id'];

    jel_ims_redirect_for_role($user);
}

// ----- Register -----
if (isset($_POST['register'])) {
    $full_name = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $contact_number = isset($_POST['contact_number']) ? trim((string) $_POST['contact_number']) : '';
    $address = isset($_POST['address']) ? trim((string) $_POST['address']) : '';

    if ($full_name === '' || $email === '' || $password === '') {
        header('Location: ../views/auth/register.php?error=missing');
        exit;
    }

    $customerRoleId = jel_ims_customer_role_id($roleModel);
    if ($customerRoleId === null) {
        header('Location: ../views/auth/register.php?error=config');
        exit;
    }

    try {
        $newUserId = $userModel->createUser($full_name, $email, $password, $customerRoleId, 'Active');
    } catch (PDOException $e) {
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        if ($code === 1062) {
            header('Location: ../views/auth/register.php?error=duplicate');
            exit;
        }
        header('Location: ../views/auth/register.php?error=server');
        exit;
    }

    if ($newUserId === false) {
        header('Location: ../views/auth/register.php?error=duplicate');
        exit;
    }

    $customerRowId = $customerModel->createCustomer((int) $newUserId, $contact_number, $address);
    if ($customerRowId === false) {
        $userModel->deleteUser((int) $newUserId);
        header('Location: ../views/auth/register.php?error=customer');
        exit;
    }

    header('Location: ../views/auth/login.php?registered=1');
    exit;
}

header('Location: ../views/auth/login.php');
exit;
