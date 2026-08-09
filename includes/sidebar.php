<?php
$base = appBaseUrl();
function navActive(array $needles): bool
{
    foreach ($needles as $n) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', $n) !== false) {
            return true;
        }
    }
    return false;
}
$isStaff = isStaff();
$isAdmin = isAdmin();
$showOps = $isStaff || hasAnyPermission(['students.view', 'attendance.view', 'fees.view', 'syllabus.view', 'tasks.view', 'progress.view', 'distribution.view', 'reports.view']);
?>
<aside id="app-sidebar" class="sidebar-nav fixed inset-y-0 left-0 z-50 w-72 shrink-0 -translate-x-full transform overflow-y-auto border-r border-emerald-900 bg-emerald-950 text-emerald-50 transition-transform duration-200 ease-in-out lg:static lg:z-20 lg:translate-x-0">
    <div class="sidebar-brand flex items-center justify-between gap-2 border-b border-emerald-900 p-5">
        <div class="sidebar-brand-text min-w-0">
            <p class="text-[11px] uppercase tracking-[0.3em] text-emerald-400">Village Education</p>
            <h2 class="mt-1 truncate text-base font-bold"><?= htmlspecialchars($_SESSION['role_name'] ?? 'Control Panel') ?></h2>
        </div>
        <button id="sidebar-close" type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-emerald-200 hover:bg-emerald-900 lg:hidden" aria-label="Close menu">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <nav class="space-y-6 p-4">
        <!-- Overview -->
        <div>
            <p class="sidebar-heading px-4 text-[11px] font-extrabold uppercase tracking-widest text-emerald-500">Overview</p>
            <div class="mt-2 space-y-1">
                <a href="<?= $base ?>/modules/dashboard/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/dashboard/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="layout-dashboard" class="h-4 w-4 <?= navActive(['/dashboard/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Dashboard</span>
                </a>
            </div>
        </div>

        <?php if ($showOps): ?>
        <!-- Operations -->
        <div>
            <p class="sidebar-heading px-4 text-[11px] font-extrabold uppercase tracking-widest text-emerald-500">Operations</p>
            <div class="mt-2 space-y-1">
                <?php if (hasPermission('students.view')): ?>
                <a href="<?= $base ?>/modules/students/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/students/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="graduation-cap" class="h-4 w-4 <?= navActive(['/students/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Students</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('attendance.view')): ?>
                <a href="<?= $base ?>/modules/attendance/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/attendance/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="calendar-check" class="h-4 w-4 <?= navActive(['/attendance/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Attendance</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('fees.view')): ?>
                <a href="<?= $base ?>/modules/fees/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/fees/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="wallet" class="h-4 w-4 <?= navActive(['/fees/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Fees &amp; Expenses</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('syllabus.view')): ?>
                <a href="<?= $base ?>/modules/syllabus/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/syllabus/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="book-open" class="h-4 w-4 <?= navActive(['/syllabus/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Syllabus</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('tasks.view')): ?>
                <a href="<?= $base ?>/modules/tasks/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/tasks/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="clipboard-list" class="h-4 w-4 <?= navActive(['/tasks/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Tasks &amp; Marks</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('progress.view')): ?>
                <a href="<?= $base ?>/modules/progress/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/progress/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="trending-up" class="h-4 w-4 <?= navActive(['/progress/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Progress</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('distribution.view')): ?>
                <a href="<?= $base ?>/modules/distribution/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/distribution/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="package" class="h-4 w-4 <?= navActive(['/distribution/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Distribution</span>
                </a>
                <?php endif; ?>
                <?php if (hasPermission('reports.view')): ?>
                <a href="<?= $base ?>/modules/reports/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/reports/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="file-text" class="h-4 w-4 <?= navActive(['/reports/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Reports</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isStudent()): ?>
        <!-- My Studies -->
        <div>
            <p class="sidebar-heading px-4 text-[11px] font-extrabold uppercase tracking-widest text-emerald-500">My Studies</p>
            <div class="mt-2 space-y-1">
                <a href="<?= $base ?>/modules/students/progress.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/students/progress.php']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="sparkles" class="h-4 w-4 <?= navActive(['/students/progress.php']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">My Progress</span>
                </a>
                <a href="<?= $base ?>/modules/students/view.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/students/view.php']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="id-card" class="h-4 w-4 <?= navActive(['/students/view.php']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">My Profile</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <!-- Administration -->
        <div>
            <p class="sidebar-heading px-4 text-[11px] font-extrabold uppercase tracking-widest text-emerald-500">Administration</p>
            <div class="mt-2 space-y-1">
                <a href="<?= $base ?>/admin/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/admin/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="settings" class="h-4 w-4 <?= navActive(['/admin/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Admin Panel</span>
                </a>
                <a href="<?= $base ?>/modules/roles/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/modules/roles/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="shield-check" class="h-4 w-4 <?= navActive(['/modules/roles/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Roles &amp; Permissions</span>
                </a>
                <?php if (hasPermission('users.manage')): ?>
                <a href="<?= $base ?>/modules/users/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/modules/users/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="users" class="h-4 w-4 <?= navActive(['/modules/users/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">User Accounts</span>
                </a>
                <?php endif; ?>
                <a href="<?= $base ?>/modules/settings/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/modules/settings/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4 <?= navActive(['/modules/settings/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Site Settings</span>
                </a>
                <a href="<?= $base ?>/modules/content/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/modules/content/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="megaphone" class="h-4 w-4 <?= navActive(['/modules/content/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">Frontend Content</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Account -->
        <div>
            <p class="sidebar-heading px-4 text-[11px] font-extrabold uppercase tracking-widest text-emerald-500">Account</p>
            <div class="mt-2 space-y-1">
                <a href="<?= $base ?>/modules/account/index.php" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold <?= navActive(['/modules/account/']) ? 'bg-emerald-600 text-white' : 'text-emerald-100 hover:bg-emerald-900 hover:text-white' ?>">
                    <i data-lucide="user-circle" class="h-4 w-4 <?= navActive(['/modules/account/']) ? 'text-white' : 'text-emerald-400' ?>"></i><span class="sidebar-label">My Account</span>
                </a>
                <a href="<?= $base ?>/modules/auth/login.php?logout=1" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-emerald-100 hover:bg-emerald-900 hover:text-white">
                    <i data-lucide="log-out" class="h-4 w-4 text-emerald-400"></i><span class="sidebar-label">Logout</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
