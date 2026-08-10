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
        if (!validateCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('Invalid request. Please refresh and try again.');
        }
        if ($action === 'add_division') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new RuntimeException('Division name is required.');
            addDivision($name);
            $success = 'Division added.';
            logActivity('Added division: ' . $name, 'admin:geography');
        } elseif ($action === 'add_district') {
            $name = trim($_POST['name'] ?? '');
            $divisionId = (int) ($_POST['division_id'] ?? 0);
            if ($name === '' || $divisionId <= 0) throw new RuntimeException('Division and district name are required.');
            addDistrict($divisionId, $name);
            $success = 'District added.';
            logActivity('Added district: ' . $name, 'admin:geography');
        } elseif ($action === 'add_upazila') {
            $name = trim($_POST['name'] ?? '');
            $districtId = (int) ($_POST['district_id'] ?? 0);
            if ($name === '' || $districtId <= 0) throw new RuntimeException('District and upazila name are required.');
            addUpazila($districtId, $name);
            $success = 'Upazila added.';
            logActivity('Added upazila: ' . $name, 'admin:geography');
        } elseif ($action === 'add_village') {
            $name = trim($_POST['name'] ?? '');
            $upazilaId = (int) ($_POST['upazila_id'] ?? 0);
            if ($name === '' || $upazilaId <= 0) throw new RuntimeException('Upazila and village name are required.');
            addVillage($upazilaId, $name);
            $success = 'Village added.';
            logActivity('Added village: ' . $name, 'admin:geography');
        } elseif ($action === 'delete') {
            deleteRow($_POST['table'] ?? '', (int) ($_POST['id'] ?? 0));
            $success = 'Record deleted.';
            logActivity('Deleted a ' . ($_POST['table'] ?? 'geography') . ' record (ID ' . (int) ($_POST['id'] ?? 0) . ')', 'admin:geography');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$divisions = getDivisions();
$selectedDivisionId = (int) ($_GET['division_id'] ?? 0);
$selectedDistrictId = (int) ($_GET['district_id'] ?? 0);
$selectedUpazilaId = (int) ($_GET['upazila_id'] ?? 0);

$districts = $selectedDivisionId > 0 ? getDistricts($selectedDivisionId) : [];
$upazilas = $selectedDistrictId > 0 ? getUpazilas($selectedDistrictId) : [];
$villages = $selectedUpazilaId > 0 ? getVillages($selectedUpazilaId) : [];

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
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Division → District → Upazila → Village</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Geography</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    Build the zone hierarchy that scopes every manager, attendance list, and report. Select a level to drill down.
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

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Add division</h2>
                    <form class="mt-4 flex gap-3" method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add_division">
                        <input type="text" name="name" placeholder="e.g. Rajshahi" class="flex-1 rounded-xl border border-emerald-200 px-3 py-2" required>
                        <button class="rounded-full bg-emerald-700 px-5 py-2 font-semibold text-white">Add</button>
                    </form>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        <?php foreach ($divisions as $division): ?>
                            <a href="?division_id=<?= (int) $division['id'] ?>"
                               class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm transition
                                      <?= $selectedDivisionId === (int) $division['id'] ? 'border-emerald-600 bg-emerald-50 font-semibold text-emerald-800' : 'border-emerald-100 bg-white text-slate-700 hover:bg-emerald-50' ?>">
                                <span><?= htmlspecialchars($division['name']) ?></span>
                                <span class="text-xs text-slate-400"><?= (int) $division['district_count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($divisions)): ?><p class="text-sm text-slate-500">No divisions yet.</p><?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">
                        Districts
                        <?php if ($selectedDivisionId > 0): ?>
                            <span class="text-sm font-normal text-slate-500">in selected division</span>
                        <?php endif; ?>
                    </h2>
                    <?php if ($selectedDivisionId > 0): ?>
                        <form class="mt-4 flex gap-3" method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="add_district">
                            <input type="hidden" name="division_id" value="<?= $selectedDivisionId ?>">
                            <input type="text" name="name" placeholder="e.g. Joypurhat" class="flex-1 rounded-xl border border-emerald-200 px-3 py-2" required>
                            <button class="rounded-full bg-emerald-700 px-5 py-2 font-semibold text-white">Add</button>
                        </form>
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($districts as $district): ?>
                                <a href="?division_id=<?= $selectedDivisionId ?>&district_id=<?= (int) $district['id'] ?>"
                                   class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm transition
                                          <?= $selectedDistrictId === (int) $district['id'] ? 'border-emerald-600 bg-emerald-50 font-semibold text-emerald-800' : 'border-emerald-100 bg-white text-slate-700 hover:bg-emerald-50' ?>">
                                    <span><?= htmlspecialchars($district['name']) ?></span>
                                    <span class="text-xs text-slate-400"><?= (int) $district['upazila_count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($districts)): ?><p class="text-sm text-slate-500">No districts in this division.</p><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-4 text-sm text-slate-500">Select a division to manage its districts.</p>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Upazilas</h2>
                    <?php if ($selectedDistrictId > 0): ?>
                        <form class="mt-4 flex gap-3" method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="add_upazila">
                            <input type="hidden" name="district_id" value="<?= $selectedDistrictId ?>">
                            <input type="text" name="name" placeholder="e.g. Kalai" class="flex-1 rounded-xl border border-emerald-200 px-3 py-2" required>
                            <button class="rounded-full bg-emerald-700 px-5 py-2 font-semibold text-white">Add</button>
                        </form>
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($upazilas as $upazila): ?>
                                <a href="?division_id=<?= $selectedDivisionId ?>&district_id=<?= $selectedDistrictId ?>&upazila_id=<?= (int) $upazila['id'] ?>"
                                   class="flex items-center justify-between rounded-2xl border px-4 py-3 text-sm transition
                                          <?= $selectedUpazilaId === (int) $upazila['id'] ? 'border-emerald-600 bg-emerald-50 font-semibold text-emerald-800' : 'border-emerald-100 bg-white text-slate-700 hover:bg-emerald-50' ?>">
                                    <span><?= htmlspecialchars($upazila['name']) ?></span>
                                    <span class="text-xs text-slate-400"><?= (int) $upazila['village_count'] ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($upazilas)): ?><p class="text-sm text-slate-500">No upazilas in this district.</p><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-4 text-sm text-slate-500">Select a district to manage its upazilas.</p>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Villages</h2>
                    <?php if ($selectedUpazilaId > 0): ?>
                        <form class="mt-4 flex gap-3" method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="add_village">
                            <input type="hidden" name="upazila_id" value="<?= $selectedUpazilaId ?>">
                            <input type="text" name="name" placeholder="e.g. Gitaloy" class="flex-1 rounded-xl border border-emerald-200 px-3 py-2" required>
                            <button class="rounded-full bg-emerald-700 px-5 py-2 font-semibold text-white">Add</button>
                        </form>
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($villages as $village): ?>
                                <div class="flex items-center justify-between rounded-2xl border border-emerald-100 bg-white px-4 py-3 text-sm">
                                    <span class="text-slate-700"><?= htmlspecialchars($village['name']) ?></span>
                                    <form method="post" onsubmit="return confirm('Delete this village?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="table" value="villages">
                                        <input type="hidden" name="id" value="<?= (int) $village['id'] ?>">
                                        <button class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($villages)): ?><p class="text-sm text-slate-500">No villages in this upazila.</p><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="mt-4 text-sm text-slate-500">Select an upazila to manage its villages.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
