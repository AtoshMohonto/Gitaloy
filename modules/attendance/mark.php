<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/attendance_db.php';
require_once __DIR__ . '/../fees/fee_db.php';

requirePermission('attendance.manage');

$pdo = getDbConnection();
$sessionId = (int) ($_GET['id'] ?? 0);
$session = getSessionById($sessionId);

if (!$session) {
    header('Location: ' . appBaseUrl() . '/modules/attendance/index.php');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $statuses = $_POST['status'] ?? [];
        $fees = $_POST['fee'] ?? [];
        $headId = (int) ($_POST['head_id'] ?? 0);
        $classFilter = trim($_POST['class_filter'] ?? '');

        try {
            saveAttendance($sessionId, $statuses);

            $feeHeadId = $headId;
            if ($feeHeadId <= 0) {
                $stmt = $pdo->query('SELECT id FROM fee_heads WHERE name LIKE "%Friday%" LIMIT 1');
                $feeHeadId = (int) $stmt->fetchColumn();
            }
            $yearId = (int) $session['year_id'];

            foreach ($fees as $studentId => $amount) {
                $amount = (float) $amount;
                if ($amount > 0) {
                    createFee([
                        'student_id' => (int) $studentId,
                        'head_id' => $feeHeadId > 0 ? $feeHeadId : null,
                        'year_id' => $yearId,
                        'period_type' => 'Friday',
                        'session_id' => $sessionId,
                        'month' => null,
                        'amount' => $amount,
                        'paid_amount' => $amount,
                        'status' => 'Paid',
                        'due_date' => $session['session_date'],
                        'paid_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $expenseAmount = (float) ($_POST['expense_amount'] ?? 0);
            if ($expenseAmount > 0) {
                createExpense([
                    'center_id' => (int) $session['center_id'],
                    'year_id' => $yearId,
                    'user_id' => (int) $_SESSION['user_id'],
                    'category' => trim($_POST['expense_category'] ?? 'General'),
                    'description' => trim($_POST['expense_description'] ?? ''),
                    'amount' => $expenseAmount,
                    'expense_date' => $session['session_date'],
                ]);
            }

            $success = 'Attendance saved. Fees and expenses recorded.';
            logActivity('Saved attendance for session on ' . $session['session_date'], 'attendance');
        } catch (Throwable $e) {
            $error = 'Could not save: ' . $e->getMessage();
        }
    }
}

$students = getCenterStudents((int) $session['center_id']);
$attendance = getAttendanceForSession($sessionId);
$feeHeads = getFeeHeads();
$existingFees = getFees(['session_id' => $sessionId]);
$existingFeesMap = [];
foreach ($existingFees as $fee) {
    $existingFeesMap[(int) $fee['student_id']] = $fee;
}

$centerFilter = getCenterScopeFilter();
$centerJoins = getCenterScopeJoins();
$stmt = $pdo->query("SELECT c.id, c.name FROM centers c $centerJoins WHERE $centerFilter ORDER BY c.name");
$centers = $stmt->fetchAll();

$yearId = (int) $session['year_id'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="<?= appBaseUrl() ?>/modules/attendance/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Sessions</a>
                <div class="flex gap-2">
                    <a href="<?= appBaseUrl() ?>/modules/reports/attendance_sheet.php?session_id=<?= $sessionId ?>" class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">🖨️ Attendance sheet</a>
                </div>
            </div>

            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300"><?= htmlspecialchars($session['type']) ?> session</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($session['session_date']) ?> — <?= htmlspecialchars($session['center_name']) ?></h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Mark present/absent and collect per-head fees for the day. Fees saved here become Friday fee records.</p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" action="<?= appBaseUrl() ?>/modules/attendance/mark.php?id=<?= $sessionId ?>">
                <input type="hidden" name="action" value="save">

                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Student list</h2>
                            <p class="text-sm text-slate-600"><?= count($students) ?> active students at this center.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium">Fee head</label>
                            <select name="head_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                <option value="0">Default (Friday Contribution)</option>
                                <?php foreach ($feeHeads as $head): ?>
                                    <option value="<?= (int) $head['id'] ?>"><?= htmlspecialchars($head['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 md:grid-cols-2">
                        <button type="button" onclick="markAll('Present')" class="rounded-full border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">Mark all present</button>
                        <button type="button" onclick="markAll('Absent')" class="rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600">Mark all absent</button>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Student</th>
                                    <th class="px-3 py-3">Class</th>
                                    <th class="px-3 py-3">Status</th>
                                    <th class="px-3 py-3 w-40">Fee collected (BDT)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No active students at this center.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                        $status = $attendance[(int) $student['id']] ?? 'Present';
                                        $existingFee = $existingFeesMap[(int) $student['id']] ?? null;
                                        $feeValue = $existingFee ? (float) $existingFee['amount'] : '';
                                    ?>
                                    <tr class="bg-white" data-status="<?= $status ?>">
                                        <td class="px-3 py-3">
                                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($student['student_id']) ?></p>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($student['class_name'] ?: '—') ?></td>
                                        <td class="px-3 py-3">
                                            <select name="status[<?= (int) $student['id'] ?>]" class="status-select rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                                <option value="Present" <?= $status === 'Present' ? 'selected' : '' ?>>Present</option>
                                                <option value="Absent" <?= $status === 'Absent' ? 'selected' : '' ?>>Absent</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input type="number" step="0.01" min="0" name="fee[<?= (int) $student['id'] ?>]" value="<?= htmlspecialchars((string) $feeValue) ?>" placeholder="0.00" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Day expense (optional)</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Amount (BDT)</label>
                            <input type="number" step="0.01" min="0" name="expense_amount" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Category</label>
                            <select name="expense_category" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="General">General</option>
                                <option value="Transport">Transport</option>
                                <option value="Materials">Materials</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Description</label>
                            <input type="text" name="expense_description" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="What was spent?">
                        </div>
                    </div>
                </section>

                <button class="mt-6 rounded-full bg-emerald-700 px-8 py-3 font-semibold text-white">💾 Save attendance &amp; fees</button>
            </form>
        </div>
    </main>
</div>
<script>
    function markAll(status) {
        document.querySelectorAll('.status-select').forEach(function (sel) {
            sel.value = status;
        });
    }
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
