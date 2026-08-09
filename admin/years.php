<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/admin_db.php';

requireRole(ROLE_ADMIN);

$pdo = getDbConnection();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $start = trim($_POST['start_date'] ?? '');
            $end = trim($_POST['end_date'] ?? '');
            if ($name === '') throw new RuntimeException('Year name is required.');
            addAcademicYear($name, $start !== '' ? $start : null, $end !== '' ? $end : null);
            $success = 'Academic year added.';
            logActivity('Added academic year: ' . $name, 'admin:years');
        } elseif ($action === 'activate') {
            setActiveYear((int) ($_POST['id'] ?? 0));
            $success = 'Academic year activated.';
            logActivity('Activated academic year #' . (int) ($_POST['id'] ?? 0), 'admin:years');
        } elseif ($action === 'delete') {
            deleteRow('academic_years', (int) ($_POST['id'] ?? 0));
            $success = 'Academic year deleted.';
            logActivity('Deleted academic year #' . (int) ($_POST['id'] ?? 0), 'admin:years');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$years = getAcademicYears();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-4xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Multi-year support</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Academic years</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Sessions, fees, tasks, and distributions roll forward year to year. The active year is the default
                    filter everywhere in the system.
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
                <h2 class="text-xl font-semibold text-slate-900">Add year</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-3" method="post">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="e.g. 2026-2027" class="rounded-xl border border-emerald-200 px-3 py-2" required>
                    <input type="date" name="start_date" class="rounded-xl border border-emerald-200 px-3 py-2">
                    <input type="date" name="end_date" class="rounded-xl border border-emerald-200 px-3 py-2">
                    <div class="md:col-span-3">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Add year</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Years (<?= count($years) ?>)</h2>
                <div class="mt-4 space-y-3">
                    <?php if (empty($years)): ?><p class="text-sm text-slate-500">No years yet.</p><?php endif; ?>
                    <?php foreach ($years as $year): ?>
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-100 bg-white p-4">
                            <div>
                                <p class="font-semibold text-slate-800">
                                    <?= htmlspecialchars($year['name']) ?>
                                    <?php if ($year['is_active']): ?>
                                        <span class="ml-2 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?= $year['start_date'] ? htmlspecialchars($year['start_date']) . ' → ' . htmlspecialchars($year['end_date'] ?: 'present') : 'No dates set' ?>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <?php if (!$year['is_active']): ?>
                                    <form method="post" onsubmit="return confirm('Set <?= htmlspecialchars($year['name']) ?> as the active year?');">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="id" value="<?= (int) $year['id'] ?>">
                                        <button class="rounded-full bg-emerald-700 px-4 py-1.5 text-xs font-semibold text-white">Set active</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('Delete this year? Existing records are kept.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $year['id'] ?>">
                                    <button class="rounded-full border border-red-200 px-4 py-1.5 text-xs font-semibold text-red-600">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
