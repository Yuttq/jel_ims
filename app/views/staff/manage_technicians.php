<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$viewerRole = (int) $_SESSION['role_id'];
if ($viewerRole !== 1 && $viewerRole !== 2) {
    header('Location: ../auth/login.php');
    exit;
}

$isReadOnly = ($viewerRole === 1);

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Service.php';
require_once __DIR__ . '/../../models/Technician.php';

$userModel = new User();
$serviceModel = new Service();
$technicianModel = new Technician();

$allUsers = $userModel->getUsersForAdminList();
$technicianUsers = array_values(array_filter($allUsers, function ($row) {
    return (int) ($row['role_id'] ?? 0) === 3;
}));

$skillServices = $serviceModel->getAllServices();

$editLookupFailed = false;
$requestedEditId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editUserRecord = false;
if ($requestedEditId > 0) {
    $candidate = $userModel->getUserForAdminEdit($requestedEditId);
    if ($candidate === false || (int) ($candidate['role_id'] ?? 0) !== 3) {
        $editLookupFailed = true;
    } else {
        $editUserRecord = $candidate;
    }
}

$pageTitle = 'Manage Technicians — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">
<h1>Manage Technicians</h1>
<?php if ($isReadOnly): ?>
<p>Read-only mode for Admin oversight. Staff users can create, update, and deactivate technicians.</p>
<?php endif; ?>

<?php if (!empty($_GET['created_technician'])): ?><p>Technician was created successfully.</p><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><p>Technician account updated.</p><?php endif; ?>
<?php if (!empty($_GET['status_updated'])): ?><p>Technician status updated.</p><?php endif; ?>
<?php if ($editLookupFailed): ?><p>That technician record could not be loaded.</p><?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
$code = (string) $_GET['error'];
$messages = [
    'forbidden' => 'You are not allowed to perform this action.',
    'staff_only_technician' => 'Only technician accounts can be managed here.',
    'tech_missing' => 'Technician requires full name, email, password, and password confirmation.',
    'tech_password_match' => 'Password and confirmation must match.',
    'tech_password_short' => 'Password must be at least 8 characters.',
    'tech_field_length' => 'Name or email is too long.',
    'tech_email' => 'Please enter a valid email address.',
    'tech_no_skill' => 'Select at least one technician skill.',
    'tech_duplicate' => 'An account with that email already exists.',
    'tech_save' => 'The technician profile could not be saved.',
    'tech_server' => 'A database error occurred while creating the technician.',
    'edit_missing_user' => 'No valid technician id was supplied.',
    'edit_unknown' => 'That technician record does not exist.',
    'edit_missing' => 'Full name and email are required.',
    'edit_field_length' => 'Name or email is too long.',
    'edit_email' => 'Please enter a valid email address.',
    'edit_password_partial' => 'Fill both password fields or leave both blank.',
    'edit_password_match' => 'Password and confirmation must match.',
    'edit_password_short' => 'New password must be at least 8 characters.',
    'edit_duplicate' => 'Another account already uses that email.',
    'edit_save' => 'Changes could not be saved.',
    'edit_server' => 'A database error occurred while saving.',
    'status_unknown' => 'That technician record does not exist.',
    'status_invalid' => 'Invalid status selection.',
    'status_technician_busy' => 'Technician has Assigned/Ongoing bookings. Resolve them first.',
];
$msg = isset($messages[$code]) ? $messages[$code] : 'Something went wrong.';
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<?php if (!$isReadOnly): ?>
<h2>Create Technician Account</h2>
<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="jel_action" value="create_technician">
    <label for="tec_full_name">Full name</label><br>
    <input id="tec_full_name" type="text" name="tech_full_name" maxlength="100" required><br>
    <label for="tec_email">Email</label><br>
    <input id="tec_email" type="email" name="tech_email" maxlength="100" required><br>
    <label for="tec_password">Password</label><br>
    <input id="tec_password" type="password" name="tech_password" minlength="8" maxlength="255" required><br>
    <label for="tec_password_confirm">Confirm password</label><br>
    <input id="tec_password_confirm" type="password" name="tech_password_confirm" minlength="8" maxlength="255" required><br>
    <fieldset>
        <legend>Skills (choose at least one)</legend>
        <?php foreach ($skillServices as $svc): ?>
        <?php $svcId = (int) ($svc['id'] ?? 0); ?>
        <div>
            <input type="checkbox" name="service_ids[]" value="<?php echo $svcId; ?>" id="<?php echo 'svc_chk_' . $svcId; ?>">
            <label for="<?php echo 'svc_chk_' . $svcId; ?>"><?php echo htmlspecialchars((string) $svc['service_name'], ENT_QUOTES, 'UTF-8'); ?></label>
        </div>
        <?php endforeach; ?>
    </fieldset>
    <input type="submit" value="Create Technician">
</form>
<?php endif; ?>

<?php if ($editUserRecord !== false): ?>
<h2>Edit Technician Account (#<?php echo (int) $editUserRecord['id']; ?>)</h2>
<?php if ($isReadOnly): ?>
<p>Read-only in Admin mode.</p>
<?php else: ?>
<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="jel_action" value="update_user">
    <input type="hidden" name="edit_user_id" value="<?php echo (int) $editUserRecord['id']; ?>">
    <input type="hidden" name="edit_role_id" value="3">
    <label for="eu_full_name">Full name</label><br>
    <input id="eu_full_name" type="text" name="edit_full_name" maxlength="100" required
        value="<?php echo htmlspecialchars((string) $editUserRecord['full_name'], ENT_QUOTES, 'UTF-8'); ?>"><br>
    <label for="eu_email">Email</label><br>
    <input id="eu_email" type="email" name="edit_email" maxlength="100" required
        value="<?php echo htmlspecialchars((string) $editUserRecord['email'], ENT_QUOTES, 'UTF-8'); ?>"><br>
    <label for="eu_pw">New password (optional)</label><br>
    <input id="eu_pw" type="password" name="edit_password" maxlength="255"><br>
    <label for="eu_pw2">Confirm new password</label><br>
    <input id="eu_pw2" type="password" name="edit_password_confirm" maxlength="255"><br>
    <input type="submit" value="Save changes">
    <a href="manage_technicians.php">Cancel</a>
</form>
<?php endif; ?>
<?php endif; ?>

<h2>Technician Account List</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
    </tr>
    <?php if (count($technicianUsers) === 0): ?>
    <tr><td colspan="6">No technician accounts found.</td></tr>
    <?php else: ?>
    <?php foreach ($technicianUsers as $row): ?>
    <?php $uid = (int) ($row['id'] ?? 0); $rowStatus = (string) ($row['status'] ?? ''); ?>
    <tr>
        <td><?php echo $uid; ?></td>
        <td><?php echo htmlspecialchars((string) ($row['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($rowStatus, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
            <a href="<?php echo htmlspecialchars('?edit=' . $uid, ENT_QUOTES, 'UTF-8'); ?>">View or Edit</a>
            <?php if (!$isReadOnly && $rowStatus === 'Active'): ?>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="jel_action" value="set_status">
                <input type="hidden" name="status_user_id" value="<?php echo $uid; ?>">
                <input type="hidden" name="new_status" value="Inactive">
                <input type="submit" value="Deactivate">
            </form>
            <?php elseif (!$isReadOnly && $rowStatus === 'Inactive'): ?>
            <form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/UserController.php', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="jel_action" value="set_status">
                <input type="hidden" name="status_user_id" value="<?php echo $uid; ?>">
                <input type="hidden" name="new_status" value="Active">
                <input type="submit" value="Reactivate">
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</table>

</main>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
