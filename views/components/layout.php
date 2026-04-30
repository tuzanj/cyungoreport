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

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    function toggleSidebar() {
        const isHidden = sidebar.classList.contains('-translate-x-full');
        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }
    }
    
    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', toggleSidebar);
    
    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', toggleSidebar);
    
    // Close sidebar when clicking a link
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) { // md breakpoint
                toggleSidebar();
            }
        });
    });
</script>
