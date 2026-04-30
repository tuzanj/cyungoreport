<?php
// views/components/layout.php
// Usage: $pageTitle, $activePage, $role already set before including this
// This file wraps the main content area

// Include head
include ROOT_PATH . '/views/components/head.php';
include ROOT_PATH . '/views/components/sidebar.php';
include ROOT_PATH . '/views/components/topbar.php';
?>
<main class="md:ml-64 pt-16 min-h-screen transition-all duration-200">
    <div class="p-3 sm:p-4 md:p-6">
        <?php include ROOT_PATH . '/views/components/flash.php'; ?>
