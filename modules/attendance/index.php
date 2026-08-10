<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/attendance_db.php';

requirePermission('attendance.view');

$pdo = getDbConnection();
$success = null;
$error = null;

$user = currentUser();
$isTeacher = isTeacher();
$teacherCenterId = $isTeacher ? (int) ($user['center_id'] ?? 0) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        if ($action === 'create') {
            $centerId = (int) ($_POST['center_id'] ?? 0);
            $sessionDate = trim($_POST['session_date'] ?? '');
            $type = $_POST['type'] ?? 'Friday';
            $notes = trim($_POST['notes'] ?? '');

            if ($centerId <= 0 || $sessionDate === '') {
                $error = 'Center and session date are required.';
            } elseif ($isTeacher && $centerId !== $teacherCenterId) {
                $error = 'You can only create sessions at your own center.';
            } else {
                $sessionId = createSession([
                    'center_id' => $centerId,
                    'year_id' => activeYearId(),
                    'teacher_id' => (int) $_SESSION['user_id'],
                    'session_date' => $sessionDate,
                    'type' => $type,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
                $success = 'Session created. Now mark attendance.';
                logActivity('Created attendance session on ' . $sessionDate, 'attendance');
                header('Location: ' . appBaseUrl() . '/modules/attendance/mark.php?id=' . $sessionId);
                exit;
            }
        }

        if ($action === 'delete') {
            $sessionId = (int) ($_POST['session_id'] ?? 0);
            deleteSession($sessionId);
            $success = 'Session deleted.';
            logActivity('Deleted attendance session #' . $sessionId, 'attendance');
        }
    }
}

$filters = [
    'center_id' => $isTeacher ? (string) $teacherCenterId : (trim($_GET['center_id'] ?? '')),
    'year_id' => trim($_GET['year_id'] ?? (string) activeYearId()),
    'from' => trim($_GET['from'] ?? ''),
    'to' => trim($_GET['to'] ?? ''),
];
$sessions = getSessions(array_filter($filters, fn($v) => $v !== ''));
$centers = $pdo->query(
    'SELECT c.id, c.name, v.name AS village_name
     FROM centers c ' . getCenterScopeJoins('c') . ' WHERE ' . getCenterScopeFilter('c') . ' ORDER BY c.name'
)->fetchAll();
$years = getAcademicYears();

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Teaching sessions</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Attendance</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Open a Friday (or weekly) session, mark each student present or absent, and collect per-head fees in one place.</p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Create a session</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-5" method="post" action="<?= appBaseUrl() ?>/modules/attendance/index.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Center</label>
                        <select name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                            <option value="">Select center</option>
                            <?php foreach ($centers as $center): ?>
                                <option value="<?= (int) $center['id'] ?>" <?= $isTeacher && $center['id'] == $teacherCenterId ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?><?= !empty($center['village_name']) ? ' — ' . htmlspecialchars($center['village_name']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Session date</label>
                        <input type="date" name="session_date" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type</label>
                        <select name="type" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="Friday">Friday</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Notes (optional)</label>
                        <input type="text" name="notes" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="e.g. Chapter 3 revision">
                    </div>
                    <div class="md:col-span-5">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Create session →</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Sessions</h2>
                        <p class="text-sm text-slate-600">Review past and upcoming teaching days.</p>
                    </div>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/modules/attendance/index.php">
                        <?php if (!$isTeacher): ?>
                            <select name="center_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                <option value="">All centers</option>
                                <?php foreach ($centers as $center): ?>
                                    <option value="<?= (int) $center['id'] ?>" <?= $filters['center_id'] === (string) $center['id'] ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <select name="year_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year['id'] ?>" <?= $filters['year_id'] === (string) $year['id'] ? 'selected' : '' ?>><?= htmlspecialchars($year['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                    </form>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Date</th>
                                <th class="px-3 py-3">Type</th>
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">Teacher</th>
                                <th class="px-3 py-3">Marked</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($sessions)): ?>
                                <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No sessions yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($sessions as $session): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-4 font-semibold text-slate-800"><?= htmlspecialchars($session['session_date']) ?></td>
                                    <td class="px-3 py-4">
                                        <span class="rounded-full <?= $session['type'] === 'Friday' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700' ?> px-2.5 py-0.5 text-xs font-medium"><?= htmlspecialchars($session['type']) ?></span>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($session['center_name']) ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($session['teacher_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= (int) $session['marked_count'] ?> student<?= (int) $session['marked_count'] === 1 ? '' : 's' ?></td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= appBaseUrl() ?>/modules/attendance/mark.php?id=<?= (int) $session['id'] ?>" class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Mark / View</a>
                                            <form method="post" action="<?= appBaseUrl() ?>/modules/attendance/index.php" onsubmit="return confirm('Delete this session and its attendance?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                                <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
