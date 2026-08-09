<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../distribution/distribution_db.php';

requireRole(ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER);

$pdo = getDbConnection();
$planId = (int) ($_GET['plan_id'] ?? 0);

$plan = getPlanById($planId);
if (!$plan) {
    header('Location: ' . appBaseUrl() . '/modules/distribution/index.php');
    exit;
}

$distributions = getPlanDistributions($planId);
$totalQty = 0;
foreach ($distributions as $d) {
    $totalQty += (int) $d['quantity'];
}

$scopeName = 'All zones';
if (($plan['scope_type'] ?? '') === 'division' && !empty($plan['scope_id'])) {
    $stmt = $pdo->prepare('SELECT name FROM divisions WHERE id = ?');
    $stmt->execute([(int) $plan['scope_id']]);
    $scopeName = 'Division — ' . ($stmt->fetchColumn() ?: '—');
} elseif (($plan['scope_type'] ?? '') === 'district' && !empty($plan['scope_id'])) {
    $stmt = $pdo->prepare('SELECT name FROM districts WHERE id = ?');
    $stmt->execute([(int) $plan['scope_id']]);
    $scopeName = 'District — ' . ($stmt->fetchColumn() ?: '—');
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between gap-3 no-print">
                <a href="<?= appBaseUrl() ?>/modules/distribution/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Distribution</a>
                <button onclick="window.print()" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">🖨️ Print</button>
            </div>

            <section class="report-doc bg-white rounded-3xl border border-emerald-100 shadow-lg p-8">
                <div class="text-center border-b-2 border-slate-900 pb-4">
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Village Education Program</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Distribution Report</h1>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Item</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($plan['item_name'] ?? '—') ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Zone</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($scopeName ?: 'All') ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Distributed</p>
                        <p class="mt-1 font-semibold text-emerald-700"><?= $totalQty ?> <?= htmlspecialchars($plan['unit'] ?? '') ?></p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-emerald-100">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100 text-left text-xs uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Student ID</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Village</th>
                                <th class="px-4 py-3">Center</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($distributions)): ?>
                                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No distributions recorded for this plan yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($distributions as $i => $d): ?>
                                <tr class="bg-white">
                                    <td class="px-4 py-3 text-slate-600"><?= $i + 1 ?></td>
                                    <td class="px-4 py-3 font-semibold text-slate-700"><?= htmlspecialchars($d['student_id']) ?></td>
                                    <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars($d['student_name']) ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($d['village_name'] ?: '—') ?></td>
                                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($d['center_name'] ?: '—') ?></td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800"><?= (int) $d['quantity'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (!empty($distributions)): ?>
                            <tfoot>
                                <tr class="bg-slate-50">
                                    <td colspan="5" class="px-4 py-3 font-semibold text-slate-800">Total</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900"><?= $totalQty ?></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>

                <div class="mt-10 grid gap-10 sm:grid-cols-2 text-center">
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Prepared by</p>
                    </div>
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Manager Signature</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
