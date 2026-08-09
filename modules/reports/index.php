<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requirePermission('reports.view');

require_once __DIR__ . '/../students/student_db.php';

$pdo = getDbConnection();
$sessions = [];
$stmt = $pdo->query(
    'SELECT ss.id, ss.session_date, ss.type, c.name AS center_name
     FROM sessions ss
     LEFT JOIN centers c ON c.id = ss.center_id
     ORDER BY ss.session_date DESC LIMIT 20'
);
$sessions = $stmt->fetchAll();

$students = getStudents([], 's.id, s.student_id, s.name');

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Print &amp; export</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Reports</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Printable attendance sheets, report cards, fee receipts, and distribution summaries.</p>
    </div>
</section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Attendance sheet</h2>
                <p class="text-sm text-slate-600">A4 printable sheet for a teaching session.</p>
                <form class="mt-4 grid gap-3 md:grid-cols-4" method="get" action="<?= appBaseUrl() ?>/modules/reports/attendance_sheet.php">
                    <select name="session_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm md:col-span-3" required>
                        <option value="">Select session</option>
                        <?php foreach ($sessions as $session): ?>
                            <option value="<?= (int) $session['id'] ?>"><?= htmlspecialchars($session['session_date']) ?> — <?= htmlspecialchars($session['center_name']) ?> (<?= htmlspecialchars($session['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">Open</button>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Report card</h2>
                <p class="text-sm text-slate-600">Attendance %, task performance, and fee status for one student.</p>
                <form class="mt-4 grid gap-3 md:grid-cols-4" method="get" action="<?= appBaseUrl() ?>/modules/reports/report_card.php">
                    <select name="id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm md:col-span-3" required>
                        <option value="">Select student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= (int) $student['id'] ?>"><?= htmlspecialchars($student['student_id']) ?> — <?= htmlspecialchars($student['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">Open</button>
                </form>
                <?php if (empty($students)): ?>
                    <p class="mt-2 text-xs text-slate-500">No students in your scope yet.</p>
                <?php endif; ?>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Fee receipt</h2>
                <p class="text-sm text-slate-600">Receipt for a fee payment. Generate it from the Fees page per record.</p>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Distribution summary</h2>
                <p class="text-sm text-slate-600">Zone-wise materials distributed. Generate it from the Distribution page per plan.</p>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
