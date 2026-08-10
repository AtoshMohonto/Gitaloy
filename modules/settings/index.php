<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requirePermission('settings.manage');

$defaults = [
    'app_name' => 'Gitaloy',
    'app_tagline' => 'Free education for underprivileged village children',
    'contact_email' => '',
    'contact_phone' => '',
    'footer_text' => 'Gitaloy — Village Student Management System · Free education for underprivileged village children',
];

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        foreach ($defaults as $key => $default) {
            saveSetting($key, trim($_POST[$key] ?? $default));
        }
        logActivity('Updated site settings', 'settings');
        $success = 'Site settings saved successfully.';
    }
}

$settings = getSettings();

$pageTitle = 'Site Settings';
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
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Site Settings</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Configure the program's site-wide preferences used across Gitaloy.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-bold text-slate-800">General</h2>
                    <p class="ml-auto text-xs font-medium text-slate-400">Shown across the whole system</p>
                </header>
                <form method="post" class="p-5 space-y-4">
                    <?= csrfField() ?>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="app_name">Application name</label>
                            <input id="app_name" name="app_name" type="text" value="<?= htmlspecialchars($settings['app_name'] ?? $defaults['app_name']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="app_tagline">Tagline</label>
                            <input id="app_tagline" name="app_tagline" type="text" value="<?= htmlspecialchars($settings['app_tagline'] ?? $defaults['app_tagline']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="contact_email">Contact email</label>
                            <input id="contact_email" name="contact_email" type="email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="contact_phone">Contact phone</label>
                            <input id="contact_phone" name="contact_phone" type="text" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="footer_text">Footer text</label>
                        <input id="footer_text" name="footer_text" type="text" value="<?= htmlspecialchars($settings['footer_text'] ?? $defaults['footer_text']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="save" class="h-4 w-4"></i>Save settings
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
