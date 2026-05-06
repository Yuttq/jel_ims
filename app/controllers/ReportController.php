<?php

require_once __DIR__ . '/../models/User.php';

/**
 * Shared checks for admin-only reporting views.
 */
function jel_ims_reports_require_admin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
        header('Location: ../views/auth/login.php');
        exit;
    }

    if ((int) $_SESSION['role_id'] !== 1) {
        header('Location: ../views/auth/login.php');
        exit;
    }

    $userModel = new User();
    $user = $userModel->getUserById((int) $_SESSION['user_id']);

    if (!$user) {
        header('Location: ../views/auth/login.php');
        exit;
    }

    if (strcasecmp((string) $user['status'], 'Active') !== 0 || (int) $user['role_id'] !== 1) {
        header('Location: ../views/auth/login.php');
        exit;
    }
}
