<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/updates_db.php';

requirePermission('updates.view');

$pdo = getDbConnection();
$canPost = hasPermission('updates.manage');
$success = null;
$error = null;

$isTeacher = isTeacher();
$teacherCenterId = $isTeacher ? (int) (currentUser()['center_id'] ?? 0) : null;

// Scoped center list for the post form
$centerFilter = getCenterScopeFilter();
$centerJoins = getCenterScopeJoins();
$stmt = $pdo->query("SELECT c.id, c.name, v.name AS village_name
                     FROM centers c $centerJoins WHERE $centerFilter ORDER BY c.name");
$scopedCenters = $stmt->fetchAll();
$classes = getClasses();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canPost) {
    $action = $_POST['action'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $updateType = trim($_POST['update_type'] ?? 'Daily');
        $customLabel = trim($_POST['custom_label'] ?? '');
        $centerId = $isTeacher ? $teacherCenterId : (int) ($_POST['center_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);

        if ($title === '') {
            $error = 'A title is required.';
        } elseif (!in_array($updateType, ['Daily', 'Weekly', 'Custom'], true)) {
            $error = 'Please choose a valid update type.';
        } elseif ($centerId <= 0 || !centerInScope($centerId)) {
            $error = 'Please choose a valid study center.';
        } elseif ($updateType === 'Custom' && $customLabel === '') {
            $error = 'Please give the custom update a label (e.g. "First Sunday study plan").';
        } else {
            $photo = null;
            if (!empty($_FILES['photo']['name'])) {
                try {
                    $photo = handlePhotoUpload($_FILES['photo']);
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
            if ($error === null) {
                createClassUpdate([
                    'teacher_id' => (int) ($_SESSION['user_id'] ?? 0),
                    'center_id' => $centerId,
                    'class_id' => $classId > 0 ? $classId : null,
                    'title' => $title,
                    'body' => $body,
                    'photo' => $photo,
                    'update_type' => $updateType,
                    'custom_label' => $updateType === 'Custom' ? $customLabel : null,
                ]);
                logActivity('Posted class update: ' . $title, 'updates');
                $success = 'Class update posted.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if (getClassUpdateById($id)) {
            toggleClassUpdate($id);
            logActivity('Toggled class update #' . $id, 'updates');
            $success = 'Update status changed.';
        } else {
            $error = 'Update not found or outside your area.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if (getClassUpdateById($id)) {
            deleteClassUpdate($id);
            logActivity('Deleted class update #' . $id, 'updates');
            $success = 'Update deleted.';
        } else {
            $error = 'Update not found or outside your area.';
        }
    }
}

$updates = getClassUpdates(50);

$pageTitle = 'Class Updates';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-5xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
                <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
                <div class="relative z-10 px-6 py-8 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Newsfeed</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Class Updates</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Share what happened in class with a daily, weekly, or custom schedule.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($canPost): ?>
                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Post a class update</h2>
                        <p class="ml-auto text-xs font-medium text-slate-400">Visible to your zone</p>
                    </header>
                    <form method="post" enctype="multipart/form-data" class="p-5 space-y-4">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-title">Title *</label>
                                <input id="up-title" name="title" type="text" required placeholder="e.g. Weekly recap — multiplication tables" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-type">Schedule</label>
                                <select id="up-type" name="update_type" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Custom">Custom</option>
                                </select>
                            </div>
                            <div id="up-custom-wrap" class="hidden">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-custom">Custom label</label>
                                <input id="up-custom" name="custom_label" type="text" placeholder="e.g. First Sunday study plan" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-center">Study center</label>
                                <?php if ($isTeacher && $teacherCenterId > 0): ?>
                                    <input type="text" value="<?= htmlspecialchars(($scopedCenters[0]['name'] ?? '') ?: 'Your center') ?>" readonly class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-slate-500">
                                    <input type="hidden" name="center_id" value="<?= (int) $teacherCenterId ?>">
                                <?php else: ?>
                                    <select id="up-center" name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                        <option value="">Select center</option>
                                        <?php foreach ($scopedCenters as $center): ?>
                                            <option value="<?= (int) $center['id'] ?>"><?= htmlspecialchars($center['name']) ?><?= !empty($center['village_name']) ? ' — ' . htmlspecialchars($center['village_name']) : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-class">Class (optional)</label>
                                <select id="up-class" name="class_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">All classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-body">Details</label>
                                <textarea id="up-body" name="body" rows="4" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="up-photo">Photo (JPG, optional)</label>
                                <input id="up-photo" name="photo" type="file" accept="image/jpeg,.jpg,.jpeg" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm text-slate-500">
                            </div>
                        </div>
                        <div class="flex items-center justify-end border-t border-emerald-100 pt-4">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i data-lucide="send" class="h-4 w-4"></i>Post update
                            </button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Recent updates</h2>
                <div class="mt-4 space-y-4">
                    <?php if (empty($updates)): ?>
                        <p class="text-sm text-slate-500">No class updates yet.</p>
                    <?php endif; ?>
                    <?php foreach ($updates as $update): ?>
                        <article class="rounded-2xl border border-emerald-100 bg-white p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-emerald-900 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white"><?= htmlspecialchars($update['update_type']) ?><?= $update['update_type'] === 'Custom' && !empty($update['custom_label']) ? ': ' . htmlspecialchars($update['custom_label']) : '' ?></span>
                                        <span class="text-xs text-slate-400"><?= htmlspecialchars(date('M j, Y g:i A', strtotime($update['created_at']))) ?></span>
                                        <?php if (!$update['is_active']): ?>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500">Hidden</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mt-2 text-base font-bold text-slate-900"><?= htmlspecialchars($update['title']) ?></h3>
                                    <?php if (!empty($update['body'])): ?>
                                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-600"><?= htmlspecialchars($update['body']) ?></p>
                                    <?php endif; ?>
                                    <p class="mt-3 text-xs text-slate-500">
                                        By <?= htmlspecialchars($update['teacher_name'] ?: 'Staff') ?> — <?= htmlspecialchars($update['center_name'] ?: 'No center') ?><?= $update['class_name'] ? ' · ' . htmlspecialchars($update['class_name']) : ' · All classes' ?>
                                    </p>
                                </div>
                                <?php if ($canPost): ?>
                                    <div class="flex shrink-0 gap-2">
                                        <form method="post" class="inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $update['id'] ?>">
                                            <button class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"><?= $update['is_active'] ? 'Hide' : 'Show' ?></button>
                                        </form>
                                        <form method="post" class="inline" onsubmit="return confirm('Delete this update?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $update['id'] ?>">
                                            <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($update['photo'])): ?>
                                <img src="<?= appBaseUrl() ?>/<?= htmlspecialchars($update['photo']) ?>" alt="" class="mt-3 h-44 w-full rounded-xl border border-emerald-100 object-cover">
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<script>
    (function () {
        var type = document.getElementById('up-type');
        var wrap = document.getElementById('up-custom-wrap');
        if (type && wrap) {
            var sync = function () {
                wrap.classList.toggle('hidden', type.value !== 'Custom');
            };
            type.addEventListener('change', sync);
            sync();
        }
    })();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
