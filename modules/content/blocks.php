<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requirePermission('content.manage');

$sectionsConfig = [
    'stat' => [
        'label' => 'Stats & Counters',
        'icon' => 'bar-chart-3',
        'fields' => ['icon', 'stat_value', 'title'],
        'hint' => 'Shown as a counter strip under the hero, e.g. icon "users", value "1,200+", label "Students reached".',
    ],
    'program' => [
        'label' => 'Programs & Causes',
        'icon' => 'heart-handshake',
        'fields' => ['icon', 'title', 'body'],
        'hint' => 'Cards describing what the program does, e.g. "Free Quran & Bangla classes".',
    ],
    'gallery' => [
        'label' => 'Gallery',
        'icon' => 'image',
        'fields' => ['image', 'title'],
        'hint' => 'Photos shown in the gallery grid on the landing page.',
    ],
    'update' => [
        'label' => 'Latest Updates',
        'icon' => 'newspaper',
        'fields' => ['image', 'title', 'body', 'link_url'],
        'hint' => 'News/update cards on the landing page, newest first.',
    ],
    'testimonial' => [
        'label' => 'Testimonials',
        'icon' => 'quote',
        'fields' => ['image', 'title', 'subtitle', 'body'],
        'hint' => 'Quotes from guardians, volunteers, or students. Title = name, subtitle = role.',
    ],
];

$section = $_GET['section'] ?? 'stat';
if (!isset($sectionsConfig[$section])) {
    $section = 'stat';
}
$config = $sectionsConfig[$section];

$success = null;
$error = null;
$editBlock = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $postSection = $_POST['section'] ?? $section;
        if (!isset($sectionsConfig[$postSection])) {
            $postSection = 'stat';
        }

        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $fields = $sectionsConfig[$postSection]['fields'];
            $data = [
                'section' => $postSection,
                'title' => trim($_POST['title'] ?? ''),
                'subtitle' => trim($_POST['subtitle'] ?? ''),
                'body' => trim($_POST['body'] ?? ''),
                'icon' => trim($_POST['icon'] ?? ''),
                'stat_value' => trim($_POST['stat_value'] ?? ''),
                'link_url' => trim($_POST['link_url'] ?? ''),
            ];

            if (in_array('image', $fields, true) && !empty($_FILES['image']['name'])) {
                try {
                    $data['image'] = handleContentImageUpload($_FILES['image'], $postSection);
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }

            $needsImage = in_array('image', $fields, true);
            if ($error === null && $id === 0 && $needsImage && empty($data['image'])) {
                $error = 'Please choose an image to upload.';
            }

            if ($error === null) {
                if ($id > 0) {
                    $existing = getContentBlockById($id);
                    if ($existing === null) {
                        $error = 'Item not found.';
                    } else {
                        updateContentBlock($id, $data);
                        logActivity('Updated content block #' . $id . ' (' . $postSection . ')', 'content');
                        $success = 'Item updated.';
                    }
                } else {
                    createContentBlock($data);
                    logActivity('Added content block (' . $postSection . ')', 'content');
                    $success = 'Item added.';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            deleteContentBlock($id);
            logActivity('Deleted content block #' . $id, 'content');
            $success = 'Item deleted.';
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            toggleContentBlock($id);
            $success = 'Item status changed.';
        } elseif ($action === 'move') {
            $id = (int) ($_POST['id'] ?? 0);
            $direction = ($_POST['direction'] ?? '') === 'up' ? 'up' : 'down';
            moveContentBlock($id, $direction);
        }
        $section = $postSection;
        $config = $sectionsConfig[$section];
    }
}

if (isset($_GET['edit'])) {
    $editBlock = getContentBlockById((int) $_GET['edit']);
    if ($editBlock !== null && $editBlock['section'] !== $section) {
        $editBlock = null;
    }
}

$items = getContentBlocks($section);

