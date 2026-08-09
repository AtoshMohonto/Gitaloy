<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/users/user_db.php';
require_once __DIR__ . '/admin_db.php';

requireRole(ROLE_ADMIN);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $scopeType = trim($_POST['scope_type'] ?? '');
            $scopeId = (int) ($_POST['scope_id'] ?? 0);
            $centerId = (int) ($_POST['center_id'] ?? 0);
            if ($name === '') throw new RuntimeException('Name is required.');
            if ($roleId === ROLE_TEACHER && $centerId <= 0) throw new RuntimeException('Teachers need a center.');
            if (in_array($roleId, [ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT], true) && ($scopeType === '' || $scopeId <= 0)) {
                throw new RuntimeException('Zone managers need a division or district scope.');
            }
            createUser([
                'name' => $name,
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? 'gitaloy123',
                'role_id' => $roleId,
                'phone' => trim($_POST['phone'] ?? ''),
                'scope_type' => $scopeType,
                'scope_id' => $scopeId > 0 ? $scopeId : null,
                'center_id' => $centerId > 0 ? $centerId : null,
                'is_active' => 1,
            ]);
            $success = 'User created.';
            logActivity('Created user account: ' . $name, 'admin:users');
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $user = getUserById($id);
            if ($user && (int) $user['role_id'] === ROLE_ADMIN) {
                throw new RuntimeException('The main admin cannot be deleted.');
            }
            $pdo = getDbConnection();
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            $success = 'User deleted.';
            logActivity('Deleted user: ' . ($user['name'] ?? ('#' . $id)), 'admin:users');
        } elseif ($action === 'toggle') {
            toggleUserActive((int) ($_POST['id'] ?? 0));
            $success = 'User status updated.';
            logActivity('Toggled active status for user #' . (int) ($_POST['id'] ?? 0), 'admin:users');
        } elseif ($action === 'reset') {
            resetUserPassword((int) ($_POST['id'] ?? 0), 'gitaloy123');
            $success = 'Password reset to gitaloy123.';
            logActivity('Reset password for user #' . (int) ($_POST['id'] ?? 0), 'admin:users');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$users = getAllUsers();
$divisions = getDivisions();
$centers = getCenters();

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Access control</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">User accounts</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Create staff accounts for every role. Managers and accountants carry a zone scope; teachers are
                    pinned to a study center.
                </p>
    </div>
</section>

            <?php require_once __DIR__ . '/admin_nav.php'; ?>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Create user</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-3" method="post">
                    <input type="hidden" name="action" value="create">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Full name</label>
                        <input type="text" name="name" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Username</label>
                        <input type="text" name="username" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="Optional">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Email</label>
                        <input type="email" name="email" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="Optional">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Phone</label>
                        <input type="text" name="phone" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="Optional">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Password</label>
                        <input type="text" name="password" value="gitaloy123" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Role</label>
                        <select name="role_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select</option>
                            <option value="<?= ROLE_DIV_MANAGER ?>">Divisional Manager</option>
                            <option value="<?= ROLE_DIST_MANAGER ?>">District Manager</option>
                            <option value="<?= ROLE_ACCOUNTANT ?>">District Accountant</option>
                            <option value="<?= ROLE_TEACHER ?>">Teacher</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Scope</label>
                        <select name="scope_type" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">No scope</option>
                            <option value="division">Division</option>
                            <option value="district">District</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Division</label>
                        <select name="scope_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">—</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?= (int) $division['id'] ?>"><?= htmlspecialchars($division['name']) ?> (division)</option>
                                <?php foreach (getDistricts((int) $division['id']) as $district): ?>
                                    <option value="<?= (int) $district['id'] ?>"><?= htmlspecialchars($district['name']) ?> (district)</option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Center (teachers)</label>
                        <select name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">—</option>
                            <?php foreach ($centers as $center): ?>
                                <option value="<?= (int) $center['id'] ?>"><?= htmlspecialchars($center['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Create user</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Users (<?= count($users) ?>)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Name</th>
                                <th class="px-3 py-3">Role</th>
                                <th class="px-3 py-3">Scope</th>
                                <th class="px-3 py-3">Contact</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($users as $user): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-3">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($user['name']) ?></p>
                                        <p class="text-xs text-slate-500">@<?= htmlspecialchars($user['username'] ?: 'n/a') ?></p>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><?= htmlspecialchars($user['role_name']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">
                                        <?php if ($user['scope_type'] === 'division'): ?>
                                            Division — <?= htmlspecialchars($user['division_name'] ?? '—') ?>
                                        <?php elseif ($user['scope_type'] === 'district'): ?>
                                            District — <?= htmlspecialchars($user['district_name'] ?? '—') ?>
                                        <?php elseif ($user['center_name']): ?>
                                            Center — <?= htmlspecialchars($user['center_name']) ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">
                                        <?= htmlspecialchars($user['phone'] ?: '') ?><?= $user['email'] ? '<br><span class="text-xs">' . htmlspecialchars($user['email']) . '</span>' : '' ?>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full <?= $user['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?> px-2.5 py-0.5 text-xs font-medium"><?= $user['is_active'] ? 'Active' : 'Disabled' ?></span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex justify-end gap-2">
                                            <form method="post">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                <button class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700"><?= $user['is_active'] ? 'Disable' : 'Enable' ?></button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Reset password to gitaloy123?');">
                                                <input type="hidden" name="action" value="reset">
                                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                <button class="rounded-full border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700">Reset</button>
                                            </form>
                                            <?php if ((int) $user['role_id'] !== ROLE_ADMIN): ?>
                                                <form method="post" onsubmit="return confirm('Delete this user?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                    <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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
