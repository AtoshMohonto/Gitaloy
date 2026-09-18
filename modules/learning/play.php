<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/learning_db.php';

requirePermission('learning.play');
requireRole(ROLE_STUDENT);

$student = getStudentByUserId((int) $_SESSION['user_id']);
if (!$student) {
    header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
    exit;
}

$levelId = (int) ($_GET['level_id'] ?? 0);
$level = $levelId ? getLevelById($levelId) : null;
if (!$level) {
    header('Location: ' . appBaseUrl() . '/modules/learning/index.php');
    exit;
}

ensureProgressRows((int) $student['id'], (int) $level['chapter_id']);
$progressRow = getProgressRow((int) $student['id'], $levelId);
if (($progressRow['status'] ?? 'locked') === 'locked') {
    header('Location: ' . appBaseUrl() . '/modules/learning/chapter.php?id=' . (int) $level['chapter_id']);
    exit;
}

$levelNo = (int) $level['level_no'];
$questions = $levelNo > 1 ? getQuestionsForLevel($levelId) : [];
$result = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($levelNo === 1) {
        $result = markConceptRead((int) $student['id'], $level);
        $result['type'] = 'concept';
    } else {
        $answers = [];
        foreach ($_POST['answer'] ?? [] as $qid => $opt) {
            $answers[(int) $qid] = in_array($opt, ['a', 'b', 'c', 'd'], true) ? $opt : null;
        }
        $result = submitLevelAttempt((int) $student['id'], $level, $answers);
        $result['type'] = 'quiz';
    }
}

$pageTitle = $level['name'] . ' — ' . $level['chapter_title'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-3xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/learning/chapter.php?id=<?= (int) $level['chapter_id'] ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><i data-lucide="arrow-left" class="h-4 w-4"></i><?= htmlspecialchars($level['chapter_title']) ?></a>

            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-6 py-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-600">Level <?= $levelNo ?></p>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900"><?= htmlspecialchars($level['name']) ?></h1>
                </header>

                <?php if ($result !== null): ?>
                    <div class="px-6 py-6 text-center">
                        <?php if ($result['type'] === 'concept'): ?>
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-2xl">✅</span>
                            <p class="mt-3 text-lg font-bold text-slate-900">Concept marked as understood!</p>
                            <?php if ($result['xp_awarded'] > 0): ?><p class="mt-1 text-sm font-semibold text-emerald-700">+<?= (int) $result['xp_awarded'] ?> XP</p><?php endif; ?>
                        <?php else: ?>
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl <?= $result['passed'] ? 'bg-emerald-100' : 'bg-amber-100' ?> text-2xl"><?= $result['passed'] ? '🏆' : '💪' ?></span>
                            <p class="mt-3 text-lg font-bold text-slate-900"><?= $result['correct'] ?> / <?= $result['total'] ?> correct (<?= $result['score_pct'] ?>%)</p>
                            <p class="mt-1 text-sm font-semibold text-emerald-700">+<?= (int) $result['xp_awarded'] ?> XP</p>
                            <p class="mt-2 text-sm text-slate-500"><?= $result['passed'] ? 'Great job — you passed this level!' : 'Not quite 60% yet — mistakes are part of learning. Try again!' ?></p>
                        <?php endif; ?>
                        <?php if (!empty($result['badges'])): ?>
                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                <?php foreach ($result['badges'] as $badge): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">🏅 New badge: <?= htmlspecialchars($badge['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            <?php if ($result['type'] === 'quiz' && !$result['passed']): ?>
                                <a href="<?= appBaseUrl() ?>/modules/learning/play.php?level_id=<?= $levelId ?>" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Retry</a>
                            <?php endif; ?>
                            <a href="<?= appBaseUrl() ?>/modules/learning/chapter.php?id=<?= (int) $level['chapter_id'] ?>" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-50">Back to chapter</a>
                        </div>
                    </div>
                <?php elseif ($levelNo === 1): ?>
                    <div class="px-6 py-6">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">🎴 Concept card</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700"><?= htmlspecialchars($level['concept_card_body'] ?: 'No concept text added yet.') ?></p>
                        <form method="post" class="mt-6">
                            <?= csrfField() ?>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700"><i data-lucide="check" class="h-4 w-4"></i>I understand this concept</button>
                        </form>
                    </div>
                <?php elseif (empty($questions)): ?>
                    <div class="px-6 py-10 text-center text-sm text-slate-500">No questions added for this level yet — check back soon.</div>
                <?php else: ?>
                    <form method="post" class="divide-y divide-emerald-50">
                        <?= csrfField() ?>
                        <?php foreach ($questions as $i => $q): ?>
                            <div class="px-6 py-5">
                                <p class="text-sm font-bold text-slate-900"><?= $i + 1 ?>. <?= htmlspecialchars($q['question_text']) ?></p>
                                <div class="mt-3 space-y-2">
                                    <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                                        <label class="flex items-center gap-2.5 rounded-xl border border-emerald-100 px-3 py-2.5 text-sm text-slate-700 transition hover:bg-emerald-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                                            <input type="radio" name="answer[<?= (int) $q['id'] ?>]" value="<?= $opt ?>" required class="text-emerald-700 focus:ring-emerald-200">
                                            <?= htmlspecialchars($q['option_' . $opt]) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="px-6 py-5">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700"><i data-lucide="send" class="h-4 w-4"></i>Submit answers</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
