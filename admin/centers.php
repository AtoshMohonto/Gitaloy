<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/admin_db.php';

requireRole(ROLE_ADMIN);

$pdo = getDbConnection();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $villageId = (int) ($_POST['village_id'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            if ($name === '') throw new RuntimeException('Center name is required.');
            addCenter($name, $villageId > 0 ? $villageId : null, $description !== '' ? $description : null);
            $success = 'Center added.';
            logActivity('Added center: ' . $name, 'admin:centers');
        } elseif ($action === 'delete') {
            deleteRow('centers', (int) ($_POST['id'] ?? 0));
            $success = 'Center deleted.';
            logActivity('Deleted center #' . (int) ($_POST['id'] ?? 0), 'admin:centers');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$centers = $pdo->query(
    'SELECT c.*, v.name AS village_name, up.name AS upazila_name, d.name AS district_name,
            (SELECT COUNT(*) FROM students s WHERE s.center_id = c.id) AS student_count
     FROM centers c
     LEFT JOIN villages v ON v.id = c.village_id
     LEFT JOIN upazilas up ON up.id = v.upazila_id
     LEFT JOIN districts d ON d.id = up.district_id
     ORDER BY c.name'
)->fetchAll();
$villages = getVillages();

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Study spots</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Study centers</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Add the mosques, madrasas, schools, and community halls where students gather each session.
                </p>
    </div>
</section>

            <?php require_once __DIR__ . '/admin_nav.php'; ?>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Add center</h2>
                <form class="mt-4 grid gap-4 md:grid-cols-3" method="post">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="e.g. Gitaloy Mondir" class="rounded-xl border border-emerald-200 px-3 py-2" required>
                    <select name="village_id" class="rounded-xl border border-emerald-200 px-3 py-2">
                        <option value="">Select village</option>
                        <?php foreach ($villages as $village): ?>
                            <option value="<?= (int) $village['id'] ?>"><?= htmlspecialchars($village['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="description" placeholder="Short description (optional)" class="rounded-xl border border-emerald-200 px-3 py-2">
                    <div class="md:col-span-3">
                        <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Add center</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Centers (<?= count($centers) ?>)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">Village</th>
                                <th class="px-3 py-3">Upazila / District</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3 text-right">Students</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($centers)): ?>
                                <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">No centers yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($centers as $center): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-3 font-semibold text-slate-800"><?= htmlspecialchars($center['name']) ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($center['village_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($center['upazila_name'] ?: '—') ?> / <?= htmlspecialchars($center['district_name'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-slate-500"><?= htmlspecialchars($center['description'] ?: '—') ?></td>
                                    <td class="px-3 py-3 text-right text-slate-700"><?= (int) $center['student_count'] ?></td>
                                    <td class="px-3 py-3 text-right">
                                        <form method="post" onsubmit="return confirm('Delete this center? Its students will keep their records.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $center['id'] ?>">
                                            <button class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                        </form>
                                    </td>
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
