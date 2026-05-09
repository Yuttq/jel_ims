<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Manage Users — JEL-IMS';

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Service.php';
require_once __DIR__ . '/../../models/Technician.php';
require_once __DIR__ . '/../../models/Role.php';

$userModel = new User();
$serviceModel = new Service();
$technicianModelForEdit = new Technician();
$roleModel = new Role();
$users = $userModel->getUsersForAdminList();
$skillServices = $serviceModel->getAllServices();
$rolesList = $roleModel->getAllRoles();

$editLookupFailed = false;
$requestedEditId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

$editUserRecord = false;
$editHasTechnician = false;
if ($requestedEditId > 0) {
    $candidate = $userModel->getUserForAdminEdit($requestedEditId);
    if ($candidate === false) {
        $editLookupFailed = true;
    } else {
        $editUserRecord = $candidate;
        $editHasTechnician = $technicianModelForEdit->getTechnicianByUserId($requestedEditId) !== false;
    }
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Manage Users</h1>

<?php if (!empty($_GET['created'])): ?>
<p>User was created successfully.</p>
<?php endif; ?>

<?php if (!empty($_GET['created_technician'])): ?>
<p>Technician was created successfully (with linked skills).</p>
<?php endif; ?>

<?php if (!empty($_GET['updated'])): ?>
<p>User was updated successfully.</p>
<?php endif; ?>

<?php if (!empty($_GET['status_updated'])): ?>
<p>Account status was updated.</p>
<?php endif; ?>

<?php if ($editLookupFailed): ?>
<p>That user ID could not be loaded.</p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    $messages = [
        'invalid_action' => 'That request is not valid. Use the forms on this page.',
        'missing_action' => 'The request was incomplete.',
        'create_missing' => 'Full name, email, password, and password confirmation are required.',
        'create_password_match' => 'Password and confirmation must match.',
        'create_password_short' => 'Password must be at least 8 characters.',
        'create_field_length' => 'Name or email is too long.',
        'create_email' => 'Please enter a valid email address.',
        'create_role' => 'That role cannot be created with this form. Use Admin or Staff only here.',
        'create_duplicate' => 'An account with that email already exists.',
        'create_save' => 'The user could not be saved.',
        'create_server' => 'A database error occurred. Please try again.',
        'tech_missing' => 'Technician requires full name, email, password, and password confirmation.',
        'tech_password_match' => 'Password and confirmation must match.',
        'tech_password_short' => 'Password must be at least 8 characters.',
        'tech_field_length' => 'Name or email is too long.',
        'tech_email' => 'Please enter a valid email address.',
        'tech_no_skill' => 'Technicians must have at least one service skill selected.',
        'tech_duplicate' => 'An account with that email already exists.',
        'tech_save' => 'The technician profile could not be saved. No changes were applied.',
        'tech_server' => 'A database error occurred while creating the technician. Please try again.',
        'edit_missing_user' => 'No valid user id was supplied for editing.',
        'edit_unknown' => 'That user record does not exist.',
        'edit_missing' => 'Full name and email are required.',
        'edit_field_length' => 'Name or email is too long.',
        'edit_email' => 'Please enter a valid email address.',
        'edit_role' => 'That role selection is invalid.',
        'edit_technician_role' => 'Technicians linked to technician profiles cannot change role.',
        'edit_last_admin_role' => 'There must stay at least one Active Admin. Change blocked.',
        'edit_password_partial' => 'Leave both password fields blank, or fill both to set a new password.',
        'edit_password_match' => 'Password and confirmation must match.',
        'edit_password_short' => 'New password must be at least 8 characters.',
        'edit_duplicate' => 'Another account already uses that email.',
        'edit_save' => 'Changes could not be saved.',
        'edit_server' => 'A database error occurred while saving. Please try again.',
        'status_unknown' => 'That user record does not exist or is invalid.',
        'status_invalid' => 'Invalid status selection.',
        'status_unsupported' => 'Only accounts with Active or Inactive status can be toggled here.',
        'status_self' => 'You cannot deactivate your own account from here. Ask another Administrator.',
        'status_last_admin' => 'Cannot deactivate the only remaining Active Administrator.',
        'status_technician_busy' => 'Technician still has Assigned or Ongoing bookings. Resolve those first.',
        'status_save' => 'Could not save the new status.',
        'status_server' => 'A database error occurred. Please try again.',
        'create_disabled' => 'Direct creation of Admin/Staff accounts is disabled in this workflow.',
        'forbidden' => 'You are not allowed to perform this action.',
        'staff_only_technician' => 'Staff can only manage technician accounts.',
    ];
    $msg = isset($messages[$code]) ? $messages[$code] : 'Something went wrong. Please try again.';
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if ($editUserRecord !== false): ?>
<?php $erid = (int) ($editUserRecord['id'] ?? 0); ?>
<h2>Edit user (#<?php echo $erid; ?>)</h2>
<p>Name, email, and role updates. Leave password blank to keep the current password. Use <strong>Deactivate / Restore</strong> on the users table below to change account status.</p>
<?php if ($editHasTechnician): ?>
<p><strong>Note:</strong> This user has a technician profile — role must remain <strong>Technician</strong>.</p>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="jel_action" value="update_user">
    <input type="hidden" name="edit_user_id" value="<?php echo $erid; ?>">
    <div>
        <label for="eu_full_name">Full name</label><br>
        <input id="eu_full_name" type="text" name="edit_full_name" maxlength="100" autocomplete="name" required
            value="<?php echo htmlspecialchars((string) ($editUserRecord['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div>
        <label for="eu_email">Email</label><br>
        <input id="eu_email" type="email" name="edit_email" maxlength="100" autocomplete="username" required
            value="<?php echo htmlspecialchars((string) ($editUserRecord['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div>
        <label for="eu_role">Role</label><br>
        <?php if ($editHasTechnician): ?>
        <input type="hidden" name="edit_role_id" value="3">
        <input type="text" id="eu_role" value="Technician" readonly aria-readonly="true">
        <?php else: ?>
        <select id="eu_role" name="edit_role_id" required>
            <?php foreach ($rolesList as $r): ?>
            <?php
                $rid = (int) ($r['id'] ?? 0);
                $rn = (string) ($r['role_name'] ?? '');
                $curRole = (int) ($editUserRecord['role_id'] ?? 0);
            ?>
            <option value="<?php echo $rid; ?>"<?php echo $rid === $curRole ? ' selected' : ''; ?>>
                <?php echo htmlspecialchars($rn, ENT_QUOTES, 'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
    <div>
        <label for="eu_pw">New password (optional)</label><br>
        <input id="eu_pw" type="password" name="edit_password" maxlength="255" autocomplete="new-password">
    </div>
    <div>
        <label for="eu_pw2">Confirm new password</label><br>
        <input id="eu_pw2" type="password" name="edit_password_confirm" maxlength="255" autocomplete="new-password">
    </div>
    <div>
        <input type="submit" value="Save changes">
        <span> — </span>
        <a href="manage_users.php">Cancel edit</a>
    </div>
</form>
<?php endif; ?>

<p>Administrative account creation and technician operational creation have been moved out of the Admin area. This page remains for account governance and system oversight.</p>

<h2>All users</h2>

<table>
    <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Role</th>
            <th scope="col">Status</th>
            <th scope="col">Created</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
        <tr>
            <td colspan="7">No users found.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($users as $row): ?>
        <?php
            $uidTbl = (int) ($row['id'] ?? 0);
            $rowStatusRaw = isset($row['status']) ? (string) $row['status'] : '';
        ?>
        <tr>
            <td><?php echo htmlspecialchars((string) ($row['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['role_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <a href="<?php echo htmlspecialchars('?edit=' . $uidTbl, ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                <?php if ($rowStatusRaw === 'Active'): ?>
                <div>
                    <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="jel_action" value="set_status">
                        <input type="hidden" name="status_user_id" value="<?php echo $uidTbl; ?>">
                        <input type="hidden" name="new_status" value="Inactive">
                        <input type="submit" value="Deactivate">
                    </form>
                </div>
                <?php elseif ($rowStatusRaw === 'Inactive'): ?>
                <div>
                    <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="jel_action" value="set_status">
                        <input type="hidden" name="status_user_id" value="<?php echo $uidTbl; ?>">
                        <input type="hidden" name="new_status" value="Active">
                        <input type="submit" value="Restore">
                    </form>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
