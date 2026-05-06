<?php

session_start();

$pageTitle = 'Login — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>JEL Air Conditioning Services — Login</h1>

<?php if (!empty($_GET['registered'])): ?>
<p>Registration successful. Please log in.</p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
<?php
    $code = (string) $_GET['error'];
    $messages = [
        'auth' => 'Invalid email or password.',
        'missing' => 'Email and password are required.',
        'inactive' => 'This account is not active.',
        'invalid_role' => 'Your role is not recognized. Contact support.',
    ];
    $msg = isset($messages[$code]) ? $messages[$code] : 'Login could not be completed.';
?>
<p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($jel_ims_web_root . '/app/controllers/AuthController.php', ENT_QUOTES, 'UTF-8'); ?>">
    <div>
        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" required maxlength="100">
    </div>
    <div>
        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" required maxlength="255">
    </div>
    <div>
        <button type="submit" name="login" value="1">Login</button>
    </div>
</form>

<p><a href="<?php echo htmlspecialchars($jel_ims_web_root . '/app/views/auth/register.php', ENT_QUOTES, 'UTF-8'); ?>">Create an account</a></p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
