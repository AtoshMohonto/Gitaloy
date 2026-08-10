<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN);

$roleId = (int) ($_GET['id'] ?? 0);
$role = getRoleById($roleId);
if ($role === null) {
    header('Location: ' . appBaseUrl() . '/modules/roles/index.php');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permissionIds = array_map('intval', $_POST['permissions'] ?? []);
    $isSystem = (int) $role['is_system'] === 1;

    if (!$isSystem && $name === '') {
        $error = 'Role name is required.';
    } else {
        if (!$isSystem) {
            updateRole($roleId, $name, $description);
        }
        saveRolePermissions($roleId, $permissionIds);
        logActivity('Updated role permissions: ' . $role['name'], 'roles');
        $success = 'Role updated. ' . count($permissionIds) . ' action(s) assigned.';
        $role = getRoleById($roleId);
    }
    }
}

$permissions = getAllPermissions();
$grouped = [];
foreach ($permissions as $permission) {
    $grouped[$permission['pgroup']][] = $permission;
}
$selected = array_flip(rolePermissionIds($roleId));
$isSystem = (int) $role['is_system'] === 1;

$pageTitle = 'Edit Role — ' . $role['name'];
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
                    <a href="<?= appBaseUrl() ?>/modules/roles/index.php" class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-emerald-300 hover:text-emerald-100">
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>Roles &amp; Permissions
                    </a>
                    <h1 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($role['name']) ?></h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Tick the actions this role is allowed to perform, then save.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="space-y-6">
                <?= csrfField() ?>
                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Role details</h2>
                        <?php if ($isSystem): ?>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">System role — name is fixed</span>
                        <?php endif; ?>
                    </header>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="name">Role name</label>
                            <input id="name" name="name" type="text" value="<?= htmlspecialchars($role['name']) ?>" <?= $isSystem ? 'disabled' : '' ?> class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:bg-slate-50 disabled:text-slate-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="description">Description</label>
                            <input id="description" name="description" type="text" value="<?= htmlspecialchars($role['description'] ?? '') ?>" <?= $isSystem ? 'disabled' : '' ?> class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100 disabled:bg-slate-50 disabled:text-slate-400">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Actions / permissions</h2>
                        <button type="button" id="check-all" class="ml-auto rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50">Select all</button>
                        <button type="button" id="uncheck-all" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50">Clear all</button>
                    </header>
                    <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($grouped as $group => $items): ?>
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4">
                                <p class="text-xs font-extrabold uppercase tracking-widest text-emerald-700"><?= htmlspecialchars($group) ?></p>
                                <div class="mt-3 space-y-2">
                                    <?php foreach ($items as $permission): ?>
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>" <?= isset($selected[(int) $permission['id']]) ? 'checked' : '' ?> class="mt-0.5 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-200">
                                            <span><?= htmlspecialchars($permission['label']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-2 rounded-2xl border border-emerald-100 bg-white px-5 py-4 shadow-sm">
                    <a href="<?= appBaseUrl() ?>/modules/roles/index.php" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>Back
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <i data-lucide="save" class="h-4 w-4"></i>Save role
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
    document.getElementById('check-all')?.addEventListener('click', function () {
        document.querySelectorAll('input[name="permissions[]"]').forEach(function (el) { el.checked = true; });
    });
    document.getElementById('uncheck-all')?.addEventListener('click', function () {
        document.querySelectorAll('input[name="permissions[]"]').forEach(function (el) { el.checked = false; });
    });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