$pageTitle = 'Content Blocks';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
                <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
                <div class="relative z-10 px-6 py-8 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">System administration</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Content Blocks</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Add, edit, reorder, or remove repeatable items on the landing page — stats, programs, gallery photos, updates, and testimonials. Add as many as you need, any time.</p>
                    <a href="<?= appBaseUrl() ?>/modules/content/index.php" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-emerald-500/40 px-3.5 py-2 text-xs font-bold text-emerald-100 transition hover:bg-emerald-500/10">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>Back to Frontend Content
                    </a>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-2">
                <?php foreach ($sectionsConfig as $key => $cfg): ?>
                    <a href="<?= appBaseUrl() ?>/modules/content/blocks.php?section=<?= urlencode($key) ?>" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-bold transition <?= $key === $section ? 'border-emerald-900 bg-emerald-900 text-white' : 'border-emerald-200 bg-white text-emerald-800 hover:bg-emerald-50' ?>">
                        <i data-lucide="<?= htmlspecialchars($cfg['icon']) ?>" class="h-4 w-4"></i><?= htmlspecialchars($cfg['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800"><?= $editBlock ? 'Edit item' : 'Add item' ?> — <?= htmlspecialchars($config['label']) ?></h2>
                    <p class="ml-auto text-xs font-medium text-slate-400"><?= htmlspecialchars($config['hint']) ?></p>
                </header>
                <form method="post" enctype="multipart/form-data" class="p-5 space-y-4">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                    <input type="hidden" name="id" value="<?= $editBlock ? (int) $editBlock['id'] : 0 ?>">

                    <div class="grid gap-4 md:grid-cols-2">
                        <?php if (in_array('icon', $config['fields'], true)): ?>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-icon">Icon</label>
                                <input id="b-icon" name="icon" type="text" value="<?= htmlspecialchars($editBlock['icon'] ?? '') ?>" placeholder="e.g. users, heart-handshake, book-open" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <p class="mt-1 text-xs text-slate-400">Any <a href="https://lucide.dev/icons" target="_blank" rel="noopener" class="underline">Lucide</a> icon name.</p>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('stat_value', $config['fields'], true)): ?>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-value">Value</label>
                                <input id="b-value" name="stat_value" type="text" value="<?= htmlspecialchars($editBlock['stat_value'] ?? '') ?>" placeholder="e.g. 1,200+" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('title', $config['fields'], true)): ?>
                            <div class="<?= $section === 'stat' ? '' : 'md:col-span-2' ?>">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-title"><?= $section === 'testimonial' ? 'Name' : 'Title' ?></label>
                                <input id="b-title" name="title" type="text" value="<?= htmlspecialchars($editBlock['title'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('subtitle', $config['fields'], true)): ?>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-subtitle"><?= $section === 'testimonial' ? 'Role (e.g. Guardian, Volunteer)' : 'Subtitle' ?></label>
                                <input id="b-subtitle" name="subtitle" type="text" value="<?= htmlspecialchars($editBlock['subtitle'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('link_url', $config['fields'], true)): ?>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-link">Link (optional)</label>
                                <input id="b-link" name="link_url" type="text" value="<?= htmlspecialchars($editBlock['link_url'] ?? '') ?>" placeholder="https://..." class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('body', $config['fields'], true)): ?>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-body"><?= $section === 'testimonial' ? 'Quote' : 'Description' ?></label>
                                <textarea id="b-body" name="body" rows="3" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?= htmlspecialchars($editBlock['body'] ?? '') ?></textarea>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('image', $config['fields'], true)): ?>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="b-image">Photo</label>
                                <?php if (!empty($editBlock['image'])): ?>
                                    <img src="<?= appBaseUrl() ?>/<?= htmlspecialchars($editBlock['image']) ?>" alt="" class="mb-2 h-24 w-40 rounded-xl border border-emerald-100 object-cover">
                                <?php endif; ?>
                                <input id="b-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full max-w-sm text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-900 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-700">
                                <p class="mt-1 text-xs text-slate-400"><?= $editBlock ? 'Leave empty to keep the current photo.' : 'Required.' ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                        <?php if ($editBlock): ?>
                            <a href="<?= appBaseUrl() ?>/modules/content/blocks.php?section=<?= urlencode($section) ?>" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-emerald-50">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="save" class="h-4 w-4"></i><?= $editBlock ? 'Save changes' : 'Add item' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">Current items</h2>
                    <p class="ml-auto text-xs font-medium text-slate-400"><?= count($items) ?> total</p>
                </header>
                <div class="divide-y divide-emerald-50">
                    <?php if (empty($items)): ?>
                        <p class="p-5 text-sm text-slate-500">Nothing here yet — use the form above to add the first item.</p>
                    <?php endif; ?>
                    <?php foreach ($items as $i => $item): ?>
                        <div class="flex flex-wrap items-center gap-4 p-4">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= appBaseUrl() ?>/<?= htmlspecialchars($item['image']) ?>" alt="" class="h-14 w-20 shrink-0 rounded-lg border border-emerald-100 object-cover">
                            <?php elseif (!empty($item['icon'])): ?>
                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-emerald-900 text-white"><i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="h-5 w-5"></i></span>
                            <?php else: ?>
                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-300"><i data-lucide="<?= htmlspecialchars($config['icon']) ?>" class="h-5 w-5"></i></span>
                            <?php endif; ?>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if (!empty($item['stat_value'])): ?>
                                        <span class="text-sm font-extrabold text-emerald-700"><?= htmlspecialchars($item['stat_value']) ?></span>
                                    <?php endif; ?>
                                    <p class="truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($item['title'] ?? '(untitled)') ?></p>
                                    <?php if (!$item['is_active']): ?>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500">Hidden</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($item['subtitle'])): ?>
                                    <p class="truncate text-xs font-semibold text-emerald-600"><?= htmlspecialchars($item['subtitle']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['body'])): ?>
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500"><?= htmlspecialchars($item['body']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <form method="post" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" <?= $i === 0 ? 'disabled' : '' ?> class="grid h-8 w-8 place-items-center rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-30" title="Move up"><i data-lucide="chevron-up" class="h-4 w-4"></i></button>
                                </form>
                                <form method="post" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="move">
                                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" <?= $i === count($items) - 1 ? 'disabled' : '' ?> class="grid h-8 w-8 place-items-center rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-30" title="Move down"><i data-lucide="chevron-down" class="h-4 w-4"></i></button>
                                </form>
                                <a href="<?= appBaseUrl() ?>/modules/content/blocks.php?section=<?= urlencode($section) ?>&edit=<?= (int) $item['id'] ?>" class="grid h-8 w-8 place-items-center rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                <form method="post" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50" title="<?= $item['is_active'] ? 'Hide' : 'Show' ?>"><i data-lucide="<?= $item['is_active'] ? 'eye-off' : 'eye' ?>" class="h-4 w-4"></i></button>
                                </form>
                                <form method="post" class="inline" onsubmit="return confirm('Delete this item?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50" title="Delete"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
