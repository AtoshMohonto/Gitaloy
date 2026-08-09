<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../attendance/attendance_db.php';

requireRole(ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT, ROLE_TEACHER);

$sessionId = (int) ($_GET['session_id'] ?? 0);
$session = getSessionById($sessionId);

if (!$session) {
    header('Location: ' . appBaseUrl() . '/modules/reports/index.php');
    exit;
}

$students = getCenterStudents((int) $session['center_id']);
$attendance = getAttendanceForSession($sessionId);
$presentCount = 0;
$absentCount = 0;
foreach ($students as $student) {
    if (($attendance[(int) $student['id']] ?? 'Present') === 'Present') {
        $presentCount++;
    } else {
        $absentCount++;
    }
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT fh.name FROM fee_heads fh WHERE fh.name LIKE "%Friday%" LIMIT 1');
$feeHeadName = $stmt->fetchColumn() ?: 'Friday Contribution';

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-center justify-between gap-3 no-print">
                <a href="<?= appBaseUrl() ?>/modules/reports/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Reports</a>
                <button onclick="window.print()" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">🖨️ Print</button>
            </div>

            <section class="report-doc bg-white rounded-3xl border border-emerald-100 shadow-lg p-8">
                <div class="text-center border-b-2 border-slate-900 pb-4">
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Village Education Program</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Gitaloy — Attendance Sheet</h1>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Center</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($session['center_name']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Date / Type</p>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($session['session_date']) ?> (<?= htmlspecialchars($session['type']) ?>)</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Summary</p>
                        <p class="mt-1 font-semibold text-slate-800">Present <?= $presentCount ?> • Absent <?= $absentCount ?> • Total <?= count($students) ?></p>
                    </div>
                </div>

                <table class="mt-6 w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100 text-left text-xs uppercase tracking-wider text-slate-600">
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">Student ID</th>
                            <th class="px-3 py-3">Name</th>
                            <th class="px-3 py-3">Class</th>
                            <th class="px-3 py-3">Guardian</th>
                            <th class="px-3 py-3 text-center">Present / Absent</th>
                            <th class="px-3 py-3 text-center">Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No students at this center.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $i => $student): ?>
                            <?php $status = $attendance[(int) $student['id']] ?? 'Present'; ?>
                            <tr class="bg-white">
                                <td class="px-3 py-3 text-slate-700"><?= $i + 1 ?></td>
                                <td class="px-3 py-3 text-slate-700 font-semibold"><?= htmlspecialchars($student['student_id']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($student['name']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($student['class_name'] ?: '—') ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($student['guardian_name'] ?: '—') ?></td>
                                <td class="px-3 py-3 text-center font-semibold <?= $status === 'Present' ? 'text-emerald-700' : 'text-red-700' ?>"><?= $status === 'Present' ? 'P' : 'A' ?></td>
                                <td class="px-3 py-3"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-10 grid gap-10 sm:grid-cols-2 text-center">
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Teacher Signature</p>
                        <p class="text-xs text-slate-500">Name &amp; Designation</p>
                    </div>
                    <div>
                        <div class="sig-rule"></div>
                        <p class="mt-2 text-xs font-semibold text-slate-700">Manager Signature</p>
                        <p class="text-xs text-slate-500">Name &amp; Designation</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
