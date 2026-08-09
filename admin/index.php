<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/admin_db.php';

requireRole(ROLE_ADMIN);

$pdo = getDbConnection();
$counts = [
    'divisions' => (int) $pdo->query('SELECT COUNT(*) FROM divisions')->fetchColumn(),
    'districts' => (int) $pdo->query('SELECT COUNT(*) FROM districts')->fetchColumn(),
    'upazilas' => (int) $pdo->query('SELECT COUNT(*) FROM upazilas')->fetchColumn(),
    'villages' => (int) $pdo->query('SELECT COUNT(*) FROM villages')->fetchColumn(),
    'centers' => (int) $pdo->query('SELECT COUNT(*) FROM centers')->fetchColumn(),
    'classes' => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    'subjects' => (int) $pdo->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'fee_heads' => (int) $pdo->query('SELECT COUNT(*) FROM fee_heads')->fetchColumn(),
    'items' => (int) $pdo->query('SELECT COUNT(*) FROM distribution_items')->fetchColumn(),
];

$activeYear = $pdo->query('SELECT name FROM academic_years WHERE is_active = 1')->fetchColumn() ?: '—';
$centers = $pdo->query(
    'SELECT c.name, v.name AS village_name, up.name AS upazila_name,
            (SELECT COUNT(*) FROM students s WHERE s.center_id = c.id) AS students
     FROM centers c
     LEFT JOIN villages v ON v.id = c.village_id
     LEFT JOIN upazilas up ON up.id = v.upazila_id
     ORDER BY c.name LIMIT 12'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">System administration</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Admin panel</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Define the program's structure: geography, study centers, classes, subjects, academic years,
                    user roles, fee heads, and distribution items.
                </p>
    </div>
</section>

            <?php require_once __DIR__ . '/admin_nav.php'; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-semibold text-slate-900">Quick stats</h2>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Active year: <?= htmlspecialchars($activeYear) ?></span>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-5">
                    <?php foreach ([
                        '🗺️' => ['Divisions', $counts['divisions']],
                        '🏙️' => ['Districts', $counts['districts']],
                        '🏘️' => ['Upazilas', $counts['upazilas']],
                        '🌾' => ['Villages', $counts['villages']],
                        '🏫' => ['Centers', $counts['centers']],
                        '📚' => ['Classes', $counts['classes']],
                        '🔬' => ['Subjects', $counts['subjects']],
                        '👥' => ['Users', $counts['users']],
                        '💰' => ['Fee heads', $counts['fee_heads']],
                        '📦' => ['Items', $counts['items']],
                    ] as [$icon, [$label, $value]]): ?>
                        <div class="rounded-2xl border border-emerald-100 bg-white p-4 text-center">
                            <p class="text-xl"><?= $icon ?></p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900"><?= $value ?></p>
                            <p class="text-xs text-slate-500"><?= $label ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Study centers</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">Village</th>
                                <th class="px-3 py-3">Upazila</th>
                                <th class="px-3 py-3 text-right">Students</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($centers)): ?>
                                <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No centers yet — add geography first, then centers.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($centers as $center): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-3 font-semibold text-slate-800"><?= htmlspecialchars($center['name']) ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($center['village_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($center['upazila_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-right text-slate-700"><?= (int) $center['students'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
