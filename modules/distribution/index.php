<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/distribution_db.php';

requirePermission('distribution.view');

$pdo = getDbConnection();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
    if ($action === 'create_plan') {
        $title = trim($_POST['title'] ?? '');
        $scopeType = $_POST['scope_type'] ?? 'district';
        $scopeId = (int) ($_POST['scope_id'] ?? 0);
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if ($title === '' || $itemId <= 0 || $quantity <= 0 || $scopeId <= 0) {
            $error = 'Title, zone, item, and quantity are required.';
        } else {
            createPlan([
                'title' => $title,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'year_id' => activeYearId(),
                'status' => 'Planned',
                'created_by' => (int) $_SESSION['user_id'],
            ]);
            $success = 'Distribution plan created. Now assign items to students.';
            logActivity('Created distribution plan: ' . $title, 'distribution');
        }
    }

    if ($action === 'delete_plan') {
        deletePlan((int) ($_POST['plan_id'] ?? 0));
        $success = 'Plan deleted.';
        logActivity('Deleted distribution plan #' . (int) ($_POST['plan_id'] ?? 0), 'distribution');
    }

    if ($action === 'save_distributions') {
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $qtys = $_POST['qty'] ?? [];
        $notes = trim($_POST['notes'] ?? '');
        try {
            saveDistributions($planId, $qtys, $notes !== '' ? $notes : null, (int) $_SESSION['user_id']);
            $success = 'Distribution saved.';
            logActivity('Saved distribution for plan #' . $planId, 'distribution');
        } catch (Throwable $e) {
            $error = 'Could not save distribution: ' . $e->getMessage();
        }
    }
    }
}

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'year_id' => trim($_GET['year_id'] ?? (string) activeYearId()),
];
$plans = getPlans(array_filter($filters, fn($v) => $v !== ''));
$items = getDistributionItems();
$years = getAcademicYears();
$stats = distributionStats(activeYearId());

// Zone dropdowns (respect manager scope)
$user = currentUser();
$scopeDivisions = getDivisions();
$scopeDistricts = getDistricts();
if (isDistManager() && ($user['scope_type'] ?? '') === 'district') {
    $scopeDistricts = array_filter($scopeDistricts, fn($d) => (int) $d['id'] === (int) ($user['scope_id'] ?? 0));
}
if (isDivManager() && ($user['scope_type'] ?? '') === 'division') {
    $scopeDivisions = array_filter($scopeDivisions, fn($d) => (int) $d['id'] === (int) ($user['scope_id'] ?? 0));
    $scopeDistricts = array_filter($scopeDistricts, fn($d) => in_array((int) $d['division_id'], [(int) ($user['scope_id'] ?? 0)], true));
}

