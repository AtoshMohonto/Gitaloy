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

$aboutTitle = $settings['about_title'] ?? 'About Gitaloy';
$aboutBody = $settings['about_body'] ?? '';
$aboutImage = $settings['about_image'] ?? '';
$aboutActive = ($settings['about_active'] ?? '1') === '1';

$stats = ($settings['stats_active'] ?? '1') === '1' ? getContentBlocks('stat', true) : [];

$programs = getContentBlocks('program', true);
$programsTitle = $settings['programs_title'] ?? 'Our Programs';
$programsSubtitle = $settings['programs_subtitle'] ?? '';
$programsActive = ($settings['programs_active'] ?? '1') === '1';

$gallery = getContentBlocks('gallery', true);
$galleryTitle = $settings['gallery_title'] ?? 'Moments From the Field';
$gallerySubtitle = $settings['gallery_subtitle'] ?? '';
$galleryActive = ($settings['gallery_active'] ?? '1') === '1';

$newsUpdates = getContentBlocks('update', true);
$updatesTitle = $settings['updates_title'] ?? 'Latest Updates';
$updatesSubtitle = $settings['updates_subtitle'] ?? '';
$updatesActive = ($settings['updates_active'] ?? '1') === '1';

$testimonials = getContentBlocks('testimonial', true);
$testimonialsTitle = $settings['testimonials_title'] ?? 'Voices From the Program';
$testimonialsSubtitle = $settings['testimonials_subtitle'] ?? '';
$testimonialsActive = ($settings['testimonials_active'] ?? '1') === '1';

$supportTitle = $settings['support_title'] ?? 'Support the Program';
$supportBody = $settings['support_body'] ?? '';
$supportBkash = $settings['support_bkash'] ?? '';
$supportBank = $settings['support_bank'] ?? '';
$supportActive = ($settings['support_active'] ?? '0') === '1';

$contactAddress = $settings['contact_address'] ?? '';
$contactEmail = $settings['contact_email'] ?? '';
$contactPhone = $settings['contact_phone'] ?? '';
$socialLinks = [
    ['facebook', $settings['social_facebook'] ?? ''],
    ['youtube', $settings['social_youtube'] ?? ''],
    ['message-circle', $settings['social_whatsapp'] ?? ''],
    ['instagram', $settings['social_instagram'] ?? ''],
];
$hasContact = $contactAddress !== '' || $contactEmail !== '' || $contactPhone !== '' || array_filter(array_column($socialLinks, 1)) !== [];

