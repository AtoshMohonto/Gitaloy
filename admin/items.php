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
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') throw new RuntimeException('Name is required.');
            if (basename($_SERVER['PHP_SELF']) === 'items.php') {
                addDistributionItem($name, trim($_POST['unit'] ?? '') !== '' ? trim($_POST['unit']) : null);
            } else {
                addFeeHead($name);
            }
            $success = 'Added.';
            logActivity((basename($_SERVER['PHP_SELF']) === 'items.php' ? 'Distribution item' : 'Fee head') . ' added: ' . $name, 'admin:catalog');
        } elseif ($action === 'delete') {
            $table = basename($_SERVER['PHP_SELF']) === 'items.php' ? 'distribution_items' : 'fee_heads';
            deleteRow($table, (int) ($_POST['id'] ?? 0));
            $success = 'Deleted.';
            logActivity(ucfirst(str_replace('_', ' ', $table)) . ' deleted (ID ' . (int) ($_POST['id'] ?? 0) . ')', 'admin:catalog');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$isItems = basename($_SERVER['PHP_SELF']) === 'items.php';
$rows = $isItems ? getDistributionItems() : getFeeHeads();
$entity = $isItems ? 'item' : 'fee head';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-4xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300"><?= $isItems ? 'Materials' : 'Money' ?></p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl"><?= $isItems ? 'Distribution items' : 'Fee heads' ?></h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">
                    <?= $isItems
                        ? 'Books, notebooks, pens, bags, and lamps distributed to students — used by the distribution plans.'
                        : 'Admission, monthly tuition, exam, and other fee categories collected from students.' ?>
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
                <h2 class="text-xl font-semibold text-slate-900">Add <?= $entity ?></h2>
                <form class="mt-4 flex gap-3" method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="<?= ucfirst($entity) ?> name" class="flex-1 rounded-xl border border-emerald-200 px-3 py-2" required>
                    <?php if ($isItems): ?>
                        <input type="text" name="unit" placeholder="Unit (pc/kg)" class="w-36 rounded-xl border border-emerald-200 px-3 py-2">
                    <?php endif; ?>
                    <button class="rounded-full bg-emerald-700 px-6 py-2.5 font-semibold text-white">Add</button>
                </form>
                <div class="mt-5 flex flex-wrap gap-2">
                    <?php foreach ($rows as $row): ?>
                        <div class="inline-flex items-center gap-3 rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm">
                            <span class="font-semibold text-slate-800">
                                <?= htmlspecialchars($row['name']) ?><?= $isItems && $row['unit'] ? ' <span class="text-xs text-slate-500">(' . htmlspecialchars($row['unit']) . ')</span>' : '' ?>
                            </span>
                            <form method="post" onsubmit="return confirm('Delete <?= htmlspecialchars($row['name']) ?>?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="text-xs font-semibold text-red-500 hover:underline">✕</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?><p class="text-sm text-slate-500">No <?= $entity ?>s yet.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
