<?php
// views/components/sidebar.php
// Variables required: $role (string), $activePage (string)

$menus = [
    'admin' => [
        ['icon' => 'fa-gauge',          'label' => 'Dashboard',       'href' => '/admin/dashboard.php'],
        ['icon' => 'fa-graduation-cap', 'label' => 'Courses',         'href' => '/admin/courses.php'],
        ['icon' => 'fa-building-columns','label'=> 'Classes',         'href' => '/admin/classes.php'],
        ['icon' => 'fa-chalkboard-user','label' => 'Teachers',        'href' => '/admin/teachers.php'],
        ['icon' => 'fa-calendar-days',  'label' => 'Timetable',       'href' => '/admin/timetable.php'],
        ['icon' => 'fa-chart-pie',      'label' => 'Analytics',       'href' => '/admin/analytics.php'],
        ['icon' => 'fa-file-lines',     'label' => 'Audit Logs',      'href' => '/admin/audit.php'],
        ['icon' => 'fa-users',          'label' => 'User Management', 'href' => '/admin/users.php'],
        ['icon' => 'fa-list-check',     'label' => 'Grade Claims',    'href' => '/admin/claims.php'],
        ['icon' => 'fa-calendar-plus',  'label' => 'Academic Years',  'href' => '/admin/academic_years.php'],
        ['icon' => 'fa-sitemap',        'label' => 'Departments',     'href' => '/admin/departments.php'],
    ],
    'secretary' => [
        ['icon' => 'fa-gauge',          'label' => 'Dashboard',       'href' => '/secretary/dashboard.php'],
        ['icon' => 'fa-user-plus',      'label' => 'Register Student','href' => '/secretary/register_student.php'],
        ['icon' => 'fa-users',          'label' => 'All Students',    'href' => '/secretary/students.php'],
        ['icon' => 'fa-link',           'label' => 'Link Parent',     'href' => '/secretary/link_parent.php'],
        ['icon' => 'fa-file-invoice',   'label' => 'Enrollment Report','href'=> '/secretary/enrollment_report.php'],
    ],
    'teacher' => [
        ['icon' => 'fa-gauge',          'label' => 'Dashboard',       'href' => '/teacher/dashboard.php'],
        ['icon' => 'fa-book-open',      'label' => 'My Courses',      'href' => '/teacher/courses.php'],
        ['icon' => 'fa-pen-to-square',  'label' => 'Enter Marks',     'href' => '/teacher/marks.php'],
        ['icon' => 'fa-calendar-check', 'label' => 'Attendance',      'href' => '/teacher/attendance.php'],
        ['icon' => 'fa-chart-bar',      'label' => 'Reports',         'href' => '/teacher/reports.php'],
        ['icon' => 'fa-envelope-open-text','label'=>'Grade Claims',   'href' => '/teacher/claims.php'],
    ],
    'student' => [
        ['icon' => 'fa-gauge',          'label' => 'Dashboard',       'href' => '/student/dashboard.php'],
        ['icon' => 'fa-book',           'label' => 'My Courses',      'href' => '/student/courses.php'],
        ['icon' => 'fa-calendar-alt',   'label' => 'Schedule',        'href' => '/student/schedule.php'],
        ['icon' => 'fa-star-half-stroke','label'=> 'My Marks',        'href' => '/student/marks.php'],
        ['icon' => 'fa-scroll',         'label' => 'Transcript',      'href' => '/student/transcript.php'],
        ['icon' => 'fa-bell',           'label' => 'Notifications',   'href' => '/student/notifications.php'],
        ['icon' => 'fa-user-pen',       'label' => 'My Profile',      'href' => '/student/profile.php'],
    ],
    'parent' => [
        ['icon' => 'fa-gauge',          'label' => 'Dashboard',       'href' => '/parent/dashboard.php'],
        ['icon' => 'fa-child-reaching', 'label' => 'My Children',     'href' => '/parent/children.php'],
        ['icon' => 'fa-medal',          'label' => 'Performance',     'href' => '/parent/performance.php'],
        ['icon' => 'fa-envelope',       'label' => 'Messages',        'href' => '/parent/messages.php'],
        ['icon' => 'fa-bell',           'label' => 'Notifications',   'href' => '/parent/notifications.php'],
    ],
];

$currentMenus = $menus[$role] ?? [];
$roleColors = [
    'admin'     => 'from-indigo-900 to-indigo-700',
    'secretary' => 'from-teal-900 to-teal-700',
    'teacher'   => 'from-blue-900 to-blue-700',
    'student'   => 'from-violet-900 to-violet-700',
    'parent'    => 'from-green-900 to-green-700',
];
$gradient = $roleColors[$role] ?? 'from-slate-900 to-slate-700';
$roleLabel = ucfirst($role);
?>
<!-- Sidebar -->
<aside class="fixed left-0 top-0 h-screen w-64 bg-gradient-to-b <?= $gradient ?> text-white flex flex-col z-40 shadow-2xl">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
        <div class="w-10 h-10 bg-white/20 rounded-xl overflow-hidden">
            <img src="<?= APP_LOGO_URL ?>" alt="Logo" class="w-full h-full object-cover">
        </div>
        <div>
            <div class="font-bold text-lg leading-tight"><?= APP_NAME ?></div>
            <div class="text-xs text-white/60"><?= APP_ADDRESS ?> - <?= $roleLabel ?> Portal</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3">
        <?php foreach ($currentMenus as $item): ?>
        <a href="<?= BASE_URL . $item['href'] ?>"
           class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm text-white/80 hover:text-white hover:bg-white/10 transition-all duration-150 <?= ($activePage === $item['href']) ? 'active !text-white !bg-white/20' : '' ?>">
            <i class="fa-solid <?= $item['icon'] ?> w-5 text-center text-white/70"></i>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User Info & Logout -->
    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center font-bold text-sm">
                <?= strtoupper(substr(currentUserName(), 0, 2)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold truncate"><?= e(currentUserName()) ?></div>
                <div class="text-xs text-white/50"><?= ucfirst(currentRole()) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/index.php?action=logout"
           class="flex items-center gap-2 text-xs text-white/60 hover:text-red-300 transition-colors">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