$selectedPlan = null;
if (isset($_GET['assign'])) {
    $selectedPlan = getPlanById((int) $_GET['assign']);
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Materials for villages</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Distribution</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Plan the distribution of study materials zone-wise (division or district) and record exactly which student received which item.</p>
    </div>
</section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Items distributed</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900"><?= number_format($stats['total_qty']) ?></p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Students served</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= number_format($stats['students']) ?></p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Item types</p>
                    <p class="mt-2 text-3xl font-semibold text-indigo-600"><?= number_format($stats['items']) ?></p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Create distribution plan</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-6" method="post" action="<?= appBaseUrl() ?>/modules/distribution/index.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create_plan">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Title</label>
                        <input type="text" name="title" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="e.g. Notebooks for Joypurhat" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Zone type</label>
                        <select name="scope_type" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="district">District</option>
                            <option value="division">Division</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Zone</label>
                        <select name="scope_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select</option>
                            <?php foreach ($scopeDivisions as $division): ?>
                                <optgroup label="<?= htmlspecialchars($division['name']) ?>">
                                    <?php foreach (getDistricts((int) $division['id']) as $district): ?>
                                        <?php if (in_array($district['id'], array_map(fn($d) => (int) $d['id'], $scopeDistricts), true)): ?>
                                            <option value="<?= (int) $district['id'] ?>"><?= htmlspecialchars($district['name']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Item</label>
                        <select name="item_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select item</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Total quantity</label>
                        <input type="number" name="quantity" min="1" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div class="md:col-span-6">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Create plan</button>
                    </div>
                </form>
            </section>

            <?php if ($selectedPlan): ?>
                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Assign: <?= htmlspecialchars($selectedPlan['title']) ?></h2>
                            <p class="text-sm text-slate-600">Item: <?= htmlspecialchars($selectedPlan['item_name']) ?> • Target: <?= (int) $selectedPlan['quantity'] ?> <?= htmlspecialchars($selectedPlan['unit'] ?: '') ?> • Status: <?= htmlspecialchars($selectedPlan['status']) ?></p>
                        </div>
                        <a href="<?= appBaseUrl() ?>/modules/distribution/index.php" class="rounded-full border border-emerald-200 px-4 py-2 text-sm font-semibold text-slate-700">Close</a>
                    </div>

                    <?php
                        $planStudents = getPlanStudents((int) $selectedPlan['id']);
                        $planDistributions = getPlanDistributions((int) $selectedPlan['id']);
                        $qtyMap = [];
                        foreach ($planDistributions as $dist) {
                            $qtyMap[(int) $dist['student_id']] = (int) $dist['quantity'];
                        }
                    ?>
                    <form class="mt-5" method="post" action="<?= appBaseUrl() ?>/modules/distribution/index.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_distributions">
                        <input type="hidden" name="plan_id" value="<?= (int) $selectedPlan['id'] ?>">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[800px] text-left text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                        <th class="px-3 py-3">Student</th>
                                        <th class="px-3 py-3">Center</th>
                                        <th class="px-3 py-3">Village</th>
                                        <th class="px-3 py-3 w-32">Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($planStudents)): ?>
                                        <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No active students in this zone.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($planStudents as $student): ?>
                                        <tr class="bg-white">
                                            <td class="px-3 py-3">
                                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                                                <p class="text-xs text-slate-500"><?= htmlspecialchars($student['student_id']) ?></p>
                                            </td>
                                            <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($student['center_name'] ?: '—') ?></td>
                                            <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($student['village_name'] ?: '—') ?></td>
                                            <td class="px-3 py-3">
                                                <input type="number" name="qty[<?= (int) $student['id'] ?>]" min="0" value="<?= $qtyMap[(int) $student['id']] ?? 0 ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            <input type="text" name="notes" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm md:col-span-2" placeholder="Distribution notes (optional)">
                            <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Save distribution</button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Plans (<?= count($plans) ?>)</h2>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/modules/distribution/index.php">
                        <select name="status" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">All statuses</option>
                            <option value="Planned" <?= $filters['status'] === 'Planned' ? 'selected' : '' ?>>Planned</option>
                            <option value="In Progress" <?= $filters['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $filters['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <select name="year_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year['id'] ?>" <?= $filters['year_id'] === (string) $year['id'] ? 'selected' : '' ?>><?= htmlspecialchars($year['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                    </form>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php if (empty($plans)): ?>
                        <p class="text-sm text-slate-500 md:col-span-2 xl:col-span-3">No distribution plans yet.</p>
                    <?php endif; ?>
                    <?php foreach ($plans as $plan): ?>
                        <div class="rounded-2xl border border-emerald-100 bg-white p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-slate-800"><?= htmlspecialchars($plan['title']) ?></h3>
                                <span class="rounded-full <?= $plan['status'] === 'Completed' ? 'bg-emerald-50 text-emerald-700' : ($plan['status'] === 'In Progress' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500') ?> px-2 py-0.5 text-xs font-medium"><?= htmlspecialchars($plan['status']) ?></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($plan['item_name']) ?> • Target <?= (int) $plan['quantity'] ?> <?= htmlspecialchars($plan['unit'] ?: '') ?></p>
                            <p class="mt-2 text-xs text-slate-500">Distributed <?= (int) $plan['distributed_qty'] ?> to <?= (int) $plan['recipient_count'] ?> students</p>
                            <div class="mt-3 h-2 w-full rounded-full bg-slate-200">
                                <div class="h-2 rounded-full bg-emerald-600" style="width:<?= min(100, round(((int) $plan['distributed_qty'] / max((int) $plan['quantity'], 1)) * 100)) ?>%"></div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="<?= appBaseUrl() ?>/modules/distribution/index.php?assign=<?= (int) $plan['id'] ?>" class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Assign</a>
                                <a href="<?= appBaseUrl() ?>/modules/reports/distribution.php?plan_id=<?= (int) $plan['id'] ?>" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Report</a>
                                <form method="post" action="<?= appBaseUrl() ?>/modules/distribution/index.php" onsubmit="return confirm('Delete this plan and its records?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_plan">
                                    <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                                    <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
