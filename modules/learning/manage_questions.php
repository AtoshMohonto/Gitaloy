<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/learning_db.php';

requirePermission('learning.manage');

$chapterId = (int) ($_GET['chapter_id'] ?? 0);
$chapter = $chapterId ? getChapterById($chapterId) : null;
if (!$chapter) {
    header('Location: ' . appBaseUrl() . '/modules/learning/manage.php');
    exit;
}

$levels = getLevelsForChapter($chapterId);
$levelNo = (int) ($_GET['level'] ?? 2);
if ($levelNo < 2 || $levelNo > 5) {
    $levelNo = 2;
}
$currentLevel = null;
foreach ($levels as $lv) {
    if ((int) $lv['level_no'] === $levelNo) {
        $currentLevel = $lv;
        break;
    }
}

$success = null;
$error = null;
$editQuestion = null;

if ($currentLevel && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $data = [
                'level_id' => (int) $currentLevel['id'],
                'question_text' => trim($_POST['question_text'] ?? ''),
                'option_a' => trim($_POST['option_a'] ?? ''),
                'option_b' => trim($_POST['option_b'] ?? ''),
                'option_c' => trim($_POST['option_c'] ?? ''),
                'option_d' => trim($_POST['option_d'] ?? ''),
                'correct_option' => $_POST['correct_option'] ?? 'a',
                'created_by' => (int) ($_SESSION['user_id'] ?? 0),
            ];
            if ($data['question_text'] === '' || $data['option_a'] === '' || $data['option_b'] === '' || $data['option_c'] === '' || $data['option_d'] === '') {
                $error = 'Please fill in the question and all four options.';
            } elseif (!in_array($data['correct_option'], ['a', 'b', 'c', 'd'], true)) {
                $error = 'Please choose a valid correct option.';
            } elseif ($id > 0) {
                updateQuestion($id, $data);
                $success = 'Question updated.';
            } else {
                createQuestion($data);
                $success = 'Question added.';
            }
            logActivity('Saved learning question for level #' . $currentLevel['id'], 'learning');
        } elseif ($action === 'delete') {
            deleteQuestion((int) ($_POST['id'] ?? 0));
            $success = 'Question deleted.';
        }
    }
}

if ($currentLevel && isset($_GET['edit'])) {
    $editQuestion = getQuestionById((int) $_GET['edit']);
}

$questions = $currentLevel ? getQuestionsForLevel((int) $currentLevel['id']) : [];

$pageTitle = 'Manage: ' . $chapter['title'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-5xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/learning/manage.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>All chapters</a>

            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
                <div class="relative z-10 px-6 py-8 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Content authoring</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($chapter['title']) ?></h1>
                    <p class="mt-2 text-sm text-emerald-100/80">Level 1 (Concept Explorer) uses the chapter's concept card, edited from the chapter form — no questions needed there.</p>
                </div>
            </section>

            <?php if ($success !== null): ?><div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error !== null): ?><div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="flex flex-wrap gap-2">
                <?php foreach ($levels as $lv): $n = (int) $lv['level_no']; if ($n === 1) continue; ?>
                    <a href="<?= appBaseUrl() ?>/modules/learning/manage_questions.php?chapter_id=<?= $chapterId ?>&level=<?= $n ?>" class="rounded-xl border px-4 py-2 text-sm font-bold <?= $n === $levelNo ? 'border-emerald-900 bg-emerald-900 text-white' : 'border-emerald-200 bg-white text-emerald-800 hover:bg-emerald-50' ?>">Level <?= $n ?> — <?= htmlspecialchars($lv['name']) ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($currentLevel): ?>
            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-5 py-4"><h2 class="text-base font-bold text-slate-800"><?= $editQuestion ? 'Edit question' : 'Add question' ?></h2></header>
                <form method="post" class="p-5 space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $editQuestion ? (int) $editQuestion['id'] : 0 ?>">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Question</label>
                        <textarea name="question_text" rows="2" required class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm"><?= htmlspecialchars($editQuestion['question_text'] ?? '') ?></textarea>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Option <?= strtoupper($opt) ?></label>
                                <input type="text" name="option_<?= $opt ?>" value="<?= htmlspecialchars($editQuestion['option_' . $opt] ?? '') ?>" required class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Correct option</label>
                        <select name="correct_option" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($editQuestion['correct_option'] ?? 'a') === $opt ? 'selected' : '' ?>>Option <?= strtoupper($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                        <?php if ($editQuestion): ?><a href="<?= appBaseUrl() ?>/modules/learning/manage_questions.php?chapter_id=<?= $chapterId ?>&level=<?= $levelNo ?>" class="rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-emerald-50">Cancel</a><?php endif; ?>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i data-lucide="save" class="h-4 w-4"></i><?= $editQuestion ? 'Save changes' : 'Add question' ?></button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-5 py-4"><h2 class="text-base font-bold text-slate-800">Questions (<?= count($questions) ?>)</h2></header>
                <div class="divide-y divide-emerald-50">
                    <?php foreach ($questions as $q): ?>
                        <div class="flex flex-wrap items-center gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($q['question_text']) ?></p>
                                <p class="text-xs text-slate-500">Correct: Option <?= strtoupper($q['correct_option']) ?> — <?= htmlspecialchars($q['option_' . $q['correct_option']]) ?></p>
                            </div>
                            <a href="<?= appBaseUrl() ?>/modules/learning/manage_questions.php?chapter_id=<?= $chapterId ?>&level=<?= $levelNo ?>&edit=<?= (int) $q['id'] ?>" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-50">Edit</a>
                            <form method="post" class="inline" onsubmit="return confirm('Delete this question?');"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $q['id'] ?>"><button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Delete</button></form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?><p class="p-5 text-sm text-slate-500">No questions yet for this level.</p><?php endif; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
