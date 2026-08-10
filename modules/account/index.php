<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAuth();

$pdo = getDbConnection();
$userId = (int) currentUser()['id'];

$stmt = $pdo->prepare('SELECT id, name, username, email, phone, role_id FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . appBaseUrl() . '/modules/auth/login.php?logout=1');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '') {
            $error = 'Name is required.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email !== '' ? $email : null, $phone !== '' ? $phone : null, $userId]);
            $_SESSION['name'] = $name;
            logActivity('Updated own profile', 'account');
            $success = 'Profile updated.';
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if ($current === '' || $newPass === '' || $confirm === '') {
            $error = 'All password fields are required.';
        } elseif (!password_verify($current, $hash)) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            logActivity('Changed own password', 'account');
            $success = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'My Account';
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
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Account</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">My Account</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Review your profile details, update your information, and change your password.</p>
                </div>
            </section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-4">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-xl font-extrabold text-emerald-700">
                                <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-slate-900"><?= htmlspecialchars($user['name']) ?></p>
                                <p class="text-sm font-semibold text-emerald-600"><?= htmlspecialchars($_SESSION['role_name'] ?? roleName((int) $user['role_id'])) ?></p>
                            </div>
                        </div>
                        <dl class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-400">Username</dt>
                                <dd class="font-semibold text-slate-700"><?= htmlspecialchars($user['username'] ?: '—') ?></dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-400">Email</dt>
                                <dd class="font-semibold text-slate-700"><?= htmlspecialchars($user['email'] ?: '—') ?></dd>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-slate-400">Phone</dt>
                                <dd class="font-semibold text-slate-700"><?= htmlspecialchars($user['phone'] ?: '—') ?></dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                        <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                            <h2 class="text-base font-bold text-slate-800">Profile information</h2>
                        </header>
                        <form method="post" class="p-5 space-y-4">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="profile">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="name">Full name *</label>
                                    <input id="name" name="name" type="text" value="<?= htmlspecialchars($user['name']) ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="email">Email</label>
                                    <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="phone">Phone</label>
                                    <input id="phone" name="phone" type="text" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <i data-lucide="user-check" class="h-4 w-4"></i>Update profile
                                </button>
                            </div>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-emerald-100 bg-white shadow-sm">
                        <header class="flex flex-wrap items-center gap-2 border-b border-emerald-100 px-5 py-4">
                            <h2 class="text-base font-bold text-slate-800">Change password</h2>
                            <p class="ml-auto text-xs font-medium text-slate-400">At least 6 characters</p>
                        </header>
                        <form method="post" class="p-5 space-y-4">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="password">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="current_password">Current password</label>
                                    <input id="current_password" name="current_password" type="password" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="new_password">New password</label>
                                    <input id="new_password" name="new_password" type="password" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500" for="confirm_password">Confirm password</label>
                                    <input id="confirm_password" name="confirm_password" type="password" class="w-full rounded-xl border border-emerald-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2 border-t border-emerald-100 pt-4">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <i data-lucide="lock" class="h-4 w-4"></i>Change password
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
