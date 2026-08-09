<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT, ROLE_TEACHER);

$pdo = getDbConnection();
$feeId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT f.*, s.name AS student_name, s.student_id, s.guardian_name, s.village_id,
            c.name AS center_name, cl.name AS class_name, fh.name AS head_name, ss.session_date
     FROM fees f
     JOIN students s ON s.id = f.student_id
     LEFT JOIN centers c ON c.id = s.center_id
     LEFT JOIN classes cl ON cl.id = s.class_id
     LEFT JOIN fee_heads fh ON fh.id = f.head_id
     LEFT JOIN sessions ss ON ss.id = f.session_id
     WHERE f.id = ?'
);
$stmt->execute([$feeId]);
$fee = $stmt->fetch();

if (!$fee) {
    header('Location: ' . appBaseUrl() . '/modules/fees/index.php');
    exit;
}

list($village, $upazila, $district, $division) = geoLabels($fee);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between gap-3 no-print">
                <a href="<?= appBaseUrl() ?>/modules/fees/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Fees</a>
                <button onclick="window.print()" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">🖨️ Print</button>
            </div>

            <section class="report-doc bg-white rounded-3xl border border-emerald-100 shadow-lg p-8">
                <div class="text-center border-b-2 border-slate-900 pb-4">
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Village Education Program</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Gitaloy — Money Receipt</h1>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Receipt no.</p>
                        <p class="mt-1 font-semibold text-slate-800">RCP-<?= str_pad((string) $fee['id'], 5, '0', STR_PAD_LEFT) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Date</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($fee['paid_at'] ? date('Y-m-d', strtotime($fee['paid_at'])) : date('Y-m-d')) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Received from</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($fee['student_name']) ?> (<?= htmlspecialchars($fee['student_id']) ?>)</p>
                        <p class="text-xs text-slate-500">Guardian: <?= htmlspecialchars($fee['guardian_name'] ?: '—') ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Center / Class</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($fee['center_name'] ?: '—') ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($fee['class_name'] ?: '—') ?> • <?= htmlspecialchars($village ?: '—') ?>, <?= htmlspecialchars($upazila) ?></p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border-2 border-slate-900">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100 text-left text-xs uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($fee['head_name'] ?: 'General fee') ?></p>
                                    <p class="text-xs text-slate-500">
                                        <?= htmlspecialchars(ucfirst($fee['period_type'])) ?>
                                        <?= $fee['month'] ? ' • ' . htmlspecialchars($fee['month']) : '' ?>
                                        <?= $fee['session_date'] ? ' • Session ' . htmlspecialchars($fee['session_date']) : '' ?>
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-right text-lg font-bold text-slate-900"><?= number_format((float) $fee['paid_amount'], 2) ?> BDT</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900"><?= number_format((float) $fee['paid_amount'], 2) ?> BDT</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p class="mt-4 text-center text-sm italic text-slate-500">
                    Received the above amount with thanks. <?= number_format((float) $fee['paid_amount'], 2) ?> BDT only.
                </p>

                <div class="mt-10 grid gap-10 sm:grid-cols-2 text-center">
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Received by</p>
                    </div>
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Authorized Signature</p>
                        <p class="text-xs text-slate-500">Name &amp; Designation</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
