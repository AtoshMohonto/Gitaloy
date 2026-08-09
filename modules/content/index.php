<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requirePermission('content.manage');

$defaults = [
    'login_title' => 'Welcome to Gitaloy',
    'login_subtitle' => 'Village student management for free education.',
    'login_button' => 'Sign in',
    'announcement' => '',
    'hero_title' => 'Free education for village children',
    'hero_subtitle' => 'Students gather at local study centers every Friday for lessons, attendance, fees, and progress tracking — all in one place.',
    'hero_image' => '',
    'notice' => '',
    'notice_active' => '1',
];

$success = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $current = getSettings();
    foreach ($defaults as $key => $default) {
        if ($key === 'hero_image') {
            continue;
        }
        if ($key === 'notice_active') {
            saveSetting($key, isset($_POST['notice_active']) ? '1' : '0');
            continue;
        }
        saveSetting($key, trim($_POST[$key] ?? ($current[$key] ?? $default)));
    }

    if (isset($_POST['remove_hero_image'])) {
        saveSetting('hero_image', '');
    }
    if (!empty($_FILES['hero_image']['name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = $_FILES['hero_image']['type'];
        if (!isset($allowed[$mime])) {
            $error = 'Please upload a JPG, PNG, WebP or GIF picture.';
        } else {
            $upDir = dirname(__DIR__, 2) . '/uploads';
            if (!is_dir($upDir)) {
                @mkdir($upDir, 0777, true);
            }
            $ext = $allowed[$mime];
            $name = 'hero_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (is_dir($upDir) && move_uploaded_file($_FILES['hero_image']['tmp_name'], $upDir . '/' . $name)) {
                saveSetting('hero_image', 'uploads/' . $name);
            } else {
                $error = 'The picture could not be uploaded.';
            }
        }
    }

    logActivity('Updated frontend content', 'content');
    if ($error === null) {
        $success = 'Frontend content saved successfully.';
    }
}

$settings = getSettings();

$pageTitle = 'Frontend Content';
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
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Frontend Content</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Control what visitors see on the landing page, the login screen, and the dashboard.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Landing page hero</h2>
                        <p class="ml-auto text-xs font-medium text-slate-400">Shown on the main page everyone sees first</p>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="hero_title">Hero heading</label>
                            <input id="hero_title" name="hero_title" type="text" value="<?= htmlspecialchars($settings['hero_title'] ?? $defaults['hero_title']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="hero_subtitle">Hero subtitle</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" rows="3" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?= htmlspecialchars($settings['hero_subtitle'] ?? $defaults['hero_subtitle']) ?></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="hero_image">Hero picture</label>
                            <?php if (!empty($settings['hero_image'])): ?>
                                <div class="flex flex-wrap items-center gap-4">
                                    <img src="<?= appBaseUrl() ?>/<?= htmlspecialchars($settings['hero_image']) ?>" alt="Current hero picture" class="h-28 w-48 rounded-xl border border-emerald-100 object-cover">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="remove_hero_image" value="1" class="rounded border-emerald-300 text-emerald-700 focus:ring-emerald-200">
                                        Remove current picture
                                    </label>
                                </div>
                                <p class="mt-2 text-xs text-slate-400">Uploading a new picture replaces the current one.</p>
                            <?php else: ?>
                                <p class="mb-2 text-xs text-slate-400">No picture yet — upload a JPG, PNG, WebP or GIF.</p>
                            <?php endif; ?>
                            <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full max-w-sm text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-900 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-700">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Notice board</h2>
                        <p class="ml-auto text-xs font-medium text-slate-400">Shown on the landing page</p>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="notice">Notice text</label>
                            <textarea id="notice" name="notice" rows="4" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?= htmlspecialchars($settings['notice'] ?? '') ?></textarea>
                            <p class="mt-1 text-xs text-slate-400">Leave empty to hide the notice board.</p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
                            <input type="checkbox" name="notice_active" value="1" <?= (($settings['notice_active'] ?? $defaults['notice_active']) === '1') ? 'checked' : '' ?> class="rounded border-emerald-300 text-emerald-700 focus:ring-emerald-200">
                            Show the notice on the landing page
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Login screen</h2>
                        <p class="ml-auto text-xs font-medium text-slate-400">Shown on the sign-in page</p>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="login_title">Login heading</label>
                            <input id="login_title" name="login_title" type="text" value="<?= htmlspecialchars($settings['login_title'] ?? $defaults['login_title']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="login_subtitle">Login subtitle</label>
                            <input id="login_subtitle" name="login_subtitle" type="text" value="<?= htmlspecialchars($settings['login_subtitle'] ?? $defaults['login_subtitle']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="login_button">Sign-in button label</label>
                            <input id="login_button" name="login_button" type="text" value="<?= htmlspecialchars($settings['login_button'] ?? $defaults['login_button']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                    <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                        <h2 class="text-base font-bold text-slate-800">Dashboard announcement</h2>
                        <p class="ml-auto text-xs font-medium text-slate-400">Shown at the top of the dashboard</p>
                    </header>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="announcement">Announcement text</label>
                            <textarea id="announcement" name="announcement" rows="4" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?= htmlspecialchars($settings['announcement'] ?? '') ?></textarea>
                            <p class="mt-1 text-xs text-slate-400">Leave empty to hide the announcement banner.</p>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-2 rounded-2xl border border-emerald-100 bg-white px-5 py-4 shadow-sm">
                    <a href="<?= appBaseUrl() ?>/index.php" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">
                        <i data-lucide="eye" class="h-4 w-4"></i>Preview landing page
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <i data-lucide="save" class="h-4 w-4"></i>Save all content
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
