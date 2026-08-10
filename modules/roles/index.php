<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            $error = 'Role name is required.';
        } else {
            $roleId = createRole($name, $description);
            logActivity('Created role: ' . $name, 'roles');
            header('Location: ' . appBaseUrl() . '/modules/roles/edit.php?id=' . $roleId);
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $role = getRoleById($id);
        if ($role !== null && deleteRole($id)) {
            $success = 'Role "' . $role['name'] . '" deleted.';
            logActivity('Deleted role: ' . $role['name'], 'roles');
        } else {
            $error = 'This role cannot be deleted. System roles and roles in use are protected.';
        }
    }
}

$roles = getAllRoles();

$pageTitle = 'Roles & Permissions';
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
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">System administration</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Roles &amp; Permissions</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Create new roles and decide which actions (permissions) each role can perform across the system.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">Create a new role</h2>
                    <p class="ml-auto text-xs font-medium text-slate-400">Then assign actions on the next screen</p>
                </header>
                <form method="post" class="p-5 space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="name">Role name</label>
                            <input id="name" name="name" type="text" placeholder="e.g. Data Entry Officer" required class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="description">Description</label>
                            <input id="description" name="description" type="text" placeholder="What this role is for" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="user-plus" class="h-4 w-4"></i>Create role
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">Roles (<?= count($roles) ?>)</h2>
                    <p class="ml-auto text-xs font-medium text-slate-400">Open a role to edit its name and permissions</p>
                </header>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-emerald-100 text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-3">Role</th>
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3 text-center">Actions granted</th>
                                <th class="px-5 py-3 text-center">Users</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($roles as $role): ?>
                                <tr class="bg-white">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-800"><?= htmlspecialchars($role['name']) ?></span>
                                            <?php if ((int) $role['is_system'] === 1): ?>
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">System</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($role['description'] ?: '—') ?></td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-emerald-900 px-2.5 py-0.5 text-xs font-bold text-white"><?= (int) $role['perm_count'] ?></span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-600"><?= (int) $role['user_count'] ?></td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?= appBaseUrl() ?>/modules/roles/edit.php?id=<?= (int) $role['id'] ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>Edit &amp; permissions
                                            </a>
                                            <?php if ((int) $role['is_system'] === 0 && (int) $role['user_count'] === 0): ?>
                                                <form method="post" onsubmit="return confirm('Delete the role &quot;<?= htmlspecialchars($role['name'], ENT_QUOTES) ?>&quot;?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $role['id'] ?>">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>Delete
                                                    </button>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
