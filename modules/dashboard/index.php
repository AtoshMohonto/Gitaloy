<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';

requireAuth();

$pdo = getDbConnection();
$user = currentUser();
$role = (int) ($user['role_id'] ?? 0);
$yearId = activeYearId();

$stats = [];
if (isStudent()) {
    $student = getStudentByUserId((int) $user['id']);
    $sid = $student ? (int) $student['id'] : 0;
    $attendance = getStudentAttendanceSummary($sid, $yearId);
    $marks = getStudentMarksSummary($sid, $yearId);
    $fees = getStudentFeeSummary($sid, $yearId);
    $stats = [
        'attendance' => $attendance,
        'marks' => $marks,
        'fees' => $fees,
        'name' => $student['name'] ?? 'Student',
        'center' => $student['center_name'] ?? '—',
        'class' => $student['class_name'] ?? '—',
    ];
} else {
    $scope = getStudentScopeFilter('s');
    $stats['students'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT s.id) FROM students s " . getStudentScopeJoins() . " WHERE $scope"
    )->fetchColumn();
    $stats['sessions'] = (int) $pdo->query(
        'SELECT COUNT(*) FROM sessions ss LEFT JOIN centers c ON c.id = ss.center_id '
        . getCenterScopeJoins('c') . ' WHERE ' . getCenterScopeFilter('c')
    )->fetchColumn();
    $stats['attendance'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM attendance a JOIN students s ON s.id = a.student_id " . getStudentScopeJoins() . " WHERE $scope AND a.status = 'Present'"
    )->fetchColumn();
    $stats['fees'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(f.paid_amount), 0) FROM fees f JOIN students s ON s.id = f.student_id " . getStudentScopeJoins() . " WHERE $scope"
    )->fetchColumn();

    $recent = $pdo->query(
        'SELECT ss.session_date, c.name AS center_name, ss.type,
                (SELECT COUNT(*) FROM attendance a WHERE a.session_id = ss.id AND a.status = "Present") AS present
         FROM sessions ss LEFT JOIN centers c ON c.id = ss.center_id '
        . getCenterScopeJoins('c') . ' WHERE ' . getCenterScopeFilter('c') . ' ORDER BY ss.session_date DESC LIMIT 5'
    )->fetchAll();
    $stats['recent'] = $recent;
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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Welcome back</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">
                                <?php $announcement = getSettings()['announcement'] ?? ''; ?>
            <?php if ($announcement !== ''): ?>
                <div class="fade-in flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700">
                        <i data-lucide="megaphone" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-amber-900">Announcement</p>
                        <p class="mt-0.5 text-sm leading-relaxed text-amber-800"><?= nl2br(htmlspecialchars($announcement)) ?></p>
                    </div>
                </div>
            <?php endif; ?>
<?php if (isStudent()): ?>
                        <?= htmlspecialchars($stats['name'] ?? '') ?>'s Dashboard
                    <?php else: ?>
                        <?= htmlspecialchars($user['name'] ?? '') ?> — <?= htmlspecialchars($_SESSION['role_name'] ?? '') ?>
                    <?php endif; ?>
                </h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    <?= isStudent()
                        ? 'Here is how your studies are going this year.'
                        : 'Here is the latest across your zone.' ?>
                </p>
    </div>
</section>

            <?php if (isStudent()): ?>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm text-center">
                        <p class="text-3xl">📅</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $stats['attendance']['rate'] ?>%</p>
                        <p class="text-sm text-slate-500">Attendance</p>
                        <p class="text-xs text-slate-400"><?= $stats['attendance']['present'] ?> of <?= $stats['attendance']['total'] ?> sessions</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm text-center">
                        <p class="text-3xl">✅</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $stats['marks']['pct'] ?>%</p>
                        <p class="text-sm text-slate-500">Performance</p>
                        <p class="text-xs text-slate-400"><?= $stats['marks']['obtained'] ?> of <?= $stats['marks']['possible'] ?> marks</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm text-center">
                        <p class="text-3xl">💰</p>
                        <p class="mt-2 text-3xl font-semibold <?= $stats['fees']['due'] > 0 ? 'text-amber-600' : 'text-emerald-700' ?>"><?= number_format($stats['fees']['due'], 2) ?> BDT</p>
                        <p class="text-sm text-slate-500">Fee balance</p>
                        <p class="text-xs text-slate-400">Paid <?= number_format($stats['fees']['paid'], 2) ?></p>
                    </div>
                </div>
                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">My details</h2>
                    <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                        <div><p class="text-slate-500">Class</p><p class="font-semibold text-slate-800"><?= htmlspecialchars($stats['class']) ?></p></div>
                        <div><p class="text-slate-500">Center</p><p class="font-semibold text-slate-800"><?= htmlspecialchars($stats['center']) ?></p></div>
                        <div class="flex items-end">
                            <a href="<?= appBaseUrl() ?>/modules/students/progress.php" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white">View my progress →</a>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Students</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= number_format($stats['students']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Sessions held</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= number_format($stats['sessions']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Presence records</p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= number_format($stats['attendance']) ?></p>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm text-slate-500">Fees collected</p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= number_format($stats['fees'], 2) ?> BDT</p>
                    </div>
                </div>

                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Recent sessions</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[500px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Date</th>
                                    <th class="px-3 py-3">Center</th>
                                    <th class="px-3 py-3">Type</th>
                                    <th class="px-3 py-3 text-right">Present</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($stats['recent'] as $session): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($session['session_date']) ?></td>
                                        <td class="px-3 py-3 font-semibold text-slate-800"><?= htmlspecialchars($session['center_name'] ?: '—') ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($session['type']) ?></td>
                                        <td class="px-3 py-3 text-right text-slate-700"><?= (int) $session['present'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($stats['recent'])): ?>
                                    <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No sessions in your zone yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <?php if (isAdmin()): ?>
                    <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6">
                        <h2 class="font-semibold text-amber-800">⚙️ First-time setup</h2>
                        <p class="mt-1 text-sm text-amber-700">
                            Build your geography in the admin panel, then add centers, classes, subjects, and fee heads
                            before registering students.
                        </p>
                        <a href="<?= appBaseUrl() ?>/admin/index.php" class="mt-3 inline-block rounded-full bg-amber-600 px-5 py-2 text-sm font-semibold text-white">Open admin panel →</a>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
