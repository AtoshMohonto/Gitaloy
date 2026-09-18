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

$earned = getStudentBadges((int) $student['id']);
$earnedCodes = array_column($earned, 'code');
$pdo = getDbConnection();
$allBadges = $pdo->query('SELECT * FROM lc_badges')->fetchAll();

$pageTitle = 'My Badges';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-3xl mx-auto space-y-6">
            <a href="<?= appBaseUrl() ?>/modules/learning/index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><i data-lucide="arrow-left" class="h-4 w-4"></i>Step-by-Step Learning</a>
            <section class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-extrabold text-slate-900">My Badges</h1>
                <p class="mt-1 text-sm text-slate-500">Badges motivate learning, not ranking — earn them at your own pace.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <?php foreach ($allBadges as $badge): $has = in_array($badge['code'], $earnedCodes, true); ?>
                        <div class="rounded-2xl border p-5 text-center <?= $has ? 'border-amber-200 bg-amber-50' : 'border-slate-100 bg-slate-50 opacity-60' ?>">
                            <span class="text-3xl"><?= $has ? '🏅' : '🔒' ?></span>
                            <p class="mt-2 text-sm font-bold text-slate-900"><?= htmlspecialchars($badge['name']) ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($badge['description'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
