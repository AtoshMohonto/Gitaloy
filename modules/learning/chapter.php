<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/learning_db.php';

requirePermission('learning.view');

$chapterId = (int) ($_GET['id'] ?? 0);
$chapter = $chapterId ? getChapterById($chapterId) : null;
if (!$chapter || (!$chapter['is_active'] && !hasPermission('learning.manage'))) {
    header('Location: ' . appBaseUrl() . '/modules/learning/index.php');
    exit;
}

$levels = getLevelsForChapter($chapterId);
$student = isStudent() ? getStudentByUserId((int) $_SESSION['user_id']) : null;
$progress = [];
if ($student) {
    ensureProgressRows((int) $student['id'], $chapterId);
    $progress = getStudentProgressForChapter((int) $student['id'], $chapterId);
}

$pageTitle = $chapter['title'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-4xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/learning/index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>All chapters</a>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="border-b border-emerald-100 px-6 py-5">
                    <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-emerald-600">
                        <span><?= htmlspecialchars($chapter['class_name'] ?: 'Any class') ?></span>
                        <span>&middot;</span>
                        <span><?= htmlspecialchars($chapter['subject_name'] ?: 'General') ?></span>
                        <?php if (!$chapter['is_active']): ?><span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">Hidden</span><?php endif; ?>
                    </div>
                    <h1 class="mt-1 text-xl font-extrabold text-slate-900"><?= htmlspecialchars($chapter['title']) ?></h1>
                </header>
                <?php if (!empty($chapter['concept_card_body'])): ?>
                    <div class="px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">🎴 Concept card</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700"><?= htmlspecialchars($chapter['concept_card_body']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (hasPermission('learning.manage')): ?>
                    <div class="border-t border-emerald-100 px-6 py-4">
                        <a href="<?= appBaseUrl() ?>/modules/learning/manage_questions.php?chapter_id=<?= (int) $chapter['id'] ?>" class="inline-flex items-center gap-2 text-xs font-bold text-emerald-700 hover:text-emerald-900"><i data-lucide="pencil" class="h-3.5 w-3.5"></i>Manage this chapter's levels &amp; questions</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="space-y-3">
                <?php foreach ($levels as $level):
                    $levelNo = (int) $level['level_no'];
                    $status = $progress[$levelNo]['status'] ?? ($levelNo === 1 ? 'unlocked' : 'locked');
                    $isLocked = $status === 'locked' && $student;
                    $isCompleted = $status === 'completed';
                    $icon = match ($levelNo) { 1 => '🎴', 2 => '🎯', 3 => '🧩', 4 => '⚔️', default => '📝' };
                ?>
                    <div class="flex items-center gap-4 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm <?= $isLocked ? 'opacity-60' : '' ?>">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl <?= $isCompleted ? 'bg-emerald-600' : 'bg-emerald-900' ?> text-xl text-white"><?= $isLocked ? '🔒' : $icon ?></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-600">Level <?= $levelNo ?></p>
                            <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($level['name']) ?></h3>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($level['description'] ?? '') ?></p>
                        </div>
                        <?php if ($isCompleted): ?>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">✓ Completed<?php if (!empty($progress[$levelNo]['score'])): ?> &middot; <?= (int) $progress[$levelNo]['score'] ?>%<?php endif; ?></span>
                        <?php elseif ($isLocked): ?>
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">Locked</span>
                        <?php elseif ($student): ?>
                            <a href="<?= appBaseUrl() ?>/modules/learning/play.php?level_id=<?= (int) $level['id'] ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">Play<i data-lucide="play" class="h-3.5 w-3.5"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
