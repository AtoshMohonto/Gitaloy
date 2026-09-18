<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/learning_db.php';

requirePermission('learning.view');

$classId = (int) ($_GET['class_id'] ?? 0);
$subjectId = (int) ($_GET['subject_id'] ?? 0);

$classes = getClasses();
$subjects = getSubjects();
$chapters = getChapters($classId ?: null, $subjectId ?: null, true);

$student = isStudent() ? getStudentByUserId((int) $_SESSION['user_id']) : null;
$chapterProgress = [];
if ($student) {
    foreach ($chapters as $ch) {
        ensureProgressRows((int) $student['id'], (int) $ch['id']);
        $prog = getStudentProgressForChapter((int) $student['id'], (int) $ch['id']);
        $completed = count(array_filter($prog, fn($p) => $p['status'] === 'completed'));
        $chapterProgress[$ch['id']] = ['completed' => $completed, 'total' => count($prog) ?: 5];
    }
}

$pageTitle = 'Step-by-Step Learning';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
                <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
                <div class="relative z-10 flex flex-wrap items-center justify-between gap-4 px-6 py-8 sm:px-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Learn Smart. Grow Steady. Succeed Surely.</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Step-by-Step Learning</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Concept first, then practice — five levels per chapter, at your own pace.</p>
                    </div>
                    <?php if ($student): ?>
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-center">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">Your XP</p>
                                <p class="mt-1 text-xl font-extrabold text-white"><?= (int) $student['xp_total'] ?></p>
                            </div>
                            <a href="<?= appBaseUrl() ?>/modules/learning/badges.php" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-center transition hover:bg-emerald-500/20">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">Badges</p>
                                <p class="mt-1 text-xl font-extrabold text-white"><?= count(getStudentBadges((int) $student['id'])) ?></p>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (hasPermission('learning.manage')): ?>
                        <a href="<?= appBaseUrl() ?>/modules/learning/manage.php" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/40 px-4 py-2.5 text-sm font-bold text-emerald-100 transition hover:bg-emerald-500/10">
                            <i data-lucide="settings" class="h-4 w-4"></i>Manage content
                        </a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <form method="get" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Class</label>
                        <select name="class_id" onchange="this.form.submit()" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="0">All classes</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= $classId === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                        <select name="subject_id" onchange="this.form.submit()" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <option value="0">All subjects</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int) $s['id'] ?>" <?= $subjectId === (int) $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </section>

            <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php if (empty($chapters)): ?>
                    <p class="text-sm text-slate-500">No chapters yet<?= hasPermission('learning.manage') ? ' — add one from Manage content.' : '.' ?></p>
                <?php endif; ?>
                <?php foreach ($chapters as $ch): ?>
                    <?php $prog = $chapterProgress[$ch['id']] ?? null; ?>
                    <a href="<?= appBaseUrl() ?>/modules/learning/chapter.php?id=<?= (int) $ch['id'] ?>" class="block rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-emerald-600">
                            <span><?= htmlspecialchars($ch['class_name'] ?: 'Any class') ?></span>
                            <span>&middot;</span>
                            <span><?= htmlspecialchars($ch['subject_name'] ?: 'General') ?></span>
                        </div>
                        <h3 class="mt-2 text-base font-bold text-slate-900"><?= htmlspecialchars($ch['title']) ?></h3>
                        <?php if ($prog): ?>
                            <div class="mt-3">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-emerald-50">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: <?= (int) round(($prog['completed'] / max(1, $prog['total'])) * 100) ?>%"></div>
                                </div>
                                <p class="mt-1.5 text-xs font-semibold text-slate-500"><?= $prog['completed'] ?> / <?= $prog['total'] ?> levels complete</p>
                            </div>
                        <?php else: ?>
                            <p class="mt-3 text-xs text-slate-500">5 levels: Concept &rarr; Practice &rarr; Skill &rarr; Logic &rarr; Exam</p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
