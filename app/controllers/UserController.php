<?php

/**
 * Admin — Manage Users (HTTP POST handlers only).
 *
 * Why POST-only GET redirect: bookmarks and crawler GETs must not mutate data.
 *
 * Planned POST dispatch field: `jel_action` (distinct name avoids collisions).
 * Dispatched POST `jel_action` values:
 *   • create_user       — Disabled for this workflow (kept for compatibility)
 *   • create_technician — User + technician + technician_skills in one TX (Step 4)
 *   • update_user       — Edit profile + optional password; technician role locked; lone Active Admin guarded
 *   • set_status       — Soft delete (Inactive) / restore (Active); self + lone-admin + technician busy guards
 *
 * CSRF: Other controllers here skip tokens too; parity for class projects.
 * Production: issue a random token in session per form POST and verify it here.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Technician.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Booking.php';

/** Seeded roles: 3 = Technician (see database/jel_ims.sql). */
function jel_ims_uc_technician_role_id(): int
{
    return 3;
}

/** Seeded roles: 1 = Admin. */
function jel_ims_uc_admin_role_id(): int
{
    return 1;
}

/**
 * Abort unless an Admin or Staff session is active.
 */
function jel_ims_uc_require_authorized_session(): void
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
        header('Location: ../views/auth/login.php');
        exit;
    }

    $roleId = (int) $_SESSION['role_id'];
    if ($roleId !== 1 && $roleId !== 2) {
        header('Location: ../views/auth/login.php?error=forbidden');
        exit;
    }
}

/**
 * Canonical URL for redirects back to the Manage Users page.
 *
 * Keeps navigation consistent if routes change later (single literal).
 *
 * @param array<string, scalar|null> $query Key → value query pairs (omit for none)
 */
function jel_ims_uc_manage_users_url(array $query = []): string
{
    $base = ((int) ($_SESSION['role_id'] ?? 0) === 2)
        ? '../views/staff/manage_technicians.php'
        : '../views/admin/manage_users.php';

    if ($query === []) {
        return $base;
    }

    return $base . '?' . http_build_query($query);
}

function jel_ims_uc_is_admin(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === 1;
}

function jel_ims_uc_is_staff(): bool
{
    return (int) ($_SESSION['role_id'] ?? 0) === 2;
}

/**
 * Step 3: create Admin or Staff (role_id 1 or 2). Status always Active.
 * Technician uses a separate transactional action in Step 4.
 */
function jel_ims_uc_handle_create_admin_staff_user(User $userModel): void
{
    if (!jel_ims_uc_is_admin()) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'forbidden']));
        exit;
    }

    header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_disabled']));
    exit;

    $full_name = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
    $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

    $allowedRoles = [1 => true, 2 => true];

    if ($full_name === '' || $email === '' || $password === '' || $confirm === '') {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_missing']));
        exit;
    }

    if ($password !== $confirm) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_password_match']));
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_password_short']));
        exit;
    }

    if (strlen($full_name) > 100 || strlen($email) > 100) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_field_length']));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_email']));
        exit;
    }

    if (!isset($allowedRoles[$role_id])) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_role']));
        exit;
    }

    if ($userModel->getUserByEmail($email) !== false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_duplicate']));
        exit;
    }

    $status = 'Active';

    try {
        $newId = $userModel->createUser($full_name, $email, $password, $role_id, $status);
    } catch (\PDOException $e) {
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        if ($code === 1062) {
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_duplicate']));
            exit;
        }
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_server']));
        exit;
    }

    if ($newId === false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'create_save']));
        exit;
    }

    header('Location: ' . jel_ims_uc_manage_users_url(['created' => 1]));
    exit;
}

/**
 * Step 4: active Technician account + technicians row + ≥1 technician_skills in one PDO transaction.
 */