$showTopBar = true;

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
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <img src="<?= $base ?>/<?= htmlspecialchars($settings['site_logo']) ?>" alt="Gitaloy logo" class="mx-auto h-20 w-20 rounded-2xl object-cover shadow-xl ring-2 ring-emerald-500/30">
                                <?php else: ?>
                                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-500/15 text-3xl">🌾</span>
                                <?php endif; ?>
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

        <?php if (!empty($stats)): ?>
            <section class="mx-auto max-w-7xl px-6 pt-10 sm:px-8">
                <div class="grid gap-4 rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm sm:grid-cols-2 sm:p-8 lg:grid-cols-<?= min(4, max(2, count($stats))) ?>">
                    <?php $statCount = count($stats); ?>
                    <?php foreach ($stats as $statIndex => $stat): ?>
                        <div class="flex items-center gap-3 <?= $statIndex < $statCount - 1 ? 'sm:border-r sm:border-emerald-50' : '' ?>">
                            <?php if (!empty($stat['icon'])): ?>
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="<?= htmlspecialchars($stat['icon']) ?>" class="h-5 w-5"></i></span>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <p class="text-xl font-extrabold text-emerald-900 sm:text-2xl"><?= htmlspecialchars($stat['stat_value'] ?? '') ?></p>
                                <p class="truncate text-xs font-semibold text-slate-500"><?= htmlspecialchars($stat['title'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($aboutActive && trim($aboutBody) !== ''): ?>
            <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                <div class="grid items-center gap-10 lg:grid-cols-2">
                    <div class="<?= $aboutImage === '' ? 'lg:order-2' : '' ?>">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">About us</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl"><?= htmlspecialchars($aboutTitle) ?></h2>
                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-600"><?= htmlspecialchars($aboutBody) ?></p>
                    </div>
                    <?php if ($aboutImage !== ''): ?>
                        <img src="<?= $base ?>/<?= htmlspecialchars($aboutImage) ?>" alt="<?= htmlspecialchars($aboutTitle) ?>" class="h-64 w-full rounded-2xl object-cover shadow-lg sm:h-80">
                    <?php else: ?>
                        <div class="grid h-64 place-items-center rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/60 text-emerald-300 sm:h-80">
                            <i data-lucide="image" class="h-10 w-10"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php
        $weeklyProgramTitle = $settings['weekly_program_title'] ?? 'Weekly Programs';
        $weeklyProgramBody = $settings['weekly_program_body'] ?? '';
        $weeklyProgramActive = ($settings['weekly_program_active'] ?? '0') === '1';
        ?>
        <?php if ($weeklyProgramActive && trim($weeklyProgramBody) !== ''): ?>
            <section class="mx-auto max-w-7xl px-6 pt-8 sm:px-8">
                <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start gap-4">
                        <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="calendar-days" class="h-5 w-5"></i></span>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Weekly programs</p>
                            <h2 class="mt-1 text-xl font-extrabold text-slate-900 sm:text-2xl"><?= htmlspecialchars($weeklyProgramTitle) ?></h2>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600"><?= htmlspecialchars($weeklyProgramBody) ?></p>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($programsActive && !empty($programs)): ?>
            <section class="bg-white">
                <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">What we do</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl"><?= htmlspecialchars($programsTitle) ?></h2>
                        <?php if ($programsSubtitle !== ''): ?>
                            <p class="mt-3 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars($programsSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($programs as $program): ?>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                                <?php if (!empty($program['icon'])): ?>
                                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="<?= htmlspecialchars($program['icon']) ?>" class="h-5 w-5"></i></span>
                                <?php endif; ?>
                                <h3 class="mt-4 text-sm font-bold text-slate-900"><?= htmlspecialchars($program['title'] ?? '') ?></h3>
                                <?php if (!empty($program['body'])): ?>
                                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500"><?= htmlspecialchars($program['body']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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

        <?php if ($galleryActive && !empty($gallery)): ?>
            <section class="bg-white">
                <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Gallery</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl"><?= htmlspecialchars($galleryTitle) ?></h2>
                        <?php if ($gallerySubtitle !== ''): ?>
                            <p class="mt-3 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars($gallerySubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <?php foreach ($gallery as $photo): ?>
                            <figure class="group relative overflow-hidden rounded-2xl border border-emerald-100">
                                <img src="<?= $base ?>/<?= htmlspecialchars($photo['image']) ?>" alt="<?= htmlspecialchars($photo['title'] ?? '') ?>" class="h-36 w-full object-cover transition duration-300 group-hover:scale-105 sm:h-44">
                                <?php if (!empty($photo['title'])): ?>
                                    <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-emerald-950/80 to-transparent px-3 py-2 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100"><?= htmlspecialchars($photo['title']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($updatesActive && !empty($newsUpdates)): ?>
            <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Newsfeed</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl"><?= htmlspecialchars($updatesTitle) ?></h2>
                    <?php if ($updatesSubtitle !== ''): ?>
                        <p class="mt-3 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars($updatesSubtitle) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($newsUpdates as $update): ?>
                        <article class="overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <?php if (!empty($update['image'])): ?>
                                <img src="<?= $base ?>/<?= htmlspecialchars($update['image']) ?>" alt="" class="h-40 w-full object-cover">
                            <?php endif; ?>
                            <div class="p-5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-500"><?= htmlspecialchars(date('M j, Y', strtotime($update['created_at']))) ?></p>
                                <h3 class="mt-1 text-sm font-bold text-slate-900"><?= htmlspecialchars($update['title'] ?? '') ?></h3>
                                <?php if (!empty($update['body'])): ?>
                                    <p class="mt-1.5 line-clamp-3 text-xs leading-relaxed text-slate-500"><?= htmlspecialchars($update['body']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($update['link_url'])): ?>
                                    <a href="<?= htmlspecialchars($update['link_url']) ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900">Read more<i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

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

        <?php if ($testimonialsActive && !empty($testimonials)): ?>
            <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Testimonials</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl"><?= htmlspecialchars($testimonialsTitle) ?></h2>
                    <?php if ($testimonialsSubtitle !== ''): ?>
                        <p class="mt-3 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars($testimonialsSubtitle) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($testimonials as $t): ?>
                        <figure class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                            <i data-lucide="quote" class="h-5 w-5 text-emerald-300"></i>
                            <?php if (!empty($t['body'])): ?>
                                <blockquote class="mt-3 text-sm leading-relaxed text-slate-600">"<?= htmlspecialchars($t['body']) ?>"</blockquote>
                            <?php endif; ?>
                            <figcaption class="mt-4 flex items-center gap-3">
                                <?php if (!empty($t['image'])): ?>
                                    <img src="<?= $base ?>/<?= htmlspecialchars($t['image']) ?>" alt="" class="h-10 w-10 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-emerald-700"><i data-lucide="user" class="h-4 w-4"></i></span>
                                <?php endif; ?>
                                <div>
                                    <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($t['title'] ?? '') ?></p>
                                    <?php if (!empty($t['subtitle'])): ?>
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600"><?= htmlspecialchars($t['subtitle']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($supportActive && trim($supportBody) !== ''): ?>
            <section class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                <div class="relative overflow-hidden rounded-2xl bg-emerald-900 px-6 py-10 sm:px-10">
                    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-emerald-600/30 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-emerald-500/20 blur-3xl"></div>
                    <div class="relative z-10 mx-auto max-w-2xl text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-500/15 text-emerald-300"><i data-lucide="hand-heart" class="h-6 w-6"></i></span>
                        <h2 class="mt-4 text-xl font-extrabold text-white sm:text-2xl"><?= htmlspecialchars($supportTitle) ?></h2>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-emerald-100/80"><?= htmlspecialchars($supportBody) ?></p>
                        <?php if ($supportBkash !== '' || $supportBank !== ''): ?>
                            <div class="mt-6 grid gap-3 text-left sm:grid-cols-2">
                                <?php if ($supportBkash !== ''): ?>
                                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3">
                                        <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">bKash / Mobile banking</p>
                                        <p class="mt-1 text-sm font-semibold text-white"><?= htmlspecialchars($supportBkash) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if ($supportBank !== ''): ?>
                                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3">
                                        <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300">Bank transfer</p>
                                        <p class="mt-1 text-sm font-semibold text-white"><?= htmlspecialchars($supportBank) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

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

        <?php if ($hasContact): ?>
            <section class="bg-white">
                <div class="mx-auto max-w-7xl px-6 py-16 sm:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Get in touch</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Contact us</h2>
                    </div>
                    <div class="mx-auto mt-10 grid max-w-3xl gap-4 sm:grid-cols-3">
                        <?php if ($contactAddress !== ''): ?>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 text-center">
                                <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="map-pin" class="h-5 w-5"></i></span>
                                <p class="mt-3 text-sm font-semibold text-slate-700"><?= htmlspecialchars($contactAddress) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($contactPhone !== ''): ?>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 text-center">
                                <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="phone" class="h-5 w-5"></i></span>
                                <p class="mt-3 text-sm font-semibold text-slate-700"><?= htmlspecialchars($contactPhone) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($contactEmail !== ''): ?>
                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-5 text-center">
                                <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-emerald-900 text-white"><i data-lucide="mail" class="h-5 w-5"></i></span>
                                <p class="mt-3 text-sm font-semibold text-slate-700"><?= htmlspecialchars($contactEmail) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (array_filter(array_column($socialLinks, 1)) !== []): ?>
                        <div class="mt-8 flex items-center justify-center gap-3">
                            <?php foreach ($socialLinks as [$icon, $url]): if ($url === '') continue; ?>
                                <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="grid h-11 w-11 place-items-center rounded-full border border-emerald-100 text-emerald-700 transition hover:bg-emerald-50"><i data-lucide="<?= htmlspecialchars($icon) ?>" class="h-5 w-5"></i></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
