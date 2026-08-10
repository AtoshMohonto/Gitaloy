<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/task_db.php';

requirePermission('tasks.view');

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        if ($action === 'create') {
            $data = [
                'class_id' => trim($_POST['class_id'] ?? ''),
                'subject_id' => trim($_POST['subject_id'] ?? ''),
                'year_id' => (int) ($_POST['year_id'] ?? activeYearId()),
                'teacher_id' => isTeacher() ? (int) $_SESSION['user_id'] : (int) ($_POST['teacher_id'] ?? $_SESSION['user_id']),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'due_date' => trim($_POST['due_date'] ?? ''),
                'total_marks' => (int) ($_POST['total_marks'] ?? 10),
            ];

            if ($data['title'] === '') {
                $error = 'Task title is required.';
            } else {
                $data['class_id'] = $data['class_id'] !== '' ? (int) $data['class_id'] : null;
                $data['subject_id'] = $data['subject_id'] !== '' ? (int) $data['subject_id'] : null;
                $data['description'] = $data['description'] !== '' ? $data['description'] : null;
                $data['due_date'] = $data['due_date'] !== '' ? $data['due_date'] : null;
                $taskId = createTask($data);
                $success = 'Task created. Now mark student completion.';
                logActivity('Created task: ' . $data['title'], 'tasks');
                header('Location: ' . appBaseUrl() . '/modules/tasks/mark.php?id=' . $taskId);
                exit;
            }
        }

        if ($action === 'delete') {
            deleteTask((int) ($_POST['task_id'] ?? 0));
            $success = 'Task deleted.';
            logActivity('Deleted task #' . (int) ($_POST['task_id'] ?? 0), 'tasks');
        }
    }
}

$filters = [
    'class_id' => trim($_GET['class_id'] ?? ''),
    'year_id' => trim($_GET['year_id'] ?? (string) activeYearId()),
    'teacher_id' => isTeacher() ? (string) $_SESSION['user_id'] : '',
];
$tasks = getTasks(array_filter($filters, fn($v) => $v !== ''));
$classes = getClasses();
$subjects = getSubjects();
$years = getAcademicYears();
$teachers = getTeachers();

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Performance</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Tasks &amp; Marks</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Teachers give tasks and completion marks per student; managers can review the progress of any task.</p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Create task</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-4" method="post" action="<?= appBaseUrl() ?>/modules/tasks/index.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Title</label>
                        <input type="text" name="title" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="e.g. Draw the national flag" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Class</label>
                        <select name="class_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">All classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Subject</label>
                        <select name="subject_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">All subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= (int) $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Due date</label>
                        <input type="date" name="due_date" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Total marks</label>
                        <input type="number" name="total_marks" value="10" min="1" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Year</label>
                        <select name="year_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year['id'] ?>" <?= $year['is_active'] ? 'selected' : '' ?>><?= htmlspecialchars($year['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!isTeacher()): ?>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Teacher</label>
                            <select name="teacher_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="md:col-span-4">
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-xl border border-emerald-200 px-3 py-2"></textarea>
                    </div>
                    <div class="md:col-span-4">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Create task →</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Tasks (<?= count($tasks) ?>)</h2>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/modules/tasks/index.php">
                        <select name="class_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">All classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>" <?= $filters['class_id'] === (string) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                    </form>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php if (empty($tasks)): ?>
                        <p class="text-sm text-slate-500 md:col-span-2 xl:col-span-3">No tasks yet.</p>
                    <?php endif; ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="rounded-2xl border border-emerald-100 bg-white p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-slate-800"><?= htmlspecialchars($task['title']) ?></h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"><?= (int) $task['total_marks'] ?> marks</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= htmlspecialchars($task['class_name'] ?: 'All classes') ?> • <?= htmlspecialchars($task['subject_name'] ?: 'All subjects') ?>
                                • <?= htmlspecialchars($task['teacher_name'] ?: '—') ?>
                            </p>
                            <?php if (!empty($task['description'])): ?>
                                <p class="mt-3 text-sm text-slate-600"><?= htmlspecialchars($task['description']) ?></p>
                            <?php endif; ?>
                            <p class="mt-3 text-xs text-slate-500">Marked: <?= (int) $task['marked_count'] ?> student<?= (int) $task['marked_count'] === 1 ? '' : 's' ?> • Due <?= htmlspecialchars($task['due_date'] ?: '—') ?></p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="<?= appBaseUrl() ?>/modules/tasks/mark.php?id=<?= (int) $task['id'] ?>" class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Mark / Review</a>
                                <form method="post" action="<?= appBaseUrl() ?>/modules/tasks/index.php" onsubmit="return confirm('Delete this task and all its marks?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
