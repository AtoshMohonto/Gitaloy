<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$base = appBaseUrl();
$settings = getSettings();

$heroTitle = $settings['hero_title'] ?? 'Free education for village children';
$heroSubtitle = $settings['hero_subtitle'] ?? 'Students gather at local study centers every Friday for lessons, attendance, fees, and progress tracking — all in one place.';
$heroImage = $settings['hero_image'] ?? '';
$notice = $settings['notice'] ?? '';
$noticeActive = ($settings['notice_active'] ?? '1') === '1';

$features = [
    ['users', 'Students', 'Registration with auto student IDs, guardian contacts, documents, and instant search.'],
    ['calendar-check', 'Attendance & sessions', 'Friday/weekly sessions with per-student Present/Absent marking and same-step fee capture.'],
    ['wallet', 'Fees & expenses', 'Per-head Friday or monthly fees, payments, receipts, and center expenses.'],
    ['book-open', 'Syllabus', 'Per class, subject, and year syllabuses that guide every teaching session.'],
    ['clipboard-list', 'Tasks & marks', 'Assignments with total marks, per-student results, and completion tracking.'],
    ['trending-up', 'Progress', 'Combined attendance + performance views for every student.'],
    ['package', 'Distribution', 'Zone-wise plans for books, notebooks, pens, and bags with per-student records.'],
    ['printer', 'Reports', 'Printable attendance sheets, report cards, fee receipts, and distribution reports.'],
];

$roles = [
    ['shield-check', 'Admin', 'Global', 'Everything — geography, centers, users, setup, and site content.'],
    ['map', 'Divisional Manager', 'One division', 'All modules across their division.'],
    ['map-pin', 'District Manager', 'One district', 'All modules across their district.'],
    ['calculator', 'Accountant', 'One district', 'Fees, expenses, attendance, students, and reports.'],
    ['presentation', 'Teacher', 'One center', 'Their center’s students, sessions, attendance, fees, and tasks.'],
    ['user', 'Student', 'Self', 'Own profile, progress, and report card via the student portal.'],
];

$pageTitle = 'Gitaloy — Village Education Program';
require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-1 bg-emerald-50/40">
    <main>
        <section class="relative overflow-hidden bg-emerald-950">
            <div class="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-emerald-600/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/3 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="relative z-10 mx-auto grid max-w-7xl gap-10 px-6 py-16 sm:px-8 lg:grid-cols-2 lg:items-center lg:py-24">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Village Education Program
                    </p>
                    <h1 class="mt-5 text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl"><?= htmlspecialchars($heroTitle) ?></h1>
                    <p class="mt-5 max-w-xl text-base leading-relaxed text-emerald-100/80 sm:text-lg"><?= htmlspecialchars($heroSubtitle) ?></p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <a href="<?= $base ?>/modules/dashboard/index.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-emerald-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400">
                                <i data-lucide="layout-dashboard" class="h-5 w-5"></i>Go to dashboard
                            </a>
                            <a href="<?= $base ?>/modules/auth/login.php?logout=1" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/40 px-5 py-3 text-sm font-bold text-emerald-100 transition hover:bg-emerald-500/10">
                                <i data-lucide="log-out" class="h-5 w-5"></i>Sign out
                            </a>
                        <?php else: ?>
                            <a href="<?= $base ?>/modules/auth/login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-emerald-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400">
                                <i data-lucide="log-in" class="h-5 w-5"></i>Sign in
                            </a>
                            <a href="<?= $base ?>/modules/auth/register.php" class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/40 px-5 py-3 text-sm font-bold text-emerald-100 transition hover:bg-emerald-500/10">
                                <i data-lucide="user-plus" class="h-5 w-5"></i>Create an account
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-emerald-200/70">
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="shield-check" class="h-4 w-4"></i>6 role levels</span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="calendar-check" class="h-4 w-4"></i>Weekly Friday sessions</span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="printer" class="h-4 w-4"></i>Printable reports</span>
                    </div>
                </div>
                <div class="relative">
                    <?php if (!empty($heroImage)): ?>
                        <img src="<?= $base ?>/<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($heroTitle) ?>" class="h-64 w-full rounded-2xl object-cover shadow-2xl ring-1 ring-white/10 sm:h-80 lg:h-96">
                    <?php else: ?>
                        <div class="grid h-64 w-full place-items-center rounded-2xl border border-emerald-500/20 bg-emerald-500/5 shadow-2xl sm:h-80 lg:h-96">
                            <div class="text-center">
                                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-500/15 text-3xl">🌾</span>
                                <p class="mt-4 text-sm font-bold text-emerald-100">Gitaloy</p>
                                <p class="mt-1 text-xs text-emerald-200/60">A picture can be added from Admin → Frontend Content.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if ($noticeActive && trim($notice) !== ''): ?>
            <section class="mx-auto max-w-7xl px-6 pt-8 sm:px-8">
                <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-amber-500 text-white"><i data-lucide="megaphone" class="h-4 w-4"></i></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-600">Notice</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-amber-900"><?= htmlspecialchars($notice) ?></p>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">What we provide</p>
                <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Everything the program needs in one place</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-500">From registering students to printing report cards — the system covers the full weekly rhythm of the program.</p>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($features as $f): [$icon, $title, $desc] = $f; ?>
                    <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="<?= htmlspecialchars($icon) ?>" class="h-5 w-5"></i></span>
                        <h3 class="mt-4 text-sm font-bold text-slate-900"><?= htmlspecialchars($title) ?></h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-slate-500"><?= htmlspecialchars($desc) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bg-white">
            <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Who uses it</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">A role for everyone in the program</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-500">Each role sees only what it needs, scoped to its division, district, or center.</p>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($roles as $r): [$icon, $name, $scope, $desc] = $r; ?>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-emerald-700 shadow-sm"><i data-lucide="<?= htmlspecialchars($icon) ?>" class="h-5 w-5"></i></span>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($name) ?></h3>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600"><?= htmlspecialchars($scope) ?></p>
                                </div>
                            </div>
                            <p class="mt-3 text-xs leading-relaxed text-slate-500"><?= htmlspecialchars($desc) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
            <div class="relative overflow-hidden rounded-2xl bg-emerald-900 px-6 py-10 text-center sm:px-10">
                <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-emerald-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-emerald-500/20 blur-3xl"></div>
                <h2 class="relative z-10 text-xl font-extrabold text-white sm:text-2xl">Join the program</h2>
                <p class="relative z-10 mx-auto mt-2 max-w-xl text-sm leading-relaxed text-emerald-100/80">Sign in as a staff member or create a student account to follow your own progress.</p>
                <div class="relative z-10 mt-6 flex flex-wrap justify-center gap-3">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="<?= $base ?>/modules/dashboard/index.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-emerald-950 transition hover:bg-emerald-400"><i data-lucide="layout-dashboard" class="h-5 w-5"></i>Go to dashboard</a>
                    <?php else: ?>
                        <a href="<?= $base ?>/modules/auth/login.php" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-emerald-950 transition hover:bg-emerald-400"><i data-lucide="log-in" class="h-5 w-5"></i>Sign in</a>
                        <a href="<?= $base ?>/modules/auth/register.php" class="inline-flex items-center gap-2 rounded-xl border border-emerald-400/40 px-5 py-3 text-sm font-bold text-emerald-100 transition hover:bg-emerald-500/10"><i data-lucide="user-plus" class="h-5 w-5"></i>Create an account</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
