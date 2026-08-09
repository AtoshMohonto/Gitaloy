<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/fee_db.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/../attendance/attendance_db.php';

requirePermission('fees.view');

$pdo = getDbConnection();
$success = null;
$error = null;

$isTeacher = isTeacher();
$teacherCenterId = $isTeacher ? (int) (currentUser()['center_id'] ?? 0) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_fee') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $headId = (int) ($_POST['head_id'] ?? 0);
        $periodType = $_POST['period_type'] ?? 'Friday';
        $month = trim($_POST['month'] ?? '');
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $paidNow = (float) ($_POST['paid_now'] ?? 0);
        $dueDate = trim($_POST['due_date'] ?? '');

        if ($studentId <= 0 || $amount <= 0) {
            $error = 'Student and amount are required.';
        } elseif ($periodType === 'Monthly' && $month === '') {
            $error = 'Month is required for monthly fees.';
        } elseif ($periodType === 'Friday' && $sessionId > 0) {
            $existing = $pdo->prepare('SELECT id FROM fees WHERE student_id = ? AND session_id = ? LIMIT 1');
            $existing->execute([$studentId, $sessionId]);
            if ($existing->fetch()) {
                $error = 'A Friday fee already exists for this student in this session.';
            }
        } else {
            $status = $paidNow >= $amount ? 'Paid' : ($paidNow > 0 ? 'Partial' : 'Unpaid');
            createFee([
                'student_id' => $studentId,
                'head_id' => $headId > 0 ? $headId : null,
                'year_id' => activeYearId(),
                'period_type' => $periodType,
                'session_id' => $sessionId > 0 ? $sessionId : null,
                'month' => $periodType === 'Monthly' ? $month : null,
                'amount' => $amount,
                'paid_amount' => $paidNow,
                'status' => $status,
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'paid_at' => $paidNow > 0 ? date('Y-m-d H:i:s') : null,
            ]);
            $success = 'Fee record created.';
            logActivity('Created fee record for student #' . $studentId . ' (' . number_format($amount, 2) . ' BDT)', 'fees');
        }
    }

    if ($action === 'record_payment') {
        $feeId = (int) ($_POST['fee_id'] ?? 0);
        $payment = (float) ($_POST['payment'] ?? 0);
        $fee = getFeeById($feeId);
        if ($fee && $payment > 0) {
            $newPaid = round((float) $fee['paid_amount'] + $payment, 2);
            $status = $newPaid >= (float) $fee['amount'] ? 'Paid' : 'Partial';
            updateFeePayment($feeId, $newPaid, $status);
            $success = 'Payment recorded.';
            logActivity('Recorded payment of ' . number_format($payment, 2) . ' BDT for fee #' . $feeId, 'fees');
        }
    }

    if ($action === 'delete_fee') {
        deleteFee((int) ($_POST['fee_id'] ?? 0));
        $success = 'Fee record deleted.';
        logActivity('Deleted fee record #' . (int) ($_POST['fee_id'] ?? 0), 'fees');
    }

    if ($action === 'create_expense') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $description = trim($_POST['description'] ?? '');
        $centerId = (int) ($_POST['center_id'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));

        if ($amount <= 0) {
            $error = 'Expense amount must be greater than zero.';
        } else {
            if ($centerId <= 0 && $isTeacher) {
                $centerId = $teacherCenterId;
            }
            if ($isTeacher && $centerId !== $teacherCenterId) {
                $error = 'You can only add expenses for your own center.';
            } else {
                createExpense([
                    'center_id' => $centerId > 0 ? $centerId : null,
                    'year_id' => activeYearId(),
                    'user_id' => (int) $_SESSION['user_id'],
                    'category' => $category,
                    'description' => $description,
                    'amount' => $amount,
                    'expense_date' => $expenseDate,
                ]);
                $success = 'Expense recorded.';
                logActivity('Recorded expense: ' . number_format($amount, 2) . ' BDT (' . $category . ')', 'fees');
            }
        }
    }

    if ($action === 'delete_expense') {
        deleteExpense((int) ($_POST['expense_id'] ?? 0));
        $success = 'Expense deleted.';
        logActivity('Deleted expense #' . (int) ($_POST['expense_id'] ?? 0), 'fees');
    }
}

