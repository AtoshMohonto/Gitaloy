<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';

requireRole(ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT, ROLE_TEACHER, ROLE_STUDENT);

$pdo = getDbConnection();

if (isStudent()) {
    $myStudent = getStudentByUserId((int) $_SESSION['user_id']);
    $studentId = $myStudent ? (int) $myStudent['id'] : 0;
} else {
    $studentId = (int) ($_GET['id'] ?? 0);
}

$student = $studentId > 0 ? getStudentById($studentId) : null;
if (!$student) {
    header('Location: ' . appBaseUrl() . '/modules/reports/index.php');
    exit;
}

$yearId = activeYearId();
$attendance = getStudentAttendanceSummary($studentId, $yearId);
$marks = getStudentMarksSummary($studentId, $yearId);
$fees = getStudentFeeSummary($studentId, $yearId);
list($village, $upazila, $district, $division) = geoLabels($student);

$stmt = $pdo->prepare(
    'SELECT t.title, t.total_marks, tr.obtained_marks, tr.completed, su.name AS subject_name
     FROM task_results tr
     JOIN tasks t ON t.id = tr.task_id
     LEFT JOIN subjects su ON su.id = t.subject_id
     WHERE tr.student_id = ? ORDER BY tr.marked_at DESC'
);
$stmt->execute([$studentId]);
$taskResults = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-center justify-between gap-3 no-print">
                <a href="<?= appBaseUrl() ?>/modules/students/view.php?id=<?= (int) $student['id'] ?>" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Back</a>
                <button onclick="window.print()" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">🖨️ Print</button>
            </div>

            <section class="report-doc bg-white rounded-3xl border border-emerald-100 shadow-lg p-8">
                <div class="text-center border-b-2 border-slate-900 pb-4">
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Village Education Program</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Student Report Card</h1>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Student</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($student['student_id']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Class / Center</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['class_name'] ?: '—') ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($student['center_name'] ?: '—') ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Village</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($village ?: '—') ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($upazila ?: '—') ?>, <?= htmlspecialchars($district ?: '—') ?></p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-100 p-4 text-center">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Attendance</p>
                        <p class="mt-1 text-3xl font-extrabold text-slate-900"><?= $attendance['rate'] ?>%</p>
                        <p class="text-xs text-slate-500"><?= $attendance['present'] ?> of <?= $attendance['total'] ?> sessions</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 p-4 text-center">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Performance</p>
                        <p class="mt-1 text-3xl font-extrabold text-slate-900"><?= $marks['pct'] ?>%</p>
                        <p class="text-xs text-slate-500"><?= $marks['obtained'] ?> of <?= $marks['possible'] ?> marks</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 p-4 text-center">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Fee balance</p>
                        <p class="mt-1 text-3xl font-extrabold <?= $fees['due'] > 0 ? 'text-amber-600' : 'text-emerald-700' ?>"><?= number_format($fees['due'], 2) ?> BDT</p>
                        <p class="text-xs text-slate-500">Paid <?= number_format($fees['paid'], 2) ?> of <?= number_format($fees['billed'], 2) ?></p>
                    </div>
                </div>

                <table class="mt-6 w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100 text-left text-xs uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-3">Task</th>
                            <th class="px-3 py-3">Subject</th>
                            <th class="px-3 py-3 text-center">Completed</th>
                            <th class="px-3 py-3 text-right">Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($taskResults)): ?>
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No tasks recorded.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($taskResults as $row): ?>
                            <tr class="bg-white">
                                <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($row['subject_name'] ?: '—') ?></td>
                                <td class="px-3 py-3 text-center text-slate-700"><?= $row['completed'] ? 'Yes' : 'No' ?></td>
                                <td class="px-3 py-3 text-right text-slate-700"><?= number_format((float) $row['obtained_marks'], 1) ?> / <?= (int) $row['total_marks'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-10 grid gap-10 sm:grid-cols-3 text-center">
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Guardian Signature</p>
                    </div>
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Teacher Signature</p>
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