function jel_ims_uc_handle_create_technician_user(User $userModel, Technician $technicianModel, Service $serviceModel): void
{
    if (!jel_ims_uc_is_staff()) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'forbidden']));
        exit;
    }

    $full_name = isset($_POST['tech_full_name']) ? trim((string) $_POST['tech_full_name']) : '';
    $email = isset($_POST['tech_email']) ? trim((string) $_POST['tech_email']) : '';
    $password = isset($_POST['tech_password']) ? (string) $_POST['tech_password'] : '';
    $confirm = isset($_POST['tech_password_confirm']) ? (string) $_POST['tech_password_confirm'] : '';
    $rawSkillIds = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];

    if (!is_array($rawSkillIds)) {
        $rawSkillIds = [];
    }

    $validServiceIds = [];
    foreach ($serviceModel->getAllServices() as $row) {
        if (isset($row['id'])) {
            $validServiceIds[(int) $row['id']] = true;
        }
    }

    $chosenIds = [];
    foreach ($rawSkillIds as $rid) {
        $sid = (int) $rid;
        if ($sid > 0 && isset($validServiceIds[$sid])) {
            $chosenIds[$sid] = true;
        }
    }

    $serviceIdsDedup = array_keys($chosenIds);

    if ($full_name === '' || $email === '' || $password === '' || $confirm === '') {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_missing']));
        exit;
    }

    if ($password !== $confirm) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_password_match']));
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_password_short']));
        exit;
    }

    if (strlen($full_name) > 100 || strlen($email) > 100) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_field_length']));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_email']));
        exit;
    }

    if (count($serviceIdsDedup) < 1) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_no_skill']));
        exit;
    }

    if ($userModel->getUserByEmail($email) !== false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_duplicate']));
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $roleTech = jel_ims_uc_technician_role_id();
    $status = 'Active';
    $availability = 'Available';

    $pdo->beginTransaction();

    try {
        $userInsertIdStr = $userModel->createUserOnConnection($pdo, $full_name, $email, $password, $roleTech, $status);
        if ($userInsertIdStr === false || $userInsertIdStr === '') {
            $pdo->rollBack();
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_save']));
            exit;
        }

        $userIdInt = (int) $userInsertIdStr;

        $techInsertIdStr = $technicianModel->createTechnicianOnConnection($pdo, $userIdInt, $availability);
        if ($techInsertIdStr === false || $techInsertIdStr === '') {
            $pdo->rollBack();
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_save']));
            exit;
        }

        $techIdInt = (int) $techInsertIdStr;

        if (!$technicianModel->insertTechnicianSkillsOnConnection($pdo, $techIdInt, $serviceIdsDedup)) {
            $pdo->rollBack();
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_save']));
            exit;
        }

        $pdo->commit();

        header('Location: ' . jel_ims_uc_manage_users_url(['created_technician' => 1]));
        exit;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mysql = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        if ($mysql === 1062) {
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_duplicate']));
            exit;
        }

        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'tech_server']));
        exit;
    }
}

/**
 * Step 5: update name/email/role (optional password reset). Keeps DB `status`; never trust POST for status here.
 */
function jel_ims_uc_handle_update_user(User $userModel, Technician $technicianModel): void
{
    $user_id = isset($_POST['edit_user_id']) ? (int) $_POST['edit_user_id'] : 0;
    $full_name = isset($_POST['edit_full_name']) ? trim((string) $_POST['edit_full_name']) : '';
    $email = isset($_POST['edit_email']) ? trim((string) $_POST['edit_email']) : '';
    $posted_role_id = isset($_POST['edit_role_id']) ? (int) $_POST['edit_role_id'] : 0;
    $password = isset($_POST['edit_password']) ? (string) $_POST['edit_password'] : '';
    $password_confirm = isset($_POST['edit_password_confirm']) ? (string) $_POST['edit_password_confirm'] : '';

    $allowedRoles = [1 => true, 2 => true, 3 => true, 4 => true];

    if ($user_id < 1) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'edit_missing_user']));
        exit;
    }

    $existing = $userModel->getUserForAdminEdit($user_id);
    if ($existing === false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'edit_unknown']));
        exit;
    }

    $retry = ['edit' => $user_id];
    $actorIsStaff = jel_ims_uc_is_staff();

    $statusKeep = isset($existing['status']) ? (string) $existing['status'] : 'Active';

    if ($full_name === '' || $email === '') {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_missing'])));
        exit;
    }

    if (strlen($full_name) > 100 || strlen($email) > 100) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_field_length'])));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_email'])));
        exit;
    }

    $technicianTech = jel_ims_uc_technician_role_id();

    $has_technician_row = $technicianModel->getTechnicianByUserId($user_id) !== false;
    if ($actorIsStaff && !$has_technician_row) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'staff_only_technician'])));
        exit;
    }

    $role_effective = $posted_role_id;
    if ($has_technician_row) {
        $role_effective = $technicianTech;
        if ($posted_role_id !== $technicianTech) {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_technician_role'])));
            exit;
        }
    } elseif (!isset($allowedRoles[$posted_role_id])) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_role'])));
        exit;
    }

    $current_role = (int) ($existing['role_id'] ?? 0);
    $adminRole = jel_ims_uc_admin_role_id();
    $is_active = strcasecmp($statusKeep, 'Active') === 0;

    if (!$actorIsStaff && $current_role === $adminRole && $is_active && $role_effective !== $adminRole) {
        $activeAdminCount = $userModel->countActiveUsersWithRole($adminRole, 'Active');
        if ($activeAdminCount <= 1) {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_last_admin_role'])));
            exit;
        }
    }

    if ($password !== '' || $password_confirm !== '') {
        if ($password === '' || $password_confirm === '') {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_password_partial'])));
            exit;
        }
        if ($password !== $password_confirm) {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_password_match'])));
            exit;
        }
        if (strlen($password) < 8) {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_password_short'])));
            exit;
        }
    }

    $plain_pw = ($password !== '' ? $password : null);

    if ($userModel->existsOtherUserWithEmail($email, $user_id)) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_duplicate'])));
        exit;
    }

    try {
        $ok = $userModel->updateUser($user_id, $full_name, $email, $role_effective, $statusKeep, $plain_pw);
    } catch (\PDOException $e) {
        $mysql = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        if ($mysql === 1062) {
            header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_duplicate'])));
            exit;
        }
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_server'])));
        exit;
    }

    if (!$ok) {
        header('Location: ' . jel_ims_uc_manage_users_url(array_merge($retry, ['error' => 'edit_save'])));
        exit;
    }

    header('Location: ' . jel_ims_uc_manage_users_url(['updated' => 1, 'edit' => $user_id]));
    exit;
}

