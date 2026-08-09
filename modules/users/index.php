<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/user_db.php';

requirePermission('users.manage');

$me = currentUser();
$allRoles = getAllRoles();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create') {
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $centerId = (int) ($_POST['center_id'] ?? 0);
            $scopeType = trim($_POST['scope_type'] ?? '');
            $scopeId = (int) ($_POST['scope_id'] ?? 0);

            if ($name === '' || $password === '' || $roleId <= 0) {
                throw new RuntimeException('Name, role, and password are required.');
            }
            if ($email !== '' && userEmailExists($email)) {
                throw new RuntimeException('Email already in use.');
            }
            if ($username !== '' && userUsernameExists($username)) {
                throw new RuntimeException('Username already in use.');
            }

            $allowedRoles = isAdmin()
                ? array_map('intval', array_column(array_filter($allRoles, fn($r) => (int) $r['id'] !== ROLE_ADMIN && (int) $r['id'] !== ROLE_STUDENT), 'id'))
                : [ROLE_TEACHER];
            if (!in_array($roleId, $allowedRoles, true)) {
                throw new RuntimeException('Role not allowed.');
            }

            if (!$isAdmin = isAdmin()) {
                $scopeType = $me['scope_type'] ?? '';
                $scopeId = (int) ($me['scope_id'] ?? 0);
                if ($roleId === ROLE_TEACHER && $centerId <= 0) {
                    throw new RuntimeException('A center is required for teachers.');
                }
            }

            createUser([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role_id' => $roleId,
                'phone' => $phone,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'center_id' => $centerId,
                'is_active' => 1,
            ]);
            $success = 'User account created.';
            logActivity('Created user account: ' . $name, 'users');
        } elseif ($action === 'toggle') {
            toggleUserActive((int) ($_POST['id'] ?? 0));
            $success = 'User status updated.';
            logActivity('Toggled active status for user #' . (int) ($_POST['id'] ?? 0), 'users');
        } elseif ($action === 'reset') {
            resetUserPassword((int) ($_POST['id'] ?? 0), 'gitaloy123');
            $success = 'Password reset to gitaloy123.';
            logActivity('Reset password for user #' . (int) ($_POST['id'] ?? 0), 'users');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$users = getScopedUsers($me);
$pdo = getDbConnection();
$centers = $pdo->query(
    'SELECT c.id, c.name, v.name AS village_name
     FROM centers c
     LEFT JOIN villages v ON v.id = c.village_id
     ORDER BY c.name'
)->fetchAll();
$divisions = getDivisions();
$divisionId = $me['scope_type'] === 'division' ? (int) $me['scope_id'] : 0;
$districts = $divisionId > 0 ? getDistricts($divisionId) : getDistricts();
$districtId = $me['scope_type'] === 'district' ? (int) $me['scope_id'] : 0;

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Staff accounts</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">User accounts</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Create divisional managers, district managers, accountants, and teachers. Each account is locked to its zone.
                </p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Create account</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-3" method="post" action="<?= appBaseUrl() ?>/modules/users/index.php">
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
                        <label class="mb-1 block text-sm font-medium text-slate-600">Role</label>
                        <select name="role_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select</option>
                            <?php if (isAdmin()): ?>
                                <?php foreach ($allRoles as $role): ?>
                                    <?php if ((int) $role['id'] === ROLE_ADMIN || (int) $role['id'] === ROLE_STUDENT) { continue; } ?>
                                    <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="<?= ROLE_TEACHER ?>">Teacher</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Center (for teachers)</label>
                        <select name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">—</option>
                            <?php foreach ($centers as $center): ?>
                                <option value="<?= (int) $center['id'] ?>"><?= htmlspecialchars($center['name']) ?><?= $center['village_name'] ? ' — ' . htmlspecialchars($center['village_name']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isAdmin()): ?>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Zone scope</label>
                            <select name="scope_type" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="">No scope</option>
                                <option value="division">Division</option>
                                <option value="district">District</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Division / District</label>
                            <select name="scope_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="">—</option>
                                <?php foreach ($divisions as $division): ?>
                                    <optgroup label="<?= htmlspecialchars($division['name']) ?> (division)">
                                        <option value="<?= (int) $division['id'] ?>" data-scope="division"><?= htmlspecialchars($division['name']) ?></option>
                                    </optgroup>
                                <?php endforeach; ?>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= (int) $district['id'] ?>" data-scope="district"><?= htmlspecialchars($district['name']) ?> (district)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Password</label>
                        <input type="text" name="password" value="gitaloy123" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div class="md:col-span-3">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Create account</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Accounts (<?= count($users) ?>)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Name</th>
                                <th class="px-3 py-3">Role</th>
                                <th class="px-3 py-3">Scope</th>
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">Contact</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($users)): ?>
                                <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No user accounts in your zone yet.</td></tr>
                            <?php endif; ?>
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
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($user['center_name'] ?? '—') ?></td>
                                    <td class="px-3 py-3 text-slate-600">
                                        <?= htmlspecialchars($user['phone'] ?: '') ?><?= $user['email'] ? '<br><span class="text-xs">' . htmlspecialchars($user['email']) . '</span>' : '' ?>
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="rounded-full <?= $user['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?> px-2.5 py-0.5 text-xs font-medium"><?= $user['is_active'] ? 'Active' : 'Disabled' ?></span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <?php if ((int) $user['id'] !== (int) $me['id']): ?>
                                            <div class="flex gap-2">
                                                <form method="post" action="<?= appBaseUrl() ?>/modules/users/index.php">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                    <button class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700"><?= $user['is_active'] ? 'Disable' : 'Enable' ?></button>
                                                </form>
                                                <form method="post" action="<?= appBaseUrl() ?>/modules/users/index.php" onsubmit="return confirm('Reset password to gitaloy123?');">
                                                    <input type="hidden" name="action" value="reset">
                                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                    <button class="rounded-full border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700">Reset pass</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">(you)</span>
                                        <?php endif; ?>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