$yearId = activeYearId();
$filters = [
    'center_id' => $isTeacher ? (string) $teacherCenterId : (trim($_GET['center_id'] ?? '')),
    'period_type' => trim($_GET['period_type'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'year_id' => trim($_GET['year_id'] ?? (string) $yearId),
];
$fees = getFees(array_filter($filters, fn($v) => $v !== ''));

$expFilters = [
    'year_id' => $filters['year_id'],
    'center_id' => $filters['center_id'],
];
$expenses = getExpenses(array_filter($expFilters, fn($v) => $v !== ''));
$expenseSum = array_sum(array_map(fn($e) => (float) $e['amount'], $expenses));

$stats = feeStats($yearId);
$estats = expenseStats($yearId);

$scopedStudents = getStudents([]);
$centers = $pdo->query('SELECT c.id, c.name FROM centers c ' . getCenterScopeJoins('c') . ' WHERE ' . getCenterScopeFilter('c') . ' ORDER BY c.name')->fetchAll();
$feeHeads = getFeeHeads();
$years = getAcademicYears();
$pendingSessions = getSessions(['year_id' => (string) $yearId]);

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Money &amp; expenses</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Fees &amp; Expenses</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Track per-head fees (Friday or monthly) and day-to-day center expenses.</p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Billed (active year)</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900"><?= number_format($stats['billed'], 2) ?> BDT</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Collected</p>
                    <p class="mt-2 text-2xl font-semibold text-emerald-600"><?= number_format($stats['collected'], 2) ?> BDT</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Outstanding</p>
                    <p class="mt-2 text-2xl font-semibold <?= $stats['due'] > 0 ? 'text-amber-600' : 'text-slate-900' ?>"><?= number_format($stats['due'], 2) ?> BDT</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Expenses (active year)</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-600"><?= number_format($estats['total'], 2) ?> BDT</p>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Add fee (per head)</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-8" method="post" action="<?= appBaseUrl() ?>/modules/fees/index.php">
                    <input type="hidden" name="action" value="create_fee">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Student</label>
                        <select name="student_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select student</option>
                            <?php foreach ($scopedStudents as $student): ?>
                                <option value="<?= (int) $student['id'] ?>"><?= htmlspecialchars($student['student_id']) ?> — <?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Head</label>
                        <select name="head_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="0">—</option>
                            <?php foreach ($feeHeads as $head): ?>
                                <option value="<?= (int) $head['id'] ?>"><?= htmlspecialchars($head['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Period</label>
                        <select name="period_type" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="Friday">Friday</option>
                            <option value="Monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Month (if monthly)</label>
                        <input type="month" name="month" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Amount (BDT)</label>
                        <input type="number" step="0.01" min="0" name="amount" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Paid now (BDT)</label>
                        <input type="number" step="0.01" min="0" name="paid_now" value="0" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Due date</label>
                        <input type="date" name="due_date" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div class="md:col-span-8">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Add fee</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Fee records</h2>
                        <p class="text-sm text-slate-600"><?= count($fees) ?> records in the current view.</p>
                    </div>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/modules/fees/index.php">
                        <?php if (!$isTeacher): ?>
                            <select name="center_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                <option value="">All centers</option>
                                <?php foreach ($centers as $center): ?>
                                    <option value="<?= (int) $center['id'] ?>" <?= $filters['center_id'] === (string) $center['id'] ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <select name="period_type" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">Any period</option>
                            <option value="Friday" <?= $filters['period_type'] === 'Friday' ? 'selected' : '' ?>>Friday</option>
                            <option value="Monthly" <?= $filters['period_type'] === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
                        </select>
                        <select name="status" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">Any status</option>
                            <option value="Paid" <?= $filters['status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Partial" <?= $filters['status'] === 'Partial' ? 'selected' : '' ?>>Partial</option>
                            <option value="Unpaid" <?= $filters['status'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        </select>
                        <select name="year_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year['id'] ?>" <?= $filters['year_id'] === (string) $year['id'] ? 'selected' : '' ?>><?= htmlspecialchars($year['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                    </form>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Student</th>
                                <th class="px-3 py-3">Head</th>
                                <th class="px-3 py-3">Period</th>
                                <th class="px-3 py-3 text-right">Amount</th>
                                <th class="px-3 py-3 text-right">Paid</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Receipt</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($fees)): ?>
                                <tr><td colspan="8" class="px-3 py-10 text-center text-slate-500">No fee records in this view.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($fees as $fee): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-4">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($fee['student_name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($fee['student_id']) ?></p>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($fee['head_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4 text-slate-600">
                                        <?= htmlspecialchars($fee['period_type']) ?>
                                        <?php if ($fee['month']): ?> (<?= htmlspecialchars($fee['month']) ?>)<?php endif; ?>
                                        <?php if ($fee['session_date']): ?><br><span class="text-xs text-slate-400">Session <?= htmlspecialchars($fee['session_date']) ?></span><?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4 text-right text-slate-700"><?= number_format((float) $fee['amount'], 2) ?></td>
                                    <td class="px-3 py-4 text-right text-emerald-700 font-semibold"><?= number_format((float) $fee['paid_amount'], 2) ?></td>
                                    <td class="px-3 py-4">
                                        <span class="rounded-full <?= $fee['status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700' : ($fee['status'] === 'Partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') ?> px-2.5 py-0.5 text-xs font-medium"><?= htmlspecialchars($fee['status']) ?></span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <?php if ((float) $fee['paid_amount'] > 0): ?>
                                            <a href="<?= appBaseUrl() ?>/modules/reports/fee_receipt.php?id=<?= (int) $fee['id'] ?>" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Receipt</a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <?php if ((float) $fee['paid_amount'] < (float) $fee['amount']): ?>
                                                <form method="post" action="<?= appBaseUrl() ?>/modules/fees/index.php" class="inline">
                                                    <input type="hidden" name="action" value="record_payment">
                                                    <input type="hidden" name="fee_id" value="<?= (int) $fee['id'] ?>">
                                                    <input type="number" name="payment" step="0.01" min="0" placeholder="Pay" class="w-20 rounded-xl border border-emerald-200 px-2 py-1.5 text-xs" required>
                                                    <button class="rounded-full border border-emerald-200 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Add</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="<?= appBaseUrl() ?>/modules/fees/index.php" onsubmit="return confirm('Delete this fee record?');">
                                                <input type="hidden" name="action" value="delete_fee">
                                                <input type="hidden" name="fee_id" value="<?= (int) $fee['id'] ?>">
                                                <button class="rounded-full border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Add expense</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-5" method="post" action="<?= appBaseUrl() ?>/modules/fees/index.php">
                    <input type="hidden" name="action" value="create_expense">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Amount (BDT)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Category</label>
                        <select name="category" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option>General</option><option>Transport</option><option>Materials</option><option>Snacks</option><option>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Center</label>
                        <select name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" <?= $isTeacher ? 'disabled' : '' ?>>
                            <option value="">No center</option>
                            <?php foreach ($centers as $center): ?>
                                <option value="<?= (int) $center['id'] ?>" <?= $isTeacher && $center['id'] == $teacherCenterId ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <input type="text" name="description" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="What was the expense?">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date</label>
                        <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div class="md:col-span-5">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Add expense</button>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Date</th>
                                <th class="px-3 py-3">Category</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">By</th>
                                <th class="px-3 py-3 text-right">Amount</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($expenses)): ?>
                                <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No expenses yet. Total: 0.00 BDT</td></tr>
                            <?php endif; ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($expense['expense_date']) ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($expense['category']) ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($expense['description'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($expense['center_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($expense['user_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-right font-semibold text-rose-700"><?= number_format((float) $expense['amount'], 2) ?></td>
                                    <td class="px-3 py-3">
                                        <form method="post" action="<?= appBaseUrl() ?>/modules/fees/index.php" onsubmit="return confirm('Delete this expense?');">
                                            <input type="hidden" name="action" value="delete_expense">
                                            <input type="hidden" name="expense_id" value="<?= (int) $expense['id'] ?>">
                                            <button class="rounded-full border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-600">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!empty($expenses)): ?>
                                <tr class="bg-slate-50 font-semibold">
                                    <td colspan="5" class="px-3 py-3 text-slate-800">Total expenses</td>
                                    <td class="px-3 py-3 text-right text-rose-700"><?= number_format($expenseSum, 2) ?> BDT</td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