/**
 * Step 6: Soft delete or restore (`users.status` only — exact casing Active | Inactive).
 */
function jel_ims_uc_handle_set_user_status(User $userModel, Technician $technicianModel, Booking $bookingModel): void
{
    $target_id = isset($_POST['status_user_id']) ? (int) $_POST['status_user_id'] : 0;

    $new_status = isset($_POST['new_status']) ? trim((string) $_POST['new_status']) : '';

    if ($target_id < 1) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_unknown']));
        exit;
    }

    if ($new_status !== 'Active' && $new_status !== 'Inactive') {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_invalid']));
        exit;
    }

    $existing = $userModel->getUserForAdminEdit($target_id);

    if ($existing === false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_unknown']));
        exit;
    }

    $current_status = isset($existing['status']) ? (string) $existing['status'] : '';
    $actorIsStaff = jel_ims_uc_is_staff();
    $techRowForStatus = $technicianModel->getTechnicianByUserId($target_id);

    if ($actorIsStaff && $techRowForStatus === false) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'staff_only_technician']));
        exit;
    }

    if ($current_status !== 'Active' && $current_status !== 'Inactive') {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_unsupported']));
        exit;
    }

    if ($current_status === $new_status) {
        header('Location: ' . jel_ims_uc_manage_users_url(['status_updated' => 1]));
        exit;
    }

    if ($new_status === 'Inactive') {
        if ($target_id === (int) $_SESSION['user_id']) {
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_self']));
            exit;
        }

        $adminRole = jel_ims_uc_admin_role_id();

        $is_active_now = ($current_status === 'Active');

        $is_active_admin = ((int) ($existing['role_id'] ?? 0) === $adminRole && $is_active_now);

        if (!$actorIsStaff && $is_active_admin && $userModel->countActiveUsersWithRole($adminRole, 'Active') <= 1) {
            header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_last_admin']));
            exit;
        }

        $techRow = $techRowForStatus;
        if ($techRow !== false) {
            $technician_id = (int) ($techRow['id'] ?? 0);
            if ($technician_id > 0 && $bookingModel->countTechnicianBusyBookings($technician_id) > 0) {
                header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_technician_busy']));
                exit;
            }
        }
    }

    try {
        $ok = $userModel->setUserStatus($target_id, $new_status);
    } catch (\PDOException $e) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_server']));
        exit;
    }

    if (!$ok) {
        header('Location: ' . jel_ims_uc_manage_users_url(['error' => 'status_save']));
        exit;
    }

    header('Location: ' . jel_ims_uc_manage_users_url(['status_updated' => 1]));
    exit;
}

jel_ims_uc_require_authorized_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . jel_ims_uc_manage_users_url());
    exit;
}

// Future steps populate this from hidden fields once forms exist.
$action = isset($_POST['jel_action']) ? trim((string) $_POST['jel_action']) : '';

$userModel = new User();
$technicianModel = new Technician();
$serviceModel = new Service();
$bookingModel = new Booking();

switch ($action) {
    case 'create_user':
        jel_ims_uc_handle_create_admin_staff_user($userModel); // exits

    case 'create_technician':
        jel_ims_uc_handle_create_technician_user($userModel, $technicianModel, $serviceModel); // exits

    case 'update_user':
        jel_ims_uc_handle_update_user($userModel, $technicianModel); // exits

    case 'set_status':
        jel_ims_uc_handle_set_user_status($userModel, $technicianModel, $bookingModel); // exits

    default:
        header('Location: ' . jel_ims_uc_manage_users_url([
            'error' => ($action === '' ? 'missing_action' : 'invalid_action'),
        ]));
        exit;
}
