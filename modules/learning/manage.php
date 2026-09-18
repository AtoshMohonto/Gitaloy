<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/learning_db.php';

requirePermission('learning.manage');

$success = null;
$error = null;
$editChapter = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = [
                'class_id' => (int) ($_POST['class_id'] ?? 0),
                'subject_id' => (int) ($_POST['subject_id'] ?? 0),
                'title' => trim($_POST['title'] ?? ''),
                'concept_card_body' => trim($_POST['concept_card_body'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'created_by' => (int) ($_SESSION['user_id'] ?? 0),
            ];
            if ($data['title'] === '') {
                $error = 'A chapter title is required.';
            } elseif ($id > 0) {
                updateChapter($id, $data);
                logActivity('Updated learning chapter #' . $id, 'learning');
                $success = 'Chapter updated.';
            } else {
                $newId = createChapter($data);
                logActivity('Added learning chapter #' . $newId, 'learning');
                $success = 'Chapter created with its 5 levels — add questions from "Manage levels & questions".';
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            deleteChapter($id);
            logActivity('Deleted learning chapter #' . $id, 'learning');
            $success = 'Chapter deleted.';
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            toggleChapter($id);
            $success = 'Chapter visibility changed.';
        }
    }
}

if (isset($_GET['edit'])) {
    $editChapter = getChapterById((int) $_GET['edit']);
}

$chapters = getChapters(null, null, false);
$classes = getClasses();
$subjects = getSubjects();

$pageTitle = 'Manage Step-by-Step Learning';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/learning/index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>Step-by-Step Learning</a>

            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
                <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
                <div class="relative z-10 px-6 py-8 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Content authoring</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Chapters</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Each chapter gets its 5 fixed levels automatically. Add questions for levels 2-5 from "Manage levels &amp; questions".</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800"><?= $editChapter ? 'Edit chapter' : 'Add chapter' ?></h2>
                </header>
                <form method="post" class="p-5 space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $editChapter ? (int) $editChapter['id'] : 0 ?>">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                            <select name="class_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                <option value="0">— Any —</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= ($editChapter['class_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                            <select name="subject_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                                <option value="0">— Any —</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" <?= ($editChapter['subject_id'] ?? null) == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sort order</label>
                            <input type="number" name="sort_order" value="<?= (int) ($editChapter['sort_order'] ?? 0) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Chapter title</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($editChapter['title'] ?? '') ?>" required class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Concept card (Level 1 content)</label>
                            <textarea name="concept_card_body" rows="4" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm"><?= htmlspecialchars($editChapter['concept_card_body'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                        <?php if ($editChapter): ?>
                            <a href="<?= appBaseUrl() ?>/modules/learning/manage.php" class="rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-emerald-50">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i data-lucide="save" class="h-4 w-4"></i><?= $editChapter ? 'Save changes' : 'Add chapter' ?></button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-5 py-4"><h2 class="text-base font-bold text-slate-800">All chapters (<?= count($chapters) ?>)</h2></header>
                <div class="divide-y divide-emerald-50">
                    <?php foreach ($chapters as $ch): ?>
                        <div class="flex flex-wrap items-center gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($ch['title']) ?> <?php if (!$ch['is_active']): ?><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">Hidden</span><?php endif; ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($ch['class_name'] ?: 'Any class') ?> &middot; <?= htmlspecialchars($ch['subject_name'] ?: 'General') ?></p>
                            </div>
                            <a href="<?= appBaseUrl() ?>/modules/learning/manage_questions.php?chapter_id=<?= (int) $ch['id'] ?>" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-50">Levels &amp; questions</a>
                            <a href="<?= appBaseUrl() ?>/modules/learning/manage.php?edit=<?= (int) $ch['id'] ?>" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-50">Edit</a>
                            <form method="post" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $ch['id'] ?>"><button class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100"><?= $ch['is_active'] ? 'Hide' : 'Show' ?></button></form>
                            <form method="post" class="inline" onsubmit="return confirm('Delete this chapter and all its levels/questions?');"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $ch['id'] ?>"><button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button></form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($chapters)): ?><p class="p-5 text-sm text-slate-500">No chapters yet — add one above.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
