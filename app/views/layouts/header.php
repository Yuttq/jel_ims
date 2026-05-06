<?php

if (!isset($pageTitle)) {
    $pageTitle = 'JEL Air Conditioning Services Management System';
}

$jel_ims_script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
$jel_ims_script_dir = dirname($jel_ims_script_name);
$jel_ims_web_root = dirname(dirname(dirname($jel_ims_script_dir)));

if ($jel_ims_web_root === '/' || $jel_ims_web_root === '.' || $jel_ims_web_root === '\\') {
    $jel_ims_web_root = '';
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($jel_ims_web_root . '/public/css/style.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="site-body">
<header class="site-header">
    <p class="site-header__title">JEL Air Conditioning Services Management System</p>
</header>
<div class="layout-shell">
