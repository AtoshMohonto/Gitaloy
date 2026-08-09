<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/syllabus_db.php';

requirePermission('syllabus.view');

$success = null;
$error = null;

$editSyllabus = null;
$formData = [
    'id' => 0, 'class_id' => '', 'subject_id' => '', 'year_id' => (string) activeYearId(),
    'title' => '', 'description' => '', 'term' => '',
];

if (isset($_GET['edit'])) {
    $editSyllabus = getSyllabuses([]);
    foreach ($editSyllabus as $sy) {
        if ((int) $sy['id'] === (int) $_GET['edit']) {
            $editSyllabus = $sy;
            $formData = [
                'id' => (int) $sy['id'], 'class_id' => $sy['class_id'], 'subject_id' => $sy['subject_id'],
                'year_id' => $sy['year_id'], 'title' => $sy['title'], 'description' => $sy['description'], 'term' => $sy['term'],
            ];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $data = [
        'class_id' => trim($_POST['class_id'] ?? ''),
        'subject_id' => trim($_POST['subject_id'] ?? ''),
        'year_id' => trim($_POST['year_id'] ?? (string) activeYearId()),
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'term' => trim($_POST['term'] ?? ''),
    ];

    if ($data['title'] === '') {
        $error = 'Syllabus title is required.';
    } else {
        $data['class_id'] = $data['class_id'] !== '' ? (int) $data['class_id'] : null;
        $data['subject_id'] = $data['subject_id'] !== '' ? (int) $data['subject_id'] : null;
        $data['year_id'] = (int) $data['year_id'];
        $data['term'] = $data['term'] !== '' ? $data['term'] : null;
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;

        if ($action === 'update') {
            updateSyllabus((int) ($_POST['id'] ?? 0), $data);
            $success = 'Syllabus updated.';
            logActivity('Updated syllabus: ' . $data['title'], 'syllabus');
        } else {
            createSyllabus($data);
            $success = 'Syllabus added.';
            logActivity('Added syllabus: ' . $data['title'], 'syllabus');
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['action'] ?? '') === 'delete') {
    deleteSyllabus((int) ($_POST['syllabus_id'] ?? 0));
    $success = 'Syllabus deleted.';
    logActivity('Deleted syllabus #' . (int) ($_POST['syllabus_id'] ?? 0), 'syllabus');
}

$filters = [
    'class_id' => trim($_GET['class_id'] ?? ''),
    'subject_id' => trim($_GET['subject_id'] ?? ''),
    'year_id' => trim($_GET['year_id'] ?? (string) activeYearId()),
];
$syllabuses = getSyllabuses(array_filter($filters, fn($v) => $v !== ''));
$classes = getClasses();
$subjects = getSubjects();
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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Curriculum</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Syllabus</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Add and organize syllabus topics per class and subject, term by term.</p>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900"><?= $editSyllabus ? 'Edit syllabus' : 'Add syllabus topic' ?></h2>
                <form class="mt-4 grid gap-4 md:grid-cols-4" method="post" action="<?= appBaseUrl() ?>/modules/syllabus/index.php">
                    <input type="hidden" name="action" value="<?= $editSyllabus ? 'update' : 'create' ?>">
                    <?php if ($editSyllabus): ?>
                        <input type="hidden" name="id" value="<?= (int) $editSyllabus['id'] ?>">
                    <?php endif; ?>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($formData['title']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Class</label>
                        <select name="class_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">Any class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>" <?= $formData['class_id'] == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Subject</label>
                        <select name="subject_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <option value="">Any subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= (int) $subject['id'] ?>" <?= $formData['subject_id'] == $subject['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Term</label>
                        <input type="text" name="term" value="<?= htmlspecialchars($formData['term']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2" placeholder="e.g. Term 1">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Year</label>
                        <select name="year_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= (int) $year['id'] ?>" <?= (string) $year['id'] === $formData['year_id'] ? 'selected' : '' ?>><?= htmlspecialchars($year['name']) ?><?= $year['is_active'] ? ' (active)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <textarea name="description" rows="2" class="w-full rounded-xl border border-emerald-200 px-3 py-2"><?= htmlspecialchars($formData['description']) ?></textarea>
                    </div>
                    <div class="md:col-span-4">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white"><?= $editSyllabus ? 'Save changes' : 'Add topic' ?></button>
                        <?php if ($editSyllabus): ?>
                            <a href="<?= appBaseUrl() ?>/modules/syllabus/index.php" class="ml-2 rounded-full border border-emerald-200 px-5 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">Topics (<?= count($syllabuses) ?>)</h2>
                    <form class="flex flex-wrap items-center gap-2" method="get" action="<?= appBaseUrl() ?>/modules/syllabus/index.php">
                        <select name="class_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">All classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>" <?= $filters['class_id'] === (string) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="subject_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="">All subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= (int) $subject['id'] ?>" <?= $filters['subject_id'] === (string) $subject['id'] ? 'selected' : '' ?>><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
                    </form>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php if (empty($syllabuses)): ?>
                        <p class="text-sm text-slate-500 md:col-span-2 xl:col-span-3">No syllabus topics found.</p>
                    <?php endif; ?>
                    <?php foreach ($syllabuses as $syllabus): ?>
                        <div class="rounded-2xl border border-emerald-100 bg-white p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-slate-800"><?= htmlspecialchars($syllabus['title']) ?></h3>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><?= htmlspecialchars($syllabus['term'] ?: '—') ?></span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= htmlspecialchars($syllabus['class_name'] ?: 'All classes') ?> • <?= htmlspecialchars($syllabus['subject_name'] ?: 'All subjects') ?> • <?= htmlspecialchars($syllabus['year_name'] ?: '—') ?>
                            </p>
                            <?php if (!empty($syllabus['description'])): ?>
                                <p class="mt-3 text-sm text-slate-600"><?= htmlspecialchars($syllabus['description']) ?></p>
                            <?php endif; ?>
                            <div class="mt-4 flex gap-2">
                                <a href="<?= appBaseUrl() ?>/modules/syllabus/index.php?edit=<?= (int) $syllabus['id'] ?>" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700">Edit</a>
                                <form method="post" action="<?= appBaseUrl() ?>/modules/syllabus/index.php" onsubmit="return confirm('Delete this topic?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="syllabus_id" value="<?= (int) $syllabus['id'] ?>">
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
