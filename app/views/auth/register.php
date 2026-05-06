<?php

session_start();

$pageTitle = 'Register — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>JEL Air Conditioning Services — Register</h1>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    $messages = [
        'missing' => 'Full name, email, and password are required.',
        'duplicate' => 'That email is already registered.',
        'customer' => 'Account could not be completed. Please try again.',
        'config' => 'Registration is temporarily unavailable.',
        'server' => 'Something went wrong. Please try again later.',
    ];
    $msg = isset($messages[$code]) ? $messages[$code] : 'Registration could not be completed.';
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/AuthController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <div>
        <label for="full_name">Full Name</label><br>
        <input type="text" id="full_name" name="full_name" required maxlength="100">
    </div>
    <div>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" required maxlength="100">
    </div>
    <div>
        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required maxlength="255">
    </div>
    <div>
        <label for="contact_number">Contact Number</label><br>
        <input type="text" id="contact_number" name="contact_number" maxlength="20">
    </div>
    <div>
        <label for="address">Address</label><br>
        <textarea id="address" name="address" rows="4" cols="40"></textarea>
    </div>
    <div>
        <button type="submit" name="register" value="1">Register</button>
    </div>
</form>

<p><a href="<?php echo htmlspecialchars($jel_ims_web_root . '/app/views/auth/login.php', ENT_QUOTES, 'UTF-8'); ?>">Back to login</a></p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
