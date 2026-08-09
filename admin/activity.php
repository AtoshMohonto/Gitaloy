<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole(ROLE_ADMIN);

$pdo = getDbConnection();

$filters = [
    'module' => trim($_GET['module'] ?? ''),
    'user_id' => trim($_GET['user_id'] ?? ''),
];

$sql = 'SELECT * FROM activity_log WHERE 1=1';
$params = [];
if ($filters['module'] !== '') {
    $sql .= ' AND module = ?';
    $params[] = $filters['module'];
}
if ($filters['user_id'] !== '') {
    $sql .= ' AND user_id = ?';
    $params[] = (int) $filters['user_id'];
}
$sql .= ' ORDER BY created_at DESC LIMIT 300';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$modules = $pdo->query('SELECT DISTINCT module FROM activity_log WHERE module IS NOT NULL ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);

$pendingUsers = $pdo->query(
    'SELECT u.*,
            CASE u.role_id WHEN 1 THEN "Admin" WHEN 2 THEN "Divisional Manager" WHEN 3 THEN "District Manager"
                 WHEN 4 THEN "Accountant" WHEN 5 THEN "Teacher" WHEN 6 THEN "Student" ELSE "Unknown" END AS role_name
     FROM users u WHERE u.is_active = 0 ORDER BY u.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Audit trail</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Site activity</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Every login and every create / update / delete across the system, newest first.
                </p>
    </div>
</section>

            <?php require_once __DIR__ . '/admin_nav.php'; ?>

            <?php if (!empty($pendingUsers)): ?>
                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold text-slate-900">Inactive accounts (<?= count($pendingUsers) ?>)</h2>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">New self-registrations and disabled accounts — approve to enable sign-in</span>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Name</th>
                                    <th class="px-3 py-3">Role</th>
                                    <th class="px-3 py-3">Contact</th>
                                    <th class="px-3 py-3">Registered</th>
                                    <th class="px-3 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($pendingUsers as $user): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3">
                                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($user['name']) ?></p>
                                            <p class="text-xs text-slate-500">@<?= htmlspecialchars($user['username'] ?: 'n/a') ?></p>
                                        </td>
                                        <td class="px-3 py-3"><span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><?= htmlspecialchars($user['role_name']) ?></span></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($user['email'] ?: ($user['phone'] ?: '—')) ?></td>
                                        <td class="px-3 py-3 text-slate-500 text-xs"><?= htmlspecialchars($user['created_at']) ?></td>
                                        <td class="px-3 py-3 text-right">
                                            <form method="post" action="<?= appBaseUrl() ?>/admin/users.php">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                <button class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Approve</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Activity log (<?= count($logs) ?> latest)</h2>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/admin/activity.php">
                        <select name="module" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">All modules</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= htmlspecialchars($module) ?>" <?= $filters['module'] === $module ? 'selected' : '' ?>><?= htmlspecialchars($module) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                        <?php if ($filters['module'] !== '' || $filters['user_id'] !== ''): ?>
                            <a href="<?= appBaseUrl() ?>/admin/activity.php" class="rounded-full border border-emerald-200 px-4 py-2 text-sm font-semibold text-slate-700">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">When</th>
                                <th class="px-3 py-3">User</th>
                                <th class="px-3 py-3">Role</th>
                                <th class="px-3 py-3">Module</th>
                                <th class="px-3 py-3">Activity</th>
                                <th class="px-3 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No activity recorded yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-3 text-xs text-slate-500 whitespace-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td class="px-3 py-3">
                                        <a href="?user_id=<?= (int) $log['user_id'] ?>" class="font-semibold text-slate-800 hover:underline"><?= htmlspecialchars($log['user_name'] ?: 'Unknown') ?></a>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($log['role_id'] !== null ? roleName((int) $log['role_id']) : '—') ?></td>
                                    <td class="px-3 py-3">
                                        <?php if ($log['module']): ?>
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><?= htmlspecialchars($log['module']) ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($log['description']) ?></td>
                                    <td class="px-3 py-3 text-xs text-slate-400"><?= htmlspecialchars($log['ip_address'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
