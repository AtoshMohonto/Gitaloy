<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/task_db.php';

requirePermission('tasks.manage');

$taskId = (int) ($_GET['id'] ?? 0);
$task = getTaskById($taskId);

if (!$task) {
    header('Location: ' . appBaseUrl() . '/modules/tasks/index.php');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $studentIds = $_POST['student_id'] ?? [];
        $marks = $_POST['marks'] ?? [];
        $completed = $_POST['completed'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $total = (int) $task['total_marks'];

        foreach ($studentIds as $i => $studentId) {
            $obtained = (float) ($marks[$i] ?? 0);
            if ($obtained > $total) {
                $obtained = $total;
            }
            if ($obtained < 0) {
                $obtained = 0;
            }
            saveTaskResult(
                $taskId,
                (int) $studentId,
                $obtained,
                isset($completed[$i]),
                trim($remarks[$i] ?? '') !== '' ? trim($remarks[$i]) : null
            );
        }
        $success = 'Marks saved.';
        logActivity('Saved marks for task: ' . $task['title'], 'tasks');
    }

    if ($action === 'remove') {
        deleteTaskResult($taskId, (int) ($_POST['student_id'] ?? 0));
        $success = 'Mark removed.';
        logActivity('Removed a mark for task: ' . $task['title'], 'tasks');
    }
}

$results = getTaskResults($taskId);
$unmarked = getUnmarkedStudents($taskId);
$total = (int) $task['total_marks'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/tasks/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← Tasks</a>

            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Task marking</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($task['title']) ?></h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80"><?= (int) $task['total_marks'] ?> marks • Due <?= htmlspecialchars($task['due_date'] ?: '—') ?></p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Mark students (<?= count($unmarked) ?> remaining)</h2>
                <?php if (empty($unmarked)): ?>
                    <p class="mt-3 text-sm text-slate-500">All students in scope are marked.</p>
                <?php else: ?>
                    <form class="mt-4 space-y-3" method="post" action="<?= appBaseUrl() ?>/modules/tasks/mark.php?id=<?= $taskId ?>">
                        <input type="hidden" name="action" value="save">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[700px] text-left text-sm">
                                <thead>
                                    <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                        <th class="px-3 py-3">Student</th>
                                        <th class="px-3 py-3">Center</th>
                                        <th class="px-3 py-3 w-24">Marks (out of <?= $total ?>)</th>
                                        <th class="px-3 py-3">Completed</th>
                                        <th class="px-3 py-3">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($unmarked as $student): ?>
                                        <tr class="bg-white">
                                            <td class="px-3 py-3">
                                                <p class="font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                                                <p class="text-xs text-slate-500"><?= htmlspecialchars($student['student_id']) ?></p>
                                            </td>
                                            <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($student['class_name'] ?: '—') ?></td>
                                            <td class="px-3 py-3">
                                                <input type="hidden" name="student_id[]" value="<?= (int) $student['id'] ?>">
                                                <input type="number" name="marks[]" min="0" max="<?= $total ?>" step="0.5" value="<?= $total ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                            </td>
                                            <td class="px-3 py-3"><input type="checkbox" name="completed[<?= (int) $student['id'] ?>]" value="1" checked class="h-5 w-5"></td>
                                            <td class="px-3 py-3"><input type="text" name="remarks[]" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm" placeholder="Optional"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Save marks</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Marked results (<?= count($results) ?>)</h2>
                <?php if (empty($results)): ?>
                    <p class="mt-3 text-sm text-slate-500">No marks recorded yet.</p>
                <?php else: ?>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Student</th>
                                    <th class="px-3 py-3">Center</th>
                                    <th class="px-3 py-3">Marks</th>
                                    <th class="px-3 py-3">Completed</th>
                                    <th class="px-3 py-3">Remarks</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $row): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3">
                                            <p class="font-semibold text-slate-800"><?= htmlspecialchars($row['student_name']) ?></p>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($row['student_id']) ?></p>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['center_name'] ?: '—') ?></td>
                                        <td class="px-3 py-3 font-semibold text-slate-800"><?= number_format((float) $row['obtained_marks'], 1) ?> / <?= $total ?></td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full <?= $row['completed'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> px-2.5 py-0.5 text-xs font-medium"><?= $row['completed'] ? 'Yes' : 'No' ?></span>
                                        </td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['remarks'] ?: '—') ?></td>
                                        <td class="px-3 py-3">
                                            <form method="post" action="<?= appBaseUrl() ?>/modules/tasks/mark.php?id=<?= $taskId ?>" onsubmit="return confirm('Remove this mark?');">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="student_id" value="<?= (int) $row['student_id'] ?>">
                                                <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Remove</button>
                                            </form>
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
