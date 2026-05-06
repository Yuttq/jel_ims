<?php

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ((int) $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Assigned Services — JEL-IMS';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

?>
<main class="site-main">

<h1>Assigned Services</h1>

<p>Placeholder.</p>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
