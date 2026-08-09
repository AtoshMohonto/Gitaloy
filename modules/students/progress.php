<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/student_db.php';

requireRole(ROLE_STUDENT);

$pdo = getDbConnection();
$student = getStudentByUserId((int) $_SESSION['user_id']);
if (!$student) {
    header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
    exit;
}

$yearId = activeYearId();
$attendance = getStudentAttendanceSummary((int) $student['id'], $yearId);
$marks = getStudentMarksSummary((int) $student['id'], $yearId);
$fees = getStudentFeeSummary((int) $student['id'], $yearId);

$taskStmt = $pdo->prepare(
    'SELECT t.title, t.total_marks, tr.obtained_marks, tr.completed, tr.remarks, tr.marked_at, cl.name AS class_name, su.name AS subject_name
     FROM task_results tr
     JOIN tasks t ON t.id = tr.task_id
     LEFT JOIN classes cl ON cl.id = t.class_id
     LEFT JOIN subjects su ON su.id = t.subject_id
     WHERE tr.student_id = ? ORDER BY tr.marked_at DESC'
);
$taskStmt->execute([(int) $student['id']]);
$taskResults = $taskStmt->fetchAll();

$feeStmt = $pdo->prepare(
    'SELECT f.*, fh.name AS head_name, ss.session_date
     FROM fees f
     LEFT JOIN fee_heads fh ON fh.id = f.head_id
     LEFT JOIN sessions ss ON ss.id = f.session_id
     WHERE f.student_id = ? ORDER BY f.created_at DESC'
);
$feeStmt->execute([(int) $student['id']]);
$feeRecords = $feeStmt->fetchAll();

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">My progress</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($student['name']) ?> — <?= htmlspecialchars($student['student_id']) ?></h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Attendance, task performance, and fee status for the active academic year.</p>
    </div>
</section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Attendance</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $attendance['rate'] ?>%</p>
                    <p class="mt-1 text-xs text-slate-500"><?= $attendance['present'] ?> present of <?= $attendance['total'] ?> sessions</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Performance</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $marks['pct'] ?>%</p>
                    <p class="mt-1 text-xs text-slate-500"><?= $marks['completed'] ?> of <?= $marks['tasks'] ?> tasks completed</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Fee balance</p>
                    <p class="mt-2 text-3xl font-semibold <?= $fees['due'] > 0 ? 'text-amber-600' : 'text-emerald-600' ?>"><?= number_format($fees['due'], 2) ?> BDT</p>
                    <p class="mt-1 text-xs text-slate-500">Paid <?= number_format($fees['paid'], 2) ?> of <?= number_format($fees['billed'], 2) ?></p>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">My tasks &amp; marks</h2>
                <?php if (empty($taskResults)): ?>
                    <p class="mt-4 text-sm text-slate-500">No tasks assigned yet.</p>
                <?php else: ?>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Task</th>
                                    <th class="px-3 py-3">Class / Subject</th>
                                    <th class="px-3 py-3">Marks</th>
                                    <th class="px-3 py-3">Completed</th>
                                    <th class="px-3 py-3">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($taskResults as $row): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3 font-semibold text-slate-800"><?= htmlspecialchars($row['title']) ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['class_name'] ?: '—') ?> • <?= htmlspecialchars($row['subject_name'] ?: '—') ?></td>
                                        <td class="px-3 py-3 text-slate-700"><?= number_format((float) $row['obtained_marks'], 1) ?> / <?= (int) $row['total_marks'] ?></td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full <?= $row['completed'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> px-2.5 py-0.5 text-xs font-medium"><?= $row['completed'] ? 'Yes' : 'No' ?></span>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['remarks'] ?: '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">My fee records</h2>
                <?php if (empty($feeRecords)): ?>
                    <p class="mt-4 text-sm text-slate-500">No fee records yet.</p>
                <?php else: ?>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Head</th>
                                    <th class="px-3 py-3">Period</th>
                                    <th class="px-3 py-3">Session</th>
                                    <th class="px-3 py-3 text-right">Amount</th>
                                    <th class="px-3 py-3 text-right">Paid</th>
                                    <th class="px-3 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($feeRecords as $row): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($row['head_name'] ?: '—') ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['period_type']) ?> <?= htmlspecialchars($row['month'] ?: '') ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['session_date'] ?: '—') ?></td>
                                        <td class="px-3 py-3 text-right text-slate-700"><?= number_format((float) $row['amount'], 2) ?></td>
                                        <td class="px-3 py-3 text-right text-slate-700"><?= number_format((float) $row['paid_amount'], 2) ?></td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full <?= $row['status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700' : ($row['status'] === 'Partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') ?> px-2.5 py-0.5 text-xs font-medium"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
